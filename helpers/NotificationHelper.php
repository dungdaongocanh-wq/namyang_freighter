<?php

/**
 * NotificationHelper - Quản lý thông báo hệ thống
 */
class NotificationHelper
{
    /**
     * Gửi thông báo tới một người dùng cụ thể
     *
     * @param int         $userId     ID người nhận
     * @param string      $message    Nội dung thông báo
     * @param int|null    $shipmentId ID lô hàng liên quan (nếu có)
     */
    public static function send(int $userId, string $message, ?int $shipmentId = null): void
    {
        try {
            $notif = new Notification();
            $notif->create([
                'user_id'     => $userId,
                'shipment_id' => $shipmentId,
                'message'     => $message,
            ]);
        } catch (Exception $e) {
            error_log('[NotificationHelper::send] ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách thông báo chưa đọc của người dùng
     *
     * @param int $userId
     * @return array
     */
    public static function getUnread(int $userId): array
    {
        try {
            $notif = new Notification();
            return $notif->getByUser($userId, true);
        } catch (Exception $e) {
            error_log('[NotificationHelper::getUnread] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Đánh dấu một thông báo đã đọc
     *
     * @param int $notifId
     */
    public static function markRead(int $notifId): void
    {
        try {
            $notif = new Notification();
            $notif->markRead($notifId);
        } catch (Exception $e) {
            error_log('[NotificationHelper::markRead] ' . $e->getMessage());
        }
    }

    /**
     * Đánh dấu tất cả thông báo của người dùng là đã đọc
     *
     * @param int $userId
     */
    public static function markAllRead(int $userId): void
    {
        try {
            $notif = new Notification();
            $notif->markAllRead($userId);
        } catch (Exception $e) {
            error_log('[NotificationHelper::markAllRead] ' . $e->getMessage());
        }
    }

    /**
     * Gửi thông báo tới tất cả người dùng có vai trò nhất định
     *
     * @param string   $role       Vai trò (admin, ops, kt, cs, customer...)
     * @param string   $message    Nội dung thông báo
     * @param int|null $shipmentId ID lô hàng liên quan (nếu có)
     */
    public static function notifyRole(string $role, string $message, ?int $shipmentId = null): void
    {
        try {
            $userModel = new User();
            $users     = $userModel->getAll($role);

            foreach ($users as $user) {
                // Chỉ gửi cho người dùng đang hoạt động
                if (!empty($user['is_active'])) {
                    self::send((int)$user['id'], $message, $shipmentId);
                }
            }
        } catch (Exception $e) {
            error_log('[NotificationHelper::notifyRole] ' . $e->getMessage());
        }
    }
}
