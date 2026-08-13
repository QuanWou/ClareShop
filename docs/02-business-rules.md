# Quy tắc nghiệp vụ

## Catalog

1. `Product` là mẫu đèn; `ProductVariant` là phiên bản bán được theo màu.
2. Giá, SKU và tồn kho chỉ thuộc `ProductVariant`. `Product` không có cột giá hoặc tồn kho.
3. Một sản phẩm không được có hai biến thể cùng `color_name`.
4. Sản phẩm/biến thể chỉ hiển thị khi `is_active = true`; trang storefront chỉ hiển thị sản phẩm đã có `published_at` không ở tương lai.
5. Giá hiển thị của sản phẩm là giá thấp nhất của biến thể đang hoạt động. Nếu có `compare_at_price`, chỉ hiển thị giảm giá khi nó lớn hơn `price`.
6. Ảnh có `sort_order`; ảnh có số nhỏ hơn là ảnh ưu tiên. Ảnh có thể dành cho cả sản phẩm hoặc riêng một biến thể.

## Giỏ hàng và checkout

1. Giỏ hàng luôn lưu `product_variant_id`, không lưu `product_id` đơn thuần.
2. Khách vãng lai có thể mua. `user_id` trong cart/order phải cho phép `null`.
3. Không giữ tồn kho chỉ vì một món nằm trong giỏ.
4. Khi tạo đơn, Action phải khóa các biến thể liên quan, kiểm tra tồn, trừ tồn và tạo order/order items trong **một database transaction**.
5. Khi đơn bị hủy, tồn kho được hoàn đúng một lần; luồng hủy phải chống việc hoàn trùng.
6. `order_items` lưu snapshot tên sản phẩm, tên màu, SKU và đơn giá. Không được đọc lại giá hiện tại từ catalog để sửa lịch sử đơn.

## Đơn hàng

Trạng thái đơn đề xuất cho V1:

```text
pending → confirmed → processing → shipped → completed
pending/confirmed/processing → cancelled
```

Trạng thái thanh toán độc lập:

```text
unpaid | pending | paid | refunded
```

Hình thức thanh toán/cách giao nhận cuối cùng cần được chốt trước khi xây checkout thật; không được giả vờ đã tích hợp cổng thanh toán.

## Tư vấn và lắp đặt

1. Một yêu cầu có loại `consultation` hoặc `installation`.
2. Người dùng chỉ gửi thời gian mong muốn; hệ thống không tự xác nhận lịch.
3. Nhân viên cập nhật trạng thái: `pending`, `confirmed`, `completed`, `cancelled`.
4. Yêu cầu lắp đặt có thể gắn với một đơn hàng, nhưng không bắt buộc.
5. Thông tin liên hệ và địa chỉ chỉ được dùng cho mục đích xử lý yêu cầu.

## Quyền quản trị

- Chỉ quản trị viên được tạo/sửa/ngừng hiển thị catalog, thay đổi trạng thái đơn và xác nhận lịch.
- Khi xây back office, thêm vai trò người dùng rõ ràng; không dùng điều kiện rải rác theo email trong controller.

