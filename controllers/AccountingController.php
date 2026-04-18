<?php
class AccountingController {

    // ===== DASHBOARD =====
    public function dashboard() {
        $db = getDB();

        $stats = [
            'kt_reviewing'     => $db->query("SELECT COUNT(*) FROM shipments WHERE status='kt_reviewing'")->fetchColumn(),
            'pending_approval' => $db->query("SELECT COUNT(*) FROM shipments WHERE status='pending_approval'")->fetchColumn(),
            'rejected'         => $db->query("SELECT COUNT(*) FROM shipments WHERE status='rejected'")->fetchColumn(),
            'debt'             => $db->query("SELECT COUNT(*) FROM shipments WHERE status='debt'")->fetchColumn(),
            'total_debt'       => $db->query("
                SELECT COALESCE(SUM(sc.amount),0)
                FROM shipment_costs sc
                JOIN shipments s ON sc.shipment_id = s.id
                WHERE s.status IN ('debt','pending_approval')
            ")->fetchColumn(),
        ];

        // Lô cần xét duyệt gần nhất
        $stmt = $db->query("
            SELECT s.*, c.company_name,
                   COALESCE(SUM(sc.amount),0) as total_cost
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.status = 'kt_reviewing'
            GROUP BY s.id
            ORDER BY s.updated_at ASC
            LIMIT 10
        ");
        $reviewList = $stmt->fetchAll();

        $viewTitle = 'Kế toán Dashboard';
        $viewFile  = __DIR__ . '/../views/accounting/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== XÉT DUYỆT CHI PHÍ =====
    public function review() {
        $db = getDB();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            // Danh sách chờ xét duyệt
            $stmt = $db->query("
                SELECT s.*, c.company_name,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       COUNT(sc.id) as cost_count
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.status = 'kt_reviewing'
                GROUP BY s.id
                ORDER BY s.updated_at ASC
            ");
            $shipments = $stmt->fetchAll();

            $viewTitle = 'Xét duyệt chi phí';
            $viewFile  = __DIR__ . '/../views/accounting/review.php';
            include __DIR__ . '/../views/layouts/main.php';
            return;
        }

        // Chi tiết 1 lô
        $stmt = $db->prepare("
            SELECT s.*, c.company_name, c.email, c.phone
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ? AND s.status = 'kt_reviewing'
        ");
        $stmt->execute([$id]);
        $shipment = $stmt->fetch();

        if (!$shipment) {
            header('Location: ' . BASE_URL . '/?page=accounting.review&err=not_found');
            exit;
        }

        // Tách chi phí theo source
        $costsStmt = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
        $costsStmt->execute([$id]);
        $allCosts = $costsStmt->fetchAll();

        // Chi phí OPS (nội bộ)
        $opsCosts = array_values(array_filter($allCosts, fn($c) => ($c['source'] ?? '') === 'ops'));

        // Chi phí charge KH (quotation + kt + auto + manual)
        $ktCosts = array_values(array_filter($allCosts, fn($c) => ($c['source'] ?? '') !== 'ops'));

        // $costs giữ lại để tương thích với code cũ (toàn bộ)
        $costs = $allCosts;

        // Quotation của KH này + quotation items (ưu tiên báo giá riêng, fallback sang báo giá chung)
        $quotationId = QuotationHelper::getQuotationId($db, $shipment['customer_id'] ? (int)$shipment['customer_id'] : null);

        $quotation = null;
        if ($quotationId) {
            $qoStmt = $db->prepare("SELECT * FROM quotations WHERE id = ?");
            $qoStmt->execute([$quotationId]);
            $quotation = $qoStmt->fetch();
        }

        $quotationItems = [];
        if ($quotation) {
            $qiStmt = $db->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order, id");
            $qiStmt->execute([$quotation['id']]);
            $quotationItems = $qiStmt->fetchAll();
        }

        $viewTitle = 'Xét duyệt - ' . $shipment['hawb'];
        $viewFile  = __DIR__ . '/../views/accounting/review_detail.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== LƯU CHI PHÍ (AJAX) =====
    public function saveCosts() {
        if (ob_get_level()) ob_clean();
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $db         = getDB();
            $shipmentId = (int)($_POST['shipment_id'] ?? 0);

            if (!$shipmentId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thiếu shipment_id']);
                exit;
            }

            // ── 1. Lưu chi phí OPS (nếu có thay đổi số tiền) ──
            $opsCosts = $_POST['ops_costs'] ?? [];
            foreach ($opsCosts as $opsId => $opsData) {
                $opsId = (int)$opsId;
                $amt   = (float)($opsData['amount'] ?? 0);
                if ($opsId > 0) {
                    $db->prepare("UPDATE shipment_costs SET amount=? WHERE id=? AND shipment_id=? AND source='ops'")
                       ->execute([$amt, $opsId, $shipmentId]);
                }
            }

            // ── 2. Xóa chi phí KT/quotation cũ, giữ lại OPS ──
            $db->prepare("DELETE FROM shipment_costs WHERE shipment_id=? AND source IN ('kt','quotation','auto','manual')")
               ->execute([$shipmentId]);

            // ── 3. Insert chi phí KT mới (từ form) ──
            $ktCosts = $_POST['kt_costs'] ?? [];
            foreach ($ktCosts as $c) {
                $name   = trim($c['name'] ?? '');
                $amt    = (float)($c['amount'] ?? 0);
                $source = in_array($c['source'] ?? '', ['kt','quotation','manual','auto']) ? $c['source'] : 'kt';
                if ($name === '' && $amt == 0) continue;
                $db->prepare("INSERT INTO shipment_costs (shipment_id, cost_name, amount, source, created_by) VALUES (?,?,?,?,?)")
                   ->execute([$shipmentId, $name, $amt, $source, $_SESSION['user_id']]);
            }

            // Ghi log
            try {
                $db->prepare("INSERT INTO shipment_logs (shipment_id, triggered_by, note, user_id) VALUES (?,'cost_updated','KT cập nhật chi phí',?)")
                   ->execute([$shipmentId, $_SESSION['user_id']]);
            } catch (Exception $e) {}

            // ── 4. Trả về danh sách chi phí KT mới để cập nhật "Báo giá tham chiếu" ──
            $savedCosts = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? AND source IN ('kt','quotation') ORDER BY id");
            $savedCosts->execute([$shipmentId]);
            $ktSaved = $savedCosts->fetchAll();
            $ktTotal = array_sum(array_column($ktSaved, 'amount'));

            ob_end_clean();
            echo json_encode([
                'success'  => true,
                'kt_costs' => $ktSaved,
                'kt_total' => $ktTotal,
            ]);
        } catch (Throwable $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
        exit;
    }

    // ===== ĐẨY SANG KHÁCH HÀNG =====
    public function pushToCustomer() {
        $db = getDB();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Danh sách lô đã duyệt nội bộ, chưa đẩy KH
            $stmt = $db->query("
                SELECT s.*, c.company_name,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       COUNT(sc.id) as cost_count
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.status IN ('kt_reviewing', 'pending_approval', 'rejected')
                GROUP BY s.id
                ORDER BY s.updated_at ASC
            ");
            $shipments = $stmt->fetchAll();

            $viewTitle = 'Đẩy chi phí sang KH';
            $viewFile  = __DIR__ . '/../views/accounting/push_customer.php';
            include __DIR__ . '/../views/layouts/main.php';
            return;
        }

        // POST
        $action = $_POST['action'] ?? 'push_one';

        if ($action === 'push_selected') {
            // Đẩy nhiều lô đã chọn
            $ids = $_POST['shipment_ids'] ?? [];
            if (empty($ids)) {
                header('Location: ' . BASE_URL . '/?page=accounting.push_customer&err=no_select');
                exit;
            }
            foreach (array_map('intval', $ids) as $sid) {
                $costCount = $db->prepare("SELECT COUNT(*) FROM shipment_costs WHERE shipment_id=? AND source IN ('kt','quotation','manual')");
                $costCount->execute([$sid]);
                if ($costCount->fetchColumn() > 0) {
                    StateTransition::transition($sid, 'kt_push_customer', $_SESSION['user_id']);
                }
            }
            header('Location: ' . BASE_URL . '/?page=accounting.push_customer&msg=pushed');
            exit;
        }

        // Đẩy 1 lô
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);

        // Phải có ít nhất 1 chi phí
        $costCount = $db->prepare("SELECT COUNT(*) FROM shipment_costs WHERE shipment_id=? AND source IN ('kt','quotation','manual')");
        $costCount->execute([$shipmentId]);
        if ($costCount->fetchColumn() == 0) {
            header('Location: ' . BASE_URL . '/?page=accounting.push_customer&err=no_cost');
            exit;
        }

        StateTransition::transition($shipmentId, 'kt_push_customer', $_SESSION['user_id']);
        header('Location: ' . BASE_URL . '/?page=accounting.push_customer&msg=pushed');
        exit;
    }

   // ===== KH TỪ CHỐI - DANH SÁCH =====
public function rejected() {
    $db = getDB();

    $stmt = $db->query("
        SELECT s.*, c.company_name,
               ah.reason as reject_reason,
               ah.created_at as rejected_at,
               COALESCE(SUM(sc.amount),0) as total_cost
        FROM shipments s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
        LEFT JOIN (
            SELECT shipment_id, reason, created_at
            FROM approval_history
            WHERE action = 'rejected'
            ORDER BY created_at DESC
        ) ah ON ah.shipment_id = s.id
        WHERE s.status = 'rejected'
        GROUP BY s.id
        ORDER BY s.updated_at DESC
    ");
    $rejectedList = $stmt->fetchAll();

    $viewTitle = 'KH từ chối';
    $viewFile  = __DIR__ . '/../views/accounting/rejected.php';
    include __DIR__ . '/../views/layouts/main.php';
}

// ===== TÁI GỬI (resubmit) sau khi KH từ chối =====
public function resubmit() {
    $db         = getDB();
    $shipmentId = (int)($_POST['shipment_id'] ?? 0);
    $reason     = trim($_POST['note'] ?? 'KT điều chỉnh và gửi lại');

    // Ghi log vào approval_history đúng cột
    $db->prepare("
        INSERT INTO approval_history (shipment_id, action, customer_id, user_id, reason)
        SELECT ?, 'resubmitted', customer_id, ?, ?
        FROM shipments WHERE id = ?
    ")->execute([$shipmentId, $_SESSION['user_id'], $reason, $shipmentId]);

    StateTransition::transition($shipmentId, 'kt_resubmit', $_SESSION['user_id'], $reason);
    header('Location: ' . BASE_URL . '/?page=accounting.rejected&msg=resubmitted');
    exit;
}

    // ===== CÔNG NỢ =====
    public function debt() {
        $db = getDB();

        // Group by customer + month
        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $db->prepare("
            SELECT s.customer_id, s.customer_code, c.company_name, c.email,
                   COUNT(s.id) as shipment_count,
                   COALESCE(SUM(sc.amount),0) as total_amount,
                   MIN(s.active_date) as from_date,
                   MAX(s.active_date) as to_date
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.status IN ('debt','invoiced')
              AND DATE_FORMAT(s.active_date,'%Y-%m') = ?
            GROUP BY s.customer_id
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$month]);
        $debtByCustomer = $stmt->fetchAll();

        // Tổng
        $totalDebt = array_sum(array_column($debtByCustomer, 'total_amount'));

        // Danh sách tháng có data
        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
            FROM shipments
            WHERE status IN ('debt','invoiced')
            ORDER BY ym DESC
            LIMIT 24
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'Công nợ';
        $viewFile  = __DIR__ . '/../views/accounting/debt.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== CHỐT THÁNG =====
    public function closeMonth() {
        $db    = getDB();
        $month = $_POST['month'] ?? date('Y-m');
        $custId = (int)($_POST['customer_id'] ?? 0);

        // Lấy lô debt của KH trong tháng
        $stmt = $db->prepare("
            SELECT id FROM shipments
            WHERE status = 'debt'
              AND customer_id = ?
              AND DATE_FORMAT(active_date,'%Y-%m') = ?
        ");
        $stmt->execute([$custId, $month]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $sid) {
            StateTransition::transition($sid, 'month_close', $_SESSION['user_id'], 'Chốt tháng ' . $month);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'closed' => count($ids)]);
        exit;
    }

    // ===== HOÁ ĐƠN =====
    public function invoice() {
        $db     = getDB();
        $custId = (int)($_GET['customer_id'] ?? 0);
        $month  = $_GET['month'] ?? date('Y-m');

        $customer = null;
        $shipments = [];
        $totalAmount = 0;

        if ($custId && $month) {
            $stmt = $db->prepare("SELECT * FROM customers WHERE id=?");
            $stmt->execute([$custId]);
            $customer = $stmt->fetch();

            $stmt2 = $db->prepare("
                SELECT s.*,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       GROUP_CONCAT(sc.cost_name, ':', sc.amount ORDER BY sc.id SEPARATOR '|') as cost_detail
                FROM shipments s
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.customer_id = ?
                  AND s.status IN ('debt','invoiced')
                  AND DATE_FORMAT(s.active_date,'%Y-%m') = ?
                GROUP BY s.id
                ORDER BY s.active_date ASC
            ");
            $stmt2->execute([$custId, $month]);
            $shipments   = $stmt2->fetchAll();
            $totalAmount = array_sum(array_column($shipments, 'total_cost'));
        }

        // Danh sách KH có data
        $customerList = $db->query("
            SELECT DISTINCT c.id, c.customer_code, c.company_name
            FROM customers c
            JOIN shipments s ON s.customer_id = c.id
            WHERE s.status IN ('debt','invoiced')
            ORDER BY c.customer_code
        ")->fetchAll();

        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
            FROM shipments WHERE status IN ('debt','invoiced')
            ORDER BY ym DESC LIMIT 24
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'Hoá đơn';
        $viewFile  = __DIR__ . '/../views/accounting/invoice.php';
        include __DIR__ . '/../views/layouts/main.php';
    }
}