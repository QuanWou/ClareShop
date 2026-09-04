# Clare

Clare là storefront bán đèn ngủ được xây bằng Laravel, Blade, Vite, Tailwind CSS và MySQL. Giao diện hướng đến cảm giác ấm, dịu và giàu tính biên tập; toàn bộ nội dung và tài sản hình ảnh của dự án là nguyên bản.

## Trạng thái hiện tại

- Catalog có category, product, biến thể màu, giá, tồn kho và ảnh.
- Storefront có trang chủ, trang collection và trang chi tiết sản phẩm.
- Cart hỗ trợ khách vãng lai/tài khoản, thêm/cập nhật/xóa theo biến thể và subtotal theo giá hiện tại.
- Checkout API báo giá ship theo địa chỉ/tổng trọng lượng, tạo đơn, trừ tồn an toàn và hỗ trợ COD hoặc QR payOS có đối soát webhook.
- Giá được hiển thị bằng VND theo dạng `100.000 VND`.
- Dữ liệu giá và tồn kho chỉ được đọc từ `product_variants`.
- UI checkout hoàn chỉnh, appointment và back office sẽ được triển khai theo các phase tiếp theo trong `docs/05-roadmap.md`.

## Khởi động cục bộ

Yêu cầu PHP 8.4+, Composer, Node.js và MySQL tại `127.0.0.1:2000`.

```powershell
composer install
npm install
php artisan migrate
php artisan db:seed --class=CatalogSeeder
npm run build
php artisan serve
```

Nếu máy có nhiều phiên bản PHP, hãy bảo đảm `php` đang trỏ tới PHP 8.4 trước khi chạy Artisan hoặc Composer.

### payOS

Đặt `PAYOS_CLIENT_ID`, `PAYOS_API_KEY` và `PAYOS_CHECKSUM_KEY` trong `.env`. Khi dùng
`start-clare.cmd`, tunnel công khai sẽ được tạo tự động và webhook payOS được đăng ký tại
`/webhooks/payos`; khi triển khai production, cấu hình `PAYOS_WEBHOOK_URL` bằng URL HTTPS
trỏ tới endpoint này. Có thể đặt `PAYOS_IP_RESOLVE=4`, `6` hoặc `auto`; script local tự lưu
IP family hoạt động sau khi payOS chấp nhận webhook. Trạng thái còn được đối soát qua API
khi khách mở trang đơn và bởi scheduler mỗi 15 giây để dự phòng callback phát triển bị mất.

## Kiểm tra

```powershell
php artisan test
npm run build
```

Các quyết định nghiệp vụ, kiến trúc và hướng giao diện nằm trong thư mục `docs/`. Đọc `AGENTS.md` trước khi thay đổi mã.
