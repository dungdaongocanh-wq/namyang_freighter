<?php

/**
 * DateRollover - Tự động cuộn ngày hoạt động (active_date) cho các lô hàng chưa hoàn tất.
 *
 * Khi một lô hàng có active_date < hôm nay và còn đang trong trạng thái chờ xử lý,
 * hệ thống sẽ cập nhật active_date = hôm nay để đảm bảo lô hàng luôn xuất hiện
 * trong danh sách công việc ngày hiện tại.
 */
class DateRollover
{
    /** Các trạng thái cần xét rollover */
    private const ACTIVE_STATUSES = ['pending_customs', 'cleared', 'waiting_pickup', 'in_transit'];

    /**
     * Thực hiện rollover cho tất cả lô hàng đủ điều kiện.
     *
     * @return array{processed:int, rolled:int} Số lô đã kiểm tra và số lô đã cập nhật
     */
    public static function rollover(): array
    {
        $result = ['processed' => 0, 'rolled' => 0];

        try {
            $shipments = self::getExpiredShipments();
            $result['processed'] = count($shipments);

            if (empty($shipments)) {
                return $result;
            }

            $db      = getDB();
            $today   = date('Y-m-d');
            $logModel = new ShipmentLog();

            // Cập nhật hàng loạt: đặt active_date = hôm nay
            $placeholders = implode(',', array_fill(0, count($shipments), '?'));
            $ids          = array_column($shipments, 'id');

            $stmt = $db->prepare(
                "UPDATE shipments SET active_date = ? WHERE id IN ({$placeholders})"
            );
            $stmt->execute(array_merge([$today], $ids));

            $result['rolled'] = $stmt->rowCount();

            // Ghi log rollover cho từng lô
            foreach ($shipments as $shipment) {
                $logModel->create([
                    'shipment_id'  => (int)$shipment['id'],
                    'from_status'  => $shipment['status'],
                    'to_status'    => $shipment['status'], // trạng thái không đổi, chỉ đổi ngày
                    'triggered_by' => 'date_rollover',
                    'user_id'      => null,
                    'note'         => "Tự động cuộn ngày: {$shipment['active_date']} → {$today}",
                ]);
            }
        } catch (Exception $e) {
            error_log('[DateRollover::rollover] ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Lấy danh sách lô hàng đủ điều kiện rollover:
     * - active_date < hôm nay
     * - status nằm trong danh sách ACTIVE_STATUSES
     *
     * @return array
     */
    public static function getExpiredShipments(): array
    {
        try {
            $db    = getDB();
            $today = date('Y-m-d');

            $statusPlaceholders = implode(',', array_fill(0, count(self::ACTIVE_STATUSES), '?'));

            $stmt = $db->prepare(
                "SELECT s.id, s.hawb, s.status, s.active_date, s.customer_id, c.company_name
                 FROM shipments s
                 LEFT JOIN customers c ON s.customer_id = c.id
                 WHERE s.active_date < ?
                   AND s.status IN ({$statusPlaceholders})
                 ORDER BY s.active_date ASC"
            );

            $stmt->execute(array_merge([$today], self::ACTIVE_STATUSES));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('[DateRollover::getExpiredShipments] ' . $e->getMessage());
            return [];
        }
    }
}
