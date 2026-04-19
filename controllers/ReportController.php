<?php
class ReportController {

    // ===== PRIVATE HELPERS =====

    /**
     * Resolve date range từ các tham số GET.
     * Hỗ trợ: date_from/date_to trực tiếp, week=this/last, month=Y-m
     */
    private function resolveDateRange(string $from, string $to, string $week, string $month): array
    {
        if ($week === 'this') {
            $monday = date('Y-m-d', strtotime('monday this week'));
            $sunday = date('Y-m-d', strtotime('sunday this week'));
            return [$monday, $sunday];
        }
        if ($week === 'last') {
            $monday = date('Y-m-d', strtotime('monday last week'));
            $sunday = date('Y-m-d', strtotime('sunday last week'));
            return [$monday, $sunday];
        }
        if ($month && !$from && !$to) {
            $from = $month . '-01';
            $to   = date('Y-m-t', strtotime($from));
        }
        return [$from, $to];
    }

    /**
     * Load cost groups & costs grouped per shipment.
     * Returns [$costGroups, $costsByShipment]
     */
    private function loadCostsByShipment(\PDO $db, array $shipments): array
    {
        $costGroups = $db->query("
            SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
        ")->fetchAll();

        $costsByShipment = [];
        if (!empty($shipments)) {
            $ids            = array_column($shipments, 'id');
            $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));

            $costsStmt = $db->prepare("
                SELECT sc.shipment_id, sc.amount,
                       COALESCE(qi.cost_group_id, NULL) as cost_group_id
                FROM shipment_costs sc
                LEFT JOIN quotation_items qi ON sc.source = 'quotation' AND qi.description = sc.cost_name
                WHERE sc.shipment_id IN ($inPlaceholders)
                  AND sc.source IN ('kt','quotation','manual','auto')
            ");
            $costsStmt->execute($ids);
            foreach ($costsStmt->fetchAll() as $cost) {
                $sid  = $cost['shipment_id'];
                $key  = $cost['cost_group_id'] ?? 'ungrouped';
                if (!isset($costsByShipment[$sid])) $costsByShipment[$sid] = [];
                if (!isset($costsByShipment[$sid][$key])) $costsByShipment[$sid][$key] = 0;
                $costsByShipment[$sid][$key] += (float)$cost['amount'];
            }
        }

        return [$costGroups, $costsByShipment];
    }

    // ===== BÁO CÁO LÔ HÀNG ĐÃ XÁC NHẬN =====

    public function shipmentReport()
    {
        $db = getDB();

        $dateFrom   = $_GET['date_from']   ?? '';
        $dateTo     = $_GET['date_to']     ?? '';
        $week       = $_GET['week']        ?? '';
        $month      = $_GET['month']       ?? '';
        $customerId = (int)($_GET['customer_id'] ?? 0) ?: null;

        [$dateFrom, $dateTo] = $this->resolveDateRange($dateFrom, $dateTo, $week, $month);

        $filtered = $dateFrom || $dateTo || $customerId;

        // Nếu export Excel
        if ($filtered && isset($_GET['export']) && $_GET['export'] === '1') {
            [$shipments, $costGroups, $costsByShipment] = $this->fetchConfirmedShipments(
                $db, $dateFrom, $dateTo, $customerId
            );
            ExcelExport::exportShipmentReport($shipments, $costGroups, $costsByShipment, $dateFrom, $dateTo);
            exit;
        }

        $shipments       = [];
        $costGroups      = [];
        $costsByShipment = [];

        if ($filtered) {
            [$shipments, $costGroups, $costsByShipment] = $this->fetchConfirmedShipments(
                $db, $dateFrom, $dateTo, $customerId
            );
        } else {
            $costGroups = $db->query("
                SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
            ")->fetchAll();
        }

        $customers = $db->query("
            SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code
        ")->fetchAll();

        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
            FROM shipments
            WHERE status IN ('debt','invoiced')
            ORDER BY ym DESC LIMIT 36
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'BC Lô đã xác nhận';
        $viewFile  = __DIR__ . '/../views/report/shipment_report.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    private function fetchConfirmedShipments(\PDO $db, string $dateFrom, string $dateTo, ?int $customerId): array
    {
        $where  = ["s.status IN ('debt','invoiced')"];
        $params = [];

        if ($customerId) {
            $where[]  = 's.customer_id = ?';
            $params[] = $customerId;
        }
        if ($dateFrom) {
            $where[]  = 's.active_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where[]  = 's.active_date <= ?';
            $params[] = $dateTo;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT s.id, s.hawb, s.active_date, s.packages, s.weight, s.remark,
                   c.company_name, c.customer_code,
                   GROUP_CONCAT(DISTINCT cd.cd_number ORDER BY cd.id SEPARATOR ', ') as cd_numbers
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN customs_declarations cd ON cd.shipment_id = s.id
            WHERE $whereStr
            GROUP BY s.id
            ORDER BY s.active_date ASC, s.id ASC
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        [$costGroups, $costsByShipment] = $this->loadCostsByShipment($db, $shipments);

        return [$shipments, $costGroups, $costsByShipment];
    }

    // ===== BÁO CÁO CHI PHÍ OPS =====

    public function opsCostReport()
    {
        $db = getDB();

        $dateFrom  = $_GET['date_from']    ?? '';
        $dateTo    = $_GET['date_to']      ?? '';
        $week      = $_GET['week']         ?? '';
        $month     = $_GET['month']        ?? '';
        $opsUserId = (int)($_GET['ops_user_id'] ?? 0) ?: null;

        [$dateFrom, $dateTo] = $this->resolveDateRange($dateFrom, $dateTo, $week, $month);

        $filtered = $dateFrom || $dateTo || $opsUserId;

        // Nếu export Excel
        if ($filtered && isset($_GET['export']) && $_GET['export'] === '1') {
            [$shipmentsByOps, $costsByOpsShipment, $costGroups] = $this->fetchOpsCosts(
                $db, $dateFrom, $dateTo, $opsUserId
            );
            ExcelExport::exportOpsCosts($shipmentsByOps, $costsByOpsShipment, $costGroups, $dateFrom, $dateTo);
            exit;
        }

        $shipmentsByOps     = [];
        $costsByOpsShipment = [];
        $costGroups         = [];

        if ($filtered) {
            [$shipmentsByOps, $costsByOpsShipment, $costGroups] = $this->fetchOpsCosts(
                $db, $dateFrom, $dateTo, $opsUserId
            );
        } else {
            $costGroups = $db->query("
                SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
            ")->fetchAll();
        }

        $opsUsers = $db->query("
            SELECT id, full_name FROM users WHERE role='ops' AND is_active=1 ORDER BY full_name
        ")->fetchAll();

        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(s.active_date,'%Y-%m') as ym
            FROM shipments s
            JOIN shipment_costs sc ON sc.shipment_id = s.id AND sc.source = 'ops'
            JOIN users u ON u.id = sc.created_by AND u.role = 'ops' AND u.is_active = 1
            ORDER BY ym DESC LIMIT 36
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'BC Chi phí OPS';
        $viewFile  = __DIR__ . '/../views/report/ops_costs.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    private function fetchOpsCosts(\PDO $db, string $dateFrom, string $dateTo, ?int $opsUserId): array
    {
        $costGroups = $db->query("
            SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
        ")->fetchAll();

        $where  = ['1=1'];
        $params = [];

        if ($opsUserId) {
            $where[]  = 'sc.created_by = ?';
            $params[] = $opsUserId;
        }
        if ($dateFrom) {
            $where[]  = 's.active_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where[]  = 's.active_date <= ?';
            $params[] = $dateTo;
        }

        $whereStr = implode(' AND ', $where);

        // Query 1: shipments grouped by (shipment, ops_user)
        $stmt = $db->prepare("
            SELECT s.id, s.hawb, s.active_date, s.packages, s.weight, s.remark,
                   c.company_name, c.customer_code,
                   GROUP_CONCAT(DISTINCT cd.cd_number ORDER BY cd.id SEPARATOR ', ') as cd_numbers,
                   sc.created_by as ops_user_id,
                   u.full_name   as ops_name
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN customs_declarations cd ON cd.shipment_id = s.id
            JOIN shipment_costs sc ON sc.shipment_id = s.id AND sc.source = 'ops'
            JOIN users u ON u.id = sc.created_by AND u.role = 'ops' AND u.is_active = 1
            WHERE $whereStr
            GROUP BY s.id, sc.created_by
            ORDER BY u.full_name ASC, s.active_date ASC, s.id ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Group shipments by ops user
        $shipmentsByOps = [];
        foreach ($rows as $row) {
            $opsId = $row['ops_user_id'];
            if (!isset($shipmentsByOps[$opsId])) {
                $shipmentsByOps[$opsId] = [
                    'ops_name'  => $row['ops_name'],
                    'shipments' => [],
                ];
            }
            $shipmentsByOps[$opsId]['shipments'][] = $row;
        }

        // Query 2: cost breakdown per (shipment, ops_user, cost_group)
        $stmt2 = $db->prepare("
            SELECT sc.shipment_id, sc.created_by as ops_user_id, sc.amount,
                   NULL as cost_group_id
            FROM shipment_costs sc
            JOIN users u ON u.id = sc.created_by AND u.role = 'ops' AND u.is_active = 1
            JOIN shipments s ON s.id = sc.shipment_id
            WHERE $whereStr
              AND sc.source = 'ops'
        ");
        $stmt2->execute($params);

        $costsByOpsShipment = [];
        foreach ($stmt2->fetchAll() as $cost) {
            $opsId = $cost['ops_user_id'];
            $sid   = $cost['shipment_id'];
            $key   = $cost['cost_group_id'] ?? 'ungrouped';
            if (!isset($costsByOpsShipment[$opsId])) $costsByOpsShipment[$opsId] = [];
            if (!isset($costsByOpsShipment[$opsId][$sid])) $costsByOpsShipment[$opsId][$sid] = [];
            if (!isset($costsByOpsShipment[$opsId][$sid][$key])) $costsByOpsShipment[$opsId][$sid][$key] = 0;
            $costsByOpsShipment[$opsId][$sid][$key] += (float)$cost['amount'];
        }

        return [$shipmentsByOps, $costsByOpsShipment, $costGroups];
    }

    // ===== EXPORT (giữ nguyên) =====

    public function export() {
        $db = getDB();

        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['date_from'])) {
            $where[]  = "s.active_date >= ?";
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $where[]  = "s.active_date <= ?";
            $params[] = $_GET['date_to'];
        }
        if (!empty($_GET['customer_id'])) {
            $where[]  = "s.customer_id = ?";
            $params[] = (int)$_GET['customer_id'];
        }
        if (!empty($_GET['status'])) {
            $where[]  = "s.status = ?";
            $params[] = $_GET['status'];
        }

        $whereStr = implode(' AND ', $where);

        // Nếu POST action=download → xuất Excel
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'download') {
            $stmt = $db->prepare("
                SELECT s.hawb, s.mawb, s.flight_no, s.customer_code,
                       c.company_name, s.packages, s.weight,
                       s.eta, s.active_date, s.status,
                       COALESCE(SUM(sc.amount),0) as total_cost,
                       s.remark
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE $whereStr
                GROUP BY s.id
                ORDER BY s.active_date DESC
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $exporter = new ExcelExport();
            $exporter->exportReport($rows);
            exit;
        }

        // GET → hiển thị form + preview
        $customers = $db->query("SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code")->fetchAll();

        $shipments = [];
        if (!empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['customer_id'])) {
            $stmt = $db->prepare("
                SELECT s.hawb, s.mawb, s.customer_code, c.company_name,
                       s.packages, s.weight, s.active_date, s.status,
                       COALESCE(SUM(sc.amount),0) as total_cost
                FROM shipments s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN shipment_costs sc ON s.id = sc.shipment_id
                WHERE $whereStr
                GROUP BY s.id
                ORDER BY s.active_date DESC
                LIMIT 200
            ");
            $stmt->execute($params);
            $shipments = $stmt->fetchAll();
        }

        $viewTitle = 'Xuất báo cáo';
        $viewFile  = __DIR__ . '/../views/report/export.php';
        include __DIR__ . '/../views/layouts/main.php';
    }
}
