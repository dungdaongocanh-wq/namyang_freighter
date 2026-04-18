<?php
class QuotationController {

    // ===== DANH SÁCH BÁO GIÁ =====
    public function index() {
        requireRole(['admin', 'accounting']);

        $db = getDB();
        $search = trim($_GET['search'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $where[]  = "(q.service_name LIKE ? OR c.company_name LIKE ? OR c.customer_code LIKE ?)";
            $kw       = '%' . $search . '%';
            $params   = array_merge($params, [$kw, $kw, $kw]);
        }

        if (!empty($_GET['customer_id'])) {
            $where[]  = "q.customer_id = ?";
            $params[] = (int)$_GET['customer_id'];
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT q.*, c.company_name, c.customer_code
            FROM quotations q
            LEFT JOIN customers c ON q.customer_id = c.id
            WHERE $whereStr AND q.is_active = 1
            ORDER BY q.customer_id, q.service_name
        ");
        $stmt->execute($params);
        $quotations = $stmt->fetchAll();

        $customers = $db->query("
            SELECT id, customer_code, company_name
            FROM customers WHERE is_active=1 ORDER BY customer_code
        ")->fetchAll();

        $viewTitle = 'Quản lý báo giá';
        $viewFile  = __DIR__ . '/../views/admin/quotation.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== FORM TẠO / SỬA =====
    public function create() {
        requireRole(['admin', 'accounting']);

        $db        = $this->getDB();
        $customers = $db->query("
            SELECT id, customer_code, company_name
            FROM customers WHERE is_active=1 ORDER BY customer_code
        ")->fetchAll();

        $viewTitle = 'Tạo báo giá mới';
        $viewFile  = __DIR__ . '/../views/admin/quotation_form.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== LƯU BÁO GIÁ MỚI =====
    public function store() {
        requireRole(['admin', 'accounting']);

        $db = getDB();
        $db->prepare("
            INSERT INTO quotations (customer_id, service_name, unit, unit_price, valid_from, valid_to, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ")->execute([
            ($_POST['customer_id'] ?? '') ?: null,
            trim($_POST['service_name'] ?? ''),
            trim($_POST['unit']         ?? ''),
            (float)($_POST['unit_price'] ?? 0),
            $_POST['valid_from'] ?: null,
            $_POST['valid_to']   ?: null,
        ]);

        header('Location: ' . BASE_URL . '/?page=admin.quotation&msg=saved');
        exit;
    }

    // ===== CHI TIẾT =====
    public function show(int $id) {
        requireRole(['admin', 'accounting']);

        $db   = getDB();
        $stmt = $db->prepare("
            SELECT q.*, c.company_name, c.customer_code
            FROM quotations q
            LEFT JOIN customers c ON q.customer_id = c.id
            WHERE q.id = ?
        ");
        $stmt->execute([$id]);
        $quotation = $stmt->fetch();

        if (!$quotation) {
            header('Location: ' . BASE_URL . '/?page=admin.quotation&err=not_found');
            exit;
        }

        $viewTitle = 'Chi tiết báo giá';
        $viewFile  = __DIR__ . '/../views/admin/quotation_detail.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== FORM SỬA =====
    public function edit(int $id) {
        requireRole(['admin', 'accounting']);

        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM quotations WHERE id = ?");
        $stmt->execute([$id]);
        $quotation = $stmt->fetch();

        if (!$quotation) {
            header('Location: ' . BASE_URL . '/?page=admin.quotation&err=not_found');
            exit;
        }

        $customers = $db->query("
            SELECT id, customer_code, company_name
            FROM customers WHERE is_active=1 ORDER BY customer_code
        ")->fetchAll();

        $viewTitle = 'Sửa báo giá';
        $viewFile  = __DIR__ . '/../views/admin/quotation_form.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== CẬP NHẬT =====
    public function update(int $id) {
        requireRole(['admin', 'accounting']);

        $db = getDB();
        $db->prepare("
            UPDATE quotations
            SET customer_id = ?,
                service_name = ?,
                unit = ?,
                unit_price = ?,
                valid_from = ?,
                valid_to = ?,
                updated_at = NOW()
            WHERE id = ?
        ")->execute([
            ($_POST['customer_id'] ?? '') ?: null,
            trim($_POST['service_name'] ?? ''),
            trim($_POST['unit']         ?? ''),
            (float)($_POST['unit_price'] ?? 0),
            $_POST['valid_from'] ?: null,
            $_POST['valid_to']   ?: null,
            $id,
        ]);

        header('Location: ' . BASE_URL . '/?page=admin.quotation&msg=saved');
        exit;
    }

    // ===== XOÁ (soft delete) =====
    public function delete(int $id) {
        requireRole(['admin', 'accounting']);

        $db = getDB();
        $db->prepare("UPDATE quotations SET is_active = 0 WHERE id = ?")->execute([$id]);

        header('Location: ' . BASE_URL . '/?page=admin.quotation&msg=deleted');
        exit;
    }

    // ===== DISPATCH (dùng khi gọi qua index.php) =====
    public function dispatch() {
        $id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $method = $_SERVER['REQUEST_METHOD'];

        // Xoá
        if (isset($_POST['_delete'])) {
            $this->delete($id);
            return;
        }

        // Lưu mới hoặc cập nhật
        if ($method === 'POST') {
            if ($id) {
                $this->update($id);
            } else {
                $this->store();
            }
            return;
        }

        // Hiện form
        if ($id && isset($_GET['edit'])) {
            $this->edit($id);
        } elseif ($id) {
            $this->show($id);
        } else {
            $this->index();
        }
    }

    private function getDB(): PDO {
        return getDB();
    }
}
