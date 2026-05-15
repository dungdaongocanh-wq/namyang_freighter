<?php
class StatementController {

    private function getFilters(): array {
        $role = $_SESSION['role'] ?? '';

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';
        $month    = $_GET['month']     ?? '';

        // Nếu chọn tháng, auto fill date_from/to
        if ($month && !$dateFrom && !$dateTo) {
            $dateFrom = $month . '-01';
            $dateTo   = date('Y-m-t', strtotime($dateFrom));
        }

        $customerId = null;
        if ($role === 'customer') {
            $customerId = (int)($_SESSION['customer_id'] ?? 0);
        } else {
            $customerId = (int)($_GET['customer_id'] ?? 0) ?: null;
        }

        return compact('dateFrom', 'dateTo', 'month', 'customerId', 'role');
    }

    private function buildQuery(array $filters): array {
        $where  = ["s.status IN ('debt','invoiced','pending_approval','kt_reviewing')"];
        $params = [];

        if ($filters['customerId']) {
            $where[]  = 's.customer_id = ?';
            $params[] = $filters['customerId'];
        }
        if ($filters['dateFrom']) {
            $where[]  = 's.active_date >= ?';
            $params[] = $filters['dateFrom'];
        }
        if ($filters['dateTo']) {
            $where[]  = 's.active_date <= ?';
            $params[] = $filters['dateTo'];
        }

        return [implode(' AND ', $where), $params];
    }

    public function index() {
        $db      = getDB();
        $filters = $this->getFilters();
        $role    = $filters['role'];

        [$whereStr, $params] = $this->buildQuery($filters);

        // Lấy shipments
        $stmt = $db->prepare("
            SELECT s.id, s.hawb, s.active_date, s.packages, s.weight, s.remark,
                   c.company_name, c.customer_code,
                   GROUP_CONCAT(DISTINCT cd.cd_number ORDER BY cd.id SEPARATOR ', ') as cd_numbers
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN shipment_customs cd ON cd.shipment_id = s.id
            WHERE $whereStr
            GROUP BY s.id
            ORDER BY s.active_date ASC, s.id ASC
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        // Lấy cost groups
        $costGroups = $db->query("
            SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
        ")->fetchAll();

        // Lấy chi phí cho từng shipment, phân theo nhóm
        $costsByShipment = [];
        if (!empty($shipments)) {
            $shipmentIds = array_column($shipments, 'id');
            $inPlaceholders = implode(',', array_fill(0, count($shipmentIds), '?'));

            $costsStmt = $db->prepare("
                SELECT sc.shipment_id, sc.amount,
                       COALESCE(qi.cost_group_id, NULL) as cost_group_id
                FROM shipment_costs sc
                LEFT JOIN quotation_items qi ON sc.quotation_item_id = qi.id
                WHERE sc.shipment_id IN ($inPlaceholders)
                  AND sc.source IN ('kt','quotation','manual','auto')
            ");
            $costsStmt->execute($shipmentIds);
            $allCosts = $costsStmt->fetchAll();

            foreach ($allCosts as $cost) {
                $sid = $cost['shipment_id'];
                $cgId = $cost['cost_group_id'] ?? null;
                if (!isset($costsByShipment[$sid])) {
                    $costsByShipment[$sid] = [];
                }
                $key = $cgId ?? 'ungrouped';
                if (!isset($costsByShipment[$sid][$key])) {
                    $costsByShipment[$sid][$key] = 0;
                }
                $costsByShipment[$sid][$key] += (float)$cost['amount'];
            }
        }

        // Danh sách khách hàng (cho dropdown cs/admin)
        $customers = [];
        if (in_array($role, ['cs', 'admin'])) {
            $customers = $db->query("
                SELECT id, customer_code, company_name FROM customers WHERE is_active=1 ORDER BY customer_code
            ")->fetchAll();
        }

        // Danh sách tháng có dữ liệu
        $months = $db->query("
            SELECT DISTINCT DATE_FORMAT(active_date,'%Y-%m') as ym
            FROM shipments
            WHERE status IN ('debt','invoiced','pending_approval','kt_reviewing')
            ORDER BY ym DESC LIMIT 36
        ")->fetchAll(PDO::FETCH_COLUMN);

        $viewTitle = 'Bảng kê chi tiết';
        $viewFile  = __DIR__ . '/../views/statement/index.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    public function export() {
        $db      = getDB();
        $filters = $this->getFilters();

        [$whereStr, $params] = $this->buildQuery($filters);

        // Lấy shipments
        $stmt = $db->prepare("
            SELECT s.id, s.hawb, s.active_date, s.packages, s.weight, s.remark,
                   c.company_name, c.customer_code,
                   GROUP_CONCAT(DISTINCT cd.cd_number ORDER BY cd.id SEPARATOR ', ') as cd_numbers
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN shipment_customs cd ON cd.shipment_id = s.id
            WHERE $whereStr
            GROUP BY s.id
            ORDER BY s.active_date ASC, s.id ASC
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        // Lấy cost groups
        $costGroups = $db->query("
            SELECT id, name FROM cost_groups WHERE is_active=1 ORDER BY sort_order, id
        ")->fetchAll();

        // Lấy chi phí
        $costsByShipment = [];
        if (!empty($shipments)) {
            $shipmentIds    = array_column($shipments, 'id');
            $inPlaceholders = implode(',', array_fill(0, count($shipmentIds), '?'));

            $costsStmt = $db->prepare("
                SELECT sc.shipment_id, sc.amount,
                       COALESCE(qi.cost_group_id, NULL) as cost_group_id
                FROM shipment_costs sc
                LEFT JOIN quotation_items qi ON sc.quotation_item_id = qi.id
                WHERE sc.shipment_id IN ($inPlaceholders)
                  AND sc.source IN ('kt','quotation','manual','auto')
            ");
            $costsStmt->execute($shipmentIds);
            $allCosts = $costsStmt->fetchAll();

            foreach ($allCosts as $cost) {
                $sid  = $cost['shipment_id'];
                $cgId = $cost['cost_group_id'] ?? null;
                if (!isset($costsByShipment[$sid])) {
                    $costsByShipment[$sid] = [];
                }
                $key = $cgId ?? 'ungrouped';
                if (!isset($costsByShipment[$sid][$key])) {
                    $costsByShipment[$sid][$key] = 0;
                }
                $costsByShipment[$sid][$key] += (float)$cost['amount'];
            }
        }

        // Export Excel
        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statement');

        // Headers
        $headers = ['NO', 'DATE', 'CONSIGNEE', 'HAWB', 'CD NO.', 'PKG', 'GW(KG)'];
        foreach ($costGroups as $cg) {
            $headers[] = strtoupper($cg['name']);
        }

        // Check if there are ungrouped costs
        $hasUngrouped = false;
        foreach ($costsByShipment as $costs) {
            if (isset($costs['ungrouped']) && $costs['ungrouped'] > 0) {
                $hasUngrouped = true;
                break;
            }
        }
        if ($hasUngrouped) {
            $headers[] = 'KHÁC';
        }
        $headers[] = 'TOTAL';
        $headers[] = 'NOTE';

        $col = 1;
        foreach ($headers as $h) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colL . '1', $h);
            $col++;
        }

        // Style header row
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['rgb' => '1e3a5f']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Data rows
        // Fixed columns: NO(1), DATE(2), CONSIGNEE(3), HAWB(4), CD NO.(5), PKG(6), GW(7)
        // Cost group columns start at column 8
        $fixedColCount = 7;
        $row = 2;
        $no  = 1;
        $colTotals = [];

        foreach ($shipments as $s) {
            $sid    = $s['id'];
            $total  = 0;

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '');
            $sheet->setCellValue('C' . $row, $s['company_name'] ?? '');
            $sheet->setCellValue('D' . $row, $s['hawb'] ?? '');
            $sheet->setCellValue('E' . $row, $s['cd_numbers'] ?? '');
            $sheet->setCellValue('F' . $row, (int)$s['packages']);
            $sheet->setCellValue('G' . $row, (float)$s['weight']);

            $cgColIdx = $fixedColCount + 1;
            foreach ($costGroups as $cg) {
                $amount = (float)($costsByShipment[$sid][$cg['id']] ?? 0);
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($colLetter . $row, $amount > 0 ? $amount : '');
                $total += $amount;
                if (!isset($colTotals[$cgColIdx])) $colTotals[$cgColIdx] = 0;
                $colTotals[$cgColIdx] += $amount;
                $cgColIdx++;
            }

            // Ungrouped costs
            $ungrouped = (float)($costsByShipment[$sid]['ungrouped'] ?? 0);
            $total    += $ungrouped;
            if ($hasUngrouped) {
                $ungroupedColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($ungroupedColLetter . $row, $ungrouped > 0 ? $ungrouped : '');
                if (!isset($colTotals['ungrouped'])) $colTotals['ungrouped'] = 0;
                $colTotals['ungrouped'] += $ungrouped;
                $cgColIdx++;
            }

            $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $noteColLetter  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx + 1);

            $sheet->setCellValue($totalColLetter . $row, $total > 0 ? $total : '');
            $sheet->setCellValue($noteColLetter  . $row, $s['remark'] ?? '');

            if (!isset($colTotals['total'])) $colTotals['total'] = 0;
            $colTotals['total'] += $total;

            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $cgColIdx = $fixedColCount + 1;
        foreach ($costGroups as $cg) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->setCellValue($colLetter . $row, $colTotals[$cgColIdx] ?? 0);
            $cgColIdx++;
        }
        if ($hasUngrouped) {
            $ungroupedColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->setCellValue($ungroupedColLetter . $row, $colTotals['ungrouped'] ?? 0);
            $cgColIdx++;
        }
        $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
        $sheet->setCellValue($totalColLetter . $row, $colTotals['total'] ?? 0);

        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getFont()->setBold(true);

        // Auto-size columns
        for ($i = 1; $i <= count($headers); $i++) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colL)->setAutoSize(true);
        }

        // Number format for cost columns
        $numFmt = '#,##0';
        $cgColIdx = $fixedColCount + 1;
        foreach ($costGroups as $cg) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->getStyle($colL . '2:' . $colL . $row)->getNumberFormat()->setFormatCode($numFmt);
            $cgColIdx++;
        }
        if ($hasUngrouped) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->getStyle($colL . '2:' . $colL . $row)->getNumberFormat()->setFormatCode($numFmt);
            $cgColIdx++;
        }
        $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
        $sheet->getStyle($totalColL . '2:' . $totalColL . $row)->getNumberFormat()->setFormatCode($numFmt);

        // Tên file
        $custCode = '';
        if ($filters['customerId']) {
            $custStmt = $db->prepare("SELECT customer_code FROM customers WHERE id=?");
            $custStmt->execute([$filters['customerId']]);
            $custCode = $custStmt->fetchColumn() ?: '';
        }
        $dfStr = str_replace('-', '', $filters['dateFrom'] ?: date('Ymd'));
        $dtStr = str_replace('-', '', $filters['dateTo']   ?: date('Ymd'));
        $filename = 'statement' . ($custCode ? '_' . $custCode : '') . '_' . $dfStr . '_' . $dtStr . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
