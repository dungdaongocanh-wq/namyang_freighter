<?php
class Customer {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll(bool $activeOnly = false): array {
        $sql = "SELECT * FROM customers";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY customer_code";
        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE customer_code = :code");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO customers (customer_code, company_name, email, phone, address, is_active)
             VALUES (:customer_code, :company_name, :email, :phone, :address, :is_active)"
        );
        $stmt->execute([
            'customer_code' => $data['customer_code'],
            'company_name'  => $data['company_name'] ?? '',
            'email'         => $data['email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'is_active'     => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = :$key";
            $params[$key] = $value;
        }
        if (empty($fields)) return false;
        return $this->db->prepare("UPDATE customers SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    }
}
