<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /namyang/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập - Nam Yang Freight</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .login-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  }
  .logo-icon { font-size: 3rem; }
  .btn-login {
    background: linear-gradient(135deg, #1e3a5f, #2d6a9f);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
  }
  .btn-login:hover { opacity: 0.9; }
  .form-control {
    border-radius: 10px;
    padding: 12px 16px;
    border: 1.5px solid #dee2e6;
  }
  .form-control:focus {
    border-color: #2d6a9f;
    box-shadow: 0 0 0 0.2rem rgba(45,106,159,0.2);
  }
  .version-badge {
    font-size: 0.7rem;
    background: #e8f4fd;
    color: #2d6a9f;
    padding: 2px 8px;
    border-radius: 20px;
  }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card login-card p-4 p-md-5">

        <!-- Logo -->
        <div class="text-center mb-4">
          <div class="logo-icon">🚢</div>
          <h4 class="fw-bold text-primary mt-2 mb-0">Nam Yang Freight</h4>
          <p class="text-muted small mt-1 mb-0">Hệ thống quản lý lô hàng</p>
          <span class="version-badge mt-2 d-inline-block">v1.0</span>
        </div>

        <!-- Error alert -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center py-2 mb-3" role="alert">
          <span class="me-2">⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Success message -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
        <div class="alert alert-success py-2 mb-3">✅ Đã đăng xuất thành công!</div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="<?= BASE_URL ?>/?page=login">
          <div class="mb-3">
            <label class="form-label fw-semibold text-secondary small">TÊN ĐĂNG NHẬP</label>
            <input type="text" name="username" class="form-control"
                   placeholder="Nhập username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required autofocus autocomplete="username">
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary small">MẬT KHẨU</label>
            <div class="input-group">
              <input type="password" name="password" id="passwordInput"
                     class="form-control" placeholder="Nhập mật khẩu"
                     required autocomplete="current-password">
              <button class="btn btn-outline-secondary" type="button"
                      onclick="togglePass()" title="Hiện/ẩn mật khẩu">👁</button>
            </div>
          </div>
          <button type="submit" class="btn btn-login btn-primary btn-lg w-100 text-white">
            🔐 Đăng nhập
          </button>
        </form>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
          © 2026 Nam Yang Freight · All rights reserved
        </p>
      </div>
    </div>
  </div>
</div>

<script>
function togglePass() {
  const input = document.getElementById('passwordInput');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>