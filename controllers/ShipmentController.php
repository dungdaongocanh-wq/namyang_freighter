<?php
class ShipmentController {

    // ===== CS DASHBOARD =====
    public function csDashboard() {
        $db    = getDB();
        $today = date('Y-m-d');

        $stats = [
            'today'           => $db->query("SELECT COUNT(*) FROM shipments WHERE DATE(created_at)='$today'")->fetchColumn(),
            'pending_customs' => $db->query("SELECT COUNT(*) FROM shipments WHERE status='pending_customs'")->fetchColumn(),
            'cleared'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status='cleared'")->fetchColumn(),
            'waiting_pickup'  => $db->query("SELECT COUNT(*) FROM shipments WHERE status='waiting_pickup'")->fetchColumn(),
        ];

        $stmt = $db->prepare("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.id DESC
            LIMIT 20
        ");
        $stmt->execute();
        $todayShipments = $stmt->fetchAll();

        $viewTitle = 'CS Dashboard';
        $viewFile  = __DIR__ . '/../views/cs/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== DANH SÁCH LÔ =====
    public function listShipments() {
        $db = getDB();

        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[]  = "(s.hawb LIKE ? OR s.mawb LIKE ? OR s.customer_code LIKE ?)";
            $kw       = '%' . $_GET['search'] . '%';
            $params   = array_merge($params, [$kw, $kw, $kw]);
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
        $perPage  = 30;
        $page     = max(1, (int)($_GET['p'] ?? 1));
        $offset   = ($page - 1) * $perPage;

        $total = $db->prepare("SELECT COUNT(*) FROM shipments s WHERE $whereStr");
        $total->execute($params);
        $totalRows = $total->fetchColumn();

        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.customer_code
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE $whereStr
            ORDER BY s.active_date DESC, s.id DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        $customers  = $db->query("SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code")->fetchAll();
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
                $file = $_FILES['excel_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Lỗi upload file!';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['xlsx', 'xls'])) {
                        $error = 'Chỉ chấp nhận file .xlsx hoặc .xls!';
                    } else {
                        if (!is_dir(UPLOAD_PATH . 'imports/')) mkdir(UPLOAD_PATH . 'imports/', 0777, true);
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
                        $importer    = new ExcelImport();
                        $rows        = $importer->parse($tmpPath);
                        $updateDupes = ($_POST['update_duplicates'] ?? '0') === '1';
                        $result      = $importer->import($rows, $_SESSION['user_id'], $updateDupes);

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

        // Load danh sách lô kèm chi tiết từng customs record
        $stmt = $db->query("
            SELECT s.id, s.hawb, s.customer_code, s.mawb, s.flight_no,
                   s.eta, s.active_date, s.status,
                   GROUP_CONCAT(sc.id          ORDER BY sc.id SEPARATOR ',')   as cd_ids,
                   GROUP_CONCAT(sc.cd_number   ORDER BY sc.id SEPARATOR '|||') as cd_numbers,
                   GROUP_CONCAT(IFNULL(sc.file_path,'') ORDER BY sc.id SEPARATOR '|||') as cd_files,
                   COUNT(sc.id)                                                 as cd_count,
                   SUM(CASE WHEN sc.file_path IS NOT NULL THEN 1 ELSE 0 END)   as uploaded_count
            FROM shipments s
            LEFT JOIN shipment_customs sc ON s.id = sc.shipment_id
            WHERE s.status NOT IN ('delivered', 'invoiced')
            GROUP BY s.id
            ORDER BY s.active_date DESC
            LIMIT 200
        ");
        $shipments = $stmt->fetchAll();

        // Xử lý upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['customs_file'])) {
            $shipmentId = (int)($_POST['shipment_id'] ?? 0);
            $cdNumber   = trim($_POST['cd_number'] ?? '');
            $file       = $_FILES['customs_file'];

            if ($file['error'] === UPLOAD_ERR_OK && $shipmentId) {
                $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Chỉ chấp nhận PDF, JPG, PNG!';
                } else {
                    // Tìm record cũ nếu có (để xóa file cũ khi đính kèm lại)
                    $cdStmt = $db->prepare("SELECT id, file_path FROM shipment_customs WHERE shipment_id=? AND cd_number=?");
                    $cdStmt->execute([$shipmentId, $cdNumber]);
                    $cdRow = $cdStmt->fetch();

                    if (!$cdRow) {
                        // Tạo mới
                        $db->prepare("INSERT INTO shipment_customs (shipment_id, cd_number) VALUES (?,?)")
                           ->execute([$shipmentId, $cdNumber]);
                        $cdId = $db->lastInsertId();
                    } else {
                        $cdId = $cdRow['id'];
                        // Xóa file cũ nếu có
                        if (!empty($cdRow['file_path'])) {
                            @unlink(__DIR__ . '/../' . $cdRow['file_path']);
                        }
                    }

                    // Lưu file mới
                    $filename = 'cd_' . $cdId . '_' . time() . '.' . $ext;
                    $destPath = UPLOAD_PATH . 'customs/' . $filename;

                    if (!is_dir(UPLOAD_PATH . 'customs/')) {
                        mkdir(UPLOAD_PATH . 'customs/', 0777, true);
                    }

                    move_uploaded_file($file['tmp_name'], $destPath);

                    $db->prepare("UPDATE shipment_customs SET file_path=?, uploaded_by=?, uploaded_at=NOW() WHERE id=?")
                       ->execute(['uploads/customs/' . $filename, $_SESSION['user_id'], $cdId]);

                    // Bước 1: Luôn chuyển pending_customs → cleared khi upload tờ khai
                    StateTransition::transition($shipmentId, 'customs_file_upload', $_SESSION['user_id'], 'Upload tờ khai');

                    // Bước 2: Nếu tất cả tờ khai đã có file → chuyển cleared → waiting_pickup
                    try {
                        $check = $db->prepare("
                            SELECT COUNT(*) as total,
                                   SUM(CASE WHEN file_path IS NOT NULL THEN 1 ELSE 0 END) as uploaded
                            FROM shipment_customs
                            WHERE shipment_id = ?
                        ");
                        $check->execute([$shipmentId]);
                        $counts = $check->fetch();

                        if ($counts['total'] > 0 && $counts['total'] == $counts['uploaded']) {
                            StateTransition::transition($shipmentId, 'ops_bulk_download', $_SESSION['user_id'], 'Đã upload đủ tờ khai');
                        }
                    } catch (Exception $e) {
                        error_log('[customsUpload] StateTransition error: ' . $e->getMessage());
                    }

                    header('Location: ' . BASE_URL . '/?page=cs.customs_upload&msg=saved');
                    exit;
                }
            } else {
                $error = 'Vui lòng chọn file và lô hàng hợp lệ!';
            }
        }

        $viewTitle = 'Upload tờ khai';
        $viewFile  = __DIR__ . '/../views/cs/customs_upload.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== DELETE CUSTOMS FILE =====
    public function deleteCustoms() {
        $db  = getDB();
        $id  = (int)($_POST['cd_id']      ?? 0);

        if ($id) {
            // Lấy đường dẫn file cũ
            $row = $db->prepare("SELECT file_path FROM shipment_customs WHERE id=?");
            $row->execute([$id]);
            $old = $row->fetch();

            // Xóa file vật lý
            if ($old && !empty($old['file_path'])) {
                @unlink(__DIR__ . '/../' . $old['file_path']);
            }

            // Clear file_path trong DB (giữ lại record + cd_number)
            $db->prepare("UPDATE shipment_customs SET file_path=NULL, uploaded_by=NULL, uploaded_at=NULL WHERE id=?")
               ->execute([$id]);
        }

        header('Location: ' . BASE_URL . '/?page=cs.customs_upload&msg=deleted');
        exit;
    }
// ===== DELETE CUSTOMS RECORD (xóa cả số tờ khai) =====
public function deleteCustomsRecord() {
    $db = getDB();
    $id = (int)($_POST['cd_id'] ?? 0);

    if ($id) {
        // Xóa file vật lý nếu có
        $row = $db->prepare("SELECT file_path FROM shipment_customs WHERE id=?");
        $row->execute([$id]);
        $old = $row->fetch();
        if ($old && !empty($old['file_path'])) {
            @unlink(__DIR__ . '/../' . $old['file_path']);
        }
        // Xóa hẳn record
        $db->prepare("DELETE FROM shipment_customs WHERE id=?")->execute([$id]);
    }

    header('Location: ' . BASE_URL . '/?page=cs.customs_upload&msg=deleted');
    exit;
}

    // ===== SAVE NOTE =====
    public function saveNote() {
        header('Content-Type: application/json');
        $db   = getDB();
        $id   = (int)($_POST['id']   ?? 0);
        $note = trim($_POST['note']  ?? '');
        $db->prepare("UPDATE shipments SET remark=?, updated_at=NOW() WHERE id=?")
           ->execute([$note, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ===== ADMIN DASHBOARD =====
    public function adminDashboard() {
        $db    = getDB();
        $stats = [
            'total_shipments' => $db->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
            'total_customers' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active=1")->fetchColumn(),
            'total_users'     => $db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'pending'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status NOT IN ('delivered','invoiced')")->fetchColumn(),
            'total_debt'      => $db->query("SELECT COALESCE(SUM(total_amount),0) FROM debts WHERE status='open'")->fetchColumn(),
        ];

        $stmt = $db->prepare("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.id DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recentShipments = $stmt->fetchAll();

        $viewTitle = 'Admin Dashboard';
        $viewFile  = __DIR__ . '/../views/admin/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== SHIPMENT MODAL (AJAX partial) =====
    public function modal() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<p class="text-muted p-3">Vui lòng đăng nhập.</p>';
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        $db = getDB();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo '<p class="text-muted p-3">Không tìm thấy lô hàng.</p>';
            exit;
        }

        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.address, c.phone, c.email
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $s = $stmt->fetch();
        if (!$s) {
            echo '<p class="text-muted p-3">Không tìm thấy lô hàng.</p>';
            exit;
        }

        // Nếu role customer, chỉ xem lô của mình
        if (($_SESSION['role'] ?? '') === 'customer') {
            $customerId = (int)($_SESSION['customer_id'] ?? 0);
            if ((int)$s['customer_id'] !== $customerId) {
                echo '<p class="text-muted p-3">Bạn không có quyền xem lô hàng này.</p>';
                exit;
            }
        }

        $customs = $db->prepare("SELECT * FROM shipment_customs WHERE shipment_id = ? ORDER BY id");
        $customs->execute([$id]);
        $customsList = $customs->fetchAll();

        $photos = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id = ? ORDER BY id");
        $photos->execute([$id]);
        $photoList = $photos->fetchAll();

        $costs = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id = ? ORDER BY id");
        $costs->execute([$id]);
        $costList = $costs->fetchAll();
        $totalCost = array_sum(array_column($costList, 'amount'));

        $logs = $db->prepare("
            SELECT sl.*, u.full_name
            FROM shipment_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE sl.shipment_id = ?
            ORDER BY sl.created_at DESC
            LIMIT 5
        ");
        $logs->execute([$id]);
        $logList = $logs->fetchAll();

        $sig = $db->prepare("SELECT * FROM delivery_signatures WHERE shipment_id = ? ORDER BY id DESC LIMIT 1");
        $sig->execute([$id]);
        $signature = $sig->fetch() ?: null;

        include __DIR__ . '/../views/shared/shipment_modal_content.php';
        exit;
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

        // Kiểm tra cột cd_status có tồn tại không
        try {
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
                trim($_POST['hawb']      ?? ''),
                ($_POST['customer_id']   ?? '') ?: null,
                trim($_POST['mawb']      ?? ''),
                trim($_POST['flight_no'] ?? ''),
                $_POST['eta']            ?: null,
                (int)($_POST['packages'] ?? 0),
                (float)($_POST['weight'] ?? 0),
                $_POST['status']         ?? 'pending_customs',
                trim($_POST['cd_status'] ?? ''),
                trim($_POST['remark']    ?? ''),
                $id,
            ]);
        } catch (PDOException $e) {
            // Nếu cột cd_status chưa có thì update không có cột đó
            if (str_contains($e->getMessage(), 'cd_status')) {
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
                        remark      = ?,
                        updated_at  = NOW()
                    WHERE id = ?
                ")->execute([
                    trim($_POST['hawb']      ?? ''),
                    ($_POST['customer_id']   ?? '') ?: null,
                    trim($_POST['mawb']      ?? ''),
                    trim($_POST['flight_no'] ?? ''),
                    $_POST['eta']            ?: null,
                    (int)($_POST['packages'] ?? 0),
                    (float)($_POST['weight'] ?? 0),
                    $_POST['status']         ?? 'pending_customs',
                    trim($_POST['remark']    ?? ''),
                    $id,
                ]);
            } else {
                throw $e;
            }
        }

        header('Location: ' . BASE_URL . '/?page=cs.list&msg=saved');
        exit;
    }

    // ===== DELETE SHIPMENT =====
    public function deleteShipment() {
        $db = getDB();
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            header('Location: ' . BASE_URL . '/?page=cs.list&err=invalid');
            exit;
        }

        try { $db->prepare("DELETE FROM shipment_logs      WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM shipment_costs     WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM shipment_documents WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM shipment_customs   WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM notifications      WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM trip_shipments     WHERE shipment_id = ?")->execute([$id]); } catch (Exception $e) {}

        $db->prepare("DELETE FROM shipments WHERE id = ?")->execute([$id]);

        header('Location: ' . BASE_URL . '/?page=cs.list&msg=deleted');
        exit;
    }

    // ===== HUỶ LÔ HÀNG =====
    public function cancelShipment() {
        $db = getDB();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        // Trạng thái không được phép huỷ
        $noCancel = ['in_transit', 'delivered', 'kt_reviewing', 'pending_approval', 'invoiced', 'cancelled'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý huỷ
            $reason = trim($_POST['reason'] ?? '');
            if (!$reason) {
                header('Location: ' . BASE_URL . '/?page=cs.cancel&id=' . $id . '&err=no_reason');
                exit;
            }

            $stmt = $db->prepare("SELECT id, status FROM shipments WHERE id = ?");
            $stmt->execute([$id]);
            $shipment = $stmt->fetch();

            if (!$shipment || in_array($shipment['status'], $noCancel)) {
                header('Location: ' . BASE_URL . '/?page=cs.list&err=cannot_cancel');
                exit;
            }

            $db->prepare("UPDATE shipments SET status='cancelled', updated_at=NOW() WHERE id=?")
               ->execute([$id]);

            // Ghi log
            try {
                $db->prepare("
                    INSERT INTO shipment_logs (shipment_id, action, note, user_id)
                    VALUES (?, 'cancelled', ?, ?)
                ")->execute([$id, $reason, $_SESSION['user_id']]);
            } catch (Exception $e) {}

            header('Location: ' . BASE_URL . '/?page=cs.list&msg=deleted');
            exit;
        }

        // GET — hiện form huỷ
        if (!$id) {
            header('Location: ' . BASE_URL . '/?page=cs.list');
            exit;
        }

        $stmt = $db->prepare("
            SELECT s.*, c.company_name
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

        $viewTitle = 'Huỷ lô hàng — ' . $shipment['hawb'];
        $viewFile  = __DIR__ . '/../views/cs/cancel.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

} // ← Đóng class ShipmentController