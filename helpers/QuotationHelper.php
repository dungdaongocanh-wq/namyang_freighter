<?php

/**
 * QuotationHelper - Hỗ trợ lấy báo giá áp dụng cho lô hàng
 */
class QuotationHelper
{
    /**
     * Lấy ID báo giá áp dụng cho khách hàng.
     *
     * Ưu tiên báo giá riêng của KH, fallback sang báo giá chung (customer_id IS NULL)
     * nếu KH không có báo giá riêng.
     *
     * @param \PDO $db       Kết nối database
     * @param int|null $customerId ID khách hàng (NULL nếu không có)
     * @return int|null      ID báo giá, hoặc NULL nếu không tìm thấy
     */
    public static function getQuotationId(\PDO $db, ?int $customerId): ?int
    {
        $quotationId = null;

        if ($customerId) {
            $stmt = $db->prepare("SELECT id FROM quotations WHERE customer_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
            $stmt->execute([$customerId]);
            $quotationId = $stmt->fetchColumn() ?: null;
        }

        if (!$quotationId) {
            $stmt = $db->prepare("SELECT id FROM quotations WHERE customer_id IS NULL AND is_active = 1 ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $quotationId = $stmt->fetchColumn() ?: null;
        }

        return $quotationId ? (int)$quotationId : null;
    }
}
