<x-mail::message>
# Clare đã nhận đơn của bạn

Chào {{ $order->customer_name }},

Cảm ơn bạn đã chọn một khoảng sáng từ Clare. Đơn **{{ $order->number }}** đã được ghi nhận và đang chờ xác nhận.

<x-mail::table>
| Sản phẩm | Số lượng | Thành tiền |
|:--|--:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }} · {{ $item->color_name }} | {{ $item->quantity }} | {{ \App\Modules\Shared\Support\Money::formatVnd($item->line_total) }} |
@endforeach
| **Tổng thanh toán** | | **{{ \App\Modules\Shared\Support\Money::formatVnd($order->total) }}** |
</x-mail::table>

**Nhận hàng:** {{ $order->shipping_recipient_name }} · {{ $order->shipping_phone }}  
{{ $order->shipping_address_line_1 }}, {{ $order->shipping_ward }}, {{ $order->shipping_district }}, {{ $order->shipping_city }}

<x-mail::button :url="route('account.orders.show', $order->number)">
Theo dõi đơn hàng
</x-mail::button>

Nếu cần hỗ trợ, bạn chỉ cần trả lời email này hoặc gửi yêu cầu tư vấn trong tài khoản Clare.

Thân mến,  
Clare
</x-mail::message>
