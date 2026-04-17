<?php
class OpsController {

    // ===== DASHBOARD =====
    public function dashboard() {
        $db  = getDB();
        $today = date('Y-m-d');

        $stats = [
            'cleared'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status='cleared'")->fetchColumn(),
            'waiting_pickup'  => $db->query("SELECT COUNT(*) FROM shipments WHERE status='waiting_pickup'")->fetchColumn(),
            'in_transit'      => $db->query("SELECT COUNT(*) FROM shipments WHERE status='in_transit'")->fetchColumn(),
            'delivered_today' => $db->query("SELECT COUNT(*) FROM shipments WHERE status='delivered' AND DATE(updated_at)='$today'")->fetchColumn(),
        ];

        // Lô chờ lấy hàng hôm nay
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

        // Chuyến hôm nay
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
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== CHI TIẾT LÔ =====
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

        // Photos
        $photos = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id=? ORDER BY id DESC");
        $photos->execute([$id]);
        $photos = $photos->fetchAll();

        // Customs files
        $customs = $db->prepare("SELECT * FROM shipment_customs WHERE shipment_id=? ORDER BY id");
        $customs->execute([$id]);
        $customs = $customs->fetchAll();

        // Logs
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
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== TẢI TỜ KHAI (BULK DOWNLOAD ZIP) =====
    public function downloadCustoms() {
        $db = getDB();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $_POST['shipment_ids'] ?? [];
            if (empty($ids)) {
                header('Location: ' . BASE_URL . '/?page=ops.dashboard&err=no_select');
                exit;
            }

            // Tạo ZIP
            $zip     = new ZipArchive();
            $zipName = 'tq_customs_' . date('Ymd_His') . '.zip';
            $zipPath = UPLOAD_PATH . 'imports/' . $zipName;
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

            // Trigger chuyển trạng thái → waiting_pickup
            foreach (array_map('intval', $ids) as $sid) {
                StateTransition::transition($sid, 'ops_bulk_download', $_SESSION['user_id']);

                // Ghi log downloaded_by
                $db->prepare("UPDATE shipment_customs SET downloaded_by=?, downloaded_at=NOW() WHERE shipment_id=? AND file_path IS NOT NULL")
                   ->execute([$_SESSION['user_id'], $sid]);
            }

            // Download ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            @unlink($zipPath);
            exit;
        }

        // GET - hiển thị danh sách cần tải
        $stmt = $db->query("
            SELECT s.id, s.hawb, s.customer_code, s.active_date, s.status,
                   COUNT(sc.id) as cd_count,
                   SUM(CASE WHEN sc.file_path IS NOT NULL THEN 1 ELSE 0 END) as file_count
            FROM shipments s
            LEFT JOIN shipment_customs sc ON s.id = sc.shipment_id
            WHERE s.status = 'cleared'
            GROUP BY s.id
            ORDER BY s.active_date ASC
        ");
        $shipments = $stmt->fetchAll();

        $viewTitle = 'Tải tờ khai TQ';
        $viewFile  = __DIR__ . '/../views/ops/pickup.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== TẠO CHUYẾN =====
    public function trip() {
        $db = getDB();
        $msg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create_trip') {
                $driverId  = (int)$_POST['driver_id'];
                $tripDate  = $_POST['trip_date'];
                $shipIds   = $_POST['shipment_ids'] ?? [];
                $note      = trim($_POST['note'] ?? '');

                if (empty($shipIds)) {
                    $msg = 'error:Chọn ít nhất 1 lô hàng!';
                } else {
                    // Insert trip
                    $stmt = $db->prepare("INSERT INTO delivery_trips (driver_id, ops_id, trip_date, note, status) VALUES (?,?,?,?,'pending')");
                    $stmt->execute([$driverId, $_SESSION['user_id'], $tripDate, $note]);
                    $tripId = $db->lastInsertId();

                    // Insert trip items + chuyển trạng thái
                    foreach (array_map('intval', $shipIds) as $sid) {
                        $db->prepare("INSERT INTO delivery_trip_items (trip_id, shipment_id) VALUES (?,?)")
                           ->execute([$tripId, $sid]);
                        StateTransition::transition($sid, 'ops_complete', $_SESSION['user_id'], 'Tạo chuyến #' . $tripId);
                    }
                    header('Location: ' . BASE_URL . '/?page=ops.trip&msg=created&trip_id=' . $tripId);
                    exit;
                }
            }
        }

        // Lô chờ lấy hàng
        $waitingShipments = $db->query("
            SELECT s.*, c.company_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.status = 'waiting_pickup'
            ORDER BY s.active_date ASC
        ")->fetchAll();

        // Danh sách drivers
        $drivers = $db->query("SELECT id, full_name FROM users WHERE role='driver' AND is_active=1")->fetchAll();

        // Chuyến gần đây
        $recentTrips = $db->query("
            SELECT dt.*, u.full_name as driver_name,
                   COUNT(dti.id) as item_count
            FROM delivery_trips dt
            LEFT JOIN users u ON dt.driver_id = u.id
            LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
            GROUP BY dt.id
            ORDER BY dt.id DESC
            LIMIT 10
        ")->fetchAll();

        $viewTitle = 'Tạo chuyến giao hàng';
        $viewFile  = __DIR__ . '/../views/ops/trip.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== CHI PHÍ =====
    public function costs() {
        $db  = getDB();
        $msg = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shipmentId = (int)$_POST['shipment_id'];
            $costs      = $_POST['costs'] ?? [];

            // Xóa cũ, insert mới
            $db->prepare("DELETE FROM shipment_costs WHERE shipment_id=? AND source='ops'")->execute([$shipmentId]);
            foreach ($costs as $cost) {
                if (empty($cost['name']) || !isset($cost['amount'])) continue;
                $db->prepare("INSERT INTO shipment_costs (shipment_id, cost_name, amount, source, created_by) VALUES (?,?,?,'ops',?)")
                   ->execute([$shipmentId, trim($cost['name']), (float)$cost['amount'], $_SESSION['user_id']]);
            }
            header('Location: ' . BASE_URL . '/?page=ops.costs&id=' . $shipmentId . '&msg=saved');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $shipment = null;
        $existingCosts = [];

        if ($id) {
            $stmt = $db->prepare("SELECT s.*, c.company_name FROM shipments s LEFT JOIN customers c ON s.customer_id=c.id WHERE s.id=?");
            $stmt->execute([$id]);
            $shipment = $stmt->fetch();

            $cstmt = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
            $cstmt->execute([$id]);
            $existingCosts = $cstmt->fetchAll();
        }

        // Lô delivered chưa có chi phí
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
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== UPLOAD ẢNH =====
    public function uploadPhotos() {
        header('Content-Type: application/json');
        if (!isset($_FILES['photos'])) {
            echo json_encode(['success' => false, 'message' => 'Không có file']);
            exit;
        }

        $db         = getDB();
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        $saved      = [];

        foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $mime = mime_content_type($tmp);
            if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) continue;

            // Resize & save
            $ext      = 'jpg';
            $filename = 'photo_' . $shipmentId . '_' . time() . '_' . $i . '.' . $ext;
            $destPath = UPLOAD_PATH . 'photos/' . $filename;

            // GD resize max 1200px
            $src = imagecreatefromstring(file_get_contents($tmp));
            if ($src) {
                [$w, $h] = getimagesize($tmp);
                if ($w > 1200) {
                    $newH = (int)($h * 1200 / $w);
                    $dst  = imagecreatetruecolor(1200, $newH);
                    imagecopyresampled($dst, $src, 0,0,0,0, 1200, $newH, $w, $h);
                    imagejpeg($dst, $destPath, 85);
                    imagedestroy($dst);
                } else {
                    imagejpeg($src, $destPath, 85);
                }
                imagedestroy($src);
            } else {
                move_uploaded_file($tmp, $destPath);
            }

            $db->prepare("INSERT INTO shipment_photos (shipment_id, photo_path, uploaded_by) VALUES (?,?,?)")
               ->execute([$shipmentId, 'uploads/photos/' . $filename, $_SESSION['user_id']]);
            $saved[] = BASE_URL . '/uploads/photos/' . $filename;
        }

        echo json_encode(['success' => true, 'saved' => count($saved), 'urls' => $saved]);
        exit;
    }

    // ===== COMPLETE (chuyển delivered) =====
    public function complete() {
        header('Content-Type: application/json');
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        StateTransition::transition($shipmentId, 'auto_to_kt', $_SESSION['user_id']);
        echo json_encode(['success' => true]);
        exit;
    }
}