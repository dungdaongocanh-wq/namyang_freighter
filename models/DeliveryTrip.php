<?php
class DeliveryTrip {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(?string $date = null, ?int $driverId = null): array {
        $sql = "SELECT dt.*, u.full_name as driver_name
                FROM delivery_trips dt
                LEFT JOIN users u ON dt.driver_id = u.id
                WHERE 1=1";
        $params = [];
        if ($date) { $sql .= " AND dt.trip_date = :date"; $params['date'] = $date; }
        if ($driverId) { $sql .= " AND dt.driver_id = :did"; $params['did'] = $driverId; }
        $sql .= " ORDER BY dt.trip_date DESC, dt.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT dt.*, u.full_name as driver_name FROM delivery_trips dt LEFT JOIN users u ON dt.driver_id = u.id WHERE dt.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $tripCode = 'TRIP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $stmt = $this->db->prepare(
            "INSERT INTO delivery_trips (trip_code, driver_id, ops_id, trip_date, status, note)
             VALUES (:tc, :did, :oid, :td, :st, :note)"
        );
        $stmt->execute([
            'tc' => $data['trip_code'] ?? $tripCode,
            'did' => $data['driver_id'],
            'oid' => $data['ops_id'] ?? null,
            'td' => $data['trip_date'] ?? date('Y-m-d'),
            'st' => 'pending',
            'note' => $data['note'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        return $this->db->prepare("UPDATE delivery_trips SET status = :st WHERE id = :id")->execute(['st' => $status, 'id' => $id]);
    }

    public function getItems(int $tripId): array {
        $stmt = $this->db->prepare(
            "SELECT dti.*, s.hawb, s.packages, s.weight, c.company_name
             FROM delivery_trip_items dti
             JOIN shipments s ON dti.shipment_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE dti.trip_id = :tid"
        );
        $stmt->execute(['tid' => $tripId]);
        return $stmt->fetchAll();
    }

    public function addItem(int $tripId, int $shipmentId): bool {
        $stmt = $this->db->prepare("INSERT INTO delivery_trip_items (trip_id, shipment_id) VALUES (:tid, :sid)");
        return $stmt->execute(['tid' => $tripId, 'sid' => $shipmentId]);
    }
}
