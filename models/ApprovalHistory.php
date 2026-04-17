<?php
class ApprovalHistory {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): array {
        $stmt = $this->db->prepare(
            "SELECT ah.*, u.full_name FROM approval_history ah LEFT JOIN users u ON ah.user_id = u.id WHERE ah.shipment_id = :sid ORDER BY ah.created_at DESC"
        );
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO approval_history (shipment_id, action, customer_id, user_id, reason)
             VALUES (:sid, :action, :cid, :uid, :reason)"
        );
        $stmt->execute([
            'sid'    => $data['shipment_id'],
            'action' => $data['action'],
            'cid'    => $data['customer_id'] ?? null,
            'uid'    => $data['user_id'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
