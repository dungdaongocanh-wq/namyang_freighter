<?php
class Debt {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getByCustomer(int $customerId, ?string $month = null): array {
        $sql = "SELECT d.*, c.company_name FROM debts d LEFT JOIN customers c ON d.customer_id = c.id WHERE d.customer_id = :cid";
        $params = ['cid' => $customerId];
        if ($month) { $sql .= " AND d.month = :month"; $params['month'] = $month; }
        $sql .= " ORDER BY d.month DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAll(?string $status = null): array {
        $sql = "SELECT d.*, c.company_name FROM debts d LEFT JOIN customers c ON d.customer_id = c.id";
        if ($status) $sql .= " WHERE d.status = :status";
        $sql .= " ORDER BY d.month DESC, c.customer_code";
        $stmt = $this->db->prepare($sql);
        if ($status) $stmt->execute(['status' => $status]);
        else $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO debts (customer_id, month, total_amount, status) VALUES (:cid, :month, :total, :status)"
        );
        $stmt->execute([
            'cid'    => $data['customer_id'],
            'month'  => $data['month'],
            'total'  => $data['total_amount'],
            'status' => $data['status'] ?? 'open',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $invoicePath = null): bool {
        $stmt = $this->db->prepare("UPDATE debts SET status = :st, invoice_path = :ip WHERE id = :id");
        return $stmt->execute(['st' => $status, 'ip' => $invoicePath, 'id' => $id]);
    }

    public function getItems(int $debtId): array {
        $stmt = $this->db->prepare(
            "SELECT di.*, s.hawb, s.eta, s.packages FROM debt_items di JOIN shipments s ON di.shipment_id = s.id WHERE di.debt_id = :did"
        );
        $stmt->execute(['did' => $debtId]);
        return $stmt->fetchAll();
    }

    public function addItem(int $debtId, int $shipmentId, float $amount): bool {
        $stmt = $this->db->prepare("INSERT INTO debt_items (debt_id, shipment_id, amount) VALUES (:did, :sid, :amt)");
        return $stmt->execute(['did' => $debtId, 'sid' => $shipmentId, 'amt' => $amount]);
    }
}
