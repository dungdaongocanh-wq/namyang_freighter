<?php

/**
 * ExcelExport - Xuất dữ liệu ra file Excel
 *
 * Sử dụng PhpSpreadsheet để tạo file .xlsx và trả về download cho trình duyệt.
 */
class ExcelExport
{
    /** Đường dẫn autoload của Composer */
    private const AUTOLOAD_PATH = __DIR__ . '/../vendor/autoload.php';

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Xuất danh sách lô hàng ra file Excel.
     *
     * @param array $filters Bộ lọc tùy chọn (status, customer_id, date, search, etd_from, etd_to)
     */
    public static function exportShipments(array $filters = []): void
    {
        self::requireSpreadsheet();

        try {
            $shipmentModel = new Shipment();
            $shipments     = $shipmentModel->getAll($filters);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Danh sách lô hàng');

            // Header row
            $headers = [
                'A' => 'Mã KH',
                'B' => 'HAWB',
                'C' => 'MAWB',
                'D' => 'Chuyến bay',
                'E' => 'Cảng xuất',
                'F' => 'Ngày xuất (ETD)',
                'G' => 'Ngày đến (ETA)',
                'H' => 'Số kiện',
                'I' => 'Trọng lượng (kg)',
                'J' => 'Trạng thái',
                'K' => 'Ngày hoạt động',
                'L' => 'Tên khách hàng',
                'M' => 'Ghi chú',
            ];

            self::writeHeaderRow($sheet, $headers);

            // Data rows
            $rowNum = 2;
            foreach ($shipments as $s) {
                $sheet->setCellValue("A{$rowNum}", htmlspecialchars($s['customer_code']  ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("B{$rowNum}", htmlspecialchars($s['hawb']           ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("C{$rowNum}", htmlspecialchars($s['mawb']           ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("D{$rowNum}", htmlspecialchars($s['flight_no']      ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("E{$rowNum}", htmlspecialchars($s['pol']            ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("F{$rowNum}", self::formatDateForExcel($s['etd']    ?? null));
                $sheet->setCellValue("G{$rowNum}", self::formatDateForExcel($s['eta']    ?? null));
                $sheet->setCellValue("H{$rowNum}", (int)($s['packages']                 ?? 0));
                $sheet->setCellValue("I{$rowNum}", (float)($s['weight']                 ?? 0));
                $sheet->setCellValue("J{$rowNum}", self::translateStatus($s['status']   ?? ''));
                $sheet->setCellValue("K{$rowNum}", self::formatDateForExcel($s['active_date'] ?? null));
                $sheet->setCellValue("L{$rowNum}", htmlspecialchars($s['company_name']  ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("M{$rowNum}", htmlspecialchars($s['remark']        ?? '', ENT_QUOTES, 'UTF-8'));
                $rowNum++;
            }

            self::autoSizeColumns($sheet, array_keys($headers));

            $filename = 'lo-hang-' . date('Ymd-His') . '.xlsx';
            self::sendDownload($spreadsheet, $filename);
        } catch (Exception $e) {
            error_log('[ExcelExport::exportShipments] ' . $e->getMessage());
            http_response_code(500);
            echo 'Lỗi xuất file Excel: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Xuất danh sách công nợ của một khách hàng trong một tháng.
     *
     * @param int    $customerId ID khách hàng
     * @param string $month      Tháng định dạng Y-m (vd: 2024-01)
     */
    public static function exportDebt(int $customerId, string $month): void
    {
        self::requireSpreadsheet();

        try {
            $db = getDB();

            // Lấy thông tin khách hàng
            $customerModel = new Customer();
            $customer      = $customerModel->getById($customerId);
            $companyName   = $customer ? htmlspecialchars($customer['company_name'] ?? '', ENT_QUOTES, 'UTF-8') : "KH #{$customerId}";

            // Kiểm tra định dạng tháng hợp lệ (Y-m, vd: 2024-01)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new InvalidArgumentException("Định dạng tháng không hợp lệ: '{$month}'. Yêu cầu: Y-m (vd: 2024-01)");
            }

            // Tính khoảng ngày từ đầu đến cuối tháng
            $dateFrom = $month . '-01';
            $dateTo   = date('Y-m-t', strtotime($dateFrom));

            $stmt = $db->prepare(
                "SELECT s.hawb, s.mawb, s.flight_no, s.etd, s.eta, s.packages, s.weight,
                        s.status, s.active_date,
                        COALESCE(SUM(sc.amount), 0) AS total_cost
                 FROM shipments s
                 LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id
                 WHERE s.customer_id = :cid
                   AND s.status IN ('debt', 'invoiced')
                   AND s.active_date BETWEEN :from AND :to
                 GROUP BY s.id
                 ORDER BY s.active_date ASC"
            );
            $stmt->execute(['cid' => $customerId, 'from' => $dateFrom, 'to' => $dateTo]);
            $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Công nợ tháng ' . $month);

            // Tiêu đề trang
            $sheet->setCellValue('A1', "DANH SÁCH CÔNG NỢ - {$companyName} - THÁNG {$month}");
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

            // Header row (dòng 3)
            $headers = [
                'A' => 'HAWB',
                'B' => 'MAWB',
                'C' => 'Chuyến bay',
                'D' => 'Ngày xuất (ETD)',
                'E' => 'Ngày đến (ETA)',
                'F' => 'Số kiện',
                'G' => 'Trọng lượng (kg)',
                'H' => 'Trạng thái',
                'I' => 'Ngày hoạt động',
                'J' => 'Tổng phí (VND)',
                'K' => 'Ghi chú',
            ];

            self::writeHeaderRow($sheet, $headers, 3);

            // Data rows
            $rowNum   = 4;
            $grandTotal = 0.0;

            foreach ($shipments as $s) {
                $totalCost = (float)$s['total_cost'];
                $grandTotal += $totalCost;

                $sheet->setCellValue("A{$rowNum}", htmlspecialchars($s['hawb']      ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("B{$rowNum}", htmlspecialchars($s['mawb']      ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("C{$rowNum}", htmlspecialchars($s['flight_no'] ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("D{$rowNum}", self::formatDateForExcel($s['etd']         ?? null));
                $sheet->setCellValue("E{$rowNum}", self::formatDateForExcel($s['eta']         ?? null));
                $sheet->setCellValue("F{$rowNum}", (int)($s['packages']                       ?? 0));
                $sheet->setCellValue("G{$rowNum}", (float)($s['weight']                       ?? 0));
                $sheet->setCellValue("H{$rowNum}", self::translateStatus($s['status']         ?? ''));
                $sheet->setCellValue("I{$rowNum}", self::formatDateForExcel($s['active_date'] ?? null));
                $sheet->setCellValue("J{$rowNum}", $totalCost);
                $sheet->setCellValue("K{$rowNum}", '');
                $rowNum++;
            }

            // Dòng tổng cộng
            $sheet->setCellValue("A{$rowNum}", 'TỔNG CỘNG');
            $sheet->setCellValue("J{$rowNum}", $grandTotal);
            $sheet->getStyle("A{$rowNum}:K{$rowNum}")->getFont()->setBold(true);
            $sheet->getStyle("J{$rowNum}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');

            self::autoSizeColumns($sheet, array_keys($headers));

            $filename = "cong-no-{$customerId}-{$month}.xlsx";
            self::sendDownload($spreadsheet, $filename);
        } catch (Exception $e) {
            error_log('[ExcelExport::exportDebt] ' . $e->getMessage());
            http_response_code(500);
            echo 'Lỗi xuất file công nợ: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Xuất Báo cáo Lô hàng đã xác nhận ra file Excel.
     *
     * @param array  $shipments       Danh sách lô hàng
     * @param array  $costGroups      Các nhóm chi phí
     * @param array  $costsByShipment Chi phí theo từng lô
     * @param string $dateFrom        Ngày bắt đầu
     * @param string $dateTo          Ngày kết thúc
     */
    public static function exportShipmentReport(
        array $shipments,
        array $costGroups,
        array $costsByShipment,
        string $dateFrom,
        string $dateTo
    ): void {
        self::requireSpreadsheet();

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('BC Lô đã xác nhận');

            // Check ungrouped
            $hasUngrouped = false;
            foreach ($costsByShipment as $costs) {
                if (!empty($costs['ungrouped'])) { $hasUngrouped = true; break; }
            }

            // Build header list
            $headers = ['NO', 'DATE', 'CONSIGNEE', 'HAWB', 'CD NO.', 'PKG', 'GW (KG)'];
            foreach ($costGroups as $cg) $headers[] = strtoupper($cg['name']);
            if ($hasUngrouped) $headers[] = 'OTHER FEE';
            $headers[] = 'TOTAL';
            $headers[] = 'NOTE';

            $totalCols = count($headers);
            $lastColL  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

            // Row 1: Title
            $dfStr = $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : '';
            $dtStr = $dateTo   ? date('d/m/Y', strtotime($dateTo))   : '';
            $title = 'BÁO CÁO LÔ HÀNG ĐÃ XÁC NHẬN';
            if ($dfStr || $dtStr) $title .= " ({$dfStr} – {$dtStr})";
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $lastColL . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Row 3: Header
            $col = 1;
            foreach ($headers as $h) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colL . '3', $h);
                $col++;
            }
            $headerStyle = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                               'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A3:' . $lastColL . '3')->applyFromArray($headerStyle);

            // Data rows starting at row 4
            $fixedCols = 7;
            $row       = 4;
            $no        = 1;
            $colTotals = [];

            foreach ($shipments as $s) {
                $sid   = $s['id'];
                $total = 0;

                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '');
                $sheet->setCellValue('C' . $row, $s['company_name'] ?? '');
                $sheet->setCellValue('D' . $row, $s['hawb'] ?? '');
                $sheet->setCellValue('E' . $row, $s['cd_numbers'] ?? '');
                $sheet->setCellValue('F' . $row, (int)$s['packages']);
                $sheet->setCellValue('G' . $row, (float)$s['weight']);

                $cgColIdx = $fixedCols + 1;
                foreach ($costGroups as $cg) {
                    $amount = (float)($costsByShipment[$sid][$cg['id']] ?? 0);
                    $colL   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                    $sheet->setCellValue($colL . $row, $amount > 0 ? $amount : '');
                    $total += $amount;
                    if (!isset($colTotals[$cgColIdx])) $colTotals[$cgColIdx] = 0;
                    $colTotals[$cgColIdx] += $amount;
                    $cgColIdx++;
                }

                $ug    = (float)($costsByShipment[$sid]['ungrouped'] ?? 0);
                $total += $ug;
                if ($hasUngrouped) {
                    $ugColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                    $sheet->setCellValue($ugColL . $row, $ug > 0 ? $ug : '');
                    if (!isset($colTotals['ungrouped'])) $colTotals['ungrouped'] = 0;
                    $colTotals['ungrouped'] += $ug;
                    $cgColIdx++;
                }

                $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $noteColL  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx + 1);
                $sheet->setCellValue($totalColL . $row, $total > 0 ? $total : '');
                $sheet->setCellValue($noteColL  . $row, $s['remark'] ?? '');
                if (!isset($colTotals['total'])) $colTotals['total'] = 0;
                $colTotals['total'] += $total;

                $row++;
            }

            // Footer row — TỔNG CỘNG
            $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
            $cgColIdx = $fixedCols + 1;
            foreach ($costGroups as $cg) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($colL . $row, $colTotals[$cgColIdx] ?? 0);
                $cgColIdx++;
            }
            if ($hasUngrouped) {
                $ugColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($ugColL . $row, $colTotals['ungrouped'] ?? 0);
                $cgColIdx++;
            }
            $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->setCellValue($totalColL . $row, $colTotals['total'] ?? 0);

            $footerStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['rgb' => 'F0F7FF']],
            ];
            $sheet->getStyle('A' . $row . ':' . $lastColL . $row)->applyFromArray($footerStyle);

            // Number format & auto-size
            $numFmt   = '#,##0';
            $cgColIdx = $fixedCols + 1;
            $dataEnd  = $row;
            foreach ($costGroups as $cg) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->getStyle($colL . '4:' . $colL . $dataEnd)->getNumberFormat()->setFormatCode($numFmt);
                $cgColIdx++;
            }
            if ($hasUngrouped) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->getStyle($colL . '4:' . $colL . $dataEnd)->getNumberFormat()->setFormatCode($numFmt);
                $cgColIdx++;
            }
            $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->getStyle($totalColL . '4:' . $totalColL . $dataEnd)->getNumberFormat()->setFormatCode($numFmt);

            for ($i = 1; $i <= $totalCols; $i++) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colL)->setAutoSize(true);
            }

            $filename = 'bao-cao-lo-hang-confirmed-' . date('Ymd') . '.xlsx';
            self::sendDownload($spreadsheet, $filename);
        } catch (Exception $e) {
            error_log('[ExcelExport::exportShipmentReport] ' . $e->getMessage());
            http_response_code(500);
            echo 'Lỗi xuất file Excel: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Xuất Báo cáo Chi phí OPS ra file Excel.
     *
     * @param array  $shipmentsByOps      Dữ liệu nhóm theo ops user
     * @param array  $costsByOpsShipment  Chi phí theo [opsId][shipmentId][cgId]
     * @param array  $costGroups          Các nhóm chi phí
     * @param string $dateFrom            Ngày bắt đầu
     * @param string $dateTo              Ngày kết thúc
     */
    public static function exportOpsCosts(
        array $shipmentsByOps,
        array $costsByOpsShipment,
        array $costGroups,
        string $dateFrom,
        string $dateTo
    ): void {
        self::requireSpreadsheet();

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('BC Chi phí OPS');

            // Check ungrouped
            $hasUngrouped = false;
            foreach ($costsByOpsShipment as $opsId => $shipCosts) {
                foreach ($shipCosts as $sid => $costs) {
                    if (!empty($costs['ungrouped'])) { $hasUngrouped = true; break 2; }
                }
            }

            // Build header list
            $headers = ['NO', 'DATE', 'CONSIGNEE', 'HAWB', 'CD NO.', 'PKG', 'GW (KG)'];
            foreach ($costGroups as $cg) $headers[] = strtoupper($cg['name']);
            if ($hasUngrouped) $headers[] = 'OTHER FEE';
            $headers[] = 'TOTAL';
            $headers[] = 'NOTE';

            $totalCols = count($headers);
            $lastColL  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

            // Row 1: Title
            $dfStr = $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : '';
            $dtStr = $dateTo   ? date('d/m/Y', strtotime($dateTo))   : '';
            $title = 'BÁO CÁO CHI PHÍ OPS';
            if ($dfStr || $dtStr) $title .= " ({$dfStr} – {$dtStr})";
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $lastColL . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $fixedCols = 7;
            $row       = 2;

            // Grand totals accumulators
            $grandByGroup  = array_fill_keys(array_column($costGroups, 'id'), 0);
            $grandUngrouped = 0;
            $grandTotal    = 0;

            $numFmt = '#,##0';

            foreach ($shipmentsByOps as $opsId => $opsData) {
                $opsShipments = $opsData['shipments'];
                $opsName      = $opsData['ops_name'];

                // OPS section header row
                $sheet->setCellValue('A' . $row, '👷 ' . $opsName);
                $sheet->mergeCells('A' . $row . ':' . $lastColL . $row);
                $opsHeaderStyle = [
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => '2F5496']],
                ];
                $sheet->getStyle('A' . $row . ':' . $lastColL . $row)->applyFromArray($opsHeaderStyle);
                $row++;

                // Column headers for this section
                $col = 1;
                foreach ($headers as $h) {
                    $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue($colL . $row, $h);
                    $col++;
                }
                $colHeaderStyle = [
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                   'startColor' => ['rgb' => '1E3A5F']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ];
                $sheet->getStyle('A' . $row . ':' . $lastColL . $row)->applyFromArray($colHeaderStyle);
                $row++;

                $dataStartRow = $row;

                // Data rows
                $no          = 1;
                $opsByGroup  = array_fill_keys(array_column($costGroups, 'id'), 0);
                $opsUngrouped = 0;
                $opsTotalSum = 0;

                foreach ($opsShipments as $s) {
                    $sid      = $s['id'];
                    $rowTotal = 0;

                    $sheet->setCellValue('A' . $row, $no++);
                    $sheet->setCellValue('B' . $row, $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '');
                    $sheet->setCellValue('C' . $row, $s['company_name'] ?? '');
                    $sheet->setCellValue('D' . $row, $s['hawb'] ?? '');
                    $sheet->setCellValue('E' . $row, $s['cd_numbers'] ?? '');
                    $sheet->setCellValue('F' . $row, (int)$s['packages']);
                    $sheet->setCellValue('G' . $row, (float)$s['weight']);

                    $cgColIdx = $fixedCols + 1;
                    foreach ($costGroups as $cg) {
                        $amount = (float)($costsByOpsShipment[$opsId][$sid][$cg['id']] ?? 0);
                        $colL   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                        $sheet->setCellValue($colL . $row, $amount > 0 ? $amount : '');
                        $sheet->getStyle($colL . $row)->getNumberFormat()->setFormatCode($numFmt);
                        $rowTotal            += $amount;
                        $opsByGroup[$cg['id']] += $amount;
                        $grandByGroup[$cg['id']] += $amount;
                        $cgColIdx++;
                    }

                    $ug = (float)($costsByOpsShipment[$opsId][$sid]['ungrouped'] ?? 0);
                    $rowTotal     += $ug;
                    $opsUngrouped += $ug;
                    $grandUngrouped += $ug;
                    if ($hasUngrouped) {
                        $ugColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                        $sheet->setCellValue($ugColL . $row, $ug > 0 ? $ug : '');
                        $sheet->getStyle($ugColL . $row)->getNumberFormat()->setFormatCode($numFmt);
                        $cgColIdx++;
                    }

                    $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                    $noteColL  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx + 1);
                    $sheet->setCellValue($totalColL . $row, $rowTotal > 0 ? $rowTotal : '');
                    $sheet->getStyle($totalColL . $row)->getNumberFormat()->setFormatCode($numFmt);
                    $sheet->setCellValue($noteColL . $row, $s['remark'] ?? '');

                    $opsTotalSum  += $rowTotal;
                    $grandTotal   += $rowTotal;
                    $row++;
                }

                // Subtotal row for this OPS
                $sheet->setCellValue('A' . $row, 'Subtotal: ' . $opsName);
                $sheet->mergeCells('A' . $row . ':G' . $row);
                $cgColIdx = $fixedCols + 1;
                foreach ($costGroups as $cg) {
                    $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                    $sheet->setCellValue($colL . $row, $opsByGroup[$cg['id']]);
                    $sheet->getStyle($colL . $row)->getNumberFormat()->setFormatCode($numFmt);
                    $cgColIdx++;
                }
                if ($hasUngrouped) {
                    $ugColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                    $sheet->setCellValue($ugColL . $row, $opsUngrouped);
                    $sheet->getStyle($ugColL . $row)->getNumberFormat()->setFormatCode($numFmt);
                    $cgColIdx++;
                }
                $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($totalColL . $row, $opsTotalSum);
                $sheet->getStyle($totalColL . $row)->getNumberFormat()->setFormatCode($numFmt);

                $subtotalStyle = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                               'startColor' => ['rgb' => 'D9E1F2']],
                ];
                $sheet->getStyle('A' . $row . ':' . $lastColL . $row)->applyFromArray($subtotalStyle);
                $row += 2; // blank row between OPS sections
            }

            // Grand Total row
            $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $cgColIdx = $fixedCols + 1;
            foreach ($costGroups as $cg) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($colL . $row, $grandByGroup[$cg['id']]);
                $sheet->getStyle($colL . $row)->getNumberFormat()->setFormatCode($numFmt);
                $cgColIdx++;
            }
            if ($hasUngrouped) {
                $ugColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
                $sheet->setCellValue($ugColL . $row, $grandUngrouped);
                $sheet->getStyle($ugColL . $row)->getNumberFormat()->setFormatCode($numFmt);
                $cgColIdx++;
            }
            $totalColL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cgColIdx);
            $sheet->setCellValue($totalColL . $row, $grandTotal);
            $sheet->getStyle($totalColL . $row)->getNumberFormat()->setFormatCode($numFmt);

            $grandStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['rgb' => '1E3A5F']],
            ];
            $sheet->getStyle('A' . $row . ':' . $lastColL . $row)->applyFromArray($grandStyle);

            // Auto-size columns
            for ($i = 1; $i <= $totalCols; $i++) {
                $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($colL)->setAutoSize(true);
            }

            $filename = 'bao-cao-chi-phi-ops-' . date('Ymd') . '.xlsx';
            self::sendDownload($spreadsheet, $filename);
        } catch (Exception $e) {
            error_log('[ExcelExport::exportOpsCosts] ' . $e->getMessage());
            http_response_code(500);
            echo 'Lỗi xuất file Excel: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Xuất báo cáo danh sách lô hàng ra file Excel.
     *
     * @param array $rows Dữ liệu lô hàng (kết quả từ ReportController::export())
     */
    public function exportReport(array $rows): void
    {
        self::requireSpreadsheet();

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Báo cáo lô hàng');

            $headers = [
                'A' => 'HAWB',
                'B' => 'MAWB',
                'C' => 'Chuyến bay',
                'D' => 'Mã KH',
                'E' => 'Tên khách hàng',
                'F' => 'Số kiện',
                'G' => 'Trọng lượng (kg)',
                'H' => 'Ngày đến (ETA)',
                'I' => 'Ngày hoạt động',
                'J' => 'Trạng thái',
                'K' => 'Tổng phí (VND)',
                'L' => 'Ghi chú',
            ];

            self::writeHeaderRow($sheet, $headers);

            $rowNum = 2;
            foreach ($rows as $s) {
                $sheet->setCellValue("A{$rowNum}", htmlspecialchars($s['hawb']          ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("B{$rowNum}", htmlspecialchars($s['mawb']          ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("C{$rowNum}", htmlspecialchars($s['flight_no']     ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("D{$rowNum}", htmlspecialchars($s['customer_code'] ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("E{$rowNum}", htmlspecialchars($s['company_name']  ?? '', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue("F{$rowNum}", (int)($s['packages']                ?? 0));
                $sheet->setCellValue("G{$rowNum}", (float)($s['weight']               ?? 0));
                $sheet->setCellValue("H{$rowNum}", self::formatDateForExcel($s['eta']          ?? null));
                $sheet->setCellValue("I{$rowNum}", self::formatDateForExcel($s['active_date']  ?? null));
                $sheet->setCellValue("J{$rowNum}", self::translateStatus($s['status']          ?? ''));
                $sheet->setCellValue("K{$rowNum}", (float)($s['total_cost']            ?? 0));
                $sheet->setCellValue("L{$rowNum}", htmlspecialchars($s['remark']       ?? '', ENT_QUOTES, 'UTF-8'));
                $rowNum++;
            }

            self::autoSizeColumns($sheet, array_keys($headers));

            $filename = 'bao-cao-lo-hang-' . date('Ymd-His') . '.xlsx';
            self::sendDownload($spreadsheet, $filename);
        } catch (Exception $e) {
            error_log('[ExcelExport::exportReport] ' . $e->getMessage());
            http_response_code(500);
            echo 'Lỗi xuất file báo cáo: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Ghi header row với style in đậm và nền màu.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array  $headers  Mảng ['A' => 'Tên cột', ...]
     * @param int    $rowNum   Dòng để ghi (mặc định 1)
     */
    private static function writeHeaderRow(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $headers,
        int $rowNum = 1
    ): void {
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}{$rowNum}", $label);
        }

        $lastCol  = array_key_last($headers);
        $rangeRef = "A{$rowNum}:{$lastCol}{$rowNum}";

        $style = $sheet->getStyle($rangeRef);
        $style->getFont()->setBold(true);
        $style->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $style->getFont()->getColor()->setARGB('FFFFFFFF');
        $style->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );
    }

    /**
     * Tự động điều chỉnh độ rộng cột.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string[] $columns Danh sách ký tự cột (vd: ['A','B','C'])
     */
    private static function autoSizeColumns(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $columns
    ): void {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Set HTTP headers và xuất file Excel ra browser.
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param string                                $filename
     */
    private static function sendDownload(
        \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,
        string $filename
    ): void {
        // Đảm bảo không có output nào trước headers
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer->save('php://output');
        exit;
    }

    /**
     * Chuyển ngày từ Y-m-d sang d/m/Y để hiển thị trong Excel.
     *
     * @param string|null $date
     * @return string
     */
    private static function formatDateForExcel(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->format('d/m/Y') : $date;
    }

    /**
     * Dịch trạng thái sang tiếng Việt.
     *
     * @param string $status
     * @return string
     */
    private static function translateStatus(string $status): string
    {
        $map = [
            'pending_customs'  => 'Chờ thông quan',
            'cleared'          => 'Đã thông quan',
            'waiting_pickup'   => 'Chờ lấy hàng',
            'in_transit'       => 'Đang vận chuyển',
            'delivered'        => 'Đã giao hàng',
            'kt_reviewing'     => 'KT đang xem xét',
            'pending_approval' => 'Chờ phê duyệt',
            'debt'             => 'Công nợ',
            'rejected'         => 'Đã từ chối',
            'invoiced'         => 'Đã lập hóa đơn',
        ];

        return $map[$status] ?? $status;
    }

    /**
     * Tải PhpSpreadsheet autoloader. Ném exception nếu chưa cài.
     *
     * @throws RuntimeException
     */
    private static function requireSpreadsheet(): void
    {
        if (!file_exists(self::AUTOLOAD_PATH)) {
            throw new RuntimeException(
                'PhpSpreadsheet chưa được cài đặt. Chạy: composer install'
            );
        }

        require_once self::AUTOLOAD_PATH;

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            throw new RuntimeException('Không tải được PhpSpreadsheet.');
        }
    }
}
