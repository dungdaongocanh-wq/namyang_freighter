<?php
class ShipmentPhoto {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): array {
        $stmt = $this->db->prepare("SELECT * FROM shipment_photos WHERE shipment_id = :sid ORDER BY uploaded_at");
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO shipment_photos (shipment_id, photo_path, uploaded_by) VALUES (:sid, :path, :uid)"
        );
        $stmt->execute(['sid' => $data['shipment_id'], 'path' => $data['photo_path'], 'uid' => $data['uploaded_by'] ?? null]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM shipment_photos WHERE id = :id")->execute(['id' => $id]);
    }
}
