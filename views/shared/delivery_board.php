<?php
$statusMap = [
    'in_transit'       => ['Đang giao',   'primary'],
    'delivered'        => ['Đã giao',     'success'],
    'kt_reviewing'     => ['KT duyệt',    'info'],
    'pending_approval' => ['Chờ duyệt',   'secondary'],
    'rejected'         => ['KH từ chối',  'danger'],
    'debt'             => ['Công nợ',     'info'],
    'invoiced'         => ['Đã xuất HĐ', 'success'],
];
$role = $_SESSION['role'] ?? '';
?>

<!-- ═══ Bộ lọc ═══ -->
<form method="GET" action="" class="row g-2 align-items-end mb-4">
  <input type="hidden" name="page" value="shared.delivery_board">
  <div class="col-auto">
    <label class="form-label small mb-1 fw-semibold">Ngày</label>
    <input type="date" name="date" class="form-control form-control-sm"
           value="<?= htmlspecialchars($date) ?>" style="min-width:150px">
  </div>
  <div class="col-sm-4">
    <label class="form-label small mb-1 fw-semibold">Tìm kiếm</label>
    <input type="text" name="q" class="form-control form-control-sm"
           placeholder="HAWB, MAWB, tên khách hàng..."
           value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-primary px-3">🔍 Lọc</button>
    <a href="?page=shared.delivery_board" class="btn btn-sm btn-outline-secondary ms-1">↺ Hôm nay</a>
  </div>
</form>

<!-- ═══ Thẻ tóm tắt ═══ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0">
      <div class="card-body py-3 text-center">
        <div style="font-size:1.6rem;font-weight:700;color:#1d4ed8"><?= $summary['in_transit'] ?></div>
        <div class="small text-muted mt-1">🚚 Đang giao</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0">
      <div class="card-body py-3 text-center">
        <div style="font-size:1.6rem;font-weight:700;color:#15803d"><?= $summary['delivered'] ?></div>
        <div class="small text-muted mt-1">✅ Đã giao</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0">
      <div class="card-body py-3 text-center">
        <div style="font-size:1.6rem;font-weight:700;color:#0369a1"><?= $summary['kt_reviewing'] ?></div>
        <div class="small text-muted mt-1">📋 KT duyệt</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card border-0">
      <div class="card-body py-3 text-center">
        <div style="font-size:1.6rem;font-weight:700;color:#dc2626"><?= $summary['no_cost'] ?></div>
        <div class="small text-muted mt-1">⚠️ Chưa nhập CP</div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ Bảng danh sách ═══ -->
<div class="card border-0 shadow-sm" style="border-radius:12px">
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:2.5rem">📭</div>
      <p class="mt-2 mb-0">Không có lô hàng nào trong ngày <strong><?= htmlspecialchars($date) ?></strong></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:0.875rem">
        <thead style="background:#f8fafc;color:#475569;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.4px">
          <tr>
            <th class="ps-3 py-3">#</th>
            <th>HAWB / MAWB</th>
            <th>Khách hàng</th>
            <th class="text-center">Kiện / KG</th>
            <th class="text-center">Ngày active</th>
            <th class="text-center">Biên bản</th>
            <th class="text-center">Trạng thái</th>
            <th class="text-center pe-3">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $i => $row):
            [$lbl, $clr] = $statusMap[$row['status']] ?? [$row['status'], 'secondary'];
            $noCost = (int)$row['cost_count'] === 0;
          ?>
          <tr class="<?= $noCost ? 'table-warning' : '' ?>"
              data-id="<?= (int)$row['id'] ?>"
              style="cursor:pointer">
            <td class="ps-3 text-muted"><?= $i + 1 ?></td>
            <td>
              <div class="fw-semibold text-primary"><?= htmlspecialchars($row['hawb']) ?></div>
              <?php if (!empty($row['mawb'])): ?>
              <div class="text-muted" style="font-size:0.78rem"><?= htmlspecialchars($row['mawb']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div><?= htmlspecialchars($row['company_name'] ?? $row['customer_code'] ?? '-') ?></div>
              <?php if (!empty($row['customer_phone'])): ?>
              <div class="text-muted" style="font-size:0.78rem"><?= htmlspecialchars($row['customer_phone']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="fw-semibold"><?= (int)$row['packages'] ?></span>
              <span class="text-muted">&nbsp;/&nbsp;</span>
              <span><?= number_format((float)$row['weight'], 1) ?> kg</span>
            </td>
            <td class="text-center">
              <?= $row['active_date'] ? date('d/m/Y', strtotime($row['active_date'])) : '-' ?>
            </td>
            <td class="text-center">
              <?php if (!empty($row['note_code'])): ?>
              <div class="small fw-semibold"><?= htmlspecialchars($row['note_code']) ?></div>
              <?php if (!empty($row['printed_at'])): ?>
              <div class="text-muted" style="font-size:0.75rem">
                🖨️ <?= date('d/m H:i', strtotime($row['printed_at'])) ?>
              </div>
              <?php endif; ?>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="badge bg-<?= $clr ?>"><?= $lbl ?></span>
            </td>
            <td class="text-center pe-3">
              <div class="d-flex gap-1 justify-content-center flex-wrap">
                <!-- Nhập chi phí -->
                <button type="button" class="btn btn-xs btn-outline-primary"
                        style="font-size:0.75rem;padding:2px 8px"
                        onclick="event.stopPropagation();dbOpenCostModal(<?= (int)$row['id'] ?>, <?= json_encode($row['hawb']) ?>)"
                        title="Nhập chi phí">💰</button>

                <!-- Xác nhận giao (chỉ khi in_transit) -->
                <?php if ($row['status'] === 'in_transit' && in_array($role, ['ops','accounting','admin'])): ?>
                <button type="button" class="btn btn-xs btn-outline-success"
                        style="font-size:0.75rem;padding:2px 8px"
                        onclick="event.stopPropagation();dbConfirmDelivered(<?= (int)$row['id'] ?>)"
                        title="Xác nhận đã giao">✅</button>
                <?php endif; ?>

                <!-- In biên bản -->
                <a href="<?= BASE_URL ?>/?page=ops.print_delivery_note&id=<?= (int)$row['id'] ?>"
                   target="_blank" class="btn btn-xs btn-outline-secondary"
                   style="font-size:0.75rem;padding:2px 8px"
                   onclick="event.stopPropagation()"
                   title="In biên bản giao hàng">🖨️</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ═══ Modal nhập chi phí ═══ -->
<div class="modal fade" id="dbCostModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(90deg,#1e3a5f,#2d6a9f);color:#fff">
        <h6 class="modal-title fw-bold mb-0">💰 Nhập chi phí — <span id="dbCostModalHawb"></span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="dbCostModalBody">
        <div class="text-center py-4 text-muted">
          <div class="spinner-border spinner-border-sm me-2"></div> Đang tải...
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.btn-xs { font-size: 0.75rem; padding: 2px 8px; line-height: 1.4; }
</style>

<script>
// ── Mở modal nhập chi phí ──────────────────────────────────
let _dbCostModal = null;
let _dbCurrentShipmentId = 0;
window.dbOpsRowIdx = 1;

function dbOpenCostModal(id, hawb) {
  _dbCurrentShipmentId = id;
  document.getElementById('dbCostModalHawb').textContent = hawb;
  document.getElementById('dbCostModalBody').innerHTML =
    '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Đang tải...</div>';

  if (!_dbCostModal) {
    _dbCostModal = new bootstrap.Modal(document.getElementById('dbCostModal'));
  }
  _dbCostModal.show();

  fetch('<?= BASE_URL ?>/?page=shared.delivery_board_cost_form&id=' + id)
    .then(r => r.text())
    .then(html => {
      const modalBody = document.getElementById('dbCostModalBody');
      modalBody.innerHTML = html;
      // Re-execute <script> tags (innerHTML does NOT run scripts)
      modalBody.querySelectorAll('script').forEach(oldScript => {
        const newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach(a => newScript.setAttribute(a.name, a.value));
        newScript.textContent = oldScript.textContent;
        document.body.appendChild(newScript);
        oldScript.remove();
      });
    })
    .catch(() => {
      document.getElementById('dbCostModalBody').innerHTML =
        '<div class="p-3 text-danger">❌ Lỗi tải dữ liệu.</div>';
    });
}

// ── Thêm dòng chi phí OPS ─────────────────────────────────
function dbAddOpsRow() {
  const container = document.getElementById('dbOpsCostRows');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'db-ops-row d-flex gap-2 mb-2 align-items-center';
  div.innerHTML = `
    <input type="text" name="ops_costs[${window.dbOpsRowIdx}][name]"
           class="form-control form-control-sm" placeholder="Tên chi phí">
    <input type="number" name="ops_costs[${window.dbOpsRowIdx}][amount]"
           class="form-control form-control-sm" placeholder="Số tiền"
           style="width:140px" step="1000" min="0">
    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
            onclick="this.closest('.db-ops-row').remove()">✕</button>
  `;
  container.appendChild(div);
  window.dbOpsRowIdx++;
}

// ── Lưu chi phí qua AJAX ──────────────────────────────────
function dbSaveCosts() {
  const form = document.getElementById('dbCostForm');
  if (!form) return;
  const btn = document.getElementById('dbSaveBtn');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Đang lưu...'; }

  const data = new FormData(form);
  fetch('<?= BASE_URL ?>/?page=shared.delivery_board_save_costs', {
    method: 'POST',
    body: data
  })
  .then(r => r.json())
  .then(d => {
    if (btn) { btn.disabled = false; btn.textContent = '💾 Lưu chi phí'; }
    if (d.success) {
      if (btn) { btn.textContent = '✅ Đã lưu!'; }
      setTimeout(() => {
        if (_dbCostModal) _dbCostModal.hide();
        location.reload();
      }, 1000);
    } else {
      alert(d.message || 'Lỗi lưu chi phí!');
    }
  })
  .catch(() => {
    if (btn) { btn.disabled = false; btn.textContent = '💾 Lưu chi phí'; }
    alert('Lỗi kết nối!');
  });
}

// ── Xác nhận giao hàng ────────────────────────────────────
function dbConfirmDelivered(id) {
  if (!confirm('Xác nhận lô hàng đã giao? Trạng thái sẽ chuyển sang KT duyệt.')) return;

  fetch('<?= BASE_URL ?>/?page=shared.delivery_board_confirm', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'shipment_id=' + id
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      location.reload();
    } else {
      alert(d.message || 'Lỗi xác nhận!');
    }
  })
  .catch(() => alert('Lỗi kết nối!'));
}
</script>
