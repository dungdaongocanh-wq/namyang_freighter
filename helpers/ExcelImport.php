<?php

/**
 * ExcelImport - Import dữ liệu lô hàng từ file Excel
 *
 * Mapping cột:
 *   A = customer_code, B = hawb, C = mawb, D = flight_no, E = pol,
 *   F = etd (ngày), G = eta (ngày), H = packages (số kiện),
 *   I = weight (trọng lượng - có thể ở dạng scientific notation),
 *   J = cd_numbers (nhiều số tờ khai cách nhau bằng newline hoặc dấu phẩy),
 *   K = cd_status, L = remark
 */
class ExcelImport
{
    /** Đường dẫn autoload của Composer */
    private const AUTOLOAD_PATH = __DIR__ . '/../vendor/autoload.php';

    /**
     * Đọc file Excel và trả về mảng dữ liệu các dòng hợp lệ.
     * Bỏ qua dòng đầu tiên (header) và các dòng không có HAWB (cột B).
     *
     * @param string $filepath Đường dẫn tuyệt đối đến file Excel
     * @return array Mảng các dòng đã được parse
     * @throws RuntimeException Nếu PhpSpreadsheet chưa được cài hoặc file không đọc được
     */
    public static function parse(string $filepath): array
    {
        self::requireSpreadsheet();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        $highestRow = $sheet->getHighestDataRow();

        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            // Cột B (index 2) là HAWB - bỏ qua nếu trống
            $hawb = trim((string)$sheet->getCellByColumnAndRow(2, $rowIdx)->getValue());
            if ($hawb === '') {
                continue;
            }

            // Đọc ngày ETD (cột F) và ETA (cột G) - PhpSpreadsheet trả về dạng số serial
            $etdRaw = $sheet->getCellByColumnAndRow(6, $rowIdx)->getValue();
            $etaRaw = $sheet->getCellByColumnAndRow(7, $rowIdx)->getValue();

            // Đọc weight (cột I) - xử lý scientific notation (vd: 1.5E+2 → 150)
            $weightRaw = $sheet->getCellByColumnAndRow(9, $rowIdx)->getCalculatedValue();
            $weight    = self::parseWeight($weightRaw);

            // Đọc cd_numbers (cột J) - tách nhiều số tờ khai bằng newline hoặc dấu phẩy
            $cdRaw     = (string)$sheet->getCellByColumnAndRow(10, $rowIdx)->getValue();
            $cdNumbers = self::parseCdNumbers($cdRaw);

            $rows[] = [
                'customer_code' => trim((string)$sheet->getCellByColumnAndRow(1, $rowIdx)->getValue()),
                'hawb'          => $hawb,
                'mawb'          => trim((string)$sheet->getCellByColumnAndRow(3, $rowIdx)->getValue()),
                'flight_no'     => trim((string)$sheet->getCellByColumnAndRow(4, $rowIdx)->getValue()),
                'pol'           => trim((string)$sheet->getCellByColumnAndRow(5, $rowIdx)->getValue()),
                'etd'           => self::parseDate($etdRaw),
                'eta'           => self::parseDate($etaRaw),
                'packages'      => (int)$sheet->getCellByColumnAndRow(8, $rowIdx)->getValue(),
                'weight'        => $weight,
                'cd_numbers'    => $cdNumbers,
                'cd_status'     => trim((string)$sheet->getCellByColumnAndRow(11, $rowIdx)->getValue()),
                'remark'        => trim((string)$sheet->getCellByColumnAndRow(12, $rowIdx)->getValue()),
            ];
        }

        return $rows;
    }

    /**
     * Import mảng dữ liệu vào database.
     *
     * @param array $rows             Mảng dữ liệu từ parse()
     * @param int   $userId           ID người dùng thực hiện import
     * @param bool  $updateDuplicates Nếu true, cập nhật lô hàng đã tồn tại; nếu false thì bỏ qua
     * @return array{inserted:int, updated:int, skipped:int, warnings:string[]}
     */
    public static function import(array $rows, int $userId, bool $updateDuplicates = false): array
    {
        $result = [
            'inserted' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'warnings' => [],
        ];

        $shipmentModel = new Shipment();
        $customsModel  = new ShipmentCustoms();

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2; // Dòng Excel (bắt đầu từ dòng 2)

            try {
                // Tìm customer_id từ customer_code
                $customerId = AutoAssign::findCustomerId($row['customer_code']);
                if ($customerId === null) {
                    $result['warnings'][] = "Dòng {$lineNum}: Không tìm thấy khách hàng '{$row['customer_code']}'";
                    $result['skipped']++;
                    continue;
                }

                // Kiểm tra HAWB đã tồn tại chưa
                $existing = $shipmentModel->checkHawbExists($row['hawb']);

                if ($existing) {
                    if (!$updateDuplicates) {
                        // Bỏ qua lô hàng đã tồn tại
                        $result['warnings'][] = "Dòng {$lineNum}: HAWB '{$row['hawb']}' đã tồn tại, bỏ qua";
                        $result['skipped']++;
                        continue;
                    }

                    // Cập nhật thông tin lô hàng đã tồn tại (không thay đổi trạng thái)
                    $shipmentModel->update((int)$existing['id'], [
                        'customer_id'   => $customerId,
                        'customer_code' => $row['customer_code'],
                        'mawb'          => $row['mawb']      ?: null,
                        'flight_no'     => $row['flight_no'] ?: null,
                        'pol'           => $row['pol']       ?: null,
                        'etd'           => $row['etd']       ?: null,
                        'eta'           => $row['eta']       ?: null,
                        'packages'      => $row['packages']  ?: null,
                        'weight'        => $row['weight']    ?: null,
                        'remark'        => $row['remark']    ?: null,
                    ]);

                    $shipmentId = (int)$existing['id'];
                    $result['updated']++;
                } else {
                    // Luôn tạo mới với trạng thái pending_customs,
                    // StateTransition sẽ chuyển sang trạng thái đúng ở bước dưới
                    $shipmentId = $shipmentModel->create([
                        'customer_id'   => $customerId,
                        'customer_code' => $row['customer_code'],
                        'hawb'          => $row['hawb'],
                        'mawb'          => $row['mawb']      ?: null,
                        'flight_no'     => $row['flight_no'] ?: null,
                        'pol'           => $row['pol']       ?: null,
                        'etd'           => $row['etd']       ?: null,
                        'eta'           => $row['eta']       ?: null,
                        'packages'      => $row['packages']  ?: null,
                        'weight'        => $row['weight']    ?: null,
                        'remark'        => $row['remark']    ?: null,
                        'status'        => 'pending_customs',
                        'import_date'   => date('Y-m-d'),
                        'active_date'   => date('Y-m-d'),
                        'imported_by'   => $userId,
                    ]);

                    $result['inserted']++;
                }

                // Lưu các số tờ khai (cd_numbers) vào shipment_customs
                foreach ($row['cd_numbers'] as $cdNumber) {
                    if ($cdNumber === '') {
                        continue;
                    }
                    $customsModel->create([
                        'shipment_id' => $shipmentId,
                        'cd_number'   => $cdNumber,
                        'cd_status'   => $row['cd_status'] ?: null,
                    ]);
                }

                // Áp dụng StateTransition cho lô hàng mới (không áp dụng cho lô đã tồn tại,
                // vì không muốn ghi đè trạng thái hiện tại khi chỉ cập nhật thông tin)
                if (!$existing) {
                    self::applyInitialTransition($shipmentId, $row['cd_status'], $row['cd_numbers'], $userId);
                }
            } catch (Exception $e) {
                $result['warnings'][] = "Dòng {$lineNum}: Lỗi xử lý - " . $e->getMessage();
                $result['skipped']++;
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Gọi StateTransition phù hợp cho lô hàng vừa import.
     * Lô hàng mới được tạo với status = 'pending_customs' ban đầu nên cần
     * chuyển sang trạng thái đúng thông qua state machine.
     */
    private static function applyInitialTransition(
        int $shipmentId,
        string $cdStatus,
        array $cdNumbers,
        int $userId
    ): void {
        $cdStatusUpper = strtoupper(trim($cdStatus));
        $hasCdNumbers  = !empty(array_filter($cdNumbers, fn($n) => $n !== ''));

        if ($cdStatusUpper === 'TQ') {
            if ($hasCdNumbers) {
                // TQ + có số tờ khai → chờ lấy hàng
                StateTransition::transition($shipmentId, 'import_tq_with_cd', $userId, 'Import Excel');
            } else {
                // TQ không số tờ khai → thông quan
                StateTransition::transition($shipmentId, 'import_tq_no_cd', $userId, 'Import Excel');
            }
        }
        // Trường hợp còn lại (pending_customs) không cần transition vì đã là trạng thái mặc định
    }

    /**
     * Tách chuỗi cd_numbers thành mảng.
     * Hỗ trợ tách bằng newline (\n, \r\n) và dấu phẩy.
     *
     * @param string $raw
     * @return string[]
     */
    private static function parseCdNumbers(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        // Chuẩn hoá dấu ngắt dòng rồi tách
        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
        $parts      = preg_split('/[\n,]+/', $normalized);

        return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
    }

    /**
     * Chuyển đổi giá trị ngày từ Excel (số serial hoặc chuỗi) sang định dạng Y-m-d.
     *
     * @param mixed $value
     * @return string|null
     */
    private static function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // PhpSpreadsheet trả về số serial cho ô ngày
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                // fallthrough
            }
        }

        // Chuỗi ngày - thử các định dạng phổ biến
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, (string)$value);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Xử lý trọng lượng - chuyển scientific notation sang số thực.
     *
     * @param mixed $value
     * @return float|null
     */
    private static function parseWeight($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Chuyển về float (xử lý cả "1.5E+2" → 150.0)
        $num = filter_var((string)$value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_SCIENTIFIC);
        if ($num === false) {
            return null;
        }

        return round((float)$num, 3);
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

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new RuntimeException('Không tải được PhpSpreadsheet.');
        }
    }
}
