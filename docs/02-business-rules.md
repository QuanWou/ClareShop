# Quy tắc nghiệp vụ

## Catalog

1. `Product` là mẫu đèn; `ProductVariant` là phiên bản bán được theo màu.
2. Giá, SKU và tồn kho chỉ thuộc `ProductVariant`. `Product` không có cột giá hoặc tồn kho.
3. Một sản phẩm không được có hai biến thể cùng `color_name`.
4. Sản phẩm/biến thể chỉ hiển thị khi `is_active = true`; trang storefront chỉ hiển thị sản phẩm đã có `published_at` không ở tương lai.
5. Giá hiển thị của sản phẩm là giá thấp nhất của biến thể đang hoạt động. Nếu có `compare_at_price`, chỉ hiển thị giảm giá khi nó lớn hơn `price`.
6. Ảnh có `sort_order`; ảnh có số nhỏ hơn là ảnh ưu tiên. Ảnh có thể dành cho cả sản phẩm hoặc riêng một biến thể.
7. Giá catalog, giỏ hàng, đơn hàng và báo cáo V1 dùng VND. Giá hiển thị không có phần thập phân, dùng dấu chấm phân cách hàng nghìn và hậu tố `VND`, ví dụ `100.000 VND`. Riêng PayPal phải snapshot số tiền gateway, tiền tệ USD và tỷ giá VND/USD vì PayPal không nhận VND.

## Giỏ hàng và checkout

1. Giỏ hàng luôn lưu `product_variant_id`, không lưu `product_id` đơn thuần.
2. Khách vãng lai có thể xem và giữ giỏ, nhưng chỉ tài khoản đang hoạt động mới được báo giá hoặc tạo đơn. Mọi đơn mới phải có `user_id`; cột được phép `null` chỉ để bảo toàn dữ liệu lịch sử đã có.
3. Không giữ tồn kho chỉ vì một món nằm trong giỏ.
3a. Khách có thể chọn một hoặc nhiều dòng giỏ để checkout riêng. Báo giá, voucher, trọng lượng, phí vận chuyển và đơn hàng chỉ tính các dòng đang được chọn; sau khi đặt đơn thành công chỉ các dòng đó bị xóa, các dòng chưa chọn tiếp tục nằm trong giỏ.
4. Khi tạo đơn, Action phải khóa các biến thể liên quan, kiểm tra tồn, trừ tồn và tạo order/order items trong **một database transaction**.
5. Khi đơn bị hủy, tồn kho được hoàn đúng một lần; luồng hủy phải chống việc hoàn trùng.
6. `order_items` lưu snapshot tên sản phẩm, tên màu, SKU và đơn giá. Không được đọc lại giá hiện tại từ catalog để sửa lịch sử đơn.
7. Cart không giữ tồn kho; mọi thao tác thêm/cập nhật phải kiểm tra lại sản phẩm, biến thể và số lượng tồn hiện tại ở server.
8. Checkout lấy email liên hệ snapshot từ tài khoản đã xác thực ở server; chỉ nhận tên, số điện thoại, địa chỉ và phương thức thanh toán từ form. Subtotal, phí giao hàng và total đều phải tính lại tại server.
9. Mỗi biến thể có `weight_grams`. Checkout cộng trọng lượng các dòng để báo giá vận chuyển và lưu snapshot quote vào order.
10. Khách chọn một đơn vị vận chuyển trong GHN, GHTK hoặc J&T Express. Khi chưa có adapter/credentials, mỗi mức phí và ETA là **ước tính động nội bộ** theo địa chỉ, tổng trọng lượng và cấu hình riêng của đơn vị; không được gọi đó là báo giá chính thức của hãng vận chuyển.
11. Trang xác nhận đơn cần signed URL tạm thời **và** yêu cầu người xem đang đăng nhập đúng tài khoản sở hữu đơn.
12. V1 cho phép tối đa một mã ưu đãi cho mỗi đơn. Mã chỉ giảm `subtotal` tiền hàng, không giảm `shipping_fee`; tổng luôn là `subtotal + shipping_fee - discount_total` và được tính lại trong transaction tạo đơn.
13. Mỗi mã có thể là mã nhập trực tiếp hoặc voucher cần nhận trước trong Ví voucher. Hệ thống kiểm tra hiệu lực, trạng thái bật, giới hạn toàn cục/cá nhân, giá trị đơn tối thiểu/tối đa và một voucher không được giữ đồng thời cho nhiều đơn.
14. Khi tạo đơn dùng ưu đãi, hệ thống tạo `voucher_reservation` trong transaction cùng order và trừ tồn, nhưng chỉ tăng lượt đã dùng sau khi payment thực sự là `paid`. Với payOS, PayPal, MoMo hoặc trả sau, reservation hết hạn sau 30 phút; scheduler hủy đơn, hoàn tồn và nhả voucher đúng một lần. Với COD, voucher được giữ đến lúc admin ghi nhận thanh toán hoặc hủy đơn.

## Đơn hàng

Trạng thái đơn đề xuất cho V1:

```text
pending → confirmed → processing → shipped → completed
pending/confirmed/processing → cancelled
```

Nhãn hiển thị cho khách lần lượt là `Chờ xác nhận → Chờ lấy hàng → Đang chuẩn bị giao → Đang giao hàng → Đã giao`; `cancelled` hiển thị là `Đã hủy`. ETA ở V1 là mô phỏng từ quote vận chuyển nội bộ, phải gắn rõ là ước tính và không thay thế tracking thật của hãng.

Trạng thái thanh toán độc lập:

```text
unpaid | pending | paid | refunded | failed | expired
```

Không được đánh dấu thanh toán thành công hay báo giá hãng vận chuyển thật khi chưa có dữ liệu đối soát hoặc phản hồi API tương ứng.

V1 có năm phương thức thanh toán:

- `cod`: đơn và payment ở trạng thái `unpaid`.
- `bank_transfer`: tạo Payment Link payOS ở server với đúng tổng tiền, nội dung Clare và `expiredAt` sau 180 giây. Chỉ webhook có chữ ký hợp lệ hoặc API payOS trả trạng thái `PAID` mới được cập nhật payment thành `paid`; admin không tự xác nhận giao dịch payOS.
- `paypal`: tạo PayPal Order ở server, chuyển khách sang URL phê duyệt, capture ở server và xác nhận bằng Capture API hoặc webhook đã kiểm tra chữ ký. Giá trị USD và tỷ giá phải được snapshot; webhook phải idempotent. Admin không được tự đánh dấu giao dịch PayPal là `paid`.
- `momo`: tạo payment MoMo Sandbox qua adapter riêng khi đủ credentials, chuyển khách đến `payUrl`, và chỉ xác nhận từ IPN có chữ ký hợp lệ/đúng order/amount; nếu thiếu cấu hình thì hiển thị luồng mô phỏng chờ tích hợp.
- `pay_later`: ghi nhận lựa chọn và tạo payment ở trạng thái `pending`; không tự duyệt hạn mức hoặc đánh dấu đã thanh toán.
- Với bất kỳ payment pending không phải COD, voucher reservation chờ quá 30 phút chuyển `expired`; hệ thống hủy đơn và hoàn tồn kho/ưu đãi đúng một lần. Luồng PayPal vẫn có job riêng để hết hạn order gateway và áp dụng cùng nguyên tắc này.
- Chủ đơn chỉ được đổi phương thức thanh toán khi đơn còn `pending` và payment hiện tại là `unpaid`, `failed` hoặc `expired` (riêng trả sau mô phỏng có thể đổi khi còn `pending`). Mỗi lần đổi phải tạo payment attempt mới, giữ lịch sử attempt cũ và không thay đổi total của order.
- Chủ đơn chỉ được tự hủy trong cùng điều kiện an toàn nói trên. Việc hủy dùng chung nghiệp vụ hoàn tồn kho, nhả voucher và ghi lịch sử; không tạo nhánh hoàn tồn riêng ở controller khách hàng.
- Một webhook/callback chỉ được xác nhận payment attempt mới nhất, đúng provider của phương thức hiện hành và thuộc order chưa hủy. Attempt cũ đến muộn phải bị từ chối để tránh thanh toán kép.

## Vận hành quản trị

1. Chỉ user có `role = admin` và đang hoạt động mới vào được back office.
2. Luồng đơn hàng: `pending → confirmed → processing → shipped → completed`; chỉ `pending`, `confirmed`, `processing` được chuyển sang `cancelled`.
3. Hủy đơn hoàn tồn kho cho từng `order_item` trong cùng transaction, tạo `inventory_movement` loại `order_cancelled` và `order_status_history`. Không thể hủy lần hai.
4. Trạng thái payment độc lập. Với COD và phương thức đối soát thủ công, admin chỉ ghi nhận `paid` sau đối soát thực tế hoặc `refunded` sau khi hoàn tiền thực tế. payOS và PayPal chỉ cập nhật `paid` từ API/webhook đã xác minh; mỗi thay đổi cần tạo `payment_status_history`.
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
- Trong quản lý khách hàng, `tổng số đơn` đếm mọi đơn gắn với tài khoản ở mọi trạng thái để bảo toàn lịch sử; `tổng tiền đã mua` chỉ cộng `total` của các đơn có trạng thái `completed`. Đơn chờ xử lý hoặc đã hủy không được tính vào giá trị đã mua.
