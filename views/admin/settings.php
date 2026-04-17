<?php
$appVersion = '1.0.0';
$appName    = 'Namyang Freighter';
$msg        = '';
$err        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $db          = getDB();
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!$currentPass || !$newPass || !$confirmPass) {
        $err = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($newPass !== $confirmPass) {
        $err = 'Mật khẩu mới và xác nhận không khớp.';
    } elseif (strlen($newPass) < 6) {
        $err = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } else {
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPass, $user['password'])) {
            $err = 'Mật khẩu hiện tại không đúng.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            $msg = 'Đổi mật khẩu thành công!';
        }
    }
}
?>

<div class="container-fluid py-3">
  <h4 class="mb-4">⚙️ Cài đặt hệ thống</h4>

  <div class="row g-4">

    <!-- Đổi mật khẩu -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header fw-bold">🔑 Đổi mật khẩu</div>
        <div class="card-body">
          <?php if ($msg): ?>
          <div class="alert alert-success"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <?php if ($err): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
              <label class="form-label">Mật khẩu hiện tại</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu mới</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
              <label class="form-label">Xác nhận mật khẩu mới</label>
              <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Thông tin hệ thống -->
    <div class="col-md-6">
      <div class="card">
        <div class="card-header fw-bold">ℹ️ Thông tin hệ thống</div>
        <div class="card-body">
          <table class="table table-borderless mb-0">
            <tr>
              <th width="40%">Tên ứng dụng</th>
              <td><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
              <th>Phiên bản</th>
              <td><?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
              <th>PHP</th>
              <td><?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
              <th>Server time</th>
              <td><?= htmlspecialchars(date('d/m/Y H:i:s'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
              <th>Base URL</th>
              <td><?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
