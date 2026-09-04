# Trạng thái hiện tại

> Cập nhật 2026-08-21: Catalog phân cấp, thương hiệu/thuộc tính, review, nhiều địa chỉ, blog, wishlist/lịch sử xem, media, báo cáo, settings/SEO/SMTP và Socialite đã được triển khai. Xem bảng đối chiếu đầy đủ tại [`08-implementation-status-2026-08-21.md`](08-implementation-status-2026-08-21.md). Các tích hợp cần credentials thật vẫn được giữ ở trạng thái cấu hình sẵn, chưa được trình bày như dịch vụ production.

## Môi trường đã kiểm tra

| Thành phần | Trạng thái |
| --- | --- |
| PHP | 8.4.24, được Herd cung cấp |
| Composer | 2.10.2 |
| Laravel Installer | 5.28.1 |
| Laravel Framework | 13.25.0 |
| Node.js | 24.12.0 |
| npm | 11.6.2 |
| Git | 2.51.0.windows.1 |
| MySQL Server | 8.0.46, Windows service `MySQL80` |
| MySQL host / port | `127.0.0.1:2000` |
| Database | `clare` |

## Việc đã hoàn thành

- Đã tạo ứng dụng Laravel tại `D:\Clare`.
- Đã tạo `APP_KEY`.
- Laravel đã kết nối được MySQL; `php artisan migrate` chạy thành công.
- Migration mặc định đã tạo: `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`.
- Migration Catalog đã tạo và đã chạy: `categories`, `products`, `product_variants`, `product_images`.
- Migration Cart đã tạo và đã chạy: `carts`, `cart_items`.
- Migration Checkout/Order đã chạy: `weight_grams` cho biến thể, `orders`, `order_items`, `order_status_histories`, `payments`, `inventory_movements`.
- Thư mục module đã được định hướng dưới `app/Modules/`.
- Bốn Model Catalog đã nằm đúng namespace `App\Modules\Catalog\Models`, có casts, quan hệ và scope storefront.
- Đã có `CatalogSeeder` idempotent với 4 category, 27 product, 30 variant và 33 ảnh catalog lưu trong `public/images/catalog/`; giá, SKU, tồn kho và trọng lượng của 24 mẫu bổ sung được suy luận từ ảnh theo yêu cầu của chủ dự án và có thể chỉnh lại qua back office sau này.
- Đã có Action và controller mỏng cho home, collection và product detail.
- Storefront Blade/Vite/Tailwind đã có layout, header, footer, responsive grid, selector màu và trạng thái tồn kho theo variant.
- Trang chủ đã dùng banner thương hiệu `public/images/catalog/banner.png` như một khoảng biên tập giữa Collections và sản phẩm nổi bật; có sticky header, reveal theo cuộn, parallax nhẹ, hover ảnh/card và hiệu ứng ánh sáng, đồng thời tắt/giảm chuyển động theo `prefers-reduced-motion`.
- Trang toàn bộ sản phẩm đã có giao diện catalog boutique: hero collage từ ảnh Catalog thật, bộ lọc danh mục sticky kèm số lượng, lưới responsive 4/3/2/1 cột, card bo mềm, phân trang tiếng Việt, CTA tư vấn và reveal animation có hỗ trợ reduced motion.
- Các trang Catalog liên quan đã dùng chung ngôn ngữ boutique: collection chuyển danh mục động kèm số lượng, tìm kiếm có lối khám phá thay thế, product detail có gallery thumbnail/đổi ảnh theo màu, khối mua hàng sticky và sản phẩm liên quan responsive.
- Tìm kiếm toàn storefront mở ngang ngay trong phạm vi header, giữ nguyên chiều cao thanh điều hướng và không che nội dung trang; form bo tròn, tự focus, đóng bằng nút/Escape/click bên ngoài và giảm chuyển động theo thiết bị.
- Phase 2 đã được kiểm tra trực quan trên desktop/mobile; menu mobile, skip-link, keyboard focus và selector biến thể đã được rà soát trong trình duyệt.
- Cart hỗ trợ khách vãng lai bằng cookie UUID 30 ngày, giỏ gắn tài khoản và hợp nhất giỏ khách khi đăng nhập. Khách vãng lai có thể giữ giỏ nhưng không thể báo giá hoặc tạo đơn.
- Trang sản phẩm có thể thêm biến thể thật vào giỏ ngay tại chỗ qua JSON, cập nhật badge và phản hồi chuyển động về giỏ mà không điều hướng; trang Cart hỗ trợ xem, cập nhật, xóa và tính subtotal từ giá biến thể hiện tại bằng VND.
- Cart hỗ trợ checkbox chọn từng dòng hoặc chọn tất cả để thanh toán riêng. Trạng thái chọn được lưu trên `cart_items`; checkout và voucher chỉ tính các dòng đã chọn, còn sản phẩm chưa chọn được giữ lại sau khi tạo đơn.
- Mọi thao tác thêm/cập nhật đều kiểm tra lại trạng thái publish, trạng thái variant, giới hạn và tồn kho ở server.
- Phase 3 đã được kiểm tra trực quan desktop/mobile với luồng chọn biến thể, thêm, cập nhật, xóa và trạng thái giỏ trống; console trình duyệt không có lỗi.
- Đã tạo Git baseline tại commit `43ca187` trước khi triển khai Catalog storefront.
- Feature test bảo vệ Catalog và các luồng Cart chính, gồm tồn kho, quyền sở hữu giỏ, hợp nhất giỏ và thay cookie hết hạn.
- Checkout API có endpoint báo giá và tạo đơn; hỗ trợ COD hoặc QR ngân hàng qua payOS với mã QR/link thanh toán động theo đúng tổng tiền. Phí ship đang là ước tính động cho đến khi kết nối GHN/GHTK thật.
- Checkout web bắt buộc khách đăng nhập bằng tài khoản đang hoạt động; form lấy email snapshot từ tài khoản ở server, gắn mọi đơn mới với `user_id` và chỉ chủ đơn được xem trang xác nhận qua signed URL. QR payOS hết hạn sau 3 phút; payment chỉ chuyển `paid` sau webhook có chữ ký hợp lệ hoặc API payOS xác nhận. Ngoài callback, trang đơn polling khoảng 4 giây và scheduler đối soát tối đa 5 giao dịch chờ mỗi 15 giây để chịu được webhook/tunnel phát triển bị gián đoạn.
- Đã có public form cho yêu cầu tư vấn hoặc lắp đặt; mỗi yêu cầu và lịch sử trạng thái được tạo ở trạng thái `pending`, còn lịch thực tế do nhân viên xác nhận ở back office sau này.
- Đã có đăng ký, đăng nhập, đăng xuất bằng session cho khách hàng; header hiển thị `Đăng nhập`/`Đăng ký` với khách vãng lai và liên kết Tài khoản sau đăng nhập. Trang auth dùng banner thương hiệu Clare, responsive, có hiệu ứng giảm chuyển động, phản hồi validation tại chỗ và validation server cho thông tin liên hệ/mật khẩu. Tài khoản có trang xem lại đơn/yêu cầu được tạo khi đang đăng nhập; giỏ khách được hợp nhất sau login.
- Đã có back office tại `/admin`, chỉ cho tài khoản đang hoạt động có role `admin`: dashboard vận hành, danh sách/chi tiết đơn hàng và yêu cầu tư vấn/lắp đặt.
- Back office ghi lịch sử đầy đủ khi chuyển trạng thái đơn, thanh toán hoặc lịch hẹn. Hủy đơn hoàn tồn kho trong transaction; đơn đã thanh toán phải được ghi nhận hoàn tiền trước khi hủy.
- Đã thêm `payment_status_histories` và lệnh an toàn `php artisan clare:grant-admin <email>` để chủ dự án cấp quyền admin cho đúng tài khoản, không tự gán quyền cho dữ liệu có sẵn.
- Luồng đơn đã có promotion code server-side (một mã/đơn, snapshot audit), ngày giao dự kiến mô phỏng, mã theo dõi nội bộ và timeline các mốc `Chờ xác nhận → Chờ lấy hàng → Đang chuẩn bị giao → Đang giao hàng → Đã giao` để khách xem trong tài khoản; admin là nơi cập nhật trạng thái và quản trị mã ưu đãi.
- Với đơn còn `pending` nhưng payment đã `unpaid`, `failed` hoặc `expired`, chủ đơn có thể chọn lại một trong các phương thức thanh toán hoặc tự hủy. Mỗi lần đổi tạo payment attempt mới và vô hiệu attempt cũ; hủy đơn dùng chung transaction hoàn tồn kho/nhả voucher. Webhook đến muộn của attempt cũ không được phép ghi đè payment hiện hành.
- Back office hiện có dashboard vận hành với biểu đồ trạng thái đơn, giá trị đơn bảy ngày, tồn kho thấp và các chỉ số khách hàng; có CRUD an toàn cho catalog (danh mục, sản phẩm, biến thể, ảnh), tìm kiếm/lọc, quản lý mã ưu đãi và module khách hàng. Danh sách khách hỗ trợ lọc/sắp xếp theo giá trị mua hàng, số đơn hoặc lần mua gần nhất; hồ sơ chi tiết hiển thị thông tin liên hệ, tổng đơn, tổng tiền từ đơn hoàn tất, sáu đơn gần đây, sổ địa chỉ và trạng thái truy cập. Ảnh admin upload vào disk `public` qua liên kết `public/storage`.
- Đã có module Content tại `/admin/content`: admin sửa nội dung thương hiệu, header/footer, các khối trang chủ, catalog, collection, tìm kiếm, chi tiết sản phẩm và tiêu đề/giới thiệu trang tài khoản; ảnh hero, banner, câu chuyện và auth có thể thay trực tiếp. Nội dung Catalog theo từng sản phẩm/danh mục vẫn do CRUD Catalog quản lý; nhãn nghiệp vụ, trạng thái và validation giữ cố định trong code.

## Điều cần kiểm tra trước khi sửa tiếp

Catalog, Cart và Checkout API hiện đã có code, migration và dữ liệu mẫu cần thiết. Trước khi sửa tiếp, kiểm tra:

```powershell
Get-ChildItem .\app\Modules\Catalog -Recurse
Get-ChildItem .\app\Modules\Cart -Recurse
Get-ChildItem .\app\Modules\Orders -Recurse
php artisan migrate:status
php artisan test
```

Không được đoán nội dung file hoặc trạng thái migration chỉ từ tên file. Terminal hiện có thể ưu tiên PHP 8.2 của XAMPP; khi đó cần gọi PHP 8.4 của Herd hoặc sửa PATH cục bộ trước khi chạy lệnh dự án.

## Kết nối database cục bộ

Trong `.env`, cấu hình phải có dạng sau; không ghi mật khẩu thật vào tài liệu hoặc source:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=2000
DB_DATABASE=clare
DB_USERNAME=root
DB_PASSWORD=<local-secret>
```

Sau này nên tạo tài khoản MySQL riêng cho ứng dụng; việc đó không cần làm trước khi hoàn thành V1 cục bộ.
