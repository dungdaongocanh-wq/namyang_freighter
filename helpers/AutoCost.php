<?php

/**
 * AutoCost - Tự động tính chi phí cho lô hàng dựa trên bảng giá (quotations)
 */
class AutoCost
{
    /**
     * Tính và lưu chi phí tự động cho một lô hàng.
     *
     * Quy tắc tính:
     *   - unit = 'kg'  → quantity = weight (trọng lượng lô hàng)
     *   - unit = 'pcs' hoặc 'kiện' → quantity = packages (số kiện)
     *   - Các đơn vị khác → quantity = 1 (tính theo lô)
     *
     * @param int $shipmentId ID lô hàng cần tính chi phí
     * @return array Danh sách các chi phí đã được tạo
     */
    public static function calculate(int $shipmentId): array
    {
        $createdCosts = [];

        try {
            $shipmentModel  = new Shipment();
            $quotationModel = new Quotation();
            $costModel      = new ShipmentCost();

            $shipment = $shipmentModel->getById($shipmentId);
            if (!$shipment) {
                error_log("[AutoCost::calculate] Không tìm thấy lô hàng ID: {$shipmentId}");
                return [];
            }

            $customerId = (int)($shipment['customer_id'] ?? 0);
            $weight     = (float)($shipment['weight']   ?? 0);
            $packages   = (int)($shipment['packages']   ?? 0);

            // Xoá các chi phí auto cũ trước khi tính lại
            $costModel->deleteByShipment($shipmentId);

            // Lấy bảng giá áp dụng cho khách hàng (ưu tiên riêng, fallback về chung)
            $quotations = $quotationModel->getAll($customerId);

            foreach ($quotations as $q) {
                $unit      = strtolower(trim($q['unit'] ?? ''));
                $unitPrice = (float)$q['unit_price'];

                // Xác định số lượng theo đơn vị tính
                if (in_array($unit, ['kg', 'kilogram'])) {
                    // Tính theo trọng lượng
                    $quantity = $weight;
                } elseif (in_array($unit, ['pcs', 'kiện', 'cái', 'thùng', 'box', 'package'])) {
                    // Tính theo số kiện
                    $quantity = (float)$packages;
                } else {
                    // Tính theo lô (flat fee)
                    $quantity = 1.0;
                }

                // Bỏ qua nếu số lượng = 0
                if ($quantity <= 0) {
                    continue;
                }

                $amount = round($quantity * $unitPrice, 2);

                $costData = [
                    'shipment_id'  => $shipmentId,
                    'service_name' => $q['service_name'],
                    'quantity'     => $quantity,
                    'unit'         => $q['unit'],
                    'unit_price'   => $unitPrice,
                    'amount'       => $amount,
                    'source'       => 'auto',
                    'created_by'   => null,
                ];

                $newId = $costModel->create($costData);
                $costData['id'] = $newId;
                $createdCosts[] = $costData;
            }
        } catch (Exception $e) {
            error_log('[AutoCost::calculate] ' . $e->getMessage());
        }

        return $createdCosts;
    }
}
