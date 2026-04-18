<?php
class QuotationService {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Lấy danh sách dịch vụ theo báo giá
    public function getByQuotationId(int $quotationId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM quotation_services WHERE quotation_id = :qid ORDER BY sort_order, id"
        );
        $stmt->execute(['qid' => $quotationId]);
        return $stmt->fetchAll();
    }

    // Tạo mới dịch vụ trong báo giá
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO quotation_services
                (quotation_id, service_type, service_name, unit, unit_price, note, sort_order)
             VALUES (:qid, :type, :name, :unit, :price, :note, :sort)"
        );
        $stmt->execute([
            'qid'   => $data['quotation_id'],
            'type'  => $data['service_type'] ?? 'other',
            'name'  => $data['service_name'],
            'unit'  => $data['unit'] ?? '',
            'price' => $data['unit_price'] ?? 0,
            'note'  => $data['note'] ?? null,
            'sort'  => $data['sort_order'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Cập nhật dịch vụ
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $k => $v) {
            $fields[] = "`$k` = :$k";
            $params[$k] = $v;
        }
        if (empty($fields)) return false;
        return $this->db->prepare(
            "UPDATE quotation_services SET " . implode(', ', $fields) . " WHERE id = :id"
        )->execute($params);
    }

    // Xoá dịch vụ
    public function delete(int $id): bool {
        return $this->db->prepare(
            "DELETE FROM quotation_services WHERE id = :id"
        )->execute(['id' => $id]);
    }

    // Xoá tất cả dịch vụ của 1 báo giá
    public function deleteByQuotationId(int $quotationId): bool {
        return $this->db->prepare(
            "DELETE FROM quotation_services WHERE quotation_id = :qid"
        )->execute(['qid' => $quotationId]);
    }

    // Trả về các loại dịch vụ
    public function getServiceTypes(): array {
        return [
            'customs'   => 'Hải quan / Thông quan',
            'transport' => 'Vận chuyển',
            'storage'   => 'Lưu kho',
            'handling'  => 'Bốc xếp / Xử lý hàng',
            'insurance' => 'Bảo hiểm',
            'document'  => 'Chứng từ / Hồ sơ',
            'other'     => 'Khác',
        ];
    }
}
