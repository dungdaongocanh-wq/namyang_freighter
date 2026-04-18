<?php
class DeliveryTripItem {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Lấy danh sách lô theo chuyến
    public function getByTripId(int $tripId): array {
        $stmt = $this->db->prepare(
            "SELECT dti.*
             FROM delivery_trip_items dti
             WHERE dti.trip_id = :tid
             ORDER BY dti.id"
        );
        $stmt->execute(['tid' => $tripId]);
        return $stmt->fetchAll();
    }

    // Thêm lô vào chuyến
    public function addItem(int $tripId, int $shipmentId): bool {
        // Kiểm tra đã tồn tại chưa
        $check = $this->db->prepare(
            "SELECT id FROM delivery_trip_items WHERE trip_id = :tid AND shipment_id = :sid"
        );
        $check->execute(['tid' => $tripId, 'sid' => $shipmentId]);
        if ($check->fetch()) {
            return false;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO delivery_trip_items (trip_id, shipment_id) VALUES (:tid, :sid)"
        );
        return $stmt->execute(['tid' => $tripId, 'sid' => $shipmentId]);
    }

    // Xoá lô khỏi chuyến
    public function removeItem(int $tripId, int $shipmentId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM delivery_trip_items WHERE trip_id = :tid AND shipment_id = :sid"
        );
        return $stmt->execute(['tid' => $tripId, 'sid' => $shipmentId]);
    }

    // Lấy chi tiết lô hàng kèm join Shipment
    public function getShipmentsByTrip(int $tripId): array {
        $stmt = $this->db->prepare(
            "SELECT dti.id as item_id, dti.trip_id, dti.shipment_id,
                    s.hawb, s.mawb, s.flight_no, s.packages, s.weight,
                    s.status, s.active_date, s.remark,
                    c.company_name, c.customer_code
             FROM delivery_trip_items dti
             JOIN shipments s ON dti.shipment_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE dti.trip_id = :tid
             ORDER BY dti.id"
        );
        $stmt->execute(['tid' => $tripId]);
        return $stmt->fetchAll();
    }

    // Đếm số lô trong chuyến
    public function countByTrip(int $tripId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM delivery_trip_items WHERE trip_id = :tid"
        );
        $stmt->execute(['tid' => $tripId]);
        return (int)$stmt->fetchColumn();
    }

    // Xoá tất cả lô trong chuyến
    public function removeAllByTrip(int $tripId): bool {
        return $this->db->prepare(
            "DELETE FROM delivery_trip_items WHERE trip_id = :tid"
        )->execute(['tid' => $tripId]);
    }
}
