# Quyết định và câu hỏi mở

## Đã chốt

| Chủ đề | Quyết định |
| --- | --- |
| Tên dự án | Clare |
| Backend / DB | Laravel 13 + MySQL riêng qua port 2000 |
| Kiến trúc | Modular monolith |
| Sản phẩm | Đèn có sẵn, không tùy biến theo yêu cầu ở V1 |
| Biến thể | Màu có SKU, giá và tồn kho riêng |
| Checkout | Khách vãng lai được giữ giỏ; chỉ tài khoản đang hoạt động được báo giá, tạo đơn và xem xác nhận đơn của chính mình |
| Tiền tệ | Chỉ dùng VND trong V1; hiển thị theo dạng `100.000 VND` |
| Thanh toán V1 | COD, payOS, MoMo, PayPal hoặc trả sau; payOS tạo Payment Link/QR 3 phút và xác nhận bằng API/webhook có chữ ký; PayPal Sandbox dùng Orders/Capture API và webhook có xác minh chữ ký |
| Vận chuyển V1 | Checkout lưu địa chỉ snapshot, tổng trọng lượng và quote khách chọn giữa GHN, GHTK, J&T Express; dùng ước tính động có thể thay bằng adapter hãng khi có cấu hình kết nối |
| Hóa đơn VAT | Không thu thập thông tin xuất hóa đơn/VAT ở V1 |
| Dịch vụ | Form tư vấn hoặc lắp đặt, nhân viên xác nhận thủ công |
| Quyền admin V1 | Chỉ tài khoản `admin` đang hoạt động được vào back office; không tự cấp quyền cho tài khoản có sẵn |
| Hủy đơn | Chỉ hủy từ `pending`, `confirmed` hoặc `processing`; đơn đã ghi nhận thanh toán phải hoàn tiền thủ công trước khi hủy và hoàn tồn kho |
| Khuyến mãi V1 | Một mã trên mỗi đơn; giảm tiền hàng, không giảm phí ship; mã có thể được nhận vào Ví voucher. Khi tạo đơn, voucher được giữ trong transaction; chỉ dùng chính thức khi payment `paid`, pending không phải COD hết hạn sau 30 phút và tự nhả voucher/hoàn tồn |
| ETA đơn hàng | Mô phỏng từ quote nội bộ, hiển thị là ước tính; mã theo dõi hiện là mã nội bộ cho tới khi có adapter hãng vận chuyển |
| Đối soát thanh toán | Admin ghi nhận `paid` cho luồng thủ công sau khi đối soát; payOS và PayPal chỉ cập nhật từ API/webhook đã xác minh; mọi thay đổi trạng thái đều có lịch sử |
| UI | Dịu, có tính biên tập, cảm hứng từ Clare nhưng tài sản và code phải nguyên bản |

## Mức mô phỏng của bản demo — chốt ngày 2026-08-24

- MoMo Sandbox đã có adapter tạo payUrl và nhận IPN xác minh chữ ký; trả sau, báo giá hãng vận chuyển và tracking hãng vẫn ở mức mô phỏng/cấu hình. PayPal Sandbox, SMTP và đăng nhập Google/Facebook dùng cấu hình tương ứng khi được cung cấp.
- Giao diện phải ghi rõ trạng thái mô phỏng hoặc chưa cấu hình; không tuyên bố đã thanh toán, đã gửi email hoặc đã nhận phản hồi chính thức từ nhà cung cấp.
- Credentials, webhook và adapter production nằm ngoài phạm vi hiện tại; chỉ triển khai khi chủ dự án yêu cầu riêng sau này.

## Thông tin cần có để bật báo giá vận chuyển hãng vận chuyển thật

1. Cung cấp thông tin xác thực API của GHN, GHTK hoặc J&T Express, kèm địa chỉ kho gửi hàng.
2. Quy tắc giao hàng còn lại: khu vực phục vụ, dịch vụ mặc định và chính sách phụ thu (nếu có).

Trước khi có các dữ kiện này, API Checkout chỉ trả phí **ước tính động** theo địa chỉ/tổng trọng lượng và lựa chọn đơn vị vận chuyển, được gắn cờ rõ ràng là `is_estimated = true`; không được trình bày như báo giá chính thức từ GHN/GHTK/J&T Express.

## Thông tin cần có để bật email và đăng nhập mạng xã hội

1. SMTP host, port, username, password, encryption và địa chỉ/tên người gửi.
2. Google OAuth client ID, client secret và callback URL đã đăng ký.
3. Facebook App ID, App Secret và callback URL đã đăng ký.

Các trường cấu hình đã có trong Admin Settings; bí mật được mã hóa trong database và không hiển thị lại. Khi thiếu một bộ thông tin, nút provider tương ứng phải ở trạng thái chưa cấu hình.

## Cần chốt trước khi xây Appointment hoàn chỉnh

1. Khu vực nào nhận tư vấn/lắp đặt?
2. Khung giờ làm việc và thời lượng một lịch là bao lâu?
3. Có cần chọn slot còn trống ngay trên web hay chỉ gửi thời gian mong muốn?
4. Cần gửi thông báo qua email, Zalo, SMS hay chỉ xem trong admin?

## Cần chốt để đưa Admin vào vận hành

1. Email của tài khoản admin ban đầu để chủ dự án cấp quyền bằng lệnh `php artisan clare:grant-admin <email>`.
2. Có cần tách thêm quyền `nhân viên đơn hàng` và `nhân viên lắp đặt` ngoài role `admin` ở V1. Hiện chưa tự suy diễn các role này.

Khách hiện đã có thể đăng ký/đăng nhập và xem các đơn/yêu cầu tạo trong lúc dùng tài khoản. Chưa có xác minh email, quên mật khẩu hoặc cơ chế gắn các đơn guest cũ vào tài khoản vì chưa có kênh email và xác minh quyền sở hữu phù hợp.

## Quy tắc khi chưa chốt

- Codex có thể xây Catalog và UI đọc dữ liệu ngay.
- Codex không được tự chọn nhà cung cấp thanh toán, chính sách phí giao hàng, lịch hoạt động hay kênh gửi tin.
- Nếu một implementation cần dữ kiện còn mở, Codex phải nêu đúng lựa chọn bị thiếu và tác động của từng phương án trước khi code.
