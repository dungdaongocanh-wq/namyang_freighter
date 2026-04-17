<?php
class ShipmentCustoms {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): array {
        $stmt = $this->db->prepare("SELECT * FROM shipment_customs WHERE shipment_id = :sid");
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO shipment_customs (shipment_id, cd_number, cd_status, file_path, uploaded_by, uploaded_at)
             VALUES (:shipment_id, :cd_number, :cd_status, :file_path, :uploaded_by, :uploaded_at)"
        );
        $stmt->execute([
            'shipment_id' => $data['shipment_id'],
            'cd_number'   => $data['cd_number'] ?? null,
            'cd_status'   => $data['cd_status'] ?? null,
            'file_path'   => $data['file_path'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'uploaded_at' => $data['uploaded_at'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateFile(int $id, string $filePath, int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE shipment_customs SET file_path = :fp, uploaded_by = :uid, uploaded_at = NOW() WHERE id = :id"
        );
        return $stmt->execute(['fp' => $filePath, 'uid' => $userId, 'id' => $id]);
    }

    public function markDownloaded(int $id, int $userId): bool {
        $stmt = $this->db->prepare(
            "UPDATE shipment_customs SET downloaded_by = :uid, downloaded_at = NOW() WHERE id = :id"
        );
        return $stmt->execute(['uid' => $userId, 'id' => $id]);
    }

    public function getWithFile(int $shipmentId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM shipment_customs WHERE shipment_id = :sid AND file_path IS NOT NULL"
        );
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }
}
