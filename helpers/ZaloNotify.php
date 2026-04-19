<?php
/**
 * ZaloNotify - Gửi thông báo qua Zalo Official Account API
 *
 * YÊU CẦU:
 * - User Zalo đã follow OA của công ty
 * - Lưu Zalo User ID của user vào cột `zalo_id` trong bảng `users`
 * - Điền ZALO_OA_ACCESS_TOKEN thật vào config/zalo.php
 */
class ZaloNotify
{
    /**
     * Gửi tin nhắn text tới 1 Zalo User ID
     *
     * @param string $zaloUserId  Zalo User ID của người nhận
     * @param string $message     Nội dung tin nhắn (tối đa 2000 ký tự)
     * @return bool
     */
    public static function send(string $zaloUserId, string $message): bool
    {
        // Kiểm tra tính năng có được bật không
        if (!defined('ZALO_NOTIFY_ENABLED') || !ZALO_NOTIFY_ENABLED) {
            return false;
        }

        $token = defined('ZALO_OA_ACCESS_TOKEN') ? ZALO_OA_ACCESS_TOKEN : '';
        if (empty($token) || $token === 'YOUR_ZALO_OA_ACCESS_TOKEN') {
            error_log('[ZaloNotify] ZALO_OA_ACCESS_TOKEN chưa được cấu hình.');
            return false;
        }

        if (empty($zaloUserId)) {
            return false;
        }

        // Giới hạn 2000 ký tự theo quy định Zalo OA
        if (mb_strlen($message, 'UTF-8') > 2000) {
            $message = mb_substr($message, 0, 1997, 'UTF-8') . '...';
        }

        $payload = json_encode([
            'recipient' => ['user_id' => $zaloUserId],
            'message'   => ['text'    => $message],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://openapi.zalo.me/v3.0/oa/message/cs');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'access_token: ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("[ZaloNotify] cURL error: {$curlErr}");
            return false;
        }

        if ($httpCode !== 200) {
            error_log("[ZaloNotify] HTTP {$httpCode}: {$response}");
            return false;
        }

        $data = json_decode($response, true);
        if (($data['error'] ?? -1) !== 0) {
            error_log("[ZaloNotify] Zalo API error: {$response}");
            return false;
        }

        return true;
    }

    /**
     * Gửi thông báo tới tất cả user có role nhất định
     * (dựa vào cột zalo_id trong bảng users)
     *
     * @param string $role     Vai trò: ops, cs, admin, accounting, ...
     * @param string $message  Nội dung tin nhắn
     */
    public static function notifyRole(string $role, string $message): void
    {
        if (!defined('ZALO_NOTIFY_ENABLED') || !ZALO_NOTIFY_ENABLED) {
            return;
        }

        try {
            $db   = getDB();
            $stmt = $db->prepare(
                "SELECT zalo_id FROM users 
                 WHERE role = ? AND is_active = 1 
                 AND zalo_id IS NOT NULL AND zalo_id != ''"
            );
            $stmt->execute([$role]);
            $zaloIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($zaloIds as $zaloId) {
                self::send((string)$zaloId, $message);
            }
        } catch (Exception $e) {
            error_log('[ZaloNotify::notifyRole] ' . $e->getMessage());
        }
    }
}
