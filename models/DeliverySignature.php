<?php
class DeliverySignature {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM delivery_signatures WHERE shipment_id = :sid LIMIT 1");
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO delivery_signatures (shipment_id, trip_id, signed_by_name, signature_path, driver_id)
             VALUES (:sid, :tid, :sname, :spath, :did)"
        );
        $stmt->execute([
            'sid'   => $data['shipment_id'],
            'tid'   => $data['trip_id'] ?? null,
            'sname' => $data['signed_by_name'],
            'spath' => $data['signature_path'],
            'did'   => $data['driver_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
