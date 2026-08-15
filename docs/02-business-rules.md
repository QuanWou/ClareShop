# Quy tắc nghiệp vụ

## Catalog

1. `Product` là mẫu đèn; `ProductVariant` là phiên bản bán được theo màu.
2. Giá, SKU và tồn kho chỉ thuộc `ProductVariant`. `Product` không có cột giá hoặc tồn kho.
3. Một sản phẩm không được có hai biến thể cùng `color_name`.
4. Sản phẩm/biến thể chỉ hiển thị khi `is_active = true`; trang storefront chỉ hiển thị sản phẩm đã có `published_at` không ở tương lai.
5. Giá hiển thị của sản phẩm là giá thấp nhất của biến thể đang hoạt động. Nếu có `compare_at_price`, chỉ hiển thị giảm giá khi nó lớn hơn `price`.
6. Ảnh có `sort_order`; ảnh có số nhỏ hơn là ảnh ưu tiên. Ảnh có thể dành cho cả sản phẩm hoặc riêng một biến thể.
7. V1 chỉ dùng VND. Giá hiển thị không có phần thập phân, dùng dấu chấm phân cách hàng nghìn và hậu tố `VND`, ví dụ `100.000 VND`.

## Giỏ hàng và checkout

1. Giỏ hàng luôn lưu `product_variant_id`, không lưu `product_id` đơn thuần.
2. Khách vãng lai có thể xem và giữ giỏ, nhưng chỉ tài khoản đang hoạt động mới được báo giá hoặc tạo đơn. Mọi đơn mới phải có `user_id`; cột được phép `null` chỉ để bảo toàn dữ liệu lịch sử đã có.
3. Không giữ tồn kho chỉ vì một món nằm trong giỏ.
4. Khi tạo đơn, Action phải khóa các biến thể liên quan, kiểm tra tồn, trừ tồn và tạo order/order items trong **một database transaction**.
5. Khi đơn bị hủy, tồn kho được hoàn đúng một lần; luồng hủy phải chống việc hoàn trùng.
6. `order_items` lưu snapshot tên sản phẩm, tên màu, SKU và đơn giá. Không được đọc lại giá hiện tại từ catalog để sửa lịch sử đơn.
7. Cart không giữ tồn kho; mọi thao tác thêm/cập nhật phải kiểm tra lại sản phẩm, biến thể và số lượng tồn hiện tại ở server.
8. Checkout lấy email liên hệ snapshot từ tài khoản đã xác thực ở server; chỉ nhận tên, số điện thoại, địa chỉ và phương thức thanh toán từ form. Subtotal, phí giao hàng và total đều phải tính lại tại server.
9. Mỗi biến thể có `weight_grams`. Checkout cộng trọng lượng các dòng để báo giá vận chuyển và lưu snapshot quote vào order.
10. Khách chọn một đơn vị vận chuyển trong GHN, GHTK hoặc J&T Express. Khi chưa có adapter/credentials, mỗi mức phí và ETA là **ước tính động nội bộ** theo địa chỉ, tổng trọng lượng và cấu hình riêng của đơn vị; không được gọi đó là báo giá chính thức của hãng vận chuyển.
11. Trang xác nhận đơn cần signed URL tạm thời **và** yêu cầu người xem đang đăng nhập đúng tài khoản sở hữu đơn.
12. V1 cho phép tối đa một mã ưu đãi cho mỗi đơn. Mã chỉ giảm `subtotal` tiền hàng, không giảm `shipping_fee`; tổng luôn là `subtotal + shipping_fee - discount_total` và được tính lại trong transaction tạo đơn.
13. Mã ưu đãi phải còn hiệu lực, đang bật, chưa quá lượt dùng và thỏa giá trị đơn tối thiểu. Lượt dùng chỉ tăng sau khi tạo đơn thành công; nếu đơn được hủy hợp lệ, lượt đó được hoàn một lần cùng transaction hoàn tồn kho.

## Đơn hàng

Trạng thái đơn đề xuất cho V1:

```text
pending → confirmed → processing → shipped → completed
pending/confirmed/processing → cancelled
```

Nhãn hiển thị cho khách lần lượt là `Chờ xác nhận → Chờ lấy hàng → Đang chuẩn bị giao → Đang giao hàng → Đã giao`; `cancelled` hiển thị là `Đã hủy`. ETA ở V1 là mô phỏng từ quote vận chuyển nội bộ, phải gắn rõ là ước tính và không thay thế tracking thật của hãng.

Trạng thái thanh toán độc lập:

```text
unpaid | pending | paid | refunded
```

Không được đánh dấu thanh toán thành công hay báo giá hãng vận chuyển thật khi chưa có dữ liệu đối soát hoặc phản hồi API tương ứng.

V1 có năm phương thức thanh toán:

- `cod`: đơn và payment ở trạng thái `unpaid`.
- `bank_transfer`: tạo Quick Link VietQR với đúng `amount` và `addInfo` bằng mã đơn; đơn/payment phải ở trạng thái `pending` cho đến khi có đối soát hoặc webhook xác nhận. Tạo QR không đồng nghĩa thanh toán thành công.
- `momo`, `bank_card`, `pay_later`: ghi nhận lựa chọn và tạo payment ở trạng thái `pending`. Khi chưa có merchant credentials/gateway phù hợp, checkout phải ghi rõ đây là luồng mô phỏng chờ tích hợp và không được thu thập số thẻ, tự duyệt trả sau hoặc đánh dấu đã thanh toán.

## Vận hành quản trị

1. Chỉ user có `role = admin` và đang hoạt động mới vào được back office.
2. Luồng đơn hàng: `pending → confirmed → processing → shipped → completed`; chỉ `pending`, `confirmed`, `processing` được chuyển sang `cancelled`.
3. Hủy đơn hoàn tồn kho cho từng `order_item` trong cùng transaction, tạo `inventory_movement` loại `order_cancelled` và `order_status_history`. Không thể hủy lần hai.
4. Trạng thái payment độc lập. Admin chỉ ghi nhận `paid` sau đối soát thực tế, hoặc `refunded` sau khi hoàn tiền thực tế; mỗi thay đổi cần ghi chú và tạo `payment_status_history`.
5. Đơn có payment `paid` chỉ được hủy sau khi payment đã được ghi nhận là `refunded`; hệ thống không giả định hay thực hiện chuyển tiền hoàn tự động.
6. Appointment đi theo `pending → confirmed → completed` hoặc `pending/confirmed → cancelled`. Khi `confirmed`, admin phải nhập lịch đã xác nhận; mọi lần thay đổi đều ghi lịch sử.

## Tư vấn và lắp đặt

1. Một yêu cầu có loại `consultation` hoặc `installation`.
2. Người dùng chỉ gửi thời gian mong muốn; hệ thống không tự xác nhận lịch.
3. Nhân viên cập nhật trạng thái: `pending`, `confirmed`, `completed`, `cancelled`.
4. Yêu cầu lắp đặt có thể gắn với một đơn hàng, nhưng không bắt buộc.
5. Thông tin liên hệ và địa chỉ chỉ được dùng cho mục đích xử lý yêu cầu.

## Quyền quản trị

- Chỉ quản trị viên được tạo/sửa/ngừng hiển thị catalog, thay đổi trạng thái đơn và xác nhận lịch.
- Khi xây back office, thêm vai trò người dùng rõ ràng; không dùng điều kiện rải rác theo email trong controller.
- Xóa sản phẩm hoặc biến thể trong back office là lưu trữ mềm; không được xóa snapshot trên `order_items`, lịch sử tồn kho hoặc dữ liệu audit. Sản phẩm/biến thể đã lưu trữ không thể được đặt hàng mới.
- Admin có thể đổi `role` giữa `customer` và `admin`, hoặc khóa tài khoản bằng `is_active`; hệ thống không cho tự gỡ quyền của phiên đang đăng nhập và phải luôn còn ít nhất một admin đang hoạt động.
