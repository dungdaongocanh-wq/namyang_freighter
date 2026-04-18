<?php
class ReportController {

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
