<?php
class AuthController {

    public function login() {
        // Nếu đã login → về dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['role']);
        }

        // POST → xử lý đăng nhập
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db       = getDB();
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                header('Location: ' . BASE_URL . '/?page=login&error=empty');
                exit;
            }

            $stmt = $db->prepare("SELECT * FROM users WHERE username=? AND is_active=1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                header('Location: ' . BASE_URL . '/?page=login&error=invalid');
                exit;
            }

            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['full_name']   = $user['full_name'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['customer_id'] = $user['customer_id'];

            $this->redirectByRole($user['role']);
        }

        // GET → show form
        $viewTitle = 'Đăng nhập';
        $error     = $_GET['error'] ?? '';
        include __DIR__ . '/../views/auth/login.php';
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/?page=login');
        exit;
    }

    private function redirectByRole(string $role) {
        $map = [
            'admin'      => 'admin.dashboard',
            'cs'         => 'cs.dashboard',
            'ops'        => 'ops.dashboard',
            'driver'     => 'driver.dashboard',
            'accounting' => 'accounting.dashboard',
            'customer'   => 'customer.dashboard',
        ];
        header('Location: ' . BASE_URL . '/?page=' . ($map[$role] ?? 'login'));
        exit;
    }
}