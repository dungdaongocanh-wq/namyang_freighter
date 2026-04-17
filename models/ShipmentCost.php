<?php
class ShipmentCost {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByShipment(int $shipmentId): array {
        $stmt = $this->db->prepare("SELECT * FROM shipment_costs WHERE shipment_id = :sid ORDER BY id");
        $stmt->execute(['sid' => $shipmentId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO shipment_costs (shipment_id, service_name, quantity, unit, unit_price, amount, source, created_by)
             VALUES (:shipment_id, :service_name, :quantity, :unit, :unit_price, :amount, :source, :created_by)"
        );
        $stmt->execute([
            'shipment_id'  => $data['shipment_id'],
            'service_name' => $data['service_name'],
            'quantity'     => $data['quantity'],
            'unit'         => $data['unit'] ?? '',
            'unit_price'   => $data['unit_price'],
            'amount'       => $data['amount'],
            'source'       => $data['source'] ?? 'manual',
            'created_by'   => $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE shipment_costs SET service_name=:sn, quantity=:qty, unit=:unit, unit_price=:up, amount=:amt WHERE id=:id"
        );
        return $stmt->execute([
            'sn' => $data['service_name'], 'qty' => $data['quantity'],
            'unit' => $data['unit'], 'up' => $data['unit_price'],
            'amt' => $data['amount'], 'id' => $id,
        ]);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM shipment_costs WHERE id = :id")->execute(['id' => $id]);
    }

    public function deleteByShipment(int $shipmentId): bool {
        return $this->db->prepare("DELETE FROM shipment_costs WHERE shipment_id = :sid AND source = 'auto'")->execute(['sid' => $shipmentId]);
    }

    public function getTotalByShipment(int $shipmentId): float {
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM shipment_costs WHERE shipment_id = :sid");
        $stmt->execute(['sid' => $shipmentId]);
        return (float)($stmt->fetchColumn() ?? 0);
    }
}
