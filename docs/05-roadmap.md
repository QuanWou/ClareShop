# Lộ trình triển khai

## Phase 0 — Nền tảng

**Đã hoàn thành**

- Laravel, Herd, MySQL và `.env` hoạt động.
- Migration mặc định và Catalog đã chạy.
- Model Catalog đã đúng namespace, casts và quan hệ; Git baseline đã được tạo trước thay đổi lớn.

## Phase 1 — Catalog có dữ liệu và storefront đọc được

**Đã hoàn thành**

1. Hoàn thiện 4 Model Catalog và quan hệ.
2. Tạo Category/Product/Variant/Image seed data có nội dung đèn thật nhưng ảnh placeholder nội bộ hoặc ảnh được phép dùng.
3. Tạo Action truy vấn catalog đã publish.
4. Tạo trang home, collection và product detail bằng Blade.
5. Hiển thị giá từ biến thể và trạng thái còn/hết hàng.

**Hoàn thành khi:** khách xem được danh sách, lọc theo category, mở sản phẩm, chọn màu và thấy giá/tồn kho đúng.

## Phase 2 — Giao diện Clare nguyên bản

**Đã hoàn thành**

1. Đã cài frontend dependencies và tạo lockfile.
2. Đã tạo layout, header, footer, design tokens và responsive styles.
3. Đã dựng home, collection và product detail bằng nội dung/asset nguyên bản của Clare.
4. Đã kiểm tra trực quan desktop/mobile, menu responsive, selector biến thể, focus skip-link, keyboard/Escape và trạng thái console trong trình duyệt.
5. Home đã có brand banner nguyên bản giữa Collections và danh mục nổi bật, cùng hệ thống chuyển động reveal/parallax/hover nhẹ có hỗ trợ reduced motion.
6. Trang All products đã được biên tập thành catalog boutique với collage ảnh thật, bộ lọc có số lượng, card responsive, phân trang tiếng Việt và CTA tư vấn.
7. Collection, Search và Product detail đã được đồng bộ với All products; mọi danh mục hiển thị lấy từ database, gallery dùng ảnh thật và các chuyển động đều hỗ trợ reduced motion.
8. Tìm kiếm mở ngang nội tuyến trong header, không dùng modal/backdrop và không đẩy nội dung bên dưới; tương tác desktop/mobile giữ đầy đủ focus, Escape cùng trạng thái `aria-expanded`.

## Phase 3 — Cart

**Đã hoàn thành**

1. Đã tạo `carts`, `cart_items` và Action cho guest/authenticated cart.
2. Đã hỗ trợ add/update/remove item theo `product_variant_id`.
3. Đã kiểm tra trạng thái product/variant và tồn kho ở mỗi thao tác quan trọng.
4. Đã hiển thị subtotal từ giá biến thể hiện tại theo định dạng VND.
5. Đã xử lý cookie giỏ khách 30 ngày, hợp nhất vào giỏ tài khoản và các trường hợp cookie hết hạn.
6. Đã có feature test cho các use case Cart chính.
7. Đã kiểm tra trực quan luồng Cart trên desktop/mobile và trạng thái console trong trình duyệt.

## Phase 4 — Checkout và Orders

**Đang triển khai**

1. Đã chốt COD, chuyển khoản VietQR động và không có VAT ở V1.
2. Đã tạo schema `orders`, `order_items`, `order_status_histories`, `payments`, `inventory_movements`; biến thể có thêm trọng lượng đóng gói.
3. Đã có Checkout API báo giá vận chuyển động theo địa chỉ/tổng trọng lượng và tạo đơn trong transaction; chỉ tài khoản đang hoạt động được dùng checkout, API snapshot sản phẩm/giá, trừ tồn và ghi inventory movement.
4. Đã trả về QR VietQR động cho chuyển khoản, có `amount` đúng theo server và `addInfo` bằng mã đơn; trạng thái vẫn là `pending` chờ đối soát.
5. Đã có UI Checkout/xác nhận đơn và back office cho luồng trạng thái đơn, xác nhận/hoàn thanh toán thủ công và hoàn tồn kho khi hủy. Checkout cho phép so sánh/chọn quote ước tính của GHN, GHTK hoặc J&T Express; hỗ trợ COD, VietQR, MoMo, thẻ ngân hàng và trả sau. Cần adapter vận chuyển thật, merchant gateway và webhook đối soát trước khi gọi các quote/thanh toán là chính thức.
6. Đã có mã ưu đãi server-side và màn quản trị mã: một mã/đơn, snapshot audit, thời hạn, điều kiện đơn tối thiểu, mức giảm tối đa, giới hạn lượt dùng và bật/tắt. ETA/mã theo dõi hiện là mô phỏng nội bộ cho đến khi có adapter vận chuyển thật.

## Phase 5 — Consultation và installation

**Đang triển khai**

1. Đã tạo bảng Appointment, lịch sử trạng thái, Form Request và Action tạo yêu cầu trong transaction.
2. Đã có form công khai cho tư vấn/lắp đặt; khách chỉ gửi thời gian mong muốn, hệ thống tạo trạng thái `pending` và không tự xác nhận lịch.
3. Đã có back office xác nhận lịch thủ công, đổi trạng thái và gắn order tùy chọn theo mã đơn.

## Phase 6 — Account, admin và content

**Đang triển khai**

1. Đã có authentication session nguyên bản của Laravel: đăng ký, đăng nhập, đăng xuất, rate limit đăng nhập, kiểm tra `is_active` và hợp nhất giỏ khách sau login. Trang đăng nhập/đăng ký đã có giao diện thương hiệu responsive, validation hai lớp, chỉ báo độ mạnh và thao tác hiện/ẩn mật khẩu.
2. Đã thêm trang Tài khoản cơ bản để khách xem các đơn/yêu cầu được tạo khi đã đăng nhập.
3. Đã có role gate `admin`, dashboard vận hành có biểu đồ/chỉ số, back office Order/Appointment, CRUD Catalog (danh mục, sản phẩm, biến thể, ảnh), quản lý mã ưu đãi và quyền/trạng thái tài khoản. Catalog archive mềm để giữ audit đơn hàng.
4. Đã có module Content và trang `/admin/content` để sửa nội dung/ảnh biên tập của storefront; dữ liệu sản phẩm/danh mục tiếp tục sửa qua Catalog Admin và nội dung trạng thái/validation vẫn cố định theo nghiệp vụ.

## Phase 7 — Chất lượng và triển khai

- Feature tests cho catalog, cart, checkout, stock và appointment.
- Kiểm tra authorization, validation, rate limit, CSRF, logging.
- Cấu hình production, backup MySQL, queue/mail thật, monitoring.
