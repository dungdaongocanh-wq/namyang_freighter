<?php
require_once __DIR__ . '/config/app.php';

echo "<h2>🔍 Debug Login</h2>";

// 1. Test kết nối DB
echo "<h3>1. Kết nối Database</h3>";
try {
    $db = getDB();
    echo "✅ Kết nối OK<br>";
} catch (Exception $e) {
    echo "❌ Lỗi DB: " . $e->getMessage() . "<br>";
    die();
}

// 2. Kiểm tra database tồn tại
echo "<h3>2. Danh sách bảng</h3>";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if(empty($tables)) {
    echo "❌ Không có bảng nào! Chưa import schema.sql<br>";
} else {
    echo "✅ Bảng: " . implode(', ', $tables) . "<br>";
}

// 3. Kiểm tra users
echo "<h3>3. Danh sách Users</h3>";
try {
    $users = $db->query("SELECT id, username, role, is_active, LEFT(password,30) as pass_preview FROM users")->fetchAll();
    if(empty($users)) {
        echo "❌ Bảng users trống! Chưa có seed data<br>";
    } else {
        echo "<table border=1 cellpadding=5>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th>Pass (30 ký tự đầu)</th></tr>";
        foreach($users as $u) {
            echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['role']}</td><td>{$u['is_active']}</td><td>{$u['pass_preview']}</td></tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}

// 4. Test verify password
echo "<h3>4. Test password_verify</h3>";
$testHash = '$2y$10$TKh8H1.PfunCE8bpHjD3OuGFiJkGqhQO6iMfCNiJMJCqrHkx4hG4i';
echo "password_verify('123456', hash) = ";
echo password_verify('123456', $testHash) ? "✅ TRUE" : "❌ FALSE";
echo "<br>";

// 5. Tạo hash mới và update luôn
echo "<h3>5. Reset Password → <b>123456</b></h3>";
$newHash = password_hash('123456', PASSWORD_BCRYPT);
$count = $db->exec("UPDATE users SET password = '$newHash'");
echo "✅ Đã update $count tài khoản<br>";
echo "Hash mới: $newHash<br>";

// 6. Verify lại hash mới
echo "<br>Test hash mới: ";
echo password_verify('123456', $newHash) ? "✅ ĐÚNG" : "❌ SAI";

echo "<br><br><a href='/namyang/?page=login' style='font-size:18px'>→ Thử đăng nhập ngay</a>";
echo "<br><br><b style='color:red'>Xóa file debug.php sau khi dùng xong!</b>";