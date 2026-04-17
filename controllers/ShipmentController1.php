<?php
class ShipmentController {

    // ===== CS DASHBOARD =====
    public function csDashboard() {
        $db = getDB();
        $today = date('Y-m-d');

        // Stats
        $stats = [
            'today'           => $db->query("SELECT COUNT(*) FROM shipments WHERE import_date='$today'")->fetchColumn(),
            'pending_customs' => $db->query("SELECT COUNT(*) FROM shipments WHERE status='pending_customs'")->fetchColumn(),
            'cleared'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status='cleared'")->fetchColumn(),
            'waiting_pickup'  => $db->query("SELECT COUNT(*) FROM shipments WHERE status='waiting_pickup'")->fetchColumn(),
        ];

        // Lô hàng hôm nay
        $stmt = $db->prepare("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.import_date = ?
            ORDER BY s.id DESC
            LIMIT 20
        ");
        $stmt->execute([$today]);
        $todayShipments = $stmt->fetchAll();

        $viewTitle = 'CS Dashboard';
        $viewFile  = __DIR__ . '/../views/cs/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== DANH SÁCH LÔ =====
    public function listShipments() {
        $db = getDB();

        // Filters
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[]  = "(s.hawb LIKE ? OR s.mawb LIKE ? OR s.customer_code LIKE ?)";
            $kw = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$kw, $kw, $kw]);
        }
        if (!empty($_GET['status'])) {
            $where[]  = "s.status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['customer_id'])) {
            $where[]  = "s.customer_id = ?";
            $params[] = $_GET['customer_id'];
        }
        if (!empty($_GET['date_from'])) {
            $where[]  = "s.active_date >= ?";
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $where[]  = "s.active_date <= ?";
            $params[] = $_GET['date_to'];
        }

        $whereStr = implode(' AND ', $where);

        // Pagination
        $perPage = 30;
        $page    = max(1, (int)($_GET['p'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $total = $db->prepare("SELECT COUNT(*) FROM shipments s WHERE $whereStr");
        $total->execute($params);
        $totalRows = $total->fetchColumn();

        $stmt = $db->prepare("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE $whereStr
            ORDER BY s.active_date DESC, s.id DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        // Customers cho filter
        $customers = $db->query("SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code")->fetchAll();

        $totalPages = ceil($totalRows / $perPage);

        $viewTitle = 'Danh sách lô hàng';
        $viewFile  = __DIR__ . '/../views/cs/list.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== UPLOAD EXCEL =====
    public function uploadExcel() {
        $result  = null;
        $preview = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'preview' && isset($_FILES['excel_file'])) {
                // Lưu file tạm
                $file = $_FILES['excel_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Lỗi upload file!';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['xlsx', 'xls'])) {
                        $error = 'Chỉ chấp nhận file .xlsx hoặc .xls!';
                    } else {
                        $tmpPath = UPLOAD_PATH . 'imports/' . uniqid('import_') . '.' . $ext;
                        move_uploaded_file($file['tmp_name'], $tmpPath);
                        $_SESSION['import_tmp_file'] = $tmpPath;
                        $_SESSION['import_filename'] = $file['name'];

                        try {
                            $importer = new ExcelImport();
                            $preview  = $importer->parse($tmpPath);
                        } catch (Exception $e) {
                            $error = 'Lỗi đọc file: ' . $e->getMessage();
                        }
                    }
                }

            } elseif ($action === 'import') {
                $tmpPath = $_SESSION['import_tmp_file'] ?? '';
                if (!$tmpPath || !file_exists($tmpPath)) {
                    $error = 'File tạm không tồn tại, vui lòng upload lại!';
                } else {
                    try {
                        $importer = new ExcelImport();
                        $rows     = $importer->parse($tmpPath);
                        $updateDupes = ($_POST['update_duplicates'] ?? '0') === '1';
                        $result   = $importer->import($rows, $_SESSION['user_id'], $updateDupes);

                        // Xóa file tạm
                        @unlink($tmpPath);
                        unset($_SESSION['import_tmp_file'], $_SESSION['import_filename']);
                    } catch (Exception $e) {
                        $error = 'Lỗi import: ' . $e->getMessage();
                    }
                }
            }
        }

        $viewTitle = 'Upload lô hàng';
        $viewFile  = __DIR__ . '/../views/cs/upload.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== UPLOAD TỜ KHAI CUSTOMS =====
    public function customsUpload() {
        $db    = getDB();
        $error = null;
        $success = null;

        // Lấy danh sách lô cần upload tờ khai
        $stmt = $db->query("
            SELECT s.id, s.hawb, s.customer_code, s.active_date, s.status,
                   GROUP_CONCAT(sc.cd_number ORDER BY sc.id SEPARATOR ', ') as cd_numbers,
                   COUNT(sc.id) as cd_count,
                   SUM(CASE WHEN sc.file_path IS NOT NULL THEN 1 ELSE 0 END) as uploaded_count
            FROM shipments s
            LEFT JOIN shipment_customs sc ON s.id = sc.shipment_id
            WHERE s.status IN ('pending_customs','cleared')
            GROUP BY s.id
            ORDER BY s.active_date DESC
            LIMIT 100
        ");
        $shipments = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customs_file'])) {
            $shipmentId = (int)($_POST['shipment_id'] ?? 0);
            $cdId       = (int)($_POST['cd_id'] ?? 0);
            $file       = $_FILES['customs_file'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf','jpg','jpeg','png'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Chỉ chấp nhận PDF, JPG, PNG!';
                } else {
                    $filename = 'cd_' . $cdId . '_' . time() . '.' . $ext;
                    $destPath = UPLOAD_PATH . 'customs/' . $filename;
                    move_uploaded_file($file['tmp_name'], $destPath);

                    // Cập nhật DB
                    $stmt2 = $db->prepare("UPDATE shipment_customs SET file_path=?, uploaded_by=?, uploaded_at=NOW() WHERE id=?");
                    $stmt2->execute(['uploads/customs/' . $filename, $_SESSION['user_id'], $cdId]);

                    // Kiểm tra tất cả CD đã upload → chuyển sang cleared
                    $checkStmt = $db->prepare("SELECT COUNT(*) FROM shipment_customs WHERE shipment_id=? AND file_path IS NULL");
                    $checkStmt->execute([$shipmentId]);
                    if ($checkStmt->fetchColumn() == 0) {
                        StateTransition::transition($shipmentId, 'customs_file_upload', $_SESSION['user_id']);
                    }
                    $success = 'Upload tờ khai thành công!';
                    header('Location: ' . BASE_URL . '/?page=cs.customs_upload&msg=ok');
                    exit;
                }
            } else {
                $error = 'Lỗi upload file!';
            }
        }

        $viewTitle = 'Upload tờ khai';
        $viewFile  = __DIR__ . '/../views/cs/customs_upload.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SAVE NOTE (AJAX) =====
    public function saveNote() {
        header('Content-Type: application/json');
        $db  = getDB();
        $id  = (int)($_POST['id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        $stmt = $db->prepare("UPDATE shipments SET customer_note=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$note, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ===== Admin Dashboard =====
    public function adminDashboard() {
        $db = getDB();
        $stats = [
            'total_shipments' => $db->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
            'total_customers' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn(),
            'total_users'     => $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'pending'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status NOT IN ('delivered','invoiced')")->fetchColumn(),
        ];
        $viewTitle = 'Admin Dashboard';
        $viewFile  = __DIR__ . '/../views/admin/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }
}
// ===== EDIT =====
public function editShipment() {
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);

    $stmt = $db->prepare("
        SELECT s.*, c.customer_code
        FROM shipments s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $shipment = $stmt->fetch();

    if (!$shipment) {
        header('Location: ' . BASE_URL . '/?page=cs.list&err=not_found');
        exit;
    }

    $customers = $db->query("
        SELECT id, customer_code, company_name
        FROM customers WHERE is_active=1 ORDER BY customer_code
    ")->fetchAll();

    $viewTitle = 'Sửa lô hàng';
    $viewFile  = __DIR__ . '/../views/cs/edit_shipment.php';
    include __DIR__ . '/../views/layouts/main.php';
}

// ===== UPDATE =====
public function updateShipment() {
    $db = getDB();
    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        header('Location: ' . BASE_URL . '/?page=cs.list&err=invalid');
        exit;
    }

    $db->prepare("
        UPDATE shipments SET
            hawb        = ?,
            customer_id = ?,
            mawb        = ?,
            flight_no   = ?,
            eta         = ?,
            packages    = ?,
            weight      = ?,
            status      = ?,
            cd_status   = ?,
            remark      = ?,
            updated_at  = NOW()
        WHERE id = ?
    ")->execute([
        trim($_POST['hawb']        ?? ''),
        ($_POST['customer_id']     ?? '') ?: null,
        trim($_POST['mawb']        ?? ''),
        trim($_POST['flight_no']   ?? ''),
        $_POST['eta']              ?: null,
        (int)($_POST['packages']   ?? 0),
        (float)($_POST['weight']   ?? 0),
        $_POST['status']           ?? 'pending_customs',
        trim($_POST['cd_status']   ?? ''),
        trim($_POST['remark']      ?? ''),
        $id,
    ]);

    header('Location: ' . BASE_URL . '/?page=cs.list&msg=saved');
    exit;
}

// ===== DELETE =====
public function deleteShipment() {
    $db = getDB();
    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        header('Location: ' . BASE_URL . '/?page=cs.list&err=invalid');
        exit;
    }

    // Xoá các bảng liên quan trước
    $db->prepare("DELETE FROM shipment_costs WHERE shipment_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM shipment_documents WHERE shipment_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM notifications WHERE shipment_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM shipments WHERE id = ?")->execute([$id]);

    header('Location: ' . BASE_URL . '/?page=cs.list&msg=deleted');
    exit;
}