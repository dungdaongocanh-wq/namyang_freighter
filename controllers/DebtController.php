<?php
class DebtController {

    // ===== DANH SÁCH CÔNG NỢ (theo KH) =====
    public function index() {
        requireRole(['admin', 'accounting', 'customer']);

        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        $month = $_GET['month'] ?? date('Y-m');

        if ($role === 'customer') {
            // KH chỉ xem công nợ của mình
            $customerId = $_SESSION['customer_id'];

            $stmt = $db->prepare("
                SELECT s.customer_id, s.customer_code,
                       c.company_name, c.email, c.phone,
                       COUNT(s.id)                        as shipment_count,
                       COALESCE(SUM(sc.amount), 0)        as total_amount,
                       MIN(s.active_date)                 as from_date,
                       MAX(s.active_date)                 as to_date
                FROM shipments s
                LEFT JOIN customers c  ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.customer_id = ?
                  AND s.status IN ('debt', 'invoiced')
                  AND DATE_FORMAT(s.active_date, '%Y-%m') = ?
                GROUP BY s.customer_id
            ");
            $stmt->execute([$customerId, $month]);
        } else {
            // Admin / KT xem tất cả
            $stmt = $db->prepare("
                SELECT s.customer_id, s.customer_code,
                       c.company_name, c.email, c.phone,
                       COUNT(s.id)                        as shipment_count,
                       COALESCE(SUM(sc.amount), 0)        as total_amount,
                       MIN(s.active_date)                 as from_date,
                       MAX(s.active_date)                 as to_date
                FROM shipments s
                LEFT JOIN customers c  ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.status IN ('debt', 'invoiced')
                  AND DATE_FORMAT(s.active_date, '%Y-%m') = ?
                GROUP BY s.customer_id
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$month]);
        }
        $debtByCustomer = $stmt->fetchAll();

        $totalDebt = array_sum(array_column($debtByCustomer, 'total_amount'));

        // Tháng có data
        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(active_date, '%Y-%m') as ym
            FROM shipments
            WHERE status IN ('debt', 'invoiced')
            ORDER BY ym DESC
            LIMIT 24
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'Công nợ';
        $viewFile  = __DIR__ . '/../views/accounting/debt.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== CHI TIẾT CÔNG NỢ THEO KH =====
    public function show(int $customerId = 0) {
        requireRole(['admin', 'accounting', 'customer']);

        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        if (!$customerId) {
            $customerId = (int)($_GET['customer_id'] ?? 0);
        }

        // KH chỉ xem của mình
        if ($role === 'customer' && $customerId !== (int)$_SESSION['customer_id']) {
            http_response_code(403);
            echo '<div class="alert alert-danger m-4">❌ Bạn không có quyền xem dữ liệu này!</div>';
            exit;
        }

        $month = $_GET['month'] ?? date('Y-m');

        $custStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $custStmt->execute([$customerId]);
        $customer = $custStmt->fetch();

        if (!$customer) {
            header('Location: ' . BASE_URL . '/?page=accounting.debt&err=not_found');
            exit;
        }

        $stmt = $db->prepare("
            SELECT s.*,
                   COALESCE(SUM(sc.amount), 0)                                          as total_cost,
                   GROUP_CONCAT(sc.cost_name, ':', sc.amount ORDER BY sc.id SEPARATOR '|') as cost_detail
            FROM shipments s
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.customer_id = ?
              AND s.status IN ('debt', 'invoiced')
              AND DATE_FORMAT(s.active_date, '%Y-%m') = ?
            GROUP BY s.id
            ORDER BY s.active_date ASC
        ");
        $stmt->execute([$customerId, $month]);
        $shipments   = $stmt->fetchAll();
        $totalAmount = array_sum(array_column($shipments, 'total_cost'));

        $months = $db->prepare("
            SELECT DISTINCT DATE_FORMAT(active_date, '%Y-%m') as ym
            FROM shipments
            WHERE customer_id = ? AND status IN ('debt', 'invoiced')
            ORDER BY ym DESC LIMIT 24
        ");
        $months->execute([$customerId]);
        $months = $months->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'Chi tiết công nợ — ' . htmlspecialchars($customer['company_name']);
        $viewFile  = __DIR__ . '/../views/accounting/invoice.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== ĐÁNH DẤU ĐÃ THANH TOÁN =====
    public function markPaid() {
        requireRole(['admin', 'accounting']);

        $db         = getDB();
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        $month      = $_POST['month'] ?? date('Y-m');
        $customerId = (int)($_POST['customer_id'] ?? 0);

        if (!$shipmentId && !$customerId) {
            header('Location: ' . BASE_URL . '/?page=accounting.debt&err=invalid');
            exit;
        }

        if ($shipmentId) {
            // Đánh dấu 1 lô
            StateTransition::transition($shipmentId, 'month_close', $_SESSION['user_id'], 'Đánh dấu đã thanh toán');
        } else {
            // Đánh dấu toàn bộ công nợ của KH trong tháng
            $stmt = $db->prepare("
                SELECT id FROM shipments
                WHERE customer_id = ?
                  AND status = 'debt'
                  AND DATE_FORMAT(active_date, '%Y-%m') = ?
            ");
            $stmt->execute([$customerId, $month]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($ids as $sid) {
                StateTransition::transition($sid, 'month_close', $_SESSION['user_id'], 'Chốt tháng ' . $month);
            }
        }

        header('Location: ' . BASE_URL . '/?page=accounting.debt&month=' . $month . '&msg=saved');
        exit;
    }

    // ===== XUẤT EXCEL CÔNG NỢ =====
    public function export() {
        requireRole(['admin', 'accounting']);

        $db    = getDB();
        $month = $_GET['month'] ?? date('Y-m');

        $stmt = $db->prepare("
            SELECT c.customer_code, c.company_name, c.email, c.phone,
                   s.hawb, s.mawb, s.active_date, s.packages, s.weight,
                   COALESCE(SUM(sc.amount), 0) as total_cost
            FROM shipments s
            LEFT JOIN customers c  ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.status IN ('debt', 'invoiced')
              AND DATE_FORMAT(s.active_date, '%Y-%m') = ?
            GROUP BY s.id
            ORDER BY c.customer_code, s.active_date
        ");
        $stmt->execute([$month]);
        $rows = $stmt->fetchAll();

        $exporter = new ExcelExport();
        $exporter->exportDebt($rows, $month);
        exit;
    }

    // ===== DISPATCH =====
    public function dispatch() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        switch ($action) {
            case 'mark_paid':
                $this->markPaid();
                break;
            case 'export':
                $this->export();
                break;
            case 'show':
                $this->show($id);
                break;
            default:
                $this->index();
        }
    }
}
