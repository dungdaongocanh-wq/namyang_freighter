<?php
class ShipmentLog {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): array {
        $stmt = $this->db->prepare(
            "SELECT sl.*, u.full_name FROM shipment_logs sl LEFT JOIN users u ON sl.user_id = u.id WHERE sl.shipment_id = :sid ORDER BY sl.created_at ASC"
        );
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO shipment_logs (shipment_id, from_status, to_status, triggered_by, user_id, note)
             VALUES (:sid, :from, :to, :trig, :uid, :note)"
        );
        $stmt->execute([
            'sid'  => $data['shipment_id'],
            'from' => $data['from_status'] ?? null,
            'to'   => $data['to_status'] ?? null,
            'trig' => $data['triggered_by'] ?? '',
            'uid'  => $data['user_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
