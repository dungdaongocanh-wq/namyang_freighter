# Hướng dẫn cấu hình Zalo OA Notification

## Bước 1: Tạo / chọn Zalo Official Account
- Vào https://oa.zalo.me → Đăng nhập → Chọn OA của công ty

## Bước 2: Lấy OA Access Token
1. Vào **Cài đặt** (⚙️) → **Thông tin ứng dụng**
2. Copy **Access Token** (dài ~200 ký tự)
3. Mở file `config/zalo.php`
4. Thay `YOUR_ZALO_OA_ACCESS_TOKEN` bằng token vừa copy
5. Đổi `ZALO_NOTIFY_ENABLED` từ `false` → `true`

## Bước 3: Lấy Zalo User ID của nhân viên OPS
Nhân viên OPS cần:
1. Mở Zalo → Tìm kiếm OA của công ty → Nhấn **Quan tâm**
2. Admin lấy Zalo User ID qua Webhook (xem bên dưới) hoặc hỏi IT

### Cách lấy Zalo User ID qua Webhook:
1. Vào https://oa.zalo.me → **Cài đặt** → **Webhook**
2. Điền URL webhook của server: `https://yourdomain.com/zalo-webhook.php`
3. Khi user follow OA, webhook nhận được `user_id_by_app`
4. Lưu `user_id_by_app` vào cột `zalo_id` trong bảng `users`

## Bước 4: Cập nhật zalo_id cho nhân viên OPS
```sql
UPDATE users SET zalo_id = 'ZALO_USER_ID_CUA_NV' WHERE username = 'ten_nhan_vien_ops';
```

## Lưu ý
- Access Token có hiệu lực **90 ngày**, cần refresh định kỳ
- User PHẢI follow OA thì mới nhận được tin nhắn
- Giới hạn: 2000 ký tự/tin nhắn
