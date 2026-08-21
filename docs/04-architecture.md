# Kiến trúc ứng dụng

## Phong cách kiến trúc

Dùng **modular monolith**: một ứng dụng Laravel, một database, nhưng code nghiệp vụ tách theo chiều dọc module. Cách này đơn giản để phát triển V1 nhưng không trộn Cart, Orders, Catalog và Appointment vào nhau.

```text
app/Modules/
├── Shared/
│   └── Support/
├── Catalog/
│   ├── Actions/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   └── Models/
├── Cart/
├── Orders/
├── Appointments/
├── Customers/
└── Content/
```

## Trách nhiệm từng phần

| Phần | Được làm | Không được làm |
| --- | --- | --- |
| Controller | Nhận HTTP, gọi Form Request/Action, trả view hoặc response | Chứa transaction, tính tiền, truy vấn nghiệp vụ dài |
| Form Request | Validation, authorization của request | Lưu model hoặc điều phối workflow |
| Action | Một use case hoàn chỉnh, ví dụ `CreateOrderAction` | Render HTML hoặc đọc input trực tiếp từ global request |
| Model | Quan hệ, casts, query scope nhỏ, trạng thái nội tại | Điều phối nhiều module hoặc gửi notification trực tiếp |
| Blade view | Hiển thị dữ liệu đã chuẩn bị | Tính tổng tiền, query database, phân quyền phức tạp |
| Shared | Helper thuần, value object, enum thật sự dùng ở từ hai module | Nơi đặt code vì chưa biết đặt vào module nào |

## Ranh giới module

| Module | Sở hữu |
| --- | --- |
| Catalog | Category, Product, ProductVariant, ProductImage và truy vấn storefront |
| Cart | Cart, CartItem, token giỏ khách vãng lai, thao tác thêm/sửa/xóa |
| Orders | Checkout, transaction tồn kho, Order, OrderItem, chuyển trạng thái |
| Appointments | Lịch tư vấn/lắp đặt và thông báo nội bộ sau này |
| Customers | Hồ sơ khách, lịch sử đơn và yêu cầu sau đăng nhập; truy vấn tổng hợp phục vụ danh sách/hồ sơ khách trong back office |
| Content | Nội dung biên tập và asset thương hiệu dùng chung trên storefront; registry khóa/trường tại config và lịch sử người cập nhật gần nhất |
| Shared | Enum, money formatter, support dùng thật sự ở nhiều module |

## Quy tắc phụ thuộc

- Cart tham chiếu `Catalog\Models\ProductVariant`, không lặp lại giá/tồn kho.
- Orders có thể đọc Catalog để tạo snapshot, nhưng Catalog không được phụ thuộc vào Orders.
- Appointment chỉ tham chiếu Order tùy chọn; Order không cần phụ thuộc ngược vào Appointment.
- Content không sở hữu dữ liệu sản phẩm/danh mục và không điều khiển nhãn trạng thái hay quy tắc validation của các module nghiệp vụ.
- Không tạo repository/service layer chỉ để bọc một câu Eloquent. Chỉ tạo Action khi có use case hoặc transaction rõ ràng.

## Routes và view

- Giữ khai báo route tại `routes/web.php`, đặt tên theo module: `catalog.*`, `cart.*`, `checkout.*`, `appointments.*`.
- View: `resources/views/catalog/`, `resources/views/cart/`, `resources/views/orders/`, `resources/views/appointments/`.
- URL catalog dùng slug, ví dụ `/collections/{category:slug}` và `/products/{product:slug}`.
