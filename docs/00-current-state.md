# Trạng thái hiện tại

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
- Thư mục module đã được định hướng dưới `app/Modules/`.

## Điều cần kiểm tra trước khi sửa tiếp

Một phần Model Catalog có thể đã được Artisan tạo. Trước khi sửa, kiểm tra:

```powershell
Get-ChildItem .\app\Modules\Catalog\Models
php artisan migrate:status
```

Không được đoán nội dung file hoặc trạng thái migration chỉ từ tên file.

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
