<x-mail::message>
# {{ $order->statusLabel() }}

Chào {{ $order->customer_name }},

Đơn **{{ $order->number }}** vừa được cập nhật sang trạng thái **{{ $order->statusLabel() }}**.

@if ($order->shipping_tracking_number)
Mã theo dõi: **{{ $order->shipping_tracking_number }}**
@endif

@if ($order->estimatedDeliveryDate())
Ngày nhận dự kiến: **{{ $order->estimatedDeliveryDate()->format('d/m/Y') }}**
@endif

@if ($order->status === 'cancelled' && $order->cancel_reason)
Lý do hủy: {{ $order->cancel_reason }}
@endif

<x-mail::button :url="route('account.orders.show', $order->number)">
Xem chi tiết và tiến độ
</x-mail::button>

Thân mến,  
Clare
</x-mail::message>
