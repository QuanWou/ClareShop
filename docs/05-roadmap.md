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

**Đã hoàn thành phần nội bộ; chờ credentials để nối dịch vụ thật**

1. Đã chốt COD, QR ngân hàng payOS động và không có VAT ở V1.
2. Đã tạo schema `orders`, `order_items`, `order_status_histories`, `payments`, `inventory_movements`; biến thể có thêm trọng lượng đóng gói.
3. Đã có Checkout API báo giá vận chuyển động theo địa chỉ/tổng trọng lượng và tạo đơn trong transaction; chỉ tài khoản đang hoạt động được dùng checkout, API snapshot sản phẩm/giá, trừ tồn và ghi inventory movement.
4. Đã tích hợp Payment Link/QR payOS với thời hạn 3 phút, return URL, webhook xác minh chữ ký và xử lý idempotent.
5. Đã có UI Checkout/xác nhận đơn và back office cho luồng trạng thái đơn, xác nhận/hoàn thanh toán và hoàn tồn kho khi hủy. Checkout cho phép so sánh/chọn quote ước tính của GHN, GHTK hoặc J&T Express; hỗ trợ COD, payOS, MoMo Sandbox, PayPal Sandbox và trả sau. Cần credentials production/adapter vận chuyển thật trước khi gọi các tích hợp là chính thức.
6. Đã có Kho ưu đãi công khai, Ví voucher của khách, picker voucher trong checkout và màn quản trị mã. Một ưu đãi/đơn được snapshot audit; reservation giữ mã khi tạo đơn, chỉ redeem khi payment `paid`, còn đơn pending không phải COD tự hết hạn sau 30 phút và hoàn tồn/nhả mã. ETA/mã theo dõi hiện là mô phỏng nội bộ cho đến khi có adapter vận chuyển thật.
7. Khách có thể phục hồi đơn chưa thanh toán bằng cách tạo payment attempt mới với phương thức khác, hoặc tự hủy đơn khi attempt đã hủy/thất bại/hết hạn. Hệ thống vô hiệu attempt cũ, chặn webhook đến muộn và tái sử dụng transaction hoàn tồn kho/voucher hiện có.

## Phase 5 — Consultation và installation

**Đã hoàn thành luồng gửi yêu cầu/xử lý thủ công; chờ chốt lịch và vùng phục vụ**

1. Đã tạo bảng Appointment, lịch sử trạng thái, Form Request và Action tạo yêu cầu trong transaction.
2. Đã có form công khai cho tư vấn/lắp đặt; khách chỉ gửi thời gian mong muốn, hệ thống tạo trạng thái `pending` và không tự xác nhận lịch.
3. Đã có back office xác nhận lịch thủ công, đổi trạng thái và gắn order tùy chọn theo mã đơn.

## Phase 6 — Account, admin và content

**Đã hoàn thành phạm vi chức năng trong tài liệu ngày 2026-08-21**

1. Đã có authentication session nguyên bản của Laravel: đăng ký, đăng nhập, đăng xuất, rate limit đăng nhập, kiểm tra `is_active` và hợp nhất giỏ khách sau login. Trang đăng nhập/đăng ký đã có giao diện thương hiệu responsive, validation hai lớp, chỉ báo độ mạnh và thao tác hiện/ẩn mật khẩu.
2. Đã thêm trang Tài khoản cơ bản để khách xem các đơn/yêu cầu được tạo khi đã đăng nhập.
3. Đã có role gate `admin`, dashboard vận hành có biểu đồ/chỉ số, back office Order/Appointment, CRUD Catalog (danh mục, sản phẩm, biến thể, ảnh), quản lý mã ưu đãi và module khách hàng. Admin có thể tìm/lọc/sắp xếp khách, xem thông tin liên hệ, trạng thái tài khoản, tổng đơn, tổng tiền từ đơn hoàn tất, đơn gần đây và địa chỉ đã lưu; quyền cập nhật/khóa/xóa an toàn hiện có vẫn được giữ. Catalog archive mềm để giữ audit đơn hàng.
4. Đã có module Content và trang `/admin/content` để sửa nội dung/ảnh biên tập của storefront; dữ liệu sản phẩm/danh mục tiếp tục sửa qua Catalog Admin và nội dung trạng thái/validation vẫn cố định theo nghiệp vụ.
5. Đã có nhiều địa chỉ, wishlist, lịch sử xem, review mua thật/kiểm duyệt, taxonomy Catalog, Blog/TinyMCE, thư viện media, báo cáo, Settings/SEO/SMTP và đăng nhập Google/Facebook ở trạng thái cấu hình sẵn.

## Phase 7 — Chất lượng và triển khai

- Feature tests cho catalog, cart, checkout, stock, appointment và các module mở rộng: đã có; lần chạy 2026-08-21 đạt 78/78 test, 645 assertion.
- Authorization, validation, rate limit và CSRF: đã có ở các luồng chính; tiếp tục rà soát khi thêm tích hợp ngoài.
- Cấu hình production, backup MySQL, queue/mail thật, OAuth/gateway/carrier và monitoring: chờ môi trường/credentials production.
