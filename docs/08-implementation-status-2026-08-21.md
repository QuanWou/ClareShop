# Đối chiếu triển khai theo tài liệu — 2026-08-21

Tài liệu này ghi lại phần đã được triển khai từ hai bản yêu cầu `Cần sửa v1_15_8.docx` và `ClareShop_KeHoach_HoanThien.docx`. Ảnh minh họa trong tài liệu chỉ được dùng để hiểu chức năng; bố cục và tài sản giao diện không được sao chép vào Clare.

## Storefront và Catalog

- Danh mục động có phân cấp, danh mục chính/phụ, nhóm gợi ý theo loại đèn, phong cách, không gian và mục đích sử dụng.
- Thương hiệu, thuộc tính và giá trị thuộc tính được quản trị động; sản phẩm có thể thuộc nhiều danh mục.
- Trang sản phẩm hỗ trợ lọc theo danh mục, thương hiệu, khoảng giá và thuộc tính; sắp xếp nổi bật, mới nhất, bán chạy, giá tăng/giảm; chuyển lưới/danh sách và bộ lọc mobile.
- Chi tiết sản phẩm có thư viện ảnh, phóng to, ảnh theo biến thể, thông số động, số đã bán, điểm đánh giá, phân bố sao, mua ngay và sản phẩm liên quan.
- Tìm kiếm có gợi ý tự động an toàn; wishlist, sản phẩm vừa xem, trạng thái trống/lỗi, toast và loading đã được bổ sung.
- Nội dung, ảnh thương hiệu và màu giao diện dùng cấu hình động; không chuyển giá, tồn kho hoặc trạng thái nghiệp vụ thành nội dung tự do.

## Đánh giá sản phẩm

- Chỉ khách có đơn `completed` chứa sản phẩm mới được gửi một đánh giá cho sản phẩm đó.
- Đánh giá gồm số sao, nhận xét và tối đa bốn ảnh; mặc định chờ duyệt.
- Admin có danh sách kiểm duyệt và có thể chuyển `pending`, `approved`, `hidden`, kèm ghi chú nội bộ.
- Storefront chỉ tổng hợp và hiển thị đánh giá đã duyệt, có nhãn mua hàng đã xác thực.

## Khách hàng, địa chỉ, checkout và đơn hàng

- Tài khoản có nhiều địa chỉ, nhãn địa chỉ và một địa chỉ mặc định; checkout cho phép chọn địa chỉ đã lưu nhưng vẫn lưu snapshot vào đơn.
- Kho Ưu đãi & Voucher công khai, Ví voucher của khách và picker voucher tại checkout đã dùng chung hệ thống promotion hiện có. Voucher được nhận, giữ, redeem hoặc giải phóng theo các bảng audit riêng; không bị tính là đã dùng chỉ vì khách đã nhận mã.
- Mã dùng với payment pending không phải COD được giữ 30 phút. Scheduler hủy đơn hết hạn, hoàn tồn và nhả voucher đúng một lần; voucher chỉ tăng lượt dùng sau payment `paid`.
- Admin khách hàng giữ đầy đủ thông tin liên hệ, trạng thái, tổng đơn, tổng tiền hoàn tất, đơn gần đây và địa chỉ.
- Email xác nhận đơn chỉ gửi một lần sau khi thanh toán được xác nhận; email đổi trạng thái vẫn hoạt động, cấu hình SMTP được đọc từ Settings và lỗi gửi mail không làm hỏng transaction đơn hàng.
- payOS đã có Payment Link/QR 3 phút, return/cancel URL, webhook xác minh chữ ký, API kiểm tra trạng thái và xử lý idempotent. PayPal Sandbox đã có Orders/Capture API; MoMo Sandbox đã có adapter ký request, tạo payUrl và IPN xác minh chữ ký. COD và các phương thức chưa có gateway không tự đánh dấu đã thanh toán.

## Blog, nội dung và SEO

- Blog storefront có danh sách, chi tiết, danh mục, thẻ, bài liên quan và sản phẩm liên quan.
- Admin có CRUD bài viết, nháp/xuất bản, ảnh đại diện, SEO, danh mục/thẻ và trình soạn thảo TinyMCE self-hosted theo giấy phép GPL.
- Title, meta description, canonical, Open Graph, favicon, logo và màu thương hiệu có thể quản lý từ Settings.
- Module Content tiếp tục quản lý copy/ảnh biên tập theo khóa; Catalog quản lý nội dung sản phẩm/danh mục; Blog quản lý bài viết. Ba ranh giới này không trộn lẫn.

## Back office

- Dashboard có doanh thu hôm nay/tháng, số đơn, khách, sản phẩm đã bán, trạng thái đơn, xu hướng và sản phẩm nổi bật.
- Catalog Admin có danh mục phân cấp, thương hiệu, thuộc tính/giá trị, sản phẩm, biến thể và ảnh.
- Có module đơn hàng, khách hàng, đánh giá, khuyến mãi, blog, thư viện media, báo cáo theo khoảng ngày, nội dung và cài đặt website.
- Settings gồm thông tin cửa hàng, liên hệ, mạng xã hội, giao diện, SEO, SMTP, ghi chú thanh toán/vận chuyển và thông tin OAuth. Trường bí mật được mã hóa trong database và không hiển thị lại ra form.

## Đăng nhập mạng xã hội

- Laravel Socialite đã được tích hợp cho Google và Facebook; liên kết tài khoản theo provider ID hoặc email, đồng thời chặn tài khoản đã khóa/xóa.
- Nút đăng nhập chỉ hoạt động khi đủ client ID, client secret và callback URL trong Admin Settings.

## Tích hợp thật ngoài phạm vi bản mô phỏng

Theo quyết định ngày 2026-08-24, các phần sau chỉ cần hoạt động ở mức mô phỏng/cấu hình trong bản demo. Nếu sau này bật production thì mới cần dữ liệu/quyết định thật:

1. OAuth Google/Facebook: client ID, client secret, callback đã đăng ký.
2. SMTP: máy chủ, cổng, tài khoản, mật khẩu, mã hóa và địa chỉ gửi.
3. Hãng vận chuyển: API credentials, kho gửi, khu vực/dịch vụ và phụ phí.
4. Cổng trả sau: merchant credentials, chữ ký, webhook và quy trình đối soát/hoàn tiền. MoMo production và PayPal production vẫn cần live credentials cùng quy trình hoàn tiền chính thức.
5. Appointment: vùng phục vụ, khung giờ, thời lượng và kênh thông báo chính thức.
6. Phân quyền nhân viên: cần chốt ma trận quyền nếu tách khỏi role `admin`.

Không được dùng cấu hình giả để trình bày các mục trên như dịch vụ thật.

## Kiểm tra kỹ thuật

- Migration mới từ `2026_08_21_090001` đến `2026_08_21_090009` đều là migration cộng thêm, không sửa migration đã chạy.
- Bộ kiểm thử ngày 2026-08-28: 87 test, 734 assertion, tất cả đạt.
- Dữ liệu giá và tồn kho tiếp tục chỉ lấy từ `product_variants`.
