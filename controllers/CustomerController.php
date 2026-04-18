<?php
class CustomerController {

    // ===== DASHBOARD =====
    public function dashboard() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        $stats = [
            'total'            => 0,
            'pending_customs'  => 0,
            'in_transit'       => 0,
            'pending_approval' => 0,
            'debt'             => 0,
        ];

        if (in_array($role, ['cs', 'admin'])) {
            // CS/Admin: thống kê tất cả lô
            $stStmt = $db->query("
                SELECT status, COUNT(*) as cnt
                FROM shipments
                GROUP BY status
            ");
            $statusCounts = $stStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $stats['total']            = array_sum($statusCounts);
            $stats['pending_customs']  = $statusCounts['pending_customs'] ?? 0;
            $stats['in_transit']       = $statusCounts['in_transit'] ?? 0;
            $stats['pending_approval'] = $statusCounts['pending_approval'] ?? 0;
            $stats['debt']             = ($statusCounts['debt'] ?? 0) + ($statusCounts['invoiced'] ?? 0);

            $urgentStmt = $db->query("
                SELECT s.*,
                       c.company_name, c.customer_code,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.status = 'pending_approval'
                GROUP BY s.id
                ORDER BY s.updated_at ASC
                LIMIT 5
            ");
            $urgentList = $urgentStmt->fetchAll();

            $recentStmt = $db->query("
                SELECT s.*,
                       c.company_name, c.customer_code,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                GROUP BY s.id
                ORDER BY s.updated_at DESC
                LIMIT 8
            ");
            $recentShipments = $recentStmt->fetchAll();
        } else {
            $customerId = $_SESSION['customer_id'];

            // Đếm đúng từng status
            $stStmt = $db->prepare("
                SELECT status, COUNT(*) as cnt
                FROM shipments
                WHERE customer_id = ?
                GROUP BY status
            ");
            $stStmt->execute([$customerId]);
            $statusCounts = $stStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $stats['total']            = array_sum($statusCounts);
            $stats['pending_customs']  = $statusCounts['pending_customs'] ?? 0;
            $stats['in_transit']       = $statusCounts['in_transit'] ?? 0;
            $stats['pending_approval'] = $statusCounts['pending_approval'] ?? 0;
            $stats['debt']             = ($statusCounts['debt'] ?? 0) + ($statusCounts['invoiced'] ?? 0);

            // Lô cần duyệt (urgent)
            $urgentStmt = $db->prepare("
                SELECT s.*,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.customer_id = ? AND s.status = 'pending_approval'
                GROUP BY s.id
                ORDER BY s.updated_at ASC
                LIMIT 5
            ");
            $urgentStmt->execute([$customerId]);
            $urgentList = $urgentStmt->fetchAll();

            // Lô gần đây
            $recentStmt = $db->prepare("
                SELECT s.*,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.customer_id = ?
                GROUP BY s.id
                ORDER BY s.updated_at DESC
                LIMIT 8
            ");
            $recentStmt->execute([$customerId]);
            $recentShipments = $recentStmt->fetchAll();
        }

        $viewTitle = 'Dashboard';
        $viewFile  = __DIR__ . '/../views/customer/dashboard.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== DANH SÁCH LÔ =====
    public function shipmentList() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        $where  = [];
        $params = [];

        if (!in_array($role, ['cs', 'admin'])) {
            $where[]  = 's.customer_id = ?';
            $params[] = $_SESSION['customer_id'];
        }

        if (!empty($_GET['search'])) {
            $where[]  = "(s.hawb LIKE ? OR s.mawb LIKE ?)";
            $kw = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$kw, $kw]);
        }
        if (!empty($_GET['status'])) {
            $where[]  = "s.status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['month'])) {
            $where[]  = "DATE_FORMAT(s.active_date,'%Y-%m') = ?";
            $params[] = $_GET['month'];
        }

        $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $perPage  = 20;
        $page     = max(1, (int)($_GET['p'] ?? 1));
        $offset   = ($page - 1) * $perPage;

        $countStmt = $db->prepare("SELECT COUNT(*) FROM shipments s $whereStr");
        $countStmt->execute($params);
        $totalRows = $countStmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT s.*,
                   c.company_name, c.customer_code as cust_code,
                   COALESCE(SUM(sc.amount),0) as total_cost
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            $whereStr
            GROUP BY s.id
            ORDER BY s.active_date DESC, s.id DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $shipments  = $stmt->fetchAll();
        $totalPages = ceil($totalRows / $perPage);

        // Tháng filter
        if (in_array($role, ['cs', 'admin'])) {
            $monthsStmt = $db->query("
                SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
                FROM shipments
                ORDER BY ym DESC LIMIT 24
            ");
            $months = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $customerId = $_SESSION['customer_id'];
            $monthsStmt = $db->prepare("
                SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
                FROM shipments WHERE customer_id=?
                ORDER BY ym DESC LIMIT 24
            ");
            $monthsStmt->execute([$customerId]);
            $months = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $viewTitle = 'Lô hàng của tôi';
        $viewFile  = __DIR__ . '/../views/customer/shipment_list.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== CHI TIẾT LÔ =====
    public function shipmentDetail() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';
        $id   = (int)($_GET['id'] ?? 0);

        if (in_array($role, ['cs', 'admin'])) {
            $stmt = $db->prepare("
                SELECT s.*,
                       c.company_name, c.customer_code as cust_code,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$id]);
        } else {
            $customerId = $_SESSION['customer_id'];
            $stmt = $db->prepare("
                SELECT s.*,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.id = ? AND s.customer_id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$id, $customerId]);
        }
        $shipment = $stmt->fetch();

        if (!$shipment) {
            header('Location: ' . BASE_URL . '/?page=customer.shipment_list');
            exit;
        }

        // Chi phí chi tiết
        $costs = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
        $costs->execute([$id]);
        $costs = $costs->fetchAll();

        // Chữ ký giao hàng
        $sig = $db->prepare("SELECT * FROM delivery_signatures WHERE shipment_id=? ORDER BY id DESC LIMIT 1");
        $sig->execute([$id]);
        $signature = $sig->fetch();

        // Ảnh
        $photos = $db->prepare("SELECT * FROM shipment_photos WHERE shipment_id=? ORDER BY id DESC");
        $photos->execute([$id]);
        $photos = $photos->fetchAll();

        // Lịch sử trạng thái
        $logs = $db->prepare("
            SELECT sl.*, u.full_name
            FROM shipment_logs sl
            LEFT JOIN users u ON sl.user_id = u.id
            WHERE sl.shipment_id = ?
            ORDER BY sl.created_at ASC
        ");
        $logs->execute([$id]);
        $logs = $logs->fetchAll();

        $viewTitle = 'Chi tiết - ' . $shipment['hawb'];
        $viewFile  = __DIR__ . '/../views/customer/shipment_detail.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== DUYỆT CHI PHÍ =====
    public function approve() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Hiển thị trang duyệt chi tiết cho 1 lô
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                header('Location: ' . BASE_URL . '/?page=customer.pending_approval');
                exit;
            }

            if (in_array($role, ['cs', 'admin'])) {
                $stmt = $db->prepare("
                    SELECT s.*,
                           c.company_name, c.customer_code,
                           COALESCE(SUM(sc.amount),0) as total_cost
                    FROM shipments s
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                    WHERE s.id = ? AND s.status = 'pending_approval'
                    GROUP BY s.id
                ");
                $stmt->execute([$id]);
            } else {
                $customerId = $_SESSION['customer_id'];
                $stmt = $db->prepare("
                    SELECT s.*,
                           c.company_name, c.customer_code,
                           COALESCE(SUM(sc.amount),0) as total_cost
                    FROM shipments s
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                    WHERE s.id = ? AND s.customer_id = ? AND s.status = 'pending_approval'
                    GROUP BY s.id
                ");
                $stmt->execute([$id, $customerId]);
            }
            $shipment = $stmt->fetch();

            if (!$shipment) {
                header('Location: ' . BASE_URL . '/?page=customer.pending_approval&err=not_found');
                exit;
            }

            $costs = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
            $costs->execute([$id]);
            $costs = $costs->fetchAll();

            $totalCost = array_sum(array_column($costs, 'amount'));

            $viewTitle = 'Duyệt chi phí — ' . $shipment['hawb'];
            $viewFile  = __DIR__ . '/../views/customer/approve.php';
            include __DIR__ . '/../views/layouts/main.php';
            return;
        }

        // POST → xử lý duyệt
        $id = (int)($_POST['shipment_id'] ?? 0);

        if (in_array($role, ['cs', 'admin'])) {
            // CS/Admin duyệt thay: không cần check customer_id
            $stmt = $db->prepare("SELECT id, customer_id FROM shipments WHERE id=? AND status='pending_approval'");
            $stmt->execute([$id]);
        } else {
            $customerId = $_SESSION['customer_id'];
            $stmt = $db->prepare("SELECT id, customer_id FROM shipments WHERE id=? AND customer_id=? AND status='pending_approval'");
            $stmt->execute([$id, $customerId]);
        }
        $row = $stmt->fetch();
        if (!$row) {
            header('Location: ' . BASE_URL . '/?page=customer.pending_approval&err=invalid');
            exit;
        }

        $approverCustomerId = $row['customer_id'];
        $reason = in_array($role, ['cs', 'admin']) ? 'CS/Admin duyệt thay khách hàng' : 'Khách hàng đồng ý chi phí';

        // Ghi approval_history
        $db->prepare("
            INSERT INTO approval_history (shipment_id, action, customer_id, user_id, reason)
            VALUES (?, 'approved', ?, ?, ?)
        ")->execute([$id, $approverCustomerId, $_SESSION['user_id'], $reason]);

        StateTransition::transition($id, 'customer_approve', $_SESSION['user_id']);

        header('Location: ' . BASE_URL . '/?page=customer.pending_approval&msg=approved');
        exit;
    }

    // ===== TỪ CHỐI CHI PHÍ =====
    public function reject() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Hiển thị trang từ chối cho 1 lô
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                header('Location: ' . BASE_URL . '/?page=customer.pending_approval');
                exit;
            }

            if (in_array($role, ['cs', 'admin'])) {
                $stmt = $db->prepare("
                    SELECT s.*,
                           c.company_name, c.customer_code,
                           COALESCE(SUM(sc.amount),0) as total_cost
                    FROM shipments s
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                    WHERE s.id = ? AND s.status = 'pending_approval'
                    GROUP BY s.id
                ");
                $stmt->execute([$id]);
            } else {
                $customerId = $_SESSION['customer_id'];
                $stmt = $db->prepare("
                    SELECT s.*,
                           c.company_name, c.customer_code,
                           COALESCE(SUM(sc.amount),0) as total_cost
                    FROM shipments s
                    LEFT JOIN customers c ON s.customer_id = c.id
                    LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                    WHERE s.id = ? AND s.customer_id = ? AND s.status = 'pending_approval'
                    GROUP BY s.id
                ");
                $stmt->execute([$id, $customerId]);
            }
            $shipment = $stmt->fetch();

            if (!$shipment) {
                header('Location: ' . BASE_URL . '/?page=customer.pending_approval&err=not_found');
                exit;
            }

            $costs = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id=? ORDER BY id");
            $costs->execute([$id]);
            $costs = $costs->fetchAll();

            $totalCost = array_sum(array_column($costs, 'amount'));

            $viewTitle = 'Từ chối chi phí — ' . $shipment['hawb'];
            $viewFile  = __DIR__ . '/../views/customer/reject.php';
            include __DIR__ . '/../views/layouts/main.php';
            return;
        }

        // POST → xử lý từ chối
        $id     = (int)($_POST['shipment_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$reason) {
            header('Location: ' . BASE_URL . '/?page=customer.pending_approval&err=no_reason');
            exit;
        }

        if (in_array($role, ['cs', 'admin'])) {
            $stmt = $db->prepare("SELECT id, customer_id FROM shipments WHERE id=? AND status='pending_approval'");
            $stmt->execute([$id]);
        } else {
            $customerId = $_SESSION['customer_id'];
            $stmt = $db->prepare("SELECT id, customer_id FROM shipments WHERE id=? AND customer_id=? AND status='pending_approval'");
            $stmt->execute([$id, $customerId]);
        }
        $row = $stmt->fetch();
        if (!$row) {
            header('Location: ' . BASE_URL . '/?page=customer.pending_approval&err=invalid');
            exit;
        }

        $approverCustomerId = $row['customer_id'];

        // Ghi approval_history
        $db->prepare("
            INSERT INTO approval_history (shipment_id, action, customer_id, user_id, reason)
            VALUES (?, 'rejected', ?, ?, ?)
        ")->execute([$id, $approverCustomerId, $_SESSION['user_id'], $reason]);

        StateTransition::transition($id, 'customer_reject', $_SESSION['user_id'], $reason);

        header('Location: ' . BASE_URL . '/?page=customer.pending_approval&msg=rejected');
        exit;
    }

    // ===== DANH SÁCH CHỜ DUYỆT =====
    public function pendingApproval() {
        $db   = getDB();
        $role = $_SESSION['role'] ?? '';

        if (in_array($role, ['cs', 'admin'])) {
            // CS/Admin thấy tất cả lô pending_approval
            $stmt = $db->query("
                SELECT s.*,
                       c.company_name, c.customer_code,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       COUNT(sc.id) as cost_lines
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.status = 'pending_approval'
                GROUP BY s.id
                ORDER BY s.updated_at ASC
            ");
            $pendingList = $stmt->fetchAll();
        } else {
            $customerId = $_SESSION['customer_id'];
            $stmt = $db->prepare("
                SELECT s.*,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       COUNT(sc.id) as cost_lines
                FROM shipments s
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE s.customer_id = ? AND s.status = 'pending_approval'
                GROUP BY s.id
                ORDER BY s.updated_at ASC
            ");
            $stmt->execute([$customerId]);
            $pendingList = $stmt->fetchAll();
        }

        $viewTitle = 'Chờ duyệt chi phí';
        $viewFile  = __DIR__ . '/../views/customer/pending_approval.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== LỊCH SỬ DUYỆT =====
    public function history() {
        $db         = getDB();
        $customerId = $_SESSION['customer_id'];

        $stmt = $db->prepare("
            SELECT ah.*, s.hawb, s.active_date, s.customer_code,
                   COALESCE(SUM(sc.amount),0) as total_cost
            FROM approval_history ah
            JOIN shipments s ON ah.shipment_id = s.id
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE ah.customer_id = ?
            GROUP BY ah.id
            ORDER BY ah.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$customerId]);
        $history = $stmt->fetchAll();

        $viewTitle = 'Lịch sử duyệt';
        $viewFile  = __DIR__ . '/../views/customer/history.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // ===== CÔNG NỢ =====
    public function debt() {
        $db         = getDB();
        $customerId = $_SESSION['customer_id'];

        $month = $_GET['month'] ?? date('Y-m');

        // Công nợ theo tháng
        $stmt = $db->prepare("
            SELECT s.*,
                   COALESCE(SUM(sc.amount),0) as total_cost
            FROM shipments s
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.customer_id = ?
              AND s.status IN ('debt','invoiced')
              AND DATE_FORMAT(s.active_date,'%Y-%m') = ?
            GROUP BY s.id
            ORDER BY s.active_date ASC
        ");
        $stmt->execute([$customerId, $month]);
        $debtShipments = $stmt->fetchAll();

        $totalDebt = array_sum(array_column($debtShipments, 'total_cost'));

        // Tháng có data
        $monthsStmt = $db->prepare("
            SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
            FROM shipments
            WHERE customer_id=? AND status IN ('debt','invoiced')
            ORDER BY ym DESC LIMIT 24
        ");
        $monthsStmt->execute([$customerId]);
        $months = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Tổng tất cả
        $allDebtStmt = $db->prepare("
            SELECT COALESCE(SUM(sc.amount),0)
            FROM shipments s
            LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
            WHERE s.customer_id = ? AND s.status IN ('debt','invoiced')
        ");
        $allDebtStmt->execute([$customerId]);
        $totalAllDebt = $allDebtStmt->fetchColumn();

        $viewTitle = 'Công nợ của tôi';
        $viewFile  = __DIR__ . '/../views/customer/debt.php';
        include __DIR__ . '/../views/layouts/main.php';
    }
}