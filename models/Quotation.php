<?php
class Quotation {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(?int $customerId = null): array {
        if ($customerId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM quotations WHERE (customer_id = :cid OR customer_id IS NULL) AND is_active = 1 ORDER BY customer_id DESC, service_name");
            $stmt->execute(['cid' => $customerId]);
        } else {
            $stmt = $this->db->query("SELECT q.*, c.company_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id ORDER BY q.customer_id, q.service_name");
        }
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO quotations (customer_id, service_name, unit, unit_price, valid_from, valid_to, is_active)
             VALUES (:cid, :sn, :unit, :up, :vf, :vt, :ia)"
        );
        $stmt->execute([
            'cid'  => $data['customer_id'] ?? null,
            'sn'   => $data['service_name'],
            'unit' => $data['unit'] ?? '',
            'up'   => $data['unit_price'],
            'vf'   => $data['valid_from'] ?? null,
            'vt'   => $data['valid_to'] ?? null,
            'ia'   => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $k => $v) { $fields[] = "`$k` = :$k"; $params[$k] = $v; }
        return $this->db->prepare("UPDATE quotations SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("UPDATE quotations SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
    }
}
