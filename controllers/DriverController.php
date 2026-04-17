<?php
class DriverController {

    // ===== DASHBOARD =====
    public function dashboard() {
        $db      = getDB();
        $today   = date('Y-m-d');
        $driverId = $_SESSION['user_id'];

        // Chuyến hôm nay
        $stmt = $db->prepare("
            SELECT dt.*,
                   u.full_name as ops_name,
                   COUNT(dti.id) as total_items,
                   SUM(CASE WHEN s.status='delivered' THEN 1 ELSE 0 END) as delivered_items
            FROM delivery_trips dt
            LEFT JOIN users u ON dt.ops_id = u.id
            LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
            LEFT JOIN shipments s ON dti.shipment_id = s.id
            WHERE dt.driver_id = ? AND dt.trip_date = ?
            GROUP BY dt.id
            ORDER BY dt.id DESC
        ");
        $stmt->execute([$driverId, $today]);
        $todayTrips = $stmt->fetchAll();

        // Chuyến đang chạy (pending/in_progress)
        $stmt2 = $db->prepare("
            SELECT dt.*,
                   COUNT(dti.id) as total_items,
                   SUM(CASE WHEN s.status='delivered' THEN 1 ELSE 0 END) as delivered_items
            FROM delivery_trips dt
            LEFT JOIN delivery_trip_items dti ON dt.id = dti.trip_id
            LEFT JOIN shipments s ON dti.shipment_id = s.id
            WHERE dt.driver_id = ?
              AND dt.status IN ('pending','in_progress')
              AND dt.trip_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            GROUP BY dt.id
            ORDER BY dt.trip_date DESC
        ");
        $stmt2->execute([$driverId]);
        $activeTrips = $stmt2->fetchAll();

        // Stats
        $stats = [
            'today_trips'    => count($todayTrips),
            'pending'        => count(array_filter($activeTrips, fn($t) => $t['status'] === 'pending')),
            'delivered_today' => $db->prepare("
                SELECT COUNT(*) FROM shipments s
                JOIN delivery_trip_items dti ON s.id = dti.shipment_id
                JOIN delivery_trips dt ON dti.trip_id = dt.id
                WHERE dt.driver_id = ? AND s.status = 'delivered' AND DATE(s.updated_at) = ?
            ")->execute([$driverId, $today]) ? 0 : 0,
        ];
        // Đếm lại cho đúng
        $stDelivered = $db->prepare("
            SELECT COUNT(*) FROM shipments s
            JOIN delivery_trip_items dti ON s.id = dti.shipment_id
            JOIN delivery_trips dt ON dti.trip_id = dt.id
            WHERE dt.driver_id = ? AND s.status='delivered' AND DATE(s.updated_at)=?
        ");
        $stDelivered->execute([$driverId, $today]);
        $stats['delivered_today'] = (int)$stDelivered->fetchColumn();

        $viewTitle = 'Chuyến của tôi';
        $viewFile  = __DIR__ . '/../views/driver/dashboard.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== CHI TIẾT CHUYẾN =====
    public function tripDetail() {
        $db       = getDB();
        $tripId   = (int)($_GET['id'] ?? 0);
        $driverId = $_SESSION['user_id'];

        // Lấy chuyến - kiểm tra quyền
        $stmt = $db->prepare("
            SELECT dt.*, u.full_name as ops_name
            FROM delivery_trips dt
            LEFT JOIN users u ON dt.ops_id = u.id
            WHERE dt.id = ? AND dt.driver_id = ?
        ");
        $stmt->execute([$tripId, $driverId]);
        $trip = $stmt->fetch();

        if (!$trip) {
            header('Location: ' . BASE_URL . '/?page=driver.dashboard');
            exit;
        }

        // Danh sách lô trong chuyến
        $stmt2 = $db->prepare("
            SELECT s.*, c.company_name, c.address as customer_address, c.phone as customer_phone,
                   ds.signature_path, ds.signed_by_name, ds.signed_at,
                   dti.id as item_id
            FROM delivery_trip_items dti
            JOIN shipments s ON dti.shipment_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN delivery_signatures ds ON ds.shipment_id = s.id AND ds.trip_id = ?
            WHERE dti.trip_id = ?
            ORDER BY dti.id ASC
        ");
        $stmt2->execute([$tripId, $tripId]);
        $items = $stmt2->fetchAll();

        // Tính tiến độ
        $totalItems     = count($items);
        $deliveredItems = count(array_filter($items, fn($i) => $i['status'] === 'delivered'));

        $viewTitle = 'Chuyến #' . $tripId;
        $viewFile  = __DIR__ . '/../views/driver/trip_detail.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== HIỆN TRANG CHỮ KÝ =====
    public function showSignature() {
        $db         = getDB();
        $shipmentId = (int)($_GET['shipment_id'] ?? 0);
        $tripId     = (int)($_GET['trip_id'] ?? 0);

        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.address as customer_address
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$shipmentId]);
        $shipment = $stmt->fetch();

        if (!$shipment) {
            header('Location: ' . BASE_URL . '/?page=driver.dashboard');
            exit;
        }

        $viewTitle = 'Lấy chữ ký';
        $viewFile  = __DIR__ . '/../views/driver/signature.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }

    // ===== LƯU CHỮ KÝ =====
    public function saveSignature() {
        header('Content-Type: application/json');
        $db = getDB();

        $shipmentId  = (int)($_POST['shipment_id'] ?? 0);
        $tripId      = (int)($_POST['trip_id'] ?? 0);
        $signedName  = trim($_POST['signed_name'] ?? '');
        $signatureB64 = $_POST['signature_data'] ?? '';

        if (!$shipmentId || !$signedName || !$signatureB64) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin!']);
            exit;
        }

        // Decode base64 → lưu PNG
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $signatureB64);
        $imgData    = base64_decode($base64Data);
        if (!$imgData) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu chữ ký không hợp lệ!']);
            exit;
        }

        $filename = 'sig_' . $shipmentId . '_' . $tripId . '_' . time() . '.png';
        $destPath = UPLOAD_PATH . 'signatures/' . $filename;
        file_put_contents($destPath, $imgData);

        // Insert/update delivery_signatures
        $checkStmt = $db->prepare("SELECT id FROM delivery_signatures WHERE shipment_id=? AND trip_id=?");
        $checkStmt->execute([$shipmentId, $tripId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $db->prepare("
                UPDATE delivery_signatures
                SET signature_path=?, signed_by_name=?, signed_at=NOW(), signed_by_driver=?
                WHERE id=?
            ")->execute(['uploads/signatures/' . $filename, $signedName, $_SESSION['user_id'], $existing['id']]);
        } else {
            $db->prepare("
                INSERT INTO delivery_signatures
                (shipment_id, trip_id, signature_path, signed_by_name, signed_at, signed_by_driver)
                VALUES (?,?,?,?,NOW(),?)
            ")->execute([$shipmentId, $tripId, 'uploads/signatures/' . $filename, $signedName, $_SESSION['user_id']]);
        }

        // Chuyển trạng thái → delivered
        StateTransition::transition($shipmentId, 'customer_signature', $_SESSION['user_id'], 'Ký nhận bởi: ' . $signedName);

        // Kiểm tra tất cả lô trong chuyến đã delivered → đóng chuyến
        $pendingStmt = $db->prepare("
            SELECT COUNT(*) FROM delivery_trip_items dti
            JOIN shipments s ON dti.shipment_id = s.id
            WHERE dti.trip_id = ? AND s.status != 'delivered'
        ");
        $pendingStmt->execute([$tripId]);
        if ($pendingStmt->fetchColumn() == 0) {
            $db->prepare("UPDATE delivery_trips SET status='completed' WHERE id=?")
               ->execute([$tripId]);
        }

        echo json_encode([
            'success'  => true,
            'message'  => 'Đã lưu chữ ký thành công!',
            'redirect' => BASE_URL . '/?page=driver.delivery_confirm&shipment_id=' . $shipmentId . '&trip_id=' . $tripId,
        ]);
        exit;
    }

    // ===== XÁC NHẬN GIAO HÀNG =====
    public function deliveryConfirm() {
        $db         = getDB();
        $shipmentId = (int)($_GET['shipment_id'] ?? 0);
        $tripId     = (int)($_GET['trip_id'] ?? 0);

        $stmt = $db->prepare("
            SELECT s.*, c.company_name,
                   ds.signature_path, ds.signed_by_name, ds.signed_at
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN delivery_signatures ds ON ds.shipment_id = s.id AND ds.trip_id = ?
            WHERE s.id = ?
        ");
        $stmt->execute([$tripId, $shipmentId]);
        $shipment = $stmt->fetch();

        $viewTitle = 'Giao thành công';
        $viewFile  = __DIR__ . '/../views/driver/delivery_confirm.php';
        include __DIR__ . '/../views/layouts/mobile.php';
    }
}