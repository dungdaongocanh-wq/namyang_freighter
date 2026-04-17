<?php
$statusMap = [
    'pending_customs'  => ['Chờ TK',       'danger'],
    'cleared'          => ['Đã TQ',         'success'],
    'waiting_pickup'   => ['Chờ lấy hàng', 'warning'],
    'in_transit'       => ['Đang giao',     'primary'],
    'delivered'        => ['Đã giao',       'secondary'],
];
?>

<?php if (isset($_GET['err']) && $_GET['err'] === 'no_select'): ?>
<div class="alert alert-warning alert-dismissible mb-3" style="border-radius:10px">
  ⚠️ Vui lòng chọn ít nhất 1 lô để tải!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'downloaded'): ?>
<div class="alert alert-success alert-dismissible mb-3" style="border-radius:10px">
  ✅ Tải tờ khai thành công!
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<!-- Filter bar -->
<form method="GET" action="" class="d-flex gap-2 mb-3 flex-wrap">
  <input type="hidden" name="page" value="ops.shipment_list">
  <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
         class="form-control form-control-sm" placeholder="🔍 Tìm HAWB / MAWB..."
         style="width:200px">
  <select name="status" class="form-select form-select-sm" style="width:170px">
    <option value="">-- Tất cả trạng thái --</option>
    <option value="pending_customs" <?= ($_GET['status']??'')==='pending_customs'?'selected':'' ?>>Chờ thông quan</option>
    <option value="cleared"         <?= ($_GET['status']??'')==='cleared'        ?'selected':'' ?>>Đã thông quan</option>
    <option value="waiting_pickup"  <?= ($_GET['status']??'')==='waiting_pickup' ?'selected':'' ?>>Chờ lấy hàng</option>
  </select>
  <button class="btn btn-sm btn-outline-primary">Lọc</button>
  <a href="<?= BASE_URL ?>/?page=ops.shipment_list" class="btn btn-sm btn-outline-secondary">Reset</a>
</form>
<form method="POST" action="<?= BASE_URL ?>/?page=ops.download_customs" id="downloadForm">

  <div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between"
         style="border-radius:16px 16px 0 0">
      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-bold">📥 Lô cần tải tờ khai hải quan</h6>
        <span class="badge bg-primary"><?= count($shipments) ?></span>
      </div>
      <div class="d-flex gap-2">
        <button type="button" onclick="toggleAll(this)"
                class="btn btn-sm btn-outline-secondary">
          ☑️ Chọn tất cả
        </button>
        <button type="submit" class="btn btn-sm btn-warning fw-semibold"
                id="downloadBtn" disabled>
          ⬇️ Tải ZIP (<span id="selectedCount">0</span> lô)
        </button>
      </div>
    </div>

    <div class="card-body p-0">
      <?php if (empty($shipments)): ?>
      <div class="text-center py-5 text-muted">
        <div style="font-size:3rem">✅</div>
        <p class="mt-2">Không có lô nào cần tải tờ khai</p>
        <a href="<?= BASE_URL ?>/?page=ops.dashboard" class="btn btn-outline-primary btn-sm mt-1">
          ← Về Dashboard
        </a>
      </div>

      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem">
          <thead class="table-light">
            <tr>
              <th class="ps-4" style="width:44px">
                <input type="checkbox" class="form-check-input" id="checkAll"
                       onchange="toggleAllCheckbox(this)" title="Chọn tất cả">
              </th>
              <th>HAWB</th>
              <th>Khách hàng</th>
              <th>MAWB</th>
              <th>Flight / ETA</th>
              <th>Active date</th>
              <th>Trạng thái</th>
              <th>Tờ khai</th>
              <th class="text-center">File</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shipments as $s):
              [$stLabel, $stClr] = $statusMap[$s['status']] ?? ['?', 'secondary'];
              $hasFile   = (int)$s['file_count'] > 0;
              $allFiles  = (int)$s['file_count'] >= (int)$s['cd_count'] && (int)$s['cd_count'] > 0;
            ?>
            <tr id="row_<?= $s['id'] ?>">
              <td class="ps-4">
                <input type="checkbox"
                       name="shipment_ids[]"
                       value="<?= $s['id'] ?>"
                       class="form-check-input shipment-check"
                       <?= !$hasFile ? 'disabled title="Chưa có file tờ khai"' : '' ?>
                       onchange="updateRow(this, <?= $s['id'] ?>)">
              </td>
              <td>
                <div class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border" style="font-size:0.78rem">
                  <?= htmlspecialchars($s['customer_code'] ?? '') ?>
                </span>
              </td>
              <td class="text-muted small"><?= htmlspecialchars($s['mawb'] ?? '') ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($s['flight_no'] ?? '-') ?></div>
                <div class="text-muted small">
                  <?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?>
                </div>
              </td>
              <td class="text-muted small">
                <?= $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '-' ?>
              </td>
              <td>
                <span class="badge bg-<?= $stClr ?>"><?= $stLabel ?></span>
              </td>
              <td class="small text-muted">
                <?= htmlspecialchars($s['cd_numbers'] ?? 'Chưa có') ?>
              </td>
              <td class="text-center">
                <?php if ($allFiles): ?>
                  <span class="badge bg-success">
                    ✅ <?= (int)$s['file_count'] ?>/<?= (int)$s['cd_count'] ?> file
                  </span>
                <?php elseif ($hasFile): ?>
                  <span class="badge bg-warning text-dark">
                    ⚠️ <?= (int)$s['file_count'] ?>/<?= (int)$s['cd_count'] ?> file
                  </span>
                <?php else: ?>
                  <span class="badge bg-danger">❌ Chưa có file</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Footer summary -->
      <div class="px-4 py-3 border-top bg-light d-flex align-items-center justify-content-between"
           style="border-radius:0 0 16px 16px;font-size:0.83rem">
        <div class="text-muted">
          Tổng: <strong><?= count($shipments) ?></strong> lô &nbsp;|&nbsp;
          Có file: <strong class="text-success">
            <?= count(array_filter($shipments, fn($s) => (int)$s['file_count'] > 0)) ?>
          </strong> &nbsp;|&nbsp;
          Chưa có file: <strong class="text-danger">
            <?= count(array_filter($shipments, fn($s) => (int)$s['file_count'] === 0)) ?>
          </strong>
        </div>
        <button type="submit" class="btn btn-warning fw-semibold btn-sm"
                id="downloadBtn2" disabled>
          ⬇️ Tải ZIP (<span class="selectedCount2">0</span> lô đã chọn)
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>

</form>

<script>
let allSelected = false;

function toggleAll(btn) {
  allSelected = !allSelected;
  document.querySelectorAll('.shipment-check:not([disabled])').forEach(cb => {
    cb.checked = allSelected;
    updateRow(cb, parseInt(cb.value));
  });
  document.getElementById('checkAll').checked = allSelected;
  btn.textContent = allSelected ? '✖️ Bỏ chọn tất cả' : '☑️ Chọn tất cả';
  updateCount();
}

function toggleAllCheckbox(masterCb) {
  document.querySelectorAll('.shipment-check:not([disabled])').forEach(cb => {
    cb.checked = masterCb.checked;
    updateRow(cb, parseInt(cb.value));
  });
  updateCount();
}

function updateRow(cb, id) {
  const row = document.getElementById('row_' + id);
  if (!row) return;
  if (cb.checked) {
    row.classList.add('table-primary');
  } else {
    row.classList.remove('table-primary');
  }
  updateCount();
}

function updateCount() {
  const count = document.querySelectorAll('.shipment-check:checked').length;
  const label = count + ' lô';

  document.getElementById('selectedCount').textContent = count;
  document.getElementById('downloadBtn').disabled  = count === 0;

  document.querySelectorAll('.selectedCount2').forEach(el => el.textContent = label);
  const btn2 = document.getElementById('downloadBtn2');
  if (btn2) btn2.disabled = count === 0;
}
</script>