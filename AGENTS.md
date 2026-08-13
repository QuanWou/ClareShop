# Clare — Hướng dẫn làm việc cho Codex

## Đọc trước khi thay đổi mã

Đọc lần lượt các tài liệu trong `docs/`:

1. `00-current-state.md`
2. `01-product-and-scope.md`
3. `02-business-rules.md`
4. `03-database.md`
5. `04-architecture.md`
6. `05-roadmap.md`
7. `06-ui-direction.md`
8. `07-decisions-and-open-questions.md`

Các file này là nguồn sự thật cho dự án Clare. Nếu code hiện tại và tài liệu mâu thuẫn, hãy kiểm tra migration, trạng thái database và hỏi người dùng trước khi đổi nghiệp vụ.

## Bối cảnh đã xác nhận

- Dự án: cửa hàng đèn ngủ Clare, giao diện dịu và giàu tính biên tập; lấy cảm hứng về cảm giác từ Clare.com nhưng không sao chép giao diện, nội dung hay tài sản của họ.
- Stack: Laravel 13.25.0, PHP 8.4.24 qua Laravel Herd, MySQL 8.0.46 chạy riêng qua cổng `2000`, Node 24.12.0, npm 11.6.2.
- Database: `clare`; kết nối bằng `127.0.0.1:2000`.
- Migration mặc định Laravel và migration Catalog đã chạy thành công.
- Catalog đã có các bảng: `categories`, `products`, `product_variants`, `product_images`.
- Code Model Catalog có thể đã được tạo bởi Artisan; luôn mở và kiểm tra file hiện có trước khi ghi đè.

## Cách làm việc bắt buộc

- Làm theo từng module và từng use case; không tạo một lớp `Service` khổng lồ hoặc copy logic giữa controller.
- Controller chỉ nhận request, gọi Action và trả response/view. Validation đặt ở Form Request. Transaction nghiệp vụ đặt trong Action.
- Dữ liệu giá và tồn kho chỉ lấy từ `product_variants`; không thêm `price` hay `stock_quantity` vào `products`.
- Không chạy các lệnh phá dữ liệu như `migrate:fresh`, `migrate:reset`, `db:wipe` hoặc xóa hàng loạt nếu chưa có chấp thuận rõ ràng.
- Không sửa `.env` để lộ mật khẩu. Không đưa bí mật vào git hoặc tài liệu.
- Mọi thay đổi schema phải có migration mới; không sửa migration đã chạy trên database dùng chung.
- Không tự quyết định các điểm trong `docs/07-decisions-and-open-questions.md`; hỏi người dùng khi bước thực hiện thực sự phụ thuộc vào chúng.
- Sau mỗi thay đổi, chạy kiểm tra nhỏ nhất phù hợp: `php artisan migrate --pretend`, test mục tiêu, lint/format hoặc kiểm tra route. Báo rõ kết quả.

## Cấu trúc code

Giữ code nghiệp vụ theo module trong `app/Modules/<Module>/`:

```text
Actions/             Một use case, ví dụ CreateOrderAction
Http/Controllers/    Điều phối HTTP mỏng
Http/Requests/       Validation và authorization của request
Models/              Eloquent model, casts và quan hệ
```

Views nằm tại `resources/views/<module>/`; route web nằm tại `routes/web.php`; migration tiếp tục ở `database/migrations/` để dùng đúng cơ chế Laravel mặc định.

## Thứ tự ưu tiên

1. Hoàn thiện Catalog: model, dữ liệu mẫu, trang danh sách và trang chi tiết.
2. Xây storefront Blade/Vite/Tailwind theo `docs/06-ui-direction.md`.
3. Cart theo biến thể sản phẩm.
4. Checkout và Orders trong transaction.
5. Appointment tư vấn/lắp đặt.
6. Back office, phân quyền và nội dung động.
7. Test, phân quyền, tối ưu và triển khai.

