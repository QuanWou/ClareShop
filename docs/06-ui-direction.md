# Định hướng giao diện

## Cảm giác cần đạt

Tham khảo cảm giác mềm, sáng và giàu không gian của Clare.com: nền ấm, màu sắc có chủ đích, typography có tính biên tập, ảnh sản phẩm lớn và CTA nhẹ. Đây là tham khảo về tinh thần, **không sao chép HTML, CSS, ảnh, logo, nội dung hay bố cục nhận diện của họ**.

## Nguyên tắc thị giác

- Nền sáng ngà/kem, chữ than nâu đen; dùng một màu nhấn trầm như burgundy hoặc olive, không dùng gradient neon.
- Nhiều khoảng trắng; card ít viền và ít shadow.
- Ảnh sản phẩm chiếm ưu tiên, crop nhất quán, có nền dịu.
- Dùng `Noto Serif` cho headline và `Be Vietnam Pro` cho nội dung. Cả hai được đóng gói cùng website để hiển thị tiếng Việt ổn định trên mọi thiết bị; headline tiếng Việt không dùng letter-spacing âm, có giãn dòng tối thiểu `1.08` và giới hạn cỡ chữ riêng để dấu thanh không bị dính hoặc va vào dòng kế.
- Chuyển động ngắn, giảm khi người dùng chọn `prefers-reduced-motion`.

## Cấu trúc trang chính

### Home

1. Header sticky ba cụm: wordmark in hoa, điều hướng theo danh mục, các nút SVG tìm kiếm/tài khoản và giỏ hàng có badge số lượng. Tìm kiếm mở ngang ngay trong chiều cao header bằng form GET bo góc, không tạo modal/backdrop hay che nội dung bên dưới; form đóng được bằng nút, Escape hoặc click ngoài header, mỗi biểu tượng phải có nhãn truy cập được.
2. Hero có một thông điệp ngắn và ảnh/khối màu nguyên bản.
3. Collection nổi bật.
4. Brand banner toàn chiều rộng đóng vai trò khoảng nghỉ biên tập giữa Collections và danh mục sản phẩm; giữ wordmark/đèn trong ảnh dễ đọc, có CTA thật đến toàn bộ sản phẩm.
5. Sản phẩm mới hoặc được chọn.
6. Khối "Need a little help?" dẫn đến tư vấn/lắp đặt.
7. Footer: liên hệ, chính sách, mạng xã hội nếu có.

### Collection

- Tiêu đề category có ảnh biên tập nguyên bản, mô tả ngắn và số lượng sản phẩm hiện có.
- Có thanh chuyển bộ sưu tập tối giản lấy động từ các category đang hiển thị, kèm số sản phẩm publish; chỉ hiển thị control nào thật sự hoạt động.
- Product grid responsive: 1 cột mobile, 2 tablet, 3–4 desktop tùy chiều rộng; mỗi card có ảnh chính và ảnh bối cảnh thứ hai chuyển mờ trong 300 ms khi hover/focus, kèm CTA xem chi tiết hiện rõ.
- Có một khối biên tập ngắn sau lưới để hỗ trợ khách chọn theo không gian, không sao chép nội dung/thông điệp của trang tham chiếu.

### Search

- Kết quả tìm kiếm có hero và form tìm lại rõ ràng, số kết quả thật, lối khám phá nhanh theo các category đang hiển thị và trạng thái trống dẫn về toàn bộ catalog.
- Dùng cùng product card, phân trang tiếng Việt, responsive grid và reveal motion với All products/Collection.

### All products

- Có trang toàn bộ sản phẩm độc lập với Home; danh sách tự lấy sản phẩm đang bán từ database, phân loại theo các category đang hiển thị và phân trang sau 12 sản phẩm.
- Mở đầu bằng collage lấy từ ảnh sản phẩm thật trong trang hiện tại; không thêm ảnh trang trí giả hoặc nội dung sản phẩm không có trong database.
- Bộ lọc danh mục hiển thị số sản phẩm đang publish, giữ trạng thái chọn rõ ràng và cuộn ngang được trên mobile. Grid dùng 4 cột desktop, 3 cột laptop, 2 cột tablet và 1 cột mobile.
- Phân trang dùng nhãn tiếng Việt; cuối danh sách có CTA thật đến form tư vấn, không hứa chức năng chưa có.

### Product detail

- Gallery ảnh lớn bên trái/trên mobile, có thumbnail thật từ `product_images`; khi chọn màu có ảnh riêng thì ảnh chính đổi theo biến thể. Thêm giỏ diễn ra ngay tại trang (không điều hướng); sau phản hồi thành công, badge giỏ cập nhật và có chuyển động ngắn từ ảnh sản phẩm về giỏ. Chuyển động phải tuân theo `prefers-reduced-motion`.
- Tên, mô tả ngắn, giá, lựa chọn màu, tồn kho và nút thêm giỏ.
- Mô tả chi tiết, vật liệu/kích thước, liên kết tư vấn/lắp đặt.
- Không giấu thông tin giá hay tình trạng hàng.

### Checkout

- Khách vãng lai được giữ giỏ; khi chọn checkout phải được chuyển đến đăng nhập/đăng ký rồi quay lại checkout. Checkout có form liên hệ gắn tài khoản, địa chỉ giao hàng, chọn COD hoặc chuyển khoản VietQR và tóm tắt đơn sticky trên desktop.
- Phí ship hiện là ước tính động theo địa chỉ/tổng trọng lượng; hiển thị rõ trạng thái ước tính và tính lại tại server khi tạo đơn.
- Trang xác nhận đơn chỉ chủ đơn đã đăng nhập mới truy cập được, ngay cả khi có signed URL tạm thời. Với chuyển khoản, hiển thị QR có đúng tổng tiền/mã đơn nhưng không tuyên bố thanh toán đã thành công trước đối soát.

## Khả năng truy cập

- Tương phản chữ đủ cao; không truyền đạt trạng thái chỉ bằng màu.
- Tất cả nút/icon có nhãn truy cập được.
- Selector màu phải là button/radio có tên màu hiển thị cho screen reader.
- Ảnh có `alt_text`; ảnh trang trí có alt rỗng.
- Không dùng hover là cách duy nhất để thấy thông tin quan trọng.

## Quản trị nội dung

- Admin Content chia trường theo đúng khu vực storefront, có điều hướng nhanh, preview ảnh, giới hạn ký tự và thanh lưu sticky.
- Ảnh hero, brand banner, khối chất liệu và auth có thể thay bằng JPG/PNG/WebP; giao diện dùng fallback trong config trước khi database được seed.
- Nội dung động phải được escape mặc định qua Blade; không cho nhập HTML tùy ý. Dữ liệu Catalog và các nhãn nghiệp vụ tiếp tục đi qua module sở hữu tương ứng.
