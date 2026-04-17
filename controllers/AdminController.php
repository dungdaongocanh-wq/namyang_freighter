<?php
class AdminController {

    // ===== DASHBOARD =====
    public function dashboard() {
        $db = getDB();

        $stats = [
            'total_shipments' => $db->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
            'total_users'     => $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'total_customers' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn(),
            'total_debt'      => $db->query("
                SELECT COALESCE(SUM(sc.amount),0)
                FROM shipment_costs sc
                JOIN shipments s ON sc.shipment_id=s.id
                WHERE s.status IN ('debt','pending_approval')
            ")->fetchColumn(),
        ];

        // Status breakdown
        $statusStmt = $db->query("SELECT status, COUNT(*) as cnt FROM shipments GROUP BY status");
        $statusBreakdown = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Import logs gần nhất
        $importLogs = $db->query("
            SELECT il.*, u.full_name
            FROM import_logs il
            LEFT JOIN users u ON il.imported_by = u.id
            ORDER BY il.created_at DESC LIMIT 10
        ")->fetchAll();

        $viewTitle = 'Admin Dashboard';
        $viewFile  = __DIR__ . '/../views/admin/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== USERS =====
    public function users() {
        $db    = getDB();
        $users = $db->query("
            SELECT u.*, c.customer_code
            FROM users u
            LEFT JOIN customers c ON u.customer_id = c.id
            ORDER BY u.role, u.full_name
        ")->fetchAll();

        $customers = $db->query("SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code")->fetchAll();

        $viewTitle = 'Quản lý người dùng';
        $viewFile  = __DIR__ . '/../views/admin/users.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function saveUser() {
        $db  = getDB();
        $id  = (int)($_POST['id'] ?? 0);
        $data = [
            'username'    => trim($_POST['username'] ?? ''),
            'full_name'   => trim($_POST['full_name'] ?? ''),
            'role'        => $_POST['role'] ?? 'cs',
            'customer_id' => ($_POST['customer_id'] ?? '') ?: null,
            'is_active'   => (int)($_POST['is_active'] ?? 1),
        ];

        if ($id) {
            // Update
            $sets = "username=?, full_name=?, role=?, customer_id=?, is_active=?";
            $params = array_values($data);
            if (!empty($_POST['password'])) {
                $sets .= ", password=?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $params[] = $id;
            $db->prepare("UPDATE users SET $sets WHERE id=?")->execute($params);
        } else {
            // Insert
            if (empty($_POST['password'])) {
                header('Location: ' . BASE_URL . '/?page=admin.users&err=no_pwd');
                exit;
            }
            $db->prepare("
                INSERT INTO users (username,password,full_name,role,customer_id,is_active)
                VALUES (?,?,?,?,?,?)
            ")->execute([
                $data['username'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $data['full_name'],
                $data['role'],
                $data['customer_id'],
                $data['is_active'],
            ]);
        }

        header('Location: ' . BASE_URL . '/?page=admin.users&msg=saved');
        exit;
    }

    // ===== CUSTOMERS =====
    public function customers() {
        $db = getDB();
        $customers = $db->query("
            SELECT c.*,
                   COUNT(s.id) as shipment_count,
                   COALESCE(SUM(sc.amount),0) as total_debt
            FROM customers c
            LEFT JOIN shipments s ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id AND s.status IN ('debt','pending_approval')
            GROUP BY c.id
            ORDER BY c.customer_code
        ")->fetchAll();

        $viewTitle = 'Quản lý khách hàng';
        $viewFile  = __DIR__ . '/../views/admin/customers.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function saveCustomer() {
        $db = getDB();
        $id = (int)($_POST['id'] ?? 0);
        $fields = ['customer_code','company_name','email','phone','address','is_active'];
        $data   = [];
        foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');
        $data['is_active'] = (int)($_POST['is_active'] ?? 1);

        if ($id) {
            $db->prepare("
                UPDATE customers SET customer_code=?,company_name=?,email=?,phone=?,address=?,is_active=?
                WHERE id=?
            ")->execute([...array_values($data), $id]);
        } else {
            $db->prepare("
                INSERT INTO customers (customer_code,company_name,email,phone,address,is_active)
                VALUES (?,?,?,?,?,?)
            ")->execute(array_values($data));
        }

        header('Location: ' . BASE_URL . '/?page=admin.customers&msg=saved');
        exit;
    }

    // ===== QUOTATION =====
public function quotation() {
    $db = getDB();

    $quotations = $db->query("
        SELECT q.*, c.customer_code, c.company_name,
               COUNT(qi.id) as item_count,
               COALESCE(SUM(qi.amount), 0) as total_amount
        FROM quotations q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quotation_items qi ON qi.quotation_id = q.id
        GROUP BY q.id
        ORDER BY c.customer_code
    ")->fetchAll();

    $customers = $db->query("
        SELECT id, customer_code, company_name
        FROM customers WHERE is_active=1 ORDER BY customer_code
    ")->fetchAll();

    $viewTitle = 'Báo giá';
    $viewFile  = __DIR__ . '/../views/admin/quotation.php';
    include __DIR__ . '/../views/layouts/main.php';
}

public function quotationDetail() {
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $db->prepare("
        SELECT q.*, c.customer_code, c.company_name, c.email, c.address
        FROM quotations q
        LEFT JOIN customers c ON q.customer_id = c.id
        WHERE q.id = ?
    ");
    $stmt->execute([$id]);
    $quotation = $stmt->fetch();

    if (!$quotation) {
        header('Location: ' . BASE_URL . '/?page=admin.quotation');
        exit;
    }

    $items = $db->prepare("
        SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order, id
    ");
    $items->execute([$id]);
    $items = $items->fetchAll();

    $customers = $db->query("
        SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code
    ")->fetchAll();

    $viewTitle = 'Báo giá - ' . $quotation['customer_code'];
    $viewFile  = __DIR__ . '/../views/admin/quotation_detail.php';
    include __DIR__ . '/../views/layouts/main.php';
}

public function saveQuotation() {
    $db = getDB();
    $id = (int)($_POST['id'] ?? 0);

    $fields = [
        'customer_id' => (int)($_POST['customer_id'] ?? 0),
        'name'        => trim($_POST['name'] ?? 'Báo giá'),
        'valid_from'  => $_POST['valid_from'] ?: null,
        'valid_to'    => $_POST['valid_to']   ?: null,
        'is_active'   => (int)($_POST['is_active'] ?? 1),
        'note'        => trim($_POST['note'] ?? ''),
    ];

    if ($id) {
        $db->prepare("
            UPDATE quotations SET customer_id=?, name=?, valid_from=?, valid_to=?, is_active=?, note=?
            WHERE id=?
        ")->execute([...array_values($fields), $id]);
    } else {
        $db->prepare("
            INSERT INTO quotations (customer_id, name, valid_from, valid_to, is_active, note)
            VALUES (?,?,?,?,?,?)
        ")->execute(array_values($fields));
        $id = $db->lastInsertId();
    }

    // Lưu items
    $db->prepare("DELETE FROM quotation_items WHERE quotation_id=?")->execute([$id]);

    $items       = $_POST['items'] ?? [];
    $descriptions = $_POST['desc']       ?? [];
    $currencies   = $_POST['currency']   ?? [];
    $unitPrices   = $_POST['unit_price'] ?? [];
    $quantities   = $_POST['quantity']   ?? [];
    $vatPcts      = $_POST['vat_pct']    ?? [];
    $notes        = $_POST['item_note']  ?? [];

    $stmt = $db->prepare("
        INSERT INTO quotation_items
        (quotation_id, description, currency, unit_price, quantity, amount, vat_pct, note, sort_order)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    foreach ($descriptions as $i => $desc) {
        if (empty(trim($desc))) continue;
        $unitPrice = (float)str_replace(',', '', $unitPrices[$i] ?? 0);
        $qty       = (float)($quantities[$i] ?? 1);
        $amount    = $unitPrice * $qty;
        $stmt->execute([
            $id,
            trim($desc),
            $currencies[$i]  ?? 'VND',
            $unitPrice,
            $qty,
            $amount,
            (int)($vatPcts[$i] ?? 8),
            trim($notes[$i]  ?? ''),
            $i,
        ]);
    }

    header('Location: ' . BASE_URL . '/?page=admin.quotation_detail&id=' . $id . '&msg=saved');
    exit;

}
}