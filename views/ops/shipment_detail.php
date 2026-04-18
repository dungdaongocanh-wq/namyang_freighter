<?php
$statusColors = [
    'pending_customs'  => ['#fee2e2','#b91c1c'],
    'cleared'          => ['#fef9c3','#92400e'],
    'waiting_pickup'   => ['#ffedd5','#c2410c'],
    'in_transit'       => ['#dbeafe','#1d4ed8'],
    'delivered'        => ['#dcfce7','#15803d'],
    'kt_reviewing'     => ['#e0f2fe','#0369a1'],
    'pending_approval' => ['#f3e8ff','#7e22ce'],
];
$statusLabels = [
    'pending_customs'  => 'Chờ tờ khai',
    'cleared'          => 'Đã thông quan',
    'waiting_pickup'   => 'Chờ lấy hàng',
    'in_transit'       => 'Đang giao',
    'delivered'        => 'Đã giao',
    'kt_reviewing'     => 'KT đang duyệt',
    'pending_approval' => 'Chờ KH duyệt',
];
$sc = $statusColors[$shipment['status']] ?? ['#f1f5f9','#334155'];
$sl = $statusLabels[$shipment['status']] ?? $shipment['status'];
?>

<!-- Header card -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div>
        <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($shipment['hawb']) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($shipment['mawb'] ?? '') ?></small>
      </div>
      <span class="badge fs-6 px-3 py-2"
            style="background:<?= $sc[0] ?>;color:<?= $sc[1] ?>">
        <?= $sl ?>
      </span>
    </div>

    <div class="row g-2 mt-1">
      <div class="col-6">
        <div style="font-size:0.72rem;color:#94a3b8">KHÁCH HÀNG</div>
        <div class="fw-semibold small"><?= htmlspecialchars($shipment['customer_code']) ?></div>
        <div style="font-size:0.75rem;color:#64748b"><?= htmlspecialchars($shipment['company_name'] ?? '') ?></div>
      </div>
      <div class="col-6">
        <div style="font-size:0.72rem;color:#94a3b8">CHUYẾN BAY</div>
        <div class="fw-semibold small"><?= htmlspecialchars($shipment['flight_no'] ?? '-') ?></div>
        <div style="font-size:0.75rem;color:#64748b">
          ETA: <?= $shipment['eta'] ? date('d/m/Y', strtotime($shipment['eta'])) : '-' ?>
        </div>
      </div>
      <div class="col-6">
        <div style="font-size:0.72rem;color:#94a3b8">SỐ KIỆN / KG</div>
        <div class="fw-semibold small">
          <?= $shipment['packages'] ?> kiện / <?= number_format($shipment['weight'],1) ?> kg
        </div>
      </div>
      <div class="col-6">
        <div style="font-size:0.72rem;color:#94a3b8">ACTIVE DATE</div>
        <div class="fw-semibold small"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></div>
      </div>
    </div>

    <?php if ($shipment['remark']): ?>
    <div class="mt-2 p-2 rounded-2" style="background:#fffbeb;font-size:0.8rem">
      📝 <?= htmlspecialchars($shipment['remark']) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- CD / Tờ khai -->
<?php if (!empty($customs)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📂 Tờ khai hải quan</h6>
    <?php foreach ($customs as $cd): ?>
    <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded-2"
         style="background:#f8fafc">
      <div>
        <span class="fw-semibold small"><?= htmlspecialchars($cd['cd_number'] ?? 'CD') ?></span>
        <?php if ($cd['cd_status'] === 'TQ'): ?>
        <span class="badge bg-danger ms-1" style="font-size:0.6rem">TQ</span>
        <?php endif; ?>
      </div>
      <?php if ($cd['file_path']): ?>
      <a href="<?= BASE_URL ?>/<?= $cd['file_path'] ?>" target="_blank"
         class="btn btn-sm btn-outline-primary" style="font-size:0.75rem">
        📄 Xem file
      </a>
      <?php else: ?>
      <span class="badge bg-warning text-dark" style="font-size:0.7rem">Chưa có file</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Upload ảnh -->
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📷 Ảnh lô hàng</h6>

    <!-- Gallery ảnh đã có -->
    <?php if (!empty($photos)): ?>
    <div class="row g-2 mb-3">
      <?php foreach ($photos as $ph): ?>
      <div class="col-4">
        <a href="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>" target="_blank">
          <img src="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>"
               class="img-fluid rounded-2"
               style="height:100px;width:100%;object-fit:cover">
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Upload form -->
    <div id="uploadArea"
         onclick="document.getElementById('photoInput').click()"
         style="border:2px dashed #cbd5e1;border-radius:12px;padding:20px;text-align:center;cursor:pointer">
      <div style="font-size:2rem">📷</div>
      <div class="small text-muted mt-1">Chụp hoặc chọn ảnh</div>
    </div>
    <input type="file" id="photoInput" multiple accept="image/*"
           capture="environment" class="d-none"
           onchange="uploadPhotos(this)">

    <!-- Preview ảnh mới -->
    <div id="photoPreview" class="row g-2 mt-2"></div>

    <div id="uploadProgress" class="d-none mt-2">
      <div class="progress" style="height:6px;border-radius:3px">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100"></div>
      </div>
      <small class="text-muted">Đang upload...</small>
    </div>
  </div>
</div>

<!-- Action buttons -->
<div class="d-grid gap-2 mb-3">
  <?php if ($shipment['status'] === 'cleared'): ?>
  <a href="<?= BASE_URL ?>/?page=ops.download_customs"
     class="btn btn-mobile-primary btn-warning">
    ⬇️ Tải tờ khai TQ
  </a>
  <?php endif; ?>

  <?php if ($shipment['status'] === 'waiting_pickup'): ?>
  <a href="<?= BASE_URL ?>/?page=ops.trip"
     class="btn btn-mobile-primary btn-primary">
    🚚 Tạo chuyến giao
  </a>
  <?php endif; ?>

  <?php if ($shipment['status'] === 'in_transit'): ?>
  <button class="btn btn-mobile-primary btn-success"
          onclick="markDelivered(<?= $shipment['id'] ?>)">
    ✅ Đánh dấu đã giao
  </button>
  <?php endif; ?>

  <?php if (in_array($shipment['status'], ['waiting_pickup','in_transit','delivered','kt_reviewing','pending_approval','rejected','debt','invoiced'])): ?>
  <a href="<?= BASE_URL ?>/?page=ops.print_delivery_note&id=<?= $shipment['id'] ?>"
     target="_blank"
     class="btn btn-mobile-primary btn-outline-secondary">
    🖨️ In biên bản giao hàng
  </a>
  <?php endif; ?>

  <a href="<?= BASE_URL ?>/?page=ops.costs&id=<?= $shipment['id'] ?>"
     class="btn btn-mobile-primary btn-outline-primary">
    💰 Nhập chi phí
  </a>
</div>

<!-- Lịch sử -->
<?php if (!empty($logs)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body">
    <h6 class="fw-bold mb-3">📜 Lịch sử</h6>
    <?php foreach ($logs as $log): ?>
    <div class="d-flex gap-2 mb-2" style="font-size:0.8rem">
      <div style="width:6px;height:6px;background:#2d6a9f;border-radius:50%;margin-top:6px;flex-shrink:0"></div>
      <div>
        <span class="fw-semibold"><?= htmlspecialchars($log['full_name'] ?? 'System') ?></span>
        <span class="text-muted"> — <?= htmlspecialchars($log['action'] ?? '') ?></span>
        <?php if ($log['note']): ?>
        <div class="text-muted small"><?= htmlspecialchars($log['note']) ?></div>
        <?php endif; ?>
        <div style="color:#94a3b8;font-size:0.7rem">
          <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
function uploadPhotos(input) {
  if (!input.files.length) return;

  // Preview
  const preview = document.getElementById('photoPreview');
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

  // Upload
  const formData = new FormData();
  formData.append('shipment_id', '<?= $shipment['id'] ?>');
  Array.from(input.files).forEach(f => formData.append('photos[]', f));

  document.getElementById('uploadProgress').classList.remove('d-none');

  fetch('<?= BASE_URL ?>/?page=ops.upload_photos', {method:'POST', body:formData})
    .then(r => r.json())
    .then(data => {
      document.getElementById('uploadProgress').classList.add('d-none');
      if (data.success) {
        location.reload();
      }
    }).catch(() => {
      document.getElementById('uploadProgress').classList.add('d-none');
      alert('Lỗi upload ảnh!');
    });
}

function markDelivered(id) {
  if (!confirm('Xác nhận lô đã được giao?')) return;
  fetch('<?= BASE_URL ?>/?page=ops.complete', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'shipment_id=' + id
  }).then(r => r.json()).then(d => {
    if (d.success) location.href = '<?= BASE_URL ?>/?page=ops.dashboard';
  });
}
</script>