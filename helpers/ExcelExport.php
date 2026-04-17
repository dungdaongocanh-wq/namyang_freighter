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
