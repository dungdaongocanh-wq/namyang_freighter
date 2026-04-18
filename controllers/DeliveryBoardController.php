<?php
class DeliveryBoardController {

    public function index() {
        $db = getDB();

        $date   = $_GET['date'] ?? date('Y-m-d');
        $search = trim($_GET['q'] ?? '');

        $where  = ["s.status IN ('in_transit','delivered','kt_reviewing','pending_approval','rejected','debt','invoiced')"];
        $params = [];

        if ($search) {
            $where[]  = "(s.hawb LIKE ? OR s.mawb LIKE ? OR c.company_name LIKE ?)";
            $kw       = '%' . $search . '%';
            $params   = array_merge($params, [$kw, $kw, $kw]);
        }

        $where[]  = "(DATE(dn.created_at) = ? OR s.active_date = ?)";
        $params[] = $date;
        $params[] = $date;

        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT s.id, s.hawb, s.mawb, s.customer_code, s.packages, s.weight,
                   s.active_date, s.status, s.flight_no, s.eta,
                   c.company_name, c.phone as customer_phone,
                   dn.note_code, dn.created_at as note_created_at, dn.printed_at,
                   COALESCE(SUM(sc.amount), 0) as total_cost,
                   COUNT(sc.id) as cost_count
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN delivery_notes dn ON dn.shipment_id = s.id
            LEFT JOIN shipment_costs sc ON sc.shipment_id = s.id
            WHERE $whereStr
            GROUP BY s.id, dn.id
            ORDER BY dn.created_at DESC, s.active_date DESC
        ");
        $stmt->execute($params);
        $shipments = $stmt->fetchAll();

        $summary = [
            'in_transit'   => 0,
            'delivered'    => 0,
            'kt_reviewing' => 0,
            'no_cost'      => 0,
        ];
        foreach ($shipments as $row) {
            if (isset($summary[$row['status']])) $summary[$row['status']]++;
            if ((int)$row['cost_count'] === 0) $summary['no_cost']++;
        }

        $viewTitle = 'Theo dõi giao hàng';
        $viewFile  = __DIR__ . '/../views/shared/delivery_board.php';
        include __DIR__ . '/../views/layouts/main.php';
    }

    // AJAX: Trả về HTML form nhập chi phí cho một lô hàng
    public function costForm() {
        $db         = getDB();
        $shipmentId = (int)($_GET['id'] ?? 0);
        if (!$shipmentId) { echo '<div class="p-3 text-danger">Thiếu ID lô hàng.</div>'; return; }

        $s = $db->prepare("SELECT s.*, c.id as customer_id, c.company_name FROM shipments s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
        $s->execute([$shipmentId]);
        $shipment = $s->fetch();
        if (!$shipment) { echo '<div class="p-3 text-danger">Không tìm thấy lô hàng.</div>'; return; }

        // Lấy chi phí hiện tại
        $cStmt = $db->prepare("SELECT * FROM shipment_costs WHERE shipment_id = ? ORDER BY id");
        $cStmt->execute([$shipmentId]);
        $costList = $cStmt->fetchAll();

        // Lấy quotation items theo khách hàng
        $quotationItemsList = [];
        if (!empty($shipment['customer_id'])) {
            $qiStmt = $db->prepare("
                SELECT qi.id, qi.description, qi.amount, qi.note
                FROM quotation_items qi
                WHERE qi.quotation_id = (
                    SELECT id FROM quotations
                    WHERE customer_id = ? AND is_active = 1
                    ORDER BY id DESC LIMIT 1
                )
                ORDER BY qi.sort_order, qi.id
            ");
            $qiStmt->execute([$shipment['customer_id']]);
            $quotationItemsList = $qiStmt->fetchAll();
        }

        $checkedNames = array_unique(array_column(
            array_filter($costList, fn($c) => ($c['source'] ?? '') === 'quotation'),
            'cost_name'
        ));
        $opsRows = array_values(array_filter($costList, fn($c) => ($c['source'] ?? 'ops') === 'ops'));
        if (empty($opsRows)) $opsRows = [['cost_name' => '', 'amount' => '']];

        $totalCost   = array_sum(array_column($costList, 'amount'));
        $baseUrl     = BASE_URL;
        $opsRowStart = count($opsRows);

        ob_start();
        ?>
<form id="dbCostForm">
  <input type="hidden" name="shipment_id" value="<?= (int)$shipmentId ?>">

  <?php if (!empty($quotationItemsList)): ?>
  <div class="mb-3">
    <div class="fw-semibold mb-2 small" style="color:#1e3a5f">
      📋 Chọn từ báo giá khách hàng:
    </div>
    <?php foreach ($quotationItemsList as $qi):
      $chk = in_array(trim($qi['description']), $checkedNames);
    ?>
    <div class="d-flex align-items-start gap-2 p-2 mb-1 rounded"
         style="border:1px solid #e2e8f0;background:<?= $chk ? '#f0fdf4' : '#fff' ?>">
      <input type="checkbox" name="quotation_items[]" value="<?= (int)$qi['id'] ?>"
             class="form-check-input mt-1 flex-shrink-0" style="width:18px;height:18px"
             <?= $chk ? 'checked' : '' ?>>
      <div>
        <div class="fw-semibold" style="font-size:0.85rem"><?= htmlspecialchars($qi['description']) ?></div>
        <?php if (!empty($qi['note'])): ?>
        <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($qi['note']) ?></div>
        <?php endif; ?>
        <?php if ((float)($qi['amount'] ?? 0) > 0): ?>
        <div class="text-success" style="font-size:0.75rem"><?= number_format((float)$qi['amount']) ?> đ</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <hr class="my-2">
  <?php endif; ?>

  <div class="mb-2">
    <div class="fw-semibold mb-2 small">💰 Chi phí thực tế OPS:</div>
    <div id="dbOpsCostRows">
      <?php foreach ($opsRows as $ri => $oc): ?>
      <div class="db-ops-row d-flex gap-2 mb-2 align-items-center">
        <input type="text" name="ops_costs[<?= $ri ?>][name]"
               class="form-control form-control-sm" placeholder="Tên chi phí"
               value="<?= htmlspecialchars($oc['cost_name'] ?? '') ?>">
        <input type="number" name="ops_costs[<?= $ri ?>][amount]"
               class="form-control form-control-sm" placeholder="Số tiền"
               style="width:140px" step="1000" min="0"
               value="<?= htmlspecialchars((string)($oc['amount'] ?? '')) ?>">
        <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                onclick="this.closest('.db-ops-row').remove()">✕</button>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-1"
            onclick="dbAddOpsRow()">+ Thêm dòng</button>
  </div>

  <?php if (!empty($costList)): ?>
  <div class="mt-3 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
    <div class="small text-muted fw-semibold mb-1">Tổng hiện tại: <span class="text-success"><?= number_format($totalCost) ?> đ</span></div>
  </div>
  <?php endif; ?>

  <div class="d-flex gap-2 justify-content-end mt-3 pt-2 border-top">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
    <button type="button" id="dbSaveBtn" class="btn btn-primary btn-sm px-4"
            onclick="dbSaveCosts()">💾 Lưu chi phí</button>
  </div>
</form>
<script>window.dbOpsRowIdx = <?= $opsRowStart ?>;</script>
        <?php
        echo ob_get_clean();
    }

    public function saveCosts() {
        header('Content-Type: application/json');
        $db         = getDB();
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        if (!$shipmentId) { echo json_encode(['success'=>false,'message'=>'Thiếu ID']); exit; }

        $db->prepare("DELETE FROM shipment_costs WHERE shipment_id = ? AND source = 'ops'")->execute([$shipmentId]);

        $qIds = array_unique(array_map('intval', $_POST['quotation_items'] ?? []));
        if (!empty($qIds)) {
            $ph    = implode(',', array_fill(0, count($qIds), '?'));
            $items = $db->prepare("SELECT id, description, amount FROM quotation_items WHERE id IN ($ph)");
            $items->execute($qIds);
            foreach ($items->fetchAll() as $qi) {
                $db->prepare("INSERT INTO shipment_costs (shipment_id,cost_name,amount,source,quotation_item_id,created_by) VALUES (?,?,?,'quotation',?,?)")
                   ->execute([$shipmentId, $qi['description'], (float)$qi['amount'], (int)$qi['id'], $_SESSION['user_id']]);
            }
        }

        foreach ($_POST['ops_costs'] ?? [] as $c) {
            $n = trim($c['name'] ?? ''); $a = (float)($c['amount'] ?? 0);
            if ($n === '' && $a == 0) continue;
            $db->prepare("INSERT INTO shipment_costs (shipment_id,cost_name,amount,source,created_by) VALUES (?,?,?,'ops',?)")
               ->execute([$shipmentId, $n, $a, $_SESSION['user_id']]);
        }

        try {
            $db->prepare("INSERT INTO shipment_logs (shipment_id,triggered_by,note,user_id) VALUES (?,'cost_updated','Cập nhật chi phí từ bảng theo dõi',?)")
               ->execute([$shipmentId, $_SESSION['user_id']]);
        } catch(Exception $e) {
            // Non-critical log write; failure does not affect the cost save result
            error_log('[DeliveryBoardController::saveCosts] log insert failed: ' . $e->getMessage());
        }
        echo json_encode(['success' => true]);
        exit;
    }

    public function confirmDelivered() {
        header('Content-Type: application/json');
        $db         = getDB();
        $shipmentId = (int)($_POST['shipment_id'] ?? 0);
        if (!$shipmentId) { echo json_encode(['success'=>false,'message'=>'Thiếu ID']); exit; }

        try {
            StateTransition::transition($shipmentId, 'manual_delivered', (int)$_SESSION['user_id'], 'Xác nhận hoàn thành từ bảng theo dõi');
            StateTransition::transition($shipmentId, 'auto_to_kt', (int)$_SESSION['user_id'], 'Tự động chuyển KT sau xác nhận giao hàng');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
