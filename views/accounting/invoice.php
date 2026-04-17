<!-- Filter -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body py-3">
    <form method="GET" action="<?= BASE_URL ?>/" class="row g-2 align-items-end">
      <input type="hidden" name="page" value="accounting.invoice">
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Khách hàng</label>
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">-- Chọn khách hàng --</option>
          <?php foreach ($customerList as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $c['id']==$custId?'selected':'' ?>>
            <?= htmlspecialchars($c['customer_code']) ?> - <?= htmlspecialchars($c['company_name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Tháng</label>
        <select name="month" class="form-select form-select-sm">
          <?php foreach ($months as $m): ?>
          <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>>
            Tháng <?= date('m/Y', strtotime($m.'-01')) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm px-4">Xem</button>
      </div>
      <?php if ($customer && !empty($shipments)): ?>
      <div class="col-auto">
        <button type="button" onclick="window.print()"
                class="btn btn-outline-secondary btn-sm px-4">
          🖨️ In PDF
        </button>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Invoice preview -->
<?php if ($customer && !empty($shipments)): ?>
<div id="invoicePreview" class="card"
     style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body p-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 pb-3"
         style="border-bottom:2px solid #1e3a5f">
      <div>
        <h4 class="fw-bold text-primary mb-0">🚢 Nam Yang Freight</h4>
        <p class="text-muted small mb-0">Hệ thống quản lý lô hàng</p>
      </div>
      <div class="text-end">
        <h5 class="fw-bold mb-1">BẢNG KÊ CƯỚC VẬN CHUYỂN</h5>
        <p class="text-muted small mb-0">
          Tháng <?= date('m/Y', strtotime($month.'-01')) ?>
        </p>
        <p class="text-muted small mb-0">
          Ngày in: <?= date('d/m/Y') ?>
        </p>
      </div>
    </div>

    <!-- Customer info -->
    <div class="row mb-4">
      <div class="col-md-6">
        <h6 class="fw-bold text-muted small mb-2">KHÁCH HÀNG</h6>
        <div class="fw-bold"><?= htmlspecialchars($customer['company_name'] ?? '') ?></div>
        <div class="small text-muted">
          Mã KH: <strong><?= htmlspecialchars($customer['customer_code']) ?></strong>
        </div>
        <?php if ($customer['address']): ?>
        <div class="small text-muted"><?= htmlspecialchars($customer['address']) ?></div>
        <?php endif; ?>
        <?php if ($customer['email']): ?>
        <div class="small text-muted">📧 <?= htmlspecialchars($customer['email']) ?></div>
        <?php endif; ?>
        <?php if ($customer['phone']): ?>
        <div class="small text-muted">📞 <?= htmlspecialchars($customer['phone']) ?></div>
        <?php endif; ?>
      </div>
      <div class="col-md-6 text-md-end">
        <div class="p-3 rounded-2" style="background:#f0fdf4;display:inline-block;min-width:200px">
          <div class="text-muted small">TỔNG CƯỚC</div>
          <div class="fw-bold text-success" style="font-size:1.8rem">
            <?= number_format($totalAmount) ?>
          </div>
          <div class="text-muted small">VNĐ</div>
        </div>
      </div>
    </div>

    <!-- Shipments table -->
    <table class="table table-bordered table-sm mb-4" style="font-size:0.83rem">
      <thead style="background:#1e3a5f;color:#fff">
        <tr>
          <th class="text-center" style="width:35px">#</th>
          <th>HAWB</th>
          <th>MAWB</th>
          <th>Flight</th>
          <th class="text-center">Kiện</th>
          <th class="text-center">KG</th>
          <th>Active date</th>
          <th>Chi tiết phí</th>
          <th class="text-end">Tổng (đ)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($shipments as $i => $s):
          // Parse cost detail
          $costDetails = [];
          if ($s['cost_detail']) {
            foreach (explode('|', $s['cost_detail']) as $cd) {
              $parts = explode(':', $cd, 2);
              if (count($parts) === 2) {
                $costDetails[] = htmlspecialchars($parts[0]) . ': ' . number_format($parts[1]) . 'đ';
              }
            }
          }
        ?>
        <tr>
          <td class="text-center text-muted"><?= $i+1 ?></td>
          <td class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
          <td class="small"><?= htmlspecialchars($s['mawb'] ?? '') ?></td>
          <td class="small"><?= htmlspecialchars($s['flight_no'] ?? '') ?></td>
          <td class="text-center"><?= $s['packages'] ?></td>
          <td class="text-center"><?= number_format($s['weight'],1) ?></td>
          <td class="small"><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
          <td class="small text-muted"><?= implode('<br>', $costDetails) ?></td>
          <td class="text-end fw-semibold"><?= number_format($s['total_cost']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot style="background:#f0fdf4">
        <tr>
          <td colspan="8" class="text-end fw-bold">TỔNG CỘNG</td>
          <td class="text-end fw-bold text-success"><?= number_format($totalAmount) ?></td>
        </tr>
      </tfoot>
    </table>

    <!-- Signature row -->
    <div class="row mt-5 pt-3">
      <div class="col-6 text-center">
        <div class="fw-semibold small mb-4">Người lập bảng kê</div>
        <div style="height:60px"></div>
        <div class="border-top pt-2 mx-4">
          <small class="text-muted">Ký tên & đóng dấu</small>
        </div>
      </div>
      <div class="col-6 text-center">
        <div class="fw-semibold small mb-4">Đại diện khách hàng</div>
        <div style="height:60px"></div>
        <div class="border-top pt-2 mx-4">
          <small class="text-muted">Ký tên & đóng dấu</small>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  #topbar, #sidebar, .card-body form, nav, .btn { display: none !important; }
  #main-content { margin: 0 !important; padding: 0 !important; }
  #invoicePreview { box-shadow: none !important; }
}
</style>
<?php elseif ($custId): ?>
<div class="alert alert-info" style="border-radius:10px">
  ℹ️ Không có dữ liệu cho khách hàng này trong tháng đã chọn.
</div>
<?php else: ?>
<div class="text-center text-muted py-5">
  <div style="font-size:3rem">🧾</div>
  <p class="mt-2">Chọn khách hàng và tháng để xem hoá đơn</p>
</div>
<?php endif; ?>