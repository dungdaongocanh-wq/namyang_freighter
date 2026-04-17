<?php
class NotificationController {

    public function markRead() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            NotificationHelper::markRead($id);
        }
        // Nếu AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/');
        header('Location: ' . $redirect);
        exit;
    }

    public function markAllRead() {
        NotificationHelper::markAllRead((int)$_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
