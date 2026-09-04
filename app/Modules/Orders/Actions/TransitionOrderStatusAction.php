<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Mail\OrderStatusUpdatedMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderDiscount;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Actions\ReleaseOrderVoucherAction;
use App\Modules\Settings\Actions\ConfigureStoreMailAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransitionOrderStatusAction
{
    public function __construct(
        private readonly ConfigureStoreMailAction $configureMail,
        private readonly ReleaseOrderVoucherAction $releaseVoucher,
    ) {}

    private const ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
    ];

    public function execute(
        Order $order,
        ?int $actorId,
        string $nextStatus,
        ?string $note,
        ?string $cancelReason,
    ): Order {
        $updatedOrder = DB::transaction(function () use ($order, $actorId, $nextStatus, $note, $cancelReason): Order {
            $lockedOrder = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($order->getKey());
            $currentStatus = $lockedOrder->status;

            $this->ensureTransitionIsAllowed($lockedOrder, $nextStatus);

            if ($nextStatus === 'cancelled') {
                $this->ensureCancellationIsAllowed($lockedOrder);
                $this->restoreInventory($lockedOrder, $actorId);
                $this->restorePromotionUsage($lockedOrder, $cancelReason ?: 'Đơn hàng đã bị hủy.');
            }

            $attributes = [
                'status' => $nextStatus,
                'admin_note' => $note ?? $lockedOrder->admin_note,
            ];

            if ($nextStatus === 'confirmed') {
                $attributes['confirmed_at'] = $lockedOrder->confirmed_at ?? now();
                $attributes['shipping_tracking_number'] = $lockedOrder->shipping_tracking_number
                    ?? $this->generateTrackingNumber();
            }

            if ($nextStatus === 'processing') {
                $attributes['preparing_at'] = $lockedOrder->preparing_at ?? now();
            }

            if ($nextStatus === 'shipped') {
                $attributes['shipped_at'] = $lockedOrder->shipped_at ?? now();
            }

            if ($nextStatus === 'completed') {
                $attributes['delivered_at'] = $lockedOrder->delivered_at ?? now();
            }

            if ($nextStatus === 'cancelled') {
                $attributes['cancelled_at'] = now();
                $attributes['cancel_reason'] = $cancelReason;
            }

            $lockedOrder->update($attributes);

            OrderStatusHistory::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
                'changed_by' => $actorId,
                'note' => $nextStatus === 'cancelled' ? $cancelReason : ($note ?: $this->defaultStatusNote($nextStatus)),
            ]);

            return $lockedOrder->fresh(['items', 'payments']);
        });

        try {
            $this->configureMail->execute();
            Mail::to($updatedOrder->customer_email)->send(new OrderStatusUpdatedMail($updatedOrder));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $updatedOrder;
    }

    public function allowedNextStatuses(Order $order): array
    {
        return self::ALLOWED_TRANSITIONS[$order->status] ?? [];
    }

    private function ensureTransitionIsAllowed(Order $order, string $nextStatus): void
    {
        if (! in_array($nextStatus, self::ALLOWED_TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể chuyển đơn từ trạng thái hiện tại sang trạng thái đã chọn.',
            ]);
        }
    }

    private function ensureCancellationIsAllowed(Order $order): void
    {
        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'status' => 'Đơn đã thanh toán cần được ghi nhận hoàn tiền trước khi hủy.',
            ]);
        }
    }

    private function restoreInventory(Order $order, ?int $actorId): void
    {
        foreach ($order->items as $item) {
            $variant = ProductVariant::withTrashed()
                ->lockForUpdate()
                ->find($item->product_variant_id);

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'status' => "Không thể hoàn tồn kho cho SKU {$item->sku} vì biến thể gốc không còn tồn tại.",
                ]);
            }

            $balanceAfter = $variant->stock_quantity + $item->quantity;

            $variant->update([
                'stock_quantity' => $balanceAfter,
            ]);

            DB::table('inventory_movements')->insert([
                'product_variant_id' => $variant->getKey(),
                'order_id' => $order->getKey(),
                'actor_id' => $actorId,
                'type' => 'order_cancelled',
                'quantity' => $item->quantity,
                'balance_after' => $balanceAfter,
                'note' => "Hủy đơn {$order->number}",
                'created_at' => now(),
            ]);
        }
    }

    private function restorePromotionUsage(Order $order, string $reason): void
    {
        if ($this->releaseVoucher->execute($order, $reason)) {
            return;
        }

        // Các đơn tạo trước khi có reservation đã tăng usage_count ngay lúc checkout.
        // Giữ nhánh này để hủy các đơn lịch sử vẫn hoàn đúng lượt sử dụng cũ.
        $discount = OrderDiscount::query()
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if ($discount?->promotion_code_id === null) {
            return;
        }

        $promotion = PromotionCode::query()
            ->whereKey($discount->promotion_code_id)
            ->lockForUpdate()
            ->first();

        if ($promotion !== null && $promotion->usage_count > 0) {
            $promotion->decrement('usage_count');
        }
    }

    private function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'CLR-SHP-'.now()->format('ymd').'-'.Str::upper(Str::random(7));
        } while (Order::query()->where('shipping_tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    private function defaultStatusNote(string $status): string
    {
        return match ($status) {
            'confirmed' => 'Đơn đã được xác nhận, chờ lấy hàng.',
            'processing' => 'Đơn đang được chuẩn bị giao.',
            'shipped' => 'Đơn đã được bàn giao để giao hàng.',
            'completed' => 'Đơn đã được giao thành công.',
            default => 'Trạng thái đơn hàng đã được cập nhật.',
        };
    }
}
