<?php
$statusMap = [
    'pending_customs'  => ['Chờ TK',       'danger'],
    'cleared'          => ['Đã TQ',         'warning'],
    'waiting_pickup'   => ['Chờ lấy hàng', 'warning'],
    'delivered'        => ['Đã giao',       'success'],
    'debt'             => ['Công nợ',       'info'],
    'pending_approval' => ['Chờ duyệt',     'secondary'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <!-- Search -->
    <form method="GET" action="" class="d-flex gap-2">
      <input type="hidden" name="page" value="cs.list">
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             class="form-control form-control-sm" placeholder="🔍 Tìm HAWB / MAWB..."
             style="width:220px">
      <select name="status" class="form-select form-select-sm" style="width:150px">
        <option value="">-- Trạng thái --</option>
        <?php foreach ($statusMap as $k => [$label, $clr]): ?>
        <option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>>
          <?= $label ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-primary">Lọc</button>
      <a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
  </div>
  <a href="<?= BASE_URL ?>/?page=cs.upload" class="btn btn-primary btn-sm">
    + Upload lô
  </a>
</div>

<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2"
       style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">📦 Danh sách lô hàng</h6>
    <span class="badge bg-primary ms-1"><?= count($shipments) ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:0.85rem">
        <thead class="table-light">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Khách hàng</th>
            <th>MAWB</th>
            <th>Flight / ETA</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th>Trạng thái</th>
            <th class="text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($shipments)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-5">
              Không có dữ liệu
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach ($shipments as $s):
            [$stLabel, $stClr] = $statusMap[$s['status']] ?? ['?', 'secondary'];
          ?>
          <tr>
            <td class="ps-4">
              <a href="<?= BASE_URL ?>/?page=cs.edit_shipment&id=<?= $s['id'] ?>"
                 class="fw-semibold text-decoration-none">
                <?= htmlspecialchars($s['hawb']) ?>
              </a>
              <?php if (!empty($s['remark'])): ?>
              <span title="<?= htmlspecialchars($s['remark']) ?>" style="cursor:help">📋</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-light text-dark border" style="font-size:0.78rem">
                <?= htmlspecialchars($s['customer_code'] ?? $s['customer_id']) ?>
              </span>
            </td>
            <td class="text-muted small"><?= htmlspecialchars($s['mawb'] ?? '') ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($s['flight_no'] ?? '') ?></div>
              <div class="text-muted small">
                <?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?>
              </div>
            </td>
            <td>
              <strong><?= (int)$s['packages'] ?></strong> kiện /
              <?= number_format((float)$s['weight'], 1) ?> kg
            </td>
            <td class="text-muted small">
              <?= $s['created_at'] ? date('d/m/Y', strtotime($s['created_at'])) : '-' ?>
            </td>
            <td>
              <span class="badge bg-<?= $stClr ?>"><?= $stLabel ?></span>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <!-- Sửa -->
                <a href="<?= BASE_URL ?>/?page=cs.edit_shipment&id=<?= $s['id'] ?>"
                   class="btn btn-sm btn-outline-primary"
                   title="Sửa">✏️</a>
                <!-- Xoá -->
                <form method="POST"
                      action="<?= BASE_URL ?>/?page=cs.delete_shipment"
                      onsubmit="return confirm('Xoá lô <?= htmlspecialchars($s['hawb']) ?>?')">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">🗑️</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>