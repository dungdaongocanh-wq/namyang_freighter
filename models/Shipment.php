<?php
class Shipment {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Lấy danh sách lô hàng với filter
    public function getAll(array $filters = []): array {
        $sql = "SELECT s.*, c.company_name, c.customer_code as c_code
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $sql .= " AND s.customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }
        if (!empty($filters['date'])) {
            $sql .= " AND s.active_date = :date";
            $params['date'] = $filters['date'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (s.hawb LIKE :search OR s.mawb LIKE :search OR c.company_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['etd_from'])) {
            $sql .= " AND s.etd >= :etd_from";
            $params['etd_from'] = $filters['etd_from'];
        }
        if (!empty($filters['etd_to'])) {
            $sql .= " AND s.etd <= :etd_to";
            $params['etd_to'] = $filters['etd_to'];
        }

        $sql .= " ORDER BY s.active_date DESC, s.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Lấy thông tin 1 lô hàng
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.company_name, c.email as customer_email
             FROM shipments s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Tạo lô hàng mới
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO shipments (customer_id, customer_code, hawb, mawb, flight_no, pol, etd, eta,
             packages, weight, remark, status, import_date, active_date, imported_by)
             VALUES (:customer_id, :customer_code, :hawb, :mawb, :flight_no, :pol, :etd, :eta,
             :packages, :weight, :remark, :status, :import_date, :active_date, :imported_by)"
        );
        $stmt->execute([
            'customer_id'   => $data['customer_id'],
            'customer_code' => $data['customer_code'],
            'hawb'          => $data['hawb'],
            'mawb'          => $data['mawb'] ?? null,
            'flight_no'     => $data['flight_no'] ?? null,
            'pol'           => $data['pol'] ?? null,
            'etd'           => $data['etd'] ?? null,
            'eta'           => $data['eta'] ?? null,
            'packages'      => $data['packages'] ?? null,
            'weight'        => $data['weight'] ?? null,
            'remark'        => $data['remark'] ?? null,
            'status'        => $data['status'] ?? 'pending_customs',
            'import_date'   => $data['import_date'] ?? date('Y-m-d'),
            'active_date'   => $data['active_date'] ?? date('Y-m-d'),
            'imported_by'   => $data['imported_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Cập nhật lô hàng
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = :$key";
            $params[$key] = $value;
        }
        if (empty($fields)) return false;
        $sql = "UPDATE shipments SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Lấy lô hàng theo khách hàng
    public function getByCustomer(int $customerId, ?string $status = null): array {
        $sql = "SELECT * FROM shipments WHERE customer_id = :customer_id";
        $params = ['customer_id' => $customerId];
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY active_date DESC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Lấy lô hàng theo trạng thái
    public function getByStatus(string $status, ?string $date = null): array {
        $sql = "SELECT s.*, c.company_name FROM shipments s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.status = :status";
        $params = ['status' => $status];
        if ($date) {
            $sql .= " AND s.active_date = :date";
            $params['date'] = $date;
        }
        $sql .= " ORDER BY s.active_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Kiểm tra HAWB đã tồn tại chưa
    public function checkHawbExists(string $hawb): ?array {
        $stmt = $this->db->prepare("SELECT * FROM shipments WHERE hawb = :hawb LIMIT 1");
        $stmt->execute(['hawb' => $hawb]);
        return $stmt->fetch() ?: null;
    }

    // Lấy lô cần xử lý hôm nay (cho OPS)
    public function getTodayPending(): array {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT s.*, c.company_name FROM shipments s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.active_date = :today
             AND s.status IN ('cleared','waiting_pickup','in_transit')
             ORDER BY s.status, s.created_at"
        );
        $stmt->execute(['today' => $today]);
        return $stmt->fetchAll();
    }

    // Đếm lô hàng theo trạng thái cho 1 khách hàng
    public function countByStatus(int $customerId): array {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) as cnt FROM shipments WHERE customer_id = :cid GROUP BY status"
        );
        $stmt->execute(['cid' => $customerId]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }
}
