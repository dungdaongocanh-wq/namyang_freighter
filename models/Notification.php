<?php
class Notification {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByUser(int $userId, bool $unreadOnly = false): array {
        $sql = "SELECT n.*, s.hawb FROM notifications n LEFT JOIN shipments s ON n.shipment_id = s.id WHERE n.user_id = :uid";
        if ($unreadOnly) $sql .= " AND n.is_read = 0";
        $sql .= " ORDER BY n.created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute(['uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, shipment_id, message) VALUES (:uid, :sid, :msg)"
        );
        $stmt->execute(['uid' => $data['user_id'], 'sid' => $data['shipment_id'] ?? null, 'msg' => $data['message']]);
        return (int)$this->db->lastInsertId();
    }

    public function markRead(int $id): bool {
        return $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id")->execute(['id' => $id]);
    }

    public function markAllRead(int $userId): bool {
        return $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid")->execute(['uid' => $userId]);
    }
}
