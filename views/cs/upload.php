<?php
$importFilename = $_SESSION['import_filename'] ?? '';
$preview   = $preview   ?? [];
$result    = $result    ?? null;
$error     = $error     ?? '';
$dupeCount = count(array_filter($preview, fn($r) => !empty($r['is_duplicate'])));
?>

<div class="row justify-content-center">
<div class="col-lg-10">

<?php if ($result): ?>
<div class="alert alert-success alert-dismissible border-0 shadow-sm mb-4" style="border-radius:12px">
  <h6 class="fw-bold mb-2">✅ Import hoàn tất!</h6>
  <div class="row text-center g-2">
    <div class="col-3">
      <div class="fw-bold text-success fs-4"><?= $result['inserted'] ?></div>
      <small>Thêm mới</small>
    </div>
    <div class="col-3">
      <div class="fw-bold text-primary fs-4"><?= $result['updated'] ?></div>
      <small>Cập nhật</small>
    </div>
    <div class="col-3">
      <div class="fw-bold text-warning fs-4"><?= $result['skipped'] ?></div>
      <small>Bỏ qua</small>
    </div>
    <div class="col-3">
      <div class="fw-bold text-danger fs-4"><?= count($result['warnings'] ?? []) ?></div>
      <small>Cảnh báo</small>
    </div>
  </div>
  <?php if (!empty($result['warnings'])): ?>
  <hr>
  <small class="fw-semibold">⚠️ Cảnh báo:</small>
  <div class="mt-1">
    <?php foreach ($result['warnings'] as $w): ?>
    <span class="badge bg-warning text-dark me-1"><?= htmlspecialchars($w) ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger mb-4" style="border-radius:12px">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Upload card -->
<div class="card mb-4" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">📤 Upload file Excel lô hàng</h6>
  </div>
  <div class="card-body px-4 pb-4">
    <div class="alert alert-info py-2 mb-4" style="border-radius:10px;font-size:0.8rem">
      <strong>Mapping cột:</strong>
      A=KH · B=HAWB · C=MAWB · D=Flight · E=POL · F=ETD · G=ETA · H=Kiện · I=KG · J=CD Numbers · K=CD Status · L=Remark
    </div>

    <form id="previewForm" method="POST" enctype="multipart/form-data"
          action="<?= BASE_URL ?>/?page=cs.upload">
      <input type="hidden" name="action" value="preview">

      <div id="dropZone"
           onclick="document.getElementById('excelFile').click()"
           ondragover="event.preventDefault();this.style.borderColor='#2d6a9f'"
           ondragleave="this.style.borderColor='#cbd5e1'"
           ondrop="handleDrop(event)"
           style="border:2px dashed #cbd5e1;border-radius:16px;padding:48px;
                  text-align:center;cursor:pointer;transition:all 0.2s;background:#f8fafc">
        <div style="font-size:3rem">📊</div>
        <h6 class="mt-2 mb-1 fw-semibold">Kéo thả file Excel vào đây</h6>
        <p class="text-muted small mb-0">hoặc click để chọn file · Hỗ trợ .xlsx, .xls</p>
      </div>

      <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls"
             class="d-none" onchange="handleFileSelect(this)">

      <div id="selectedFile" class="mt-3 d-none">
        <div class="d-flex align-items-center gap-3 p-3"
             style="background:#f0f7ff;border-radius:10px;border:1px solid #bfdbfe">
          <span style="font-size:2rem">📄</span>
          <div class="flex-grow-1">
            <div class="fw-semibold" id="selectedFileName"></div>
            <small class="text-muted" id="selectedFileSize"></small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">✕</button>
        </div>
        <button type="submit" class="btn btn-primary mt-3 px-5">
          🔍 Preview dữ liệu
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Preview table -->
<?php if (!empty($preview)): ?>
<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">📋 Preview - <?= count($preview) ?> dòng</h6>
    <?php if ($dupeCount > 0): ?>
    <span class="badge bg-danger"><?= $dupeCount ?> trùng</span>
    <?php endif; ?>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/?page=cs.upload" id="importForm">
    <input type="hidden" name="action" value="import">

    <?php if ($dupeCount > 0): ?>
    <div class="px-4 pt-3">
      <div class="alert alert-warning py-2 mb-0" style="border-radius:10px">
        <div class="form-check mb-0">
          <input class="form-check-input" type="checkbox" name="update_duplicates" value="1" id="updateDupes">
          <label class="form-check-label fw-semibold small" for="updateDupes">
            ⚠️ Cập nhật <strong><?= $dupeCount ?></strong> HAWB đã tồn tại (thay vì bỏ qua)
          </label>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:450px;overflow-y:auto">
        <table class="table table-sm table-hover mb-0 align-middle" style="font-size:0.8rem">
          <thead class="table-light sticky-top">
            <tr>
              <th class="ps-4">#</th>
              <th>HAWB</th>
              <th>Khách hàng</th>
              <th>MAWB</th>
              <th>Flight</th>
              <th>ETA</th>
              <th>Kiện</th>
              <th>KG</th>
              <th>CD No</th>
              <th>CD Status</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($preview as $i => $row):
              $isDuplicate    = !empty($row['is_duplicate']);
              $customerFound  = !empty($row['customer_found']);
              $initialStatus  = $row['initial_status'] ?? 'pending_customs';
              $stMap = [
                'pending_customs' => ['Chờ TK', 'danger'],
                'cleared'         => ['Đã TQ',  'warning'],
                'waiting_pickup'  => ['Chờ lấy','warning'],
              ];
              [$stLabel, $stClr] = $stMap[$initialStatus] ?? ['Chờ TK', 'secondary'];
            ?>
            <tr <?= $isDuplicate ? 'style="background:#fff1f2"' : '' ?>>
              <td class="ps-4 text-muted"><?= $i + 1 ?></td>

              <td>
                <span class="fw-semibold <?= $isDuplicate ? 'text-danger' : 'text-primary' ?>">
                  <?= htmlspecialchars($row['hawb'] ?? '') ?>
                </span>
                <?php if ($isDuplicate): ?>
                <span class="badge bg-danger ms-1" style="font-size:0.6rem">TRÙNG</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($customerFound): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                  <?= htmlspecialchars($row['customer_code'] ?? '') ?>
                </span>
                <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"
                      title="Không tìm thấy trong hệ thống">
                  <?= htmlspecialchars($row['customer_code'] ?? '') ?> ⚠️
                </span>
                <?php endif; ?>
              </td>

              <td><?= htmlspecialchars($row['mawb'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['flight_no'] ?? '') ?></td>
              <td><?= !empty($row['eta']) ? date('d/m/Y', strtotime($row['eta'])) : '-' ?></td>
              <td><?= (int)($row['packages'] ?? 0) ?></td>
              <td><?= number_format((float)($row['weight'] ?? 0), 1) ?></td>
              <td class="small">
                <?php
                $cdNums = $row['cd_numbers'] ?? [];
                echo htmlspecialchars(is_array($cdNums) ? implode(', ', $cdNums) : $cdNums);
                ?>
              </td>
              <td><?= htmlspecialchars($row['cd_status'] ?? '') ?></td>
              <td><span class="badge bg-<?= $stClr ?>"><?= $stLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card-footer bg-white px-4 py-3 d-flex gap-2"
         style="border-radius:0 0 16px 16px">
      <button type="submit" class="btn btn-success px-4 fw-semibold">
        ✅ Import <?= count($preview) ?> dòng
      </button>
      <a href="<?= BASE_URL ?>/?page=cs.upload" class="btn btn-outline-secondary">Hủy</a>
    </div>
  </form>
</div>
<?php endif; ?>

</div>
</div>

<script>
function handleFileSelect(input) {
  if (input.files && input.files[0]) showFile(input.files[0]);
}
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor = '#cbd5e1';
  const file = e.dataTransfer.files[0];
  if (file) {
    document.getElementById('excelFile').files = e.dataTransfer.files;
    showFile(file);
  }
}
function showFile(file) {
  document.getElementById('selectedFile').classList.remove('d-none');
  document.getElementById('dropZone').style.display = 'none';
  document.getElementById('selectedFileName').textContent = file.name;
  document.getElementById('selectedFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
}
function clearFile() {
  document.getElementById('selectedFile').classList.add('d-none');
  document.getElementById('dropZone').style.display = '';
  document.getElementById('excelFile').value = '';
}
</script>