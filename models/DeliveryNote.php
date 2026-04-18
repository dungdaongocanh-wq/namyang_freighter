<?php
class DeliveryNote {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Tạo biên bản giao hàng
    public function create(array $data): int {
        $noteCode = 'DN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $stmt = $this->db->prepare(
            "INSERT INTO delivery_notes
                (note_code, shipment_id, trip_id, delivery_address, recipient_name,
                 recipient_phone, note, created_by)
             VALUES (:code, :sid, :tid, :addr, :rname, :rphone, :note, :by)"
        );
        $stmt->execute([
            'code'   => $data['note_code'] ?? $noteCode,
            'sid'    => $data['shipment_id'] ?? null,
            'tid'    => $data['trip_id'] ?? null,
            'addr'   => $data['delivery_address'] ?? null,
            'rname'  => $data['recipient_name'] ?? null,
            'rphone' => $data['recipient_phone'] ?? null,
            'note'   => $data['note'] ?? null,
            'by'     => $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Lấy biên bản theo lô hàng
    public function getByShipmentId(int $shipmentId): array {
        $stmt = $this->db->prepare(
            "SELECT dn.*, u.full_name as created_by_name
             FROM delivery_notes dn
             LEFT JOIN users u ON dn.created_by = u.id
             WHERE dn.shipment_id = :sid
             ORDER BY dn.created_at DESC"
        );
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    // Lấy biên bản theo chuyến ghép
    public function getByTripId(int $tripId): array {
        $stmt = $this->db->prepare(
            "SELECT dn.*, u.full_name as created_by_name
             FROM delivery_notes dn
             LEFT JOIN users u ON dn.created_by = u.id
             WHERE dn.trip_id = :tid
             ORDER BY dn.created_at DESC"
        );
        $stmt->execute(['tid' => $tripId]);
        return $stmt->fetchAll();
    }

    // Lấy biên bản theo ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT dn.*, u.full_name as created_by_name
             FROM delivery_notes dn
             LEFT JOIN users u ON dn.created_by = u.id
             WHERE dn.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Đánh dấu đã in
    public function markPrinted(int $id): bool {
        return $this->db->prepare(
            "UPDATE delivery_notes SET is_printed = 1, printed_at = NOW() WHERE id = :id"
        )->execute(['id' => $id]);
    }
}
