<?php
$statusOptions = [
    ''                 => 'Tất cả trạng thái',
    'pending_customs'  => 'Chờ thông quan',
    'cleared'          => 'Đã thông quan',
    'waiting_pickup'   => 'Chờ lấy hàng',
    'in_transit'       => 'Đang vận chuyển',
    'delivered'        => 'Đã giao hàng',
    'kt_reviewing'     => 'KT đang xem xét',
    'pending_approval' => 'Chờ phê duyệt',
    'rejected'         => 'Đã từ chối',
    'debt'             => 'Công nợ',
    'invoiced'         => 'Đã lập hóa đơn',
];

$dateFrom   = htmlspecialchars($_GET['date_from']   ?? '', ENT_QUOTES, 'UTF-8');
$dateTo     = htmlspecialchars($_GET['date_to']     ?? '', ENT_QUOTES, 'UTF-8');
$customerId = (int)($_GET['customer_id'] ?? 0);
$status     = htmlspecialchars($_GET['status'] ?? '', ENT_QUOTES, 'UTF-8');

$customers  = $customers  ?? [];
$shipments  = $shipments  ?? [];
?>

<div class="container-fluid py-3">
  <h4 class="mb-3">📊 Xuất báo cáo</h4>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?= BASE_URL ?>/" class="row g-3">
        <input type="hidden" name="page" value="report.export">

        <div class="col-md-3">
          <label class="form-label">Từ ngày</label>
          <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Đến ngày</label>
          <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Khách hàng</label>
          <select name="customer_id" class="form-select">
            <option value="">Tất cả khách hàng</option>
            <?php foreach ($customers as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $customerId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['customer_code'] . ' - ' . $c['company_name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Trạng thái</label>
          <select name="status" class="form-select">
            <?php foreach ($statusOptions as $val => $label): ?>
            <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $val ? 'selected' : '' ?>>
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary">🔍 Xem trước</button>
          <?php if (!empty($shipments)): ?>
          <form method="post" action="<?= BASE_URL ?>/?page=report.export&<?= http_build_query(array_filter(['date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? '', 'customer_id' => $_GET['customer_id'] ?? '', 'status' => $_GET['status'] ?? ''])) ?>" style="display:inline">
            <input type="hidden" name="action" value="download">
            <button type="submit" class="btn btn-success">⬇️ Tải Excel</button>
          </form>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($shipments)): ?>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Kết quả: <strong><?= count($shipments) ?></strong> lô hàng</span>
      <form method="post" action="<?= BASE_URL ?>/?page=report.export&<?= http_build_query(array_filter(['date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? '', 'customer_id' => $_GET['customer_id'] ?? '', 'status' => $_GET['status'] ?? ''])) ?>">
        <input type="hidden" name="action" value="download">
        <button type="submit" class="btn btn-sm btn-success">⬇️ Tải Excel</button>
      </form>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th>HAWB</th>
              <th>MAWB</th>
              <th>Mã KH</th>
              <th>Tên khách hàng</th>
              <th class="text-center">Kiện</th>
              <th class="text-center">KG</th>
              <th>Ngày HĐ</th>
              <th>Trạng thái</th>
              <th class="text-end">Tổng phí</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $grandTotal = 0;
            foreach ($shipments as $s):
                $grandTotal += (float)$s['total_cost'];
            ?>
            <tr>
              <td><?= htmlspecialchars($s['hawb'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($s['mawb'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($s['customer_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($s['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= (int)($s['packages'] ?? 0) ?></td>
              <td class="text-center"><?= number_format((float)($s['weight'] ?? 0), 2) ?></td>
              <td><?= htmlspecialchars($s['active_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($statusOptions[$s['status'] ?? ''] ?? $s['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end"><?= number_format((float)$s['total_cost']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-secondary fw-bold">
            <tr>
              <td colspan="8" class="text-end">Tổng cộng:</td>
              <td class="text-end"><?= number_format($grandTotal) ?> đ</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <?php elseif (!empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['customer_id'])): ?>
  <div class="alert alert-info">Không tìm thấy lô hàng nào với bộ lọc đã chọn.</div>
  <?php else: ?>
  <div class="alert alert-secondary">Chọn bộ lọc và nhấn <strong>Xem trước</strong> để hiển thị kết quả.</div>
  <?php endif; ?>
</div>
