<?php

/**
 * AutoAssign - Tra cứu thông tin khách hàng từ mã khách hàng
 */
class AutoAssign
{
    /**
     * Tìm customer_id từ mã khách hàng
     *
     * @param string $customerCode Mã khách hàng (vd: KH001)
     * @return int|null Trả về customer_id hoặc null nếu không tìm thấy
     */
    public static function findCustomerId(string $customerCode): ?int
    {
        $customer = self::getCustomerByCode($customerCode);

        if ($customer === null) {
            return null;
        }

        return (int)$customer['id'];
    }

    /**
     * Lấy toàn bộ thông tin khách hàng theo mã
     *
     * @param string $code Mã khách hàng
     * @return array|null Mảng thông tin khách hàng hoặc null nếu không tìm thấy
     */
    public static function getCustomerByCode(string $code): ?array
    {
        try {
            $customerModel = new Customer();
            return $customerModel->findByCode(trim($code));
        } catch (Exception $e) {
            error_log('[AutoAssign::getCustomerByCode] ' . $e->getMessage());
            return null;
        }
    }
}
