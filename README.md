# Hướng Dẫn Cài Đặt Hệ Thống Quản Lý Vận Chuyển

## Giới Thiệu
Hệ thống Quản Lý Vận Chuyển (Shipment Management System) được phát triển để giúp quản lý các quá trình liên quan tới vận chuyển hàng hóa một cách hiệu quả.

## Yêu Cầu Hệ Thống
- XAMPP (bao gồm Apache và MySQL)
- PHP 7.4 hoặc cao hơn

## Hướng Dẫn Cài Đặt
1. **Tải XAMPP**: Tải phiên bản mới nhất của XAMPP từ trang chính thức [Apache Friends](https://www.apachefriends.org/index.html) và cài đặt.
2. **Tạo Cơ Sở Dữ Liệu**:
   - Mở XAMPP Control Panel và khởi động Apache và MySQL.
   - Truy cập `http://localhost/phpmyadmin` để mở phpMyAdmin.
   - Tạo một cơ sở dữ liệu mới với tên là `shipment_management`.
3. **Tải Mã Nguồn**:
   - Clone dự án từ repository: `git clone https://github.com/dungdaongocanh-wq/namyang.git`
   - Di chuyển vào thư mục dự án: `cd namyang`
4. **Cấu Hình Kết Nối Cơ Sở Dữ Liệu**:
   - Mở file `config.php` và cấu hình thông tin kết nối tới cơ sở dữ liệu vừa tạo.
5. **Chạy Dự Án**:
   - Truy cập `http://localhost/namyang` trong trình duyệt để xem hệ thống vận hành.

## Hỗ Trợ
Nếu bạn gặp bất kỳ vấn đề nào trong quá trình cài đặt, xin vui lòng liên hệ qua email hoặc mở issue trong repository.