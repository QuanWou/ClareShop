# Quyết định và câu hỏi mở

## Đã chốt

| Chủ đề | Quyết định |
| --- | --- |
| Tên dự án | Clare |
| Backend / DB | Laravel 13 + MySQL riêng qua port 2000 |
| Kiến trúc | Modular monolith |
| Sản phẩm | Đèn có sẵn, không tùy biến theo yêu cầu ở V1 |
| Biến thể | Màu có SKU, giá và tồn kho riêng |
| Checkout | Khách được mua không cần đăng nhập |
| Dịch vụ | Form tư vấn hoặc lắp đặt, nhân viên xác nhận thủ công |
| UI | Dịu, có tính biên tập, cảm hứng từ Clare nhưng tài sản và code phải nguyên bản |

## Cần chốt trước khi Codex xây Checkout thật

1. **Thanh toán V1:** COD, chuyển khoản thủ công, hay cổng cụ thể (VNPay, MoMo, Stripe...)?
2. **Giao hàng:** tự giao/đơn vị vận chuyển nào, phí cố định hay theo khu vực/giá trị đơn?
3. **Tiền tệ:** website chỉ dùng VND hay cần khả năng nhiều tiền tệ?
4. **Hóa đơn:** có cần VAT hoặc thông tin công ty không?

## Cần chốt trước khi xây Appointment hoàn chỉnh

1. Khu vực nào nhận tư vấn/lắp đặt?
2. Khung giờ làm việc và thời lượng một lịch là bao lâu?
3. Có cần chọn slot còn trống ngay trên web hay chỉ gửi thời gian mong muốn?
4. Cần gửi thông báo qua email, Zalo, SMS hay chỉ xem trong admin?

## Cần chốt trước khi xây Admin / Account

1. Ai là admin ban đầu và cách tạo tài khoản admin?
2. Có cần nhiều cấp quyền: admin, nhân viên đơn hàng, nhân viên lắp đặt?
3. Khách có cần tự xem lịch sử đơn/yêu cầu hay V1 chỉ gửi email xác nhận?

## Quy tắc khi chưa chốt

- Codex có thể xây Catalog và UI đọc dữ liệu ngay.
- Codex không được tự chọn nhà cung cấp thanh toán, chính sách phí giao hàng, lịch hoạt động hay kênh gửi tin.
- Nếu một implementation cần dữ kiện còn mở, Codex phải nêu đúng lựa chọn bị thiếu và tác động của từng phương án trước khi code.

