# Lộ trình triển khai

## Phase 0 — Nền tảng

**Đã gần hoàn thành**

- Laravel, Herd, MySQL và `.env` hoạt động.
- Migration mặc định và Catalog đã chạy.
- Việc còn lại: xác nhận Model Catalog có đúng namespace, casts và quan hệ; tạo git baseline trước thay đổi lớn.

## Phase 1 — Catalog có dữ liệu và storefront đọc được

1. Hoàn thiện 4 Model Catalog và quan hệ.
2. Tạo Category/Product/Variant/Image seed data có nội dung đèn thật nhưng ảnh placeholder nội bộ hoặc ảnh được phép dùng.
3. Tạo Action truy vấn catalog đã publish.
4. Tạo trang home, collection và product detail bằng Blade.
5. Hiển thị giá từ biến thể và trạng thái còn/hết hàng.

**Hoàn thành khi:** khách xem được danh sách, lọc theo category, mở sản phẩm, chọn màu và thấy giá/tồn kho đúng.

## Phase 2 — Giao diện Clare nguyên bản

1. Cài frontend dependencies bằng `npm install` khi bắt đầu UI.
2. Tạo layout, header, footer, design tokens và responsive styles.
3. Dựng trang theo `06-ui-direction.md` bằng nội dung/asset của Clare, không copy mã hoặc asset từ website tham khảo.
4. Kiểm tra mobile, keyboard navigation, ảnh có alt text.

## Phase 3 — Cart

1. Tạo `carts`, `cart_items` và Action cho guest/authenticated cart.
2. Add/update/remove item theo `product_variant_id`.
3. Kiểm tra active variant và tồn kho ở mỗi thao tác quan trọng.
4. Hiển thị subtotal từ giá biến thể hiện tại.

## Phase 4 — Checkout và Orders

1. Chốt payment method, giao nhận và cách tính phí từ `07-decisions-and-open-questions.md`.
2. Tạo `orders`, `order_items`; tạo `CreateOrderAction` chạy transaction.
3. Snapshot sản phẩm/giá; trừ tồn kho an toàn; hiển thị trang xác nhận đơn.
4. Tạo luồng admin cập nhật trạng thái và hoàn tồn khi hủy.

## Phase 5 — Consultation và installation

1. Tạo bảng và Form Request cho appointment.
2. Form công khai cho tư vấn/lắp đặt.
3. Xây trạng thái xử lý trong back office; gắn order tùy chọn cho installation.

## Phase 6 — Account, admin và content

1. Chọn starter/auth approach phù hợp rồi mới thêm authentication.
2. Thêm role/authorization cho admin.
3. Back office catalog, order, appointment.
4. Nội dung landing page động nếu cần.

## Phase 7 — Chất lượng và triển khai

- Feature tests cho catalog, cart, checkout, stock và appointment.
- Kiểm tra authorization, validation, rate limit, CSRF, logging.
- Cấu hình production, backup MySQL, queue/mail thật, monitoring.

