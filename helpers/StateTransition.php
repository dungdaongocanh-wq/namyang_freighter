<?php

/**
 * StateTransition - Máy trạng thái tập trung cho lô hàng (shipment status machine)
 *
 * Luồng trạng thái:
 *   pending_customs → cleared → waiting_pickup → in_transit → delivered
 *   → kt_reviewing → pending_approval → debt → invoiced
 *   → rejected (từ pending_approval)
 */
class StateTransition
{
    /**
     * Bảng định nghĩa các trigger và chuyển trạng thái tương ứng.
     *
     * Cấu trúc: 'trigger' => ['from' => string|string[], 'to' => string]
     */
    private static array $transitions = [
        // Import: TQ có số tờ khai → chờ lấy hàng
        'import_tq_with_cd'   => ['from' => ['pending_customs', 'cleared'], 'to' => 'waiting_pickup'],

        // Import: TQ không có số tờ khai → thông quan
        'import_tq_no_cd'     => ['from' => ['pending_customs'],            'to' => 'cleared'],

        // CS tải file tờ khai lên → thông quan
        'customs_file_upload' => ['from' => ['pending_customs'],            'to' => 'cleared'],

        // OPS tải toàn bộ file tờ khai → chờ lấy hàng
        'ops_bulk_download'   => ['from' => ['cleared'],                    'to' => 'waiting_pickup'],

        // OPS xác nhận đã lấy hàng → đang vận chuyển
        'ops_complete'        => ['from' => ['waiting_pickup'],             'to' => 'in_transit'],

        // Lái xe lấy chữ ký khách hàng → đã giao
        'customer_signature'  => ['from' => ['in_transit'],                 'to' => 'delivered'],

        // Tự động chuyển sau khi giao → KT đang xem xét
        'auto_to_kt'          => ['from' => ['delivered'],                  'to' => 'kt_reviewing'],

        // KT đẩy lên cho khách hàng duyệt
        'kt_push_customer'    => ['from' => ['kt_reviewing'],               'to' => 'pending_approval'],

        // Khách hàng phê duyệt → công nợ
        'customer_approve'    => ['from' => ['pending_approval'],           'to' => 'debt'],

        // Khách hàng từ chối
        'customer_reject'     => ['from' => ['pending_approval'],           'to' => 'rejected'],

        // KT nộp lại sau khi bị từ chối
        'kt_resubmit'         => ['from' => ['rejected'],                   'to' => 'pending_approval'],

        // Chốt tháng → đã lập hóa đơn
        'month_close'         => ['from' => ['debt'],                       'to' => 'invoiced'],

        // Xác nhận thủ công đã giao (từ bảng theo dõi)
        'manual_delivered'    => ['from' => ['in_transit'],                 'to' => 'delivered'],
    ];

    /**
     * Thực hiện chuyển trạng thái cho một lô hàng.
     *
     * @param int    $shipmentId  ID lô hàng
     * @param string $trigger     Tên trigger
     * @param int    $userId      ID người thực hiện
     * @param string $note        Ghi chú thêm
     * @return bool               Thành công hay không
     */
    public static function transition(int $shipmentId, string $trigger, int $userId, string $note = ''): bool
    {
        if (!isset(self::$transitions[$trigger])) {
            error_log("[StateTransition] Trigger không hợp lệ: {$trigger}");
            return false;
        }

        try {
            $db           = getDB();
            $shipmentModel = new Shipment();
            $logModel      = new ShipmentLog();

            $shipment = $shipmentModel->getById($shipmentId);
            if (!$shipment) {
                error_log("[StateTransition] Không tìm thấy lô hàng ID: {$shipmentId}");
                return false;
            }

            $currentStatus = $shipment['status'];
            $rule          = self::$transitions[$trigger];
            $allowedFrom   = (array)$rule['from'];
            $targetStatus  = $rule['to'];

            // Kiểm tra trạng thái hiện tại có hợp lệ không
            if (!in_array($currentStatus, $allowedFrom, true)) {
                error_log(
                    "[StateTransition] Trigger '{$trigger}' không áp dụng cho trạng thái '{$currentStatus}' "
                    . "(lô hàng #{$shipmentId})"
                );
                return false;
            }

            // Cập nhật trạng thái lô hàng
            $shipmentModel->update($shipmentId, ['status' => $targetStatus]);

            // Ghi log chuyển trạng thái
            $logModel->create([
                'shipment_id'  => $shipmentId,
                'from_status'  => $currentStatus,
                'to_status'    => $targetStatus,
                'triggered_by' => $trigger,
                'user_id'      => $userId,
                'note'         => $note,
            ]);

            // Gửi thông báo cho các bên liên quan theo từng trigger
            self::sendNotifications($shipmentId, $trigger, $targetStatus, $shipment);

            return true;
        } catch (Exception $e) {
            error_log('[StateTransition::transition] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem trigger có thể thực hiện với lô hàng hiện tại không.
     *
     * @param int    $shipmentId
     * @param string $trigger
     * @return bool
     */
    public static function canTransition(int $shipmentId, string $trigger): bool
    {
        if (!isset(self::$transitions[$trigger])) {
            return false;
        }

        try {
            $shipmentModel = new Shipment();
            $shipment      = $shipmentModel->getById($shipmentId);

            if (!$shipment) {
                return false;
            }

            $allowedFrom = (array)self::$transitions[$trigger]['from'];
            return in_array($shipment['status'], $allowedFrom, true);
        } catch (Exception $e) {
            error_log('[StateTransition::canTransition] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi thông báo cho các bên liên quan sau khi chuyển trạng thái.
     *
     * @param int    $shipmentId
     * @param string $trigger
     * @param string $newStatus
     * @param array  $shipment   Thông tin lô hàng
     */
    private static function sendNotifications(
        int $shipmentId,
        string $trigger,
        string $newStatus,
        array $shipment
    ): void {
        $hawb       = htmlspecialchars($shipment['hawb'] ?? '', ENT_QUOTES, 'UTF-8');
        $customerId = (int)($shipment['customer_id'] ?? 0);

        switch ($trigger) {
            case 'import_tq_with_cd':
            case 'import_tq_no_cd':
                // Thông báo OPS khi lô hàng import mới sẵn sàng
                NotificationHelper::notifyRole('ops', "Lô hàng {$hawb} đã import, trạng thái: {$newStatus}", $shipmentId);
                // Thông báo Zalo
                ZaloNotify::notifyRole('ops', "🆕 [Nam Yang]\nLô hàng mới: {$hawb}\nTrạng thái: {$newStatus}");
                break;

            case 'customs_file_upload':
                // CS tải file xong → báo OPS
                NotificationHelper::notifyRole('ops', "Tờ khai lô hàng {$hawb} đã được tải lên, chờ lấy hàng", $shipmentId);
                // Thông báo Zalo
                ZaloNotify::notifyRole('ops', "📦 [Nam Yang]\nTờ khai lô hàng {$hawb} đã được CS tải lên.\nTrạng thái: Chờ lấy hàng ✅");
                break;

            case 'ops_bulk_download':
                // OPS tải tờ khai → báo CS
                NotificationHelper::notifyRole('cs', "OPS đã tải tờ khai lô hàng {$hawb}", $shipmentId);
                break;

            case 'ops_complete':
                // OPS lấy hàng xong → báo admin và KT
                NotificationHelper::notifyRole('admin', "Lô hàng {$hawb} đã xuất kho, đang vận chuyển", $shipmentId);
                break;

            case 'customer_signature':
                // Giao hàng xong → báo KT và admin
                NotificationHelper::notifyRole('accounting', "Lô hàng {$hawb} đã giao thành công, cần kiểm tra chi phí", $shipmentId);
                NotificationHelper::notifyRole('admin', "Lô hàng {$hawb} đã giao hàng", $shipmentId);
                break;

            case 'auto_to_kt':
                // Chuyển sang KT xem xét
                NotificationHelper::notifyRole('accounting', "Lô hàng {$hawb} chờ KT xem xét chi phí", $shipmentId);
                break;

            case 'kt_push_customer':
                // KT đẩy cho khách → thông báo tài khoản khách hàng
                self::notifyCustomerUsers($customerId, "Lô hàng {$hawb} đang chờ bạn phê duyệt chi phí", $shipmentId);
                break;

            case 'customer_approve':
                // Khách duyệt → báo KT
                NotificationHelper::notifyRole('accounting', "Khách hàng đã phê duyệt chi phí lô hàng {$hawb}", $shipmentId);
                break;

            case 'customer_reject':
                // Khách từ chối → báo KT
                NotificationHelper::notifyRole('accounting', "Khách hàng từ chối chi phí lô hàng {$hawb}, cần xem lại", $shipmentId);
                break;

            case 'kt_resubmit':
                // KT nộp lại → báo khách
                self::notifyCustomerUsers($customerId, "Chi phí lô hàng {$hawb} đã được điều chỉnh, chờ bạn phê duyệt", $shipmentId);
                break;

            case 'month_close':
                // Chốt tháng → báo admin và khách
                NotificationHelper::notifyRole('admin', "Lô hàng {$hawb} đã được chốt hóa đơn", $shipmentId);
                self::notifyCustomerUsers($customerId, "Hóa đơn lô hàng {$hawb} đã được phát hành", $shipmentId);
                break;
        }
    }

    /**
     * Gửi thông báo tới tất cả user thuộc về một khách hàng.
     *
     * @param int      $customerId
     * @param string   $message
     * @param int|null $shipmentId
     */
    private static function notifyCustomerUsers(int $customerId, string $message, ?int $shipmentId = null): void
    {
        if ($customerId <= 0) {
            return;
        }

        try {
            $db    = getDB();
            $stmt  = $db->prepare("SELECT id FROM users WHERE customer_id = :cid AND is_active = 1");
            $stmt->execute(['cid' => $customerId]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($users as $uid) {
                NotificationHelper::send((int)$uid, $message, $shipmentId);
            }
        } catch (Exception $e) {
            error_log('[StateTransition::notifyCustomerUsers] ' . $e->getMessage());
        }
    }
}
