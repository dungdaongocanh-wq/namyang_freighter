<?php
// Cấu hình ứng dụng
define('APP_NAME', 'Nam Yang Freight');
define('BASE_URL', '/namyang');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Timezone Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload classes
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/../controllers/',
        __DIR__ . '/../models/',
        __DIR__ . '/../helpers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load config files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/roles.php';
