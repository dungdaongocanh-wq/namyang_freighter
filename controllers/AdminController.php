<?php
class AdminController {

    // ===== DASHBOARD =====
    public function dashboard() {
        requireRole('admin');
        $db = getDB();

        $stats = [
            'total_shipments' => (int)$db->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
            'total_users'     => (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'total_customers' => (int)$db->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn(),
            'total_debt'      => (float)$db->query("
                SELECT COALESCE(SUM(sc.amount),0)
                FROM shipment_costs sc
                JOIN shipments s ON sc.shipment_id = s.id
                WHERE s.status NOT IN ('invoiced','cancelled') AND (sc.source IS NULL OR sc.source != 'ops')
            ")->fetchColumn(),
        ];

        $statusBreakdown = $db->query("
            SELECT status, COUNT(*) as cnt
            FROM shipments
            GROUP BY status
        ")->fetchAll();

        $recentShipments = $db->query("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.is_active=1
            ORDER BY s.created_at DESC
            LIMIT 10
        ")->fetchAll();

        $importLogs = [];

        $viewTitle = 'Dashboard';
        $viewFile  = __DIR__ . '/../views/admin/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== USERS =====
    public function users() {
        requireRole('admin');
        $db = getDB();

        $users = $db->query("
            SELECT u.*, c.customer_code
            FROM users u
            LEFT JOIN customers c ON u.customer_id = c.id
            ORDER BY u.id
        ")->fetchAll();

        $customers = $db->query("
            SELECT id, customer_code, company_name
            FROM customers  ORDER BY customer_code
        ")->fetchAll();

        $viewTitle = 'Quản lý người dùng';
        $viewFile  = __DIR__ . '/../views/admin/users.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SAVE USER =====
    public function saveUser() {
        requireRole('admin');
        $db = getDB();

        $id         = (int)($_POST['id'] ?? 0);
        $username   = trim($_POST['username']   ?? '');
        $password   = trim($_POST['password']   ?? '');
        $fullName   = trim($_POST['full_name']  ?? '');
        $role       = trim($_POST['role']       ?? 'cs');
        $customerId = ($_POST['customer_id']    ?? '') ?: null;
        $isActive   = (int)($_POST['is_active'] ?? 1);

        if ($id) {
            if ($password) {
                $db->prepare("
                    UPDATE users SET username=?, full_name=?, role=?, customer_id=?, is_active=?,
                                    password_hash=? WHERE id=?
                ")->execute([$username, $fullName, $role, $customerId, $isActive,
                              password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $db->prepare("
                    UPDATE users SET username=?, full_name=?, role=?, customer_id=?, is_active=? WHERE id=?
                ")->execute([$username, $fullName, $role, $customerId, $isActive, $id]);
            }
        } else {
            $hash = password_hash($password ?: 'changeme', PASSWORD_DEFAULT);
            $db->prepare("
                INSERT INTO users (username, password_hash, full_name, role, customer_id, is_active)
                VALUES (?,?,?,?,?,?)
            ")->execute([$username, $hash, $fullName, $role, $customerId, $isActive]);
        }

        header('Location: ' . BASE_URL . '/?page=admin.users&msg=saved');
        exit;
    }

    // ===== CUSTOMERS =====
    public function customers() {
        requireRole('admin');
        $db = getDB();

        $customers = $db->query("
            SELECT c.*,
                   COUNT(DISTINCT s.id) as shipment_count,
                   COALESCE(SUM(
                       CASE WHEN s.status NOT IN ('invoiced','cancelled') THEN sc.amount ELSE 0 END
                   ), 0) as total_debt
            FROM customers c
            LEFT JOIN shipments s ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id AND (sc.source IS NULL OR sc.source != 'ops')
            GROUP BY c.id
            ORDER BY c.customer_code
        ")->fetchAll();

        $viewTitle = 'Quản lý khách hàng';
        $viewFile  = __DIR__ . '/../views/admin/customers.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SAVE CUSTOMER =====
    public function saveCustomer() {
        requireRole('admin');
        $db = getDB();

        $id           = (int)($_POST['id'] ?? 0);
        $customerCode = trim($_POST['customer_code'] ?? '');
        $companyName  = trim($_POST['company_name']  ?? '');
        $email        = trim($_POST['email']         ?? '') ?: null;
        $phone        = trim($_POST['phone']         ?? '') ?: null;
        $address      = trim($_POST['address']       ?? '') ?: null;
        $isActive     = (int)($_POST['is_active']    ?? 1);

        if ($id) {
            $db->prepare("
                UPDATE customers SET customer_code=?, company_name=?, email=?, phone=?, address=?, is_active=?
                WHERE id=?
            ")->execute([$customerCode, $companyName, $email, $phone, $address, $isActive, $id]);
        } else {
            $db->prepare("
                INSERT INTO customers (customer_code, company_name, email, phone, address, is_active)
                VALUES (?,?,?,?,?,?)
            ")->execute([$customerCode, $companyName, $email, $phone, $address, $isActive]);
        }

        header('Location: ' . BASE_URL . '/?page=admin.customers&msg=saved');
        exit;
    }

    // ===== QUOTATION LIST =====
    public function quotation() {
        (new QuotationController())->index();
    }

    // ===== QUOTATION DETAIL =====
    public function quotationDetail() {
        requireRole(['admin', 'accounting']);
        $db = getDB();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: ' . BASE_URL . '/?page=admin.quotation&err=invalid');
            exit;
        }

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

        $iStmt = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order, id");
        $iStmt->execute([$id]);
        $items = $iStmt->fetchAll();

        $costGroups = $db->query("
            SELECT id, name FROM cost_groups ORDER BY sort_order, id
        ")->fetchAll();

        $customers = $db->query("
            SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code
        ")->fetchAll();

        $viewTitle = 'Chi tiết báo giá';
        $viewFile  = __DIR__ . '/../views/admin/quotation_detail.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SAVE QUOTATION (tạo mới + cập nhật header + items) =====
    public function saveQuotation() {
        requireRole(['admin', 'accounting']);
        $db = getDB();

        $id         = (int)($_POST['id'] ?? 0);
        $customerId = ($_POST['customer_id'] ?? '') ?: null;
        $name       = trim($_POST['name']       ?? 'Báo giá');
        $validFrom  = $_POST['valid_from']      ?: null;
        $validTo    = $_POST['valid_to']        ?: null;
        $isActive   = (int)($_POST['is_active'] ?? 1);
        $note       = trim($_POST['note']       ?? '') ?: null;

        if ($id) {
            $db->prepare("
                UPDATE quotations
                SET customer_id=?, name=?, valid_from=?, valid_to=?, is_active=?, note=?
                WHERE id=?
            ")->execute([$customerId, $name, $validFrom, $validTo, $isActive, $note, $id]);
        } else {
            $db->prepare("
                INSERT INTO quotations (customer_id, name, valid_from, valid_to, is_active, note)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$customerId, $name, $validFrom, $validTo, $isActive, $note]);
            $id = (int)$db->lastInsertId();
        }

        // Lưu items (chỉ khi có dữ liệu items)
        if (isset($_POST['desc'])) {
            $db->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);

            $descs      = $_POST['desc']          ?? [];
            $cgIds      = $_POST['cost_group_id'] ?? [];
            $currencies = $_POST['currency']      ?? [];
            $unitPrices = $_POST['unit_price']    ?? [];
            $quantities = $_POST['quantity']      ?? [];
            $amounts    = $_POST['amount']        ?? [];
            $vatPcts    = $_POST['vat_pct']       ?? [];
            $itemNotes  = $_POST['item_note']     ?? [];

            $iStmt = $db->prepare("
                INSERT INTO quotation_items
                    (quotation_id, description, cost_group_id, currency, unit_price, quantity, amount, vat_pct, note, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($descs as $i => $desc) {
                if (trim($desc) === '') continue;
                $iStmt->execute([
                    $id,
                    trim($desc),
                    ($cgIds[$i] ?? '') ?: null,
                    $currencies[$i] ?? 'VND',
                    (float)($unitPrices[$i] ?? 0),
                    (float)($quantities[$i] ?? 1),
                    (float)($amounts[$i]    ?? 0),
                    (int)($vatPcts[$i]      ?? 8),
                    trim($itemNotes[$i]     ?? '') ?: null,
                    $i,
                ]);
            }
        }

        header('Location: ' . BASE_URL . '/?page=admin.quotation_detail&id=' . $id . '&msg=saved');
        exit;
    }

    // ===== COST GROUPS =====
    public function costGroups() {
        requireRole('admin');
        $db = getDB();

        $costGroups = $db->query("SELECT * FROM cost_groups ORDER BY sort_order, id")->fetchAll();

        $viewTitle = 'Nhóm chi phí';
        $viewFile  = __DIR__ . '/../views/admin/cost_groups.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SAVE COST GROUP =====
    public function saveCostGroup() {
        requireRole('admin');
        $db = getDB();

        $id        = (int)($_POST['id']         ?? 0);
        $name      = trim($_POST['name']        ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive  = (int)($_POST['is_active']  ?? 1);

        if (!$name) {
            header('Location: ' . BASE_URL . '/?page=admin.cost_groups&err=empty_name');
            exit;
        }

        if ($id) {
            $db->prepare("
                UPDATE cost_groups SET name=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?
            ")->execute([$name, $sortOrder, $isActive, $id]);
        } else {
            $db->prepare("
                INSERT INTO cost_groups (name, sort_order, is_active) VALUES (?,?,?)
            ")->execute([$name, $sortOrder, $isActive]);
        }

        header('Location: ' . BASE_URL . '/?page=admin.cost_groups&msg=saved');
        exit;
    }

    // ===== DELETE COST GROUP =====
    public function deleteCostGroup() {
        requireRole('admin');
        $db = getDB();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: ' . BASE_URL . '/?page=admin.cost_groups&err=invalid');
            exit;
        }

        // Kiểm tra xem có đang dùng không
        $chkStmt = $db->prepare("SELECT COUNT(*) FROM quotation_items WHERE cost_group_id = ?");
        $chkStmt->execute([$id]);
        $inUse = (int)$chkStmt->fetchColumn();

        if ($inUse) {
            header('Location: ' . BASE_URL . '/?page=admin.cost_groups&err=in_use');
            exit;
        }

        $db->prepare("DELETE FROM cost_groups WHERE id = ?")->execute([$id]);

        header('Location: ' . BASE_URL . '/?page=admin.cost_groups&msg=deleted');
        exit;
    }
}
