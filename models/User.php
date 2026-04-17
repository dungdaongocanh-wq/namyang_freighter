<?php
class User {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(?string $role = null): array {
        if ($role) {
            $stmt = $this->db->prepare("SELECT u.*, c.company_name FROM users u LEFT JOIN customers c ON u.customer_id = c.id WHERE u.role = :role ORDER BY u.full_name");
            $stmt->execute(['role' => $role]);
        } else {
            $stmt = $this->db->query("SELECT u.*, c.company_name FROM users u LEFT JOIN customers c ON u.customer_id = c.id ORDER BY u.role, u.full_name");
        }
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, full_name, role, customer_id, is_active)
             VALUES (:username, :password, :full_name, :role, :customer_id, :is_active)"
        );
        $stmt->execute([
            'username'    => $data['username'],
            'password'    => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name'   => $data['full_name'] ?? '',
            'role'        => $data['role'],
            'customer_id' => $data['customer_id'] ?? null,
            'is_active'   => $data['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = :$key";
            $params[$key] = $value;
        }
        if (empty($fields)) return false;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }

    public function toggleActive(int $id): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
