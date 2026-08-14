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
| Thanh toán V1 | COD hoặc chuyển khoản VietQR động; chuyển khoản chờ đối soát trước khi đánh dấu đã thanh toán |
| Vận chuyển V1 | Checkout lưu địa chỉ snapshot, tổng trọng lượng và quote vận chuyển; dùng ước tính động có thể thay bằng adapter GHN/GHTK khi có cấu hình kết nối |
| Hóa đơn VAT | Không thu thập thông tin xuất hóa đơn/VAT ở V1 |
| Dịch vụ | Form tư vấn hoặc lắp đặt, nhân viên xác nhận thủ công |
| Quyền admin V1 | Chỉ tài khoản `admin` đang hoạt động được vào back office; không tự cấp quyền cho tài khoản có sẵn |
| Hủy đơn | Chỉ hủy từ `pending`, `confirmed` hoặc `processing`; đơn đã ghi nhận thanh toán phải hoàn tiền thủ công trước khi hủy và hoàn tồn kho |
| Khuyến mãi V1 | Một mã trên mỗi đơn; giảm tiền hàng, không giảm phí ship; kiểm tra và ghi snapshot trong transaction tạo đơn |
| ETA đơn hàng | Mô phỏng từ quote nội bộ, hiển thị là ước tính; mã theo dõi hiện là mã nội bộ cho tới khi có adapter hãng vận chuyển |
| Đối soát thanh toán | Admin ghi nhận `paid` sau khi đối soát thực tế; mọi thay đổi trạng thái đều có lịch sử, V1 không tự chuyển tiền |
| UI | Dịu, có tính biên tập, cảm hứng từ Clare nhưng tài sản và code phải nguyên bản |

## Thông tin cần có để bật báo giá vận chuyển hãng vận chuyển thật

1. Chọn GHN hoặc GHTK, kèm thông tin xác thực API và địa chỉ kho gửi hàng.
2. Quy tắc giao hàng còn lại: khu vực phục vụ, dịch vụ mặc định và chính sách phụ thu (nếu có).

Trước khi có các dữ kiện này, API Checkout chỉ trả phí **ước tính động** theo địa chỉ/tổng trọng lượng, được gắn cờ rõ ràng là `is_estimated = true`; không được trình bày như báo giá chính thức từ GHN/GHTK.

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
