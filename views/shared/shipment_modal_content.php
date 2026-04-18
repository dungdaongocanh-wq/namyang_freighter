<?php
// Shipment Offcanvas Content — HTML fragment, NO layout
// Variables available: $s, $customsList, $photoList, $costList, $logList, $totalCost, $signature

$statusMap = [
    'pending_customs'  => ['Chờ tờ khai',   'background:#fee2e2;color:#b91c1c'],
    'cleared'          => ['Đã thông quan',  'background:#fef9c3;color:#92400e'],
    'waiting_pickup'   => ['Chờ lấy hàng',  'background:#ffedd5;color:#c2410c'],
    'in_transit'       => ['Đang giao',      'background:#dbeafe;color:#1d4ed8'],
    'delivered'        => ['Đã giao',        'background:#dcfce7;color:#15803d'],
    'kt_reviewing'     => ['KT duyệt',       'background:#e0f2fe;color:#0369a1'],
    'pending_approval' => ['Chờ KH duyệt',   'background:#f3e8ff;color:#7e22ce'],
    'rejected'         => ['KH từ chối',     'background:#f1f5f9;color:#334155'],
    'debt'             => ['Công nợ',         'background:#f0fdf4;color:#15803d'],
    'invoiced'         => ['Đã xuất HĐ',     'background:#dcfce7;color:#15803d'],
    'cancelled'        => ['Đã huỷ',         'background:#f1f5f9;color:#94a3b8'],
];
[$statusLbl, $statusStyle] = $statusMap[$s['status']] ?? [$s['status'], 'background:#f1f5f9;color:#334155'];
$role = $_SESSION['role'] ?? '';
?>
<style>
.oc-tab-nav .nav-link { font-size:0.8rem; padding:6px 10px; white-space:nowrap; }
.oc-info-row { display:flex; gap:8px; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:0.85rem; }
.oc-info-label { color:#64748b; flex:0 0 130px; }
.oc-info-val { color:#1e293b; font-weight:500; flex:1; word-break:break-word; }
.oc-empty { text-align:center; padding:2rem 1rem; color:#94a3b8; }
.oc-empty .oc-empty-icon { font-size:2.2rem; display:block; margin-bottom:8px; }
.oc-timeline-item { display:flex; gap:12px; margin-bottom:14px; }
.oc-timeline-dot { width:30px; height:30px; border-radius:50%; background:#e0f2fe; color:#0369a1;
                   display:flex; align-items:center; justify-content:center; font-size:0.8rem;
                   flex-shrink:0; margin-top:2px; }
.oc-photo-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.oc-photo-grid a img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:8px;
                        border:2px solid transparent; transition:border-color 0.15s; }
.oc-photo-grid a:hover img { border-color:#2d6a9f; }
</style>

<!-- ── Header ── -->
<div class="p-3 border-bottom" style="background:#f8fafc">
  <div class="d-flex justify-content-between align-items-start gap-2">
    <div>
      <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($s['hawb']) ?></h5>
      <small class="text-muted"><?= htmlspecialchars($s['company_name'] ?? $s['customer_code'] ?? '') ?></small>
    </div>
    <span class="badge flex-shrink-0" style="<?= $statusStyle ?>;padding:5px 10px;font-size:0.78rem">
      <?= $statusLbl ?>
    </span>
  </div>
</div>

<!-- ── Tabs ── -->
<ul class="nav nav-tabs px-3 pt-2 oc-tab-nav flex-nowrap overflow-auto" id="ocModalTabs" role="tablist"
    style="scrollbar-width:none">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#oc-tab-info"
            type="button" role="tab">ℹ️ <span class="d-none d-sm-inline">Thông tin</span></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-customs"
            type="button" role="tab">
      📂 <span class="d-none d-sm-inline">Tờ khai</span>
      <?php if (!empty($customsList)): ?>
      <span class="badge bg-primary rounded-pill ms-1" style="font-size:0.65rem"><?= count($customsList) ?></span>
      <?php endif; ?>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-photos"
            type="button" role="tab">
      📷 <span class="d-none d-sm-inline">Ảnh</span>
      <?php if (!empty($photoList)): ?>
      <span class="badge bg-primary rounded-pill ms-1" style="font-size:0.65rem"><?= count($photoList) ?></span>
      <?php endif; ?>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-costs"
            type="button" role="tab">💰 <span class="d-none d-sm-inline">Chi phí</span></button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#oc-tab-logs"
            type="button" role="tab">📋 <span class="d-none d-sm-inline">Lịch sử</span></button>
  </li>
</ul>

<div class="tab-content p-3">

  <!-- ── Tab: Thông tin ── -->
  <div class="tab-pane fade show active" id="oc-tab-info" role="tabpanel">
    <div class="mb-3">
      <div class="oc-info-row"><span class="oc-info-label">HAWB</span><span class="oc-info-val fw-bold text-primary"><?= htmlspecialchars($s['hawb']) ?></span></div>
      <div class="oc-info-row"><span class="oc-info-label">MAWB</span><span class="oc-info-val"><?= htmlspecialchars($s['mawb'] ?? '-') ?></span></div>
      <div class="oc-info-row"><span class="oc-info-label">Chuyến bay</span><span class="oc-info-val"><?= htmlspecialchars($s['flight_no'] ?? '-') ?></span></div>
      <div class="oc-info-row"><span class="oc-info-label">POL / ETD</span><span class="oc-info-val"><?= htmlspecialchars($s['pol'] ?? '-') ?> / <?= $s['etd'] ? date('d/m/Y', strtotime($s['etd'])) : '-' ?></span></div>
      <div class="oc-info-row"><span class="oc-info-label">ETA</span><span class="oc-info-val"><?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?></span></div>
      <div class="oc-info-row"><span class="oc-info-label">Kiện / Trọng lượng</span><span class="oc-info-val"><?= (int)$s['packages'] ?> kiện / <?= number_format((float)$s['weight'], 1) ?> kg</span></div>
      <div class="oc-info-row"><span class="oc-info-label">Active date</span><span class="oc-info-val"><?= $s['active_date'] ? date('d/m/Y', strtotime($s['active_date'])) : '-' ?></span></div>
      <?php if (!empty($s['remark'])): ?>
      <div class="oc-info-row"><span class="oc-info-label">Ghi chú CS</span><span class="oc-info-val text-muted"><?= htmlspecialchars($s['remark']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($s['customer_note'])): ?>
      <div class="oc-info-row"><span class="oc-info-label">Ghi chú KH</span><span class="oc-info-val text-muted"><?= htmlspecialchars($s['customer_note']) ?></span></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($s['company_name']) || !empty($s['email']) || !empty($s['phone'])): ?>
    <div class="p-3 rounded-3" style="background:#f0f7ff;font-size:0.83rem">
      <div class="fw-semibold text-primary mb-2">🏢 Thông tin khách hàng</div>
      <?php if (!empty($s['company_name'])): ?>
      <div class="oc-info-row" style="border-color:#dbeafe"><span class="oc-info-label">Công ty</span><span class="oc-info-val"><?= htmlspecialchars($s['company_name']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($s['email'])): ?>
      <div class="oc-info-row" style="border-color:#dbeafe"><span class="oc-info-label">Email</span><span class="oc-info-val"><?= htmlspecialchars($s['email']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($s['phone'])): ?>
      <div class="oc-info-row" style="border-color:#dbeafe"><span class="oc-info-label">SĐT</span><span class="oc-info-val"><?= htmlspecialchars($s['phone']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($s['address'])): ?>
      <div class="oc-info-row" style="border-color:#dbeafe;border-bottom:none"><span class="oc-info-label">Địa chỉ</span><span class="oc-info-val"><?= htmlspecialchars($s['address']) ?></span></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Tờ khai ── -->
  <div class="tab-pane fade" id="oc-tab-customs" role="tabpanel">
    <?php if (empty($customsList)): ?>
    <div class="oc-empty">
      <span class="oc-empty-icon">📂</span>
      <p class="mb-0 small">Chưa có tờ khai nào</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0" style="font-size:0.83rem">
        <thead style="background:#f8fafc;color:#64748b">
          <tr>
            <th class="ps-2">Số tờ khai</th>
            <th>Trạng thái</th>
            <th class="text-end pe-2">File</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customsList as $cd): ?>
          <tr>
            <td class="ps-2 fw-semibold"><?= htmlspecialchars($cd['cd_number'] ?? '-') ?></td>
            <td>
              <?php if (!empty($cd['file_path'])): ?>
              <span class="badge" style="background:#dcfce7;color:#15803d">✅ Đã đính kèm</span>
              <?php else: ?>
              <span class="badge" style="background:#fee2e2;color:#b91c1c">Chưa có file</span>
              <?php endif; ?>
            </td>
            <td class="text-end pe-2">
              <?php if (!empty($cd['file_path'])): ?>
              <a href="<?= BASE_URL ?>/<?= htmlspecialchars($cd['file_path']) ?>"
                 target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"
                 style="font-size:0.75rem">⬇ Tải</a>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Ảnh ── -->
  <div class="tab-pane fade" id="oc-tab-photos" role="tabpanel">
    <?php if (empty($photoList) && !$signature && $role !== 'ops'): ?>
    <div class="oc-empty">
      <span class="oc-empty-icon">📷</span>
      <p class="mb-0 small">Chưa có ảnh nào</p>
    </div>
    <?php else: ?>
    <?php if (!empty($photoList)): ?>
    <div class="oc-photo-grid mb-3">
      <?php foreach ($photoList as $photo): ?>
      <a href="<?= BASE_URL ?>/<?= htmlspecialchars($photo['photo_path']) ?>" target="_blank"
         title="Xem ảnh gốc">
        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($photo['photo_path']) ?>"
             alt="Ảnh lô hàng"
             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect fill=%22%23f1f5f9%22 width=%2260%22 height=%2260%22/%3E%3Ctext x=%2250%25%22 y=%2255%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%2394a3b8%22 font-size=%2210%22%3E?%3C/text%3E%3C/svg%3E'">
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($signature): ?>
    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
      <div class="fw-semibold small text-muted mb-2">✍️ Chữ ký điện tử</div>
      <img src="<?= BASE_URL ?>/<?= htmlspecialchars(!empty($signature['signature_path']) ? $signature['signature_path'] : ($signature['file_path'] ?? '')) ?>"
           alt="Chữ ký" style="max-width:100%;border:1px solid #e2e8f0;border-radius:6px">
      <?php if (!empty($signature['signer_name'])): ?>
      <div class="small text-muted mt-1">Người ký: <?= htmlspecialchars($signature['signer_name']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($role === 'ops'): ?>
    <div class="mt-3">
      <div id="ocUploadArea"
           onclick="document.getElementById('ocPhotoInput').click()"
           style="border:2px dashed #cbd5e1;border-radius:12px;padding:20px;text-align:center;cursor:pointer">
        <div style="font-size:2rem">📷</div>
        <div class="small text-muted mt-1">Chụp hoặc chọn ảnh</div>
      </div>
      <input type="file" id="ocPhotoInput" multiple accept="image/*"
             capture="environment" class="d-none"
             onchange="ocUploadPhotos(this, <?= (int)$s['id'] ?>)">
      <div id="ocPhotoPreview" class="row g-2 mt-2"></div>
      <div id="ocUploadProgress" class="d-none mt-2">
        <div class="progress" style="height:6px;border-radius:3px">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100"></div>
        </div>
        <small class="text-muted">Đang upload...</small>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Chi phí ── -->
  <div class="tab-pane fade" id="oc-tab-costs" role="tabpanel">
    <?php if (empty($costList)): ?>
    <div class="oc-empty">
      <span class="oc-empty-icon">💰</span>
      <p class="mb-0 small">Chưa có chi phí nào</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0" style="font-size:0.83rem">
        <thead style="background:#f8fafc;color:#64748b">
          <tr>
            <th class="ps-2">#</th>
            <th>Tên chi phí</th>
            <th class="text-center">SL</th>
            <th class="text-end">Đơn giá</th>
            <th class="text-end pe-2">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($costList as $i => $c): ?>
          <tr>
            <td class="ps-2 text-muted"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($c['cost_name'] ?? $c['name'] ?? '-') ?></td>
            <td class="text-center"><?= (int)($c['quantity'] ?? 1) ?></td>
            <td class="text-end"><?= number_format((float)($c['unit_price'] ?? $c['amount'] ?? 0)) ?></td>
            <td class="text-end pe-2 fw-semibold"><?= number_format((float)$c['amount']) ?> đ</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#f0fdf4">
            <td colspan="4" class="ps-2 fw-bold">Tổng cộng</td>
            <td class="text-end pe-2 fw-bold text-success"><?= number_format($totalCost) ?> đ</td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Lịch sử ── -->
  <div class="tab-pane fade" id="oc-tab-logs" role="tabpanel">
    <?php if (empty($logList)): ?>
    <div class="oc-empty">
      <span class="oc-empty-icon">📋</span>
      <p class="mb-0 small">Chưa có lịch sử</p>
    </div>
    <?php else: ?>
    <div>
      <?php foreach ($logList as $log): ?>
      <div class="oc-timeline-item">
        <div class="oc-timeline-dot">🔄</div>
        <div style="font-size:0.83rem">
          <div class="fw-semibold text-dark">
            <?php if (!empty($log['from_status']) && !empty($log['to_status'])): ?>
            <?= htmlspecialchars($log['from_status']) ?> → <?= htmlspecialchars($log['to_status']) ?>
            <?php elseif (!empty($log['action'])): ?>
            <?= htmlspecialchars($log['action']) ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($log['note'])): ?>
          <div class="text-muted small"><?= htmlspecialchars($log['note']) ?></div>
          <?php endif; ?>
          <div class="text-muted" style="font-size:0.75rem">
            <?= htmlspecialchars($log['full_name'] ?? 'System') ?> ·
            <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── Footer: nút hành động theo role ── -->
<div class="border-top p-3 d-flex gap-2 flex-wrap" style="background:#f8fafc">
  <?php if (in_array($role, ['cs', 'admin'])): ?>
  <a href="<?= BASE_URL ?>/?page=cs.edit_shipment&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-primary">✏️ Sửa lô</a>
  <a href="<?= BASE_URL ?>/?page=cs.cancel&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-outline-danger">🚫 Huỷ</a>
  <?php elseif ($role === 'ops'): ?>
  <a href="<?= BASE_URL ?>/?page=ops.detail&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-primary">📋 Chi tiết OPS</a>

  <?php if ($s['status'] === 'cleared'): ?>
  <a href="<?= BASE_URL ?>/?page=ops.download_customs&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-warning">⬇️ Tải tờ khai TQ</a>
  <?php endif; ?>

  <?php if ($s['status'] === 'waiting_pickup'): ?>
  <a href="<?= BASE_URL ?>/?page=ops.trip&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-primary">🚚 Tạo chuyến giao</a>
  <?php endif; ?>

  <?php if ($s['status'] === 'in_transit'): ?>
  <button class="btn btn-sm btn-success"
          onclick="ocMarkDelivered(<?= (int)$s['id'] ?>)">✅ Đánh dấu đã giao</button>
  <?php endif; ?>

  <?php if (in_array($s['status'], ['waiting_pickup','in_transit','delivered','kt_reviewing','pending_approval','rejected','debt','invoiced'])): ?>
  <a href="<?= BASE_URL ?>/?page=ops.print_delivery_note&id=<?= (int)$s['id'] ?>"
     target="_blank"
     class="btn btn-sm btn-outline-secondary">🖨️ In biên bản giao hàng</a>
  <?php endif; ?>

  <a href="<?= BASE_URL ?>/?page=ops.costs&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-outline-primary">💰 Nhập chi phí</a>
  <?php elseif ($role === 'accounting'): ?>
  <a href="<?= BASE_URL ?>/?page=accounting.review&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-warning">🔍 Xét duyệt</a>
  <?php elseif ($role === 'customer'): ?>
  <a href="<?= BASE_URL ?>/?page=customer.shipment_detail&id=<?= (int)$s['id'] ?>"
     class="btn btn-sm btn-primary">📦 Xem chi tiết</a>
  <?php endif; ?>
</div>

<script>
function ocMarkDelivered(id) {
  if (!confirm('Xác nhận lô đã được giao?')) return;
  fetch('<?= BASE_URL ?>/?page=ops.complete', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'shipment_id=' + id
  }).then(r => r.json()).then(d => {
    if (d.success) location.reload();
  });
}

function ocUploadPhotos(input, shipmentId) {
  if (!input.files.length) return;

  const preview = document.getElementById('ocPhotoPreview');
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const col = document.createElement('div');
      col.className = 'col-4';
      col.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded-2"
                            style="height:80px;width:100%;object-fit:cover;opacity:0.6">`;
      preview.appendChild(col);
    };
    reader.readAsDataURL(file);
  });

  const formData = new FormData();
  formData.append('shipment_id', shipmentId);
  Array.from(input.files).forEach(f => formData.append('photos[]', f));

  document.getElementById('ocUploadProgress').classList.remove('d-none');

  fetch('<?= BASE_URL ?>/?page=ops.upload_photos', {method:'POST', body:formData})
    .then(r => r.json())
    .then(data => {
      document.getElementById('ocUploadProgress').classList.add('d-none');
      if (data.success) location.reload();
    }).catch(() => {
      document.getElementById('ocUploadProgress').classList.add('d-none');
      alert('Lỗi upload ảnh!');
    });
}
</script>
