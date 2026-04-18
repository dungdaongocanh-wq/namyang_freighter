<?php
class OpsController {

    public function dashboard() {
        $db    = getDB();
        $today = date('Y-m-d');

        $stats = [
            'cleared'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status='cleared'")->fetchColumn(),
            'waiting_pickup'  => $db->query("SELECT COUNT(*) FROM shipments WHERE status='waiting_pickup'")->fetchColumn(),
            'in_transit'      => $db->query("SELECT COUNT(*) FROM shipments WHERE status='in_transit'")->fetchColumn(),
            'delivered_today' => $db->query("SELECT COUNT(*) FROM shipments WHERE status='delivered' AND DATE(updated_at)='$today'")->fetchColumn(),
        ];

        $stmt = $db->prepare("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.status IN ('cleared','waiting_pickup')
            ORDER BY s.active_date ASC, s.id ASC
            LIMIT 50
        ");
        $stmt->execute();
        $pendingShipments = $stmt->fetchAll();

        $stmt2 = $db->prepare("
            SELECT dt.*, u.full_name as driver_name,
                   COUNT(dti.id) as item_count
            FROM delivery_trips dt
            LEFT JOIN users u ON dt.driver_id = u.id
            LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
            WHERE dt.trip_date = ?
            GROUP BY dt.id
            ORDER BY dt.id DESC
        ");
        $stmt2->execute([$today]);
        $todayTrips = $stmt2->fetchAll();

        $viewTitle = 'OPS Dashboard';
        $viewFile  = __DIR__ . '/../views/ops/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function detail() {
        $db = getDB();
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.phone as customer_phone
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $shipment = $stmt->fetch();

        if (!$shipment) {
            header('Location: ' . BASE_URL . '/?page=ops.dashboard');
            exit;
        }

        $photos = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id=? ORDER BY id DESC");
        $photos->execute([$id]);
        $photos = $photos->fetchAll();

        $customs = $db->prepare("SELECT * FROM shipment_customs WHERE shipment_id=? ORDER BY id");
        $customs->execute([$id]);
        $customs = $customs->fetchAll();

        $logs = $db->prepare("
            SELECT sl.*, u.full_name
            FROM shipment_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE sl.shipment_id = ?
            ORDER BY sl.created_at DESC
            LIMIT 10
        ");
        $logs->execute([$id]);
        $logs = $logs->fetchAll();

        $viewTitle = 'Chi tiết lô hàng';
        $viewFile  = __DIR__ . '/../views/ops/shipment_detail.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function shipmentList() {
    $db = getDB();

    // Lọc theo status nếu có, mặc định lấy cleared + waiting_pickup
    $status = $_GET['status'] ?? '';
    $search = trim($_GET['search'] ?? '');

    $where  = ['1=1'];
    $params = [];

    if ($status) {
        $where[]  = "s.status = ?";
        $params[] = $status;
    } else {
        // Mặc định: lấy lô đã thông quan hoặc đang chờ lấy hàng
        $where[] = "s.status IN ('cleared', 'waiting_pickup', 'pending_customs')";
    }

    if ($search) {
        $where[]  = "(s.hawb LIKE ? OR s.mawb LIKE ? OR s.customer_code LIKE ?)";
        $kw       = '%' . $search . '%';
        $params   = array_merge($params, [$kw, $kw, $kw]);
    }

    $whereStr = implode(' AND ', $where);

    $stmt = $db->prepare("
        SELECT s.id, s.hawb, s.customer_code, s.mawb, s.flight_no,
               s.eta, s.active_date, s.status,
               GROUP_CONCAT(sc.cd_number ORDER BY sc.id SEPARATOR ', ') as cd_numbers,
               COUNT(sc.id)                                              as cd_count,
               SUM(CASE WHEN sc.file_path IS NOT NULL THEN 1 ELSE 0 END) as file_count
        FROM shipments s
        LEFT JOIN shipment_customs sc ON s.id = sc.shipment_id
        WHERE $whereStr
        GROUP BY s.id
        ORDER BY s.active_date ASC
    ");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();

    $viewTitle = 'Tải tờ khai TQ';
    $viewFile  = __DIR__ . '/../views/ops/shipment_list.php';
    include __DIR__ . '/../views/layouts/main.php';
}

    public function downloadCustoms() {
        $db = getDB();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $_POST['shipment_ids'] ?? [];
            if (empty($ids)) {
                header('Location: ' . BASE_URL . '/?page=ops.shipment_list&err=no_select');
                exit;
            }

            $zip     = new ZipArchive();
            $zipName = 'tq_customs_' . date('Ymd_His') . '.zip';
            $zipPath = UPLOAD_PATH . 'imports/' . $zipName;
            if (!is_dir(UPLOAD_PATH . 'imports/')) mkdir(UPLOAD_PATH . 'imports/', 0777, true);
            $zip->open($zipPath, ZipArchive::CREATE);

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                SELECT sc.*, s.hawb, s.customer_code
                FROM shipment_customs sc
                JOIN shipments s ON sc.shipment_id = s.id
                WHERE sc.shipment_id IN ($placeholders)
                AND sc.file_path IS NOT NULL
            ");
            $stmt->execute(array_map('intval', $ids));
            $files = $stmt->fetchAll();

            foreach ($files as $f) {
                $filePath = __DIR__ . '/../' . $f['file_path'];
                if (file_exists($filePath)) {
                    $zipEntry = $f['customer_code'] . '_' . $f['hawb'] . '_' . basename($f['file_path']);
                    $zip->addFile($filePath, $zipEntry);
                }
            }
            $zip->close();

            foreach (array_map('intval', $ids) as $sid) {
                try { StateTransition::transition($sid, 'ops_bulk_download', $_SESSION['user_id']); } catch (Exception $e) {}
                $db->prepare("UPDATE shipment_customs SET downloaded_by=?, downloaded_at=NOW() WHERE shipment_id=? AND file_path IS NOT NULL")
                   ->execute([$_SESSION['user_id'], $sid]);
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            @unlink($zipPath);
            exit;
        }

        $this->shipmentList();
    }

    public function trip() {
        $db  = getDB();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'print_delivery') {
                $carrier    = trim($_POST['carrier'] ?? '');
                $customerId = (int)($_POST['customer_id'] ?? 0);
                $shipIds    = $_POST['shipment_ids'] ?? [];

                if (empty($shipIds)) {
                    $msg = 'error:Chọn ít nhất 1 lô hàng!';
                } else {
                    $stmt = $db->prepare("INSERT INTO delivery_trips (driver_id, ops_id, trip_date, note, status) VALUES (NULL,?,CURDATE(),?,'pending')");
                    $stmt->execute([$_SESSION['user_id'], $carrier]);
                    $tripId = $db->lastInsertId();

                    foreach (array_map('intval', $shipIds) as $sid) {
                        $db->prepare("INSERT INTO delivery_trip_items (trip_id, shipment_id) VALUES (?,?)")
                           ->execute([$tripId, $sid]);
                        // Tạo delivery_note cho từng lô
                        $noteCode = 'DN-' . date('Ymd') . '-T' . $tripId . '-' . str_pad($sid, 4, '0', STR_PAD_LEFT);
                        try {
                            $db->prepare("INSERT INTO delivery_notes (shipment_id, note_code, created_by, created_at) VALUES (?,?,?,NOW())")
                               ->execute([$sid, $noteCode, $_SESSION['user_id']]);
                        } catch (Exception $e) {}
                    }
                    header('Location: ' . BASE_URL . '/?page=ops.print_multi_delivery_note&trip_id=' . $tripId . '&autoprint=1');
                    exit;
                }
            } elseif ($action === 'create_trip') {
                $driverId = (int)$_POST['driver_id'];
                $tripDate = $_POST['trip_date'];
                $shipIds  = $_POST['shipment_ids'] ?? [];
                $note     = trim($_POST['note'] ?? '');

                if (empty($shipIds)) {
                    $msg = 'error:Chọn ít nhất 1 lô hàng!';
                } else {
                    $stmt = $db->prepare("INSERT INTO delivery_trips (driver_id, ops_id, trip_date, note, status) VALUES (?,?,?,?,'pending')");
                    $stmt->execute([$driverId, $_SESSION['user_id'], $tripDate, $note]);
                    $tripId = $db->lastInsertId();

                    foreach (array_map('intval', $shipIds) as $sid) {
                        $db->prepare("INSERT INTO delivery_trip_items (trip_id, shipment_id) VALUES (?,?)")
                           ->execute([$tripId, $sid]);
                        try { StateTransition::transition($sid, 'ops_complete', $_SESSION['user_id'], 'Tạo chuyến #' . $tripId); } catch (Exception $e) {}
                    }
                    header('Location: ' . BASE_URL . '/?page=ops.create_trip&msg=saved&trip_id=' . $tripId);
                    exit;
                }
            }
        }

        $customers = $db->query("SELECT id, company_name FROM customers WHERE is_active=1 ORDER BY company_name ASC")->fetchAll();

        $recentNotes = $db->query("
            SELECT dt.id, dt.note, dt.trip_date,
                   COUNT(dti.id) as item_count,
                   c.company_name
            FROM delivery_trips dt
            LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
            LEFT JOIN shipments s ON dti.shipment_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            GROUP BY dt.id
            ORDER BY dt.id DESC
            LIMIT 10
        ")->fetchAll();

        $viewTitle = 'In Biên Bản Giao Hàng';
        $viewFile  = __DIR__ . '/../views/ops/trip.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // Alias cho index.php gọi ops.create_trip
    public function createTrip() {
        $this->trip();
    }

    // AJAX: lấy danh sách lô hàng waiting_pickup theo customer_id
    public function shipmentsByCustomer() {
        header('Content-Type: application/json');
        $customerId = (int)($_GET['customer_id'] ?? 0);
        if (!$customerId) { echo json_encode([]); exit; }
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, hawb, customer_code, packages, weight, active_date
            FROM shipments
            WHERE customer_id = ? AND status = 'waiting_pickup'
            ORDER BY active_date ASC
        ");
        $stmt->execute([$customerId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // In biên bản giao hàng nhiều lô theo trip_id
    public function printMultiDeliveryNote() {
        $db     = getDB();
        $tripId = (int)($_GET['trip_id'] ?? 0);

        if (!$tripId) {
            header('Location: ' . BASE_URL . '/?page=ops.create_trip');
            exit;
        }

        // Lấy thông tin trip
        $tripStmt = $db->prepare("SELECT * FROM delivery_trips WHERE id = ?");
        $tripStmt->execute([$tripId]);
        $trip = $tripStmt->fetch();

        if (!$trip) {
            header('Location: ' . BASE_URL . '/?page=ops.create_trip&err=not_found');
            exit;
        }

        // Lấy danh sách shipment trong trip (join delivery_trip_items)
        $shipStmt = $db->prepare("
            SELECT s.id, s.hawb, s.mawb, s.customer_code, s.packages, s.weight, s.active_date,
                   c.company_name, c.address as customer_address, c.phone as customer_phone
            FROM delivery_trip_items dti
            JOIN shipments s ON dti.shipment_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE dti.trip_id = ?
            ORDER BY dti.id ASC
        ");
        $shipStmt->execute([$tripId]);
        $shipments = $shipStmt->fetchAll();

        if (empty($shipments)) {
            header('Location: ' . BASE_URL . '/?page=ops.create_trip&err=not_found');
            exit;
        }

        // Lấy thông tin khách hàng từ shipment đầu tiên
        $customer = [
            'company_name' => $shipments[0]['company_name']    ?? '',
            'address'      => $shipments[0]['customer_address'] ?? '',
            'phone'        => $shipments[0]['customer_phone']   ?? '',
        ];

        $carrier   = $trip['note'] ?? '';
        $viewTitle = 'Biên Bản Giao Nhận Hàng Hóa';

        include __DIR__ . '/../templates/delivery_note_multi.php';
        exit;
    }

    public function costs() {
        $db = getDB();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shipmentId = (int)$_POST['shipment_id'];
            $costs      = $_POST['costs'] ?? [];

            $db->prepare("DELETE FROM shipment_costs WHERE shipment_id=? AND source='ops'")->execute([$shipmentId]);
            foreach ($costs as $cost) {
                if (empty($cost['name']) || !isset($cost['amount'])) continue;
                $db->prepare("INSERT INTO shipment_costs (shipment_id, cost_name, amount, source, created_by) VALUES (?,?,?,'ops',?)")
                   ->execute([$shipmentId, trim($cost['name']), (float)$cost['amount'], $_SESSION['user_id']]);
            }
            header('Location: ' . BASE_URL . '/?page=ops.costs&id=' . $shipmentId . '&msg=saved');
            exit;
        }

        $id            = (int)($_GET['id'] ?? 0);
        $shipment      = null;
        $existingCosts = [];

        if ($id) {
            $stmt = $db->prepare("SELECT s.*, c.company_name FROM shipments s LEFT JOIN customers c ON s.customer_id=c.id WHERE s.id=?");
            $stmt->execute([$id]);
            $shipment = $stmt->fetch();

            $cstmt = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
            $cstmt->execute([$id]);
            $existingCosts = $cstmt->fetchAll();
        }

        $pendingCosts = $db->query("
            SELECT s.id, s.hawb, s.customer_code, s.active_date
            FROM shipments s
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.status = 'delivered'
            GROUP BY s.id
            HAVING COUNT(sc.id) = 0
            ORDER BY s.active_date DESC
            LIMIT 30
        ")->fetchAll();

        $viewTitle = 'Chi phí lô hàng';
        $viewFile  = __DIR__ . '/../views/ops/costs.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function complete() {
        $db = getDB();
        $id = (int)($_GET['id'] ?? $_POST['shipment_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Hiển thị màn hình xác nhận hoàn thành
            if (!$id) {
                header('Location: ' . BASE_URL . '/?page=ops.dashboard');
                exit;
            }

            $stmt = $db->prepare("
                SELECT s.*, c.company_name, c.customer_code
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $shipment = $stmt->fetch();

            if (!$shipment) {
                header('Location: ' . BASE_URL . '/?page=ops.dashboard&err=not_found');
                exit;
            }

            $photos = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id=? ORDER BY id DESC");
            $photos->execute([$id]);
            $photos = $photos->fetchAll();

            // Chuyến đang mở (có thể gán lô vào)
            $availableTrips = $db->query("
                SELECT dt.*, u.full_name as driver_name,
                       COUNT(dti.id) as item_count
                FROM delivery_trips dt
                LEFT JOIN users u ON dt.driver_id = u.id
                LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
                WHERE dt.status = 'pending' AND dt.trip_date = CURDATE()
                GROUP BY dt.id
                ORDER BY dt.id DESC
            ")->fetchAll();

            $viewTitle = 'Hoàn thành lô — ' . $shipment['hawb'];
            $viewFile  = __DIR__ . '/../views/ops/complete.php';
            include __DIR__ . '/../views/layouts/mobile.php';
            return;
        }

        // POST — xử lý xác nhận hoàn thành / gán chuyến
        $action = $_POST['action'] ?? 'confirm_complete';

        if ($action === 'assign_trip') {
            $tripId = (int)($_POST['trip_id'] ?? 0);
            if ($tripId && $id) {
                $db->prepare("INSERT IGNORE INTO delivery_trip_items (trip_id, shipment_id) VALUES (?,?)")
                   ->execute([$tripId, $id]);
                try { StateTransition::transition($id, 'ops_complete', $_SESSION['user_id'], 'Gán vào chuyến #' . $tripId); } catch (Exception $e) {}
            }
            header('Location: ' . BASE_URL . '/?page=ops.complete&id=' . $id . '&msg=saved');
            exit;
        }

        // Xác nhận hoàn thành → chuyển trạng thái
        if ($id) {
            try { StateTransition::transition($id, 'auto_to_kt', $_SESSION['user_id']); } catch (Exception $e) {}
            header('Location: ' . BASE_URL . '/?page=ops.dashboard&msg=saved');
            exit;
        }

        // Fallback JSON (gọi từ AJAX cũ)
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function pickup() {
        $this->shipmentList();
    }

    public function printDeliveryNote() {
        $db = getDB();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            header('Location: ' . BASE_URL . '/?page=ops.dashboard');
            exit;
        }

        // Lấy thông tin lô hàng + khách hàng
        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.address, c.phone, c.email
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $shipment = $stmt->fetch();

        if (!$shipment) {
            header('Location: ' . BASE_URL . '/?page=ops.dashboard&err=not_found');
            exit;
        }

        // Lấy delivery note nếu có (lấy cái mới nhất)
        $dnStmt = $db->prepare("
            SELECT dn.*, u.full_name as created_by_name
            FROM delivery_notes dn
            LEFT JOIN users u ON dn.created_by = u.id
            WHERE dn.shipment_id = ?
            ORDER BY dn.id DESC
            LIMIT 1
        ");
        $dnStmt->execute([$id]);
        $deliveryNote = $dnStmt->fetch();

        // Nếu chưa có delivery_note → tạo mới tự động
        if (!$deliveryNote) {
            $noteCode = 'DN-' . date('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
            try {
                $db->prepare("
                    INSERT INTO delivery_notes (shipment_id, note_code, created_by, created_at)
                    VALUES (?, ?, ?, NOW())
                ")->execute([$id, $noteCode, $_SESSION['user_id']]);
                $dnId = $db->lastInsertId();
                $db->prepare("UPDATE delivery_notes SET printed_at=NOW(), printed_by=? WHERE id=?")
                   ->execute([$_SESSION['user_id'], $dnId]);
                $dnStmt2 = $db->prepare("
                    SELECT dn.*, u.full_name as created_by_name
                    FROM delivery_notes dn
                    LEFT JOIN users u ON dn.created_by = u.id
                    WHERE dn.id = ?
                ");
                $dnStmt2->execute([$dnId]);
                $deliveryNote = $dnStmt2->fetch();
            } catch (Exception $e) {
                $deliveryNote = [
                    'id'               => 0,
                    'note_code'        => 'DN-' . date('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'created_by_name'  => $_SESSION['full_name'] ?? 'OPS',
                    'recipient_name'   => '',
                    'recipient_phone'  => '',
                    'delivery_address' => $shipment['address'] ?? '',
                ];
            }
        } else {
            if (empty($deliveryNote['printed_at'])) {
                $db->prepare("UPDATE delivery_notes SET printed_at=NOW(), printed_by=? WHERE id=?")
                   ->execute([$_SESSION['user_id'], $deliveryNote['id']]);
            }
        }

        // Lấy chữ ký điện tử nếu có
        $sigStmt = $db->prepare("SELECT * FROM delivery_signatures WHERE shipment_id=? ORDER BY id DESC LIMIT 1");
        $sigStmt->execute([$id]);
        $signature = $sigStmt->fetch();

        // Ảnh lô hàng
        $photoStmt = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id=? ORDER BY id");
        $photoStmt->execute([$id]);
        $photos = $photoStmt->fetchAll();

        $items = [];

        include __DIR__ . '/../templates/delivery_note.php';
        exit;
    }

    public function saveCostsModal() {
        header('Content-Type: application/json');
        $db         = getDB();
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        if (!$shipmentId) { echo json_encode(['success'=>false,'message'=>'Thiếu ID']); exit; }

        $db->prepare("DELETE FROM shipment_costs WHERE shipment_id = ?")->execute([$shipmentId]);

        // Chi phí từ báo giá
        $qIds = array_map('intval', $_POST['quotation_items'] ?? []);
        if (!empty($qIds)) {
            $ph    = implode(',', array_fill(0, count($qIds), '?'));
            $items = $db->prepare("SELECT id, description, amount FROM quotation_items WHERE id IN ($ph)");
            $items->execute($qIds);
            foreach ($items->fetchAll() as $qi) {
                $db->prepare("INSERT INTO shipment_costs (shipment_id,cost_name,amount,source,created_by) VALUES (?,?,?,'quotation',?)")
                   ->execute([$shipmentId, $qi['description'], (float)$qi['amount'], $_SESSION['user_id']]);
            }
        }

        // Chi phí OPS tự điền
        foreach ($_POST['ops_costs'] ?? [] as $c) {
            $n = trim($c['name'] ?? ''); $a = (float)($c['amount'] ?? 0);
            if ($n === '' && $a == 0) continue;
            $db->prepare("INSERT INTO shipment_costs (shipment_id,cost_name,amount,source,created_by) VALUES (?,?,?,'ops',?)")
               ->execute([$shipmentId, $n, $a, $_SESSION['user_id']]);
        }

        try { $db->prepare("INSERT INTO shipment_logs (shipment_id,action,note,user_id) VALUES (?,'cost_updated','OPS cập nhật chi phí từ modal',?)")->execute([$shipmentId,$_SESSION['user_id']]); } catch(Exception $e){}
        echo json_encode(['success' => true]);
        exit;
    }
}