<?php
$statusMap = [
    'pending_customs'  => ['Chờ TK',        'danger'],
    'cleared'          => ['Đã TQ',          'success'],
    'waiting_pickup'   => ['Chờ lấy hàng',  'warning'],
    'in_transit'       => ['Đang giao',      'primary'],
    'delivered'        => ['Đã giao',        'secondary'],
    'pending_approval' => ['Chờ duyệt',      'info'],
];
?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible">
  <?= htmlspecialchars($error) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-<?= in_array($_GET['msg'], ['deleted','record_deleted']) ? 'warning' : 'success' ?> alert-dismissible">
  <?php
    $msgMap = [
      'saved'          => '✅ Upload tờ khai thành công!',
      'deleted'        => '🗑️ Đã xoá file tờ khai!',
      'record_deleted' => '🗑️ Đã xoá số tờ khai!',
    ];
    echo $msgMap[$_GET['msg']] ?? htmlspecialchars($_GET['msg']);
  ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2"
       style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">📂 Upload tờ khai hải quan</h6>
    <span class="badge bg-primary ms-1"><?= count($shipments) ?></span>
  </div>

  <div class="card-body p-0">

    <?php if (empty($shipments)): ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:3rem">✅</div>
      <p class="mt-2">Không có lô nào cần upload tờ khai</p>
    </div>

    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:0.85rem">
        <thead class="table-light">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>KH</th>
            <th>Flight / ETA</th>
            <th>Trạng thái</th>
            <th>Tờ khai đã upload</th>
            <th class="text-center" style="width:140px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s):
            [$stLabel, $stClr] = $statusMap[$s['status']] ?? ['?', 'secondary'];
            $uploadedCount = (int)$s['uploaded_count'];
            $cdCount       = (int)$s['cd_count'];

            $cdIds   = $s['cd_ids']     ? explode(',',   $s['cd_ids'])     : [];
            $cdNums  = $s['cd_numbers'] ? explode('|||', $s['cd_numbers']) : [];
            $cdFiles = $s['cd_files']   ? explode('|||', $s['cd_files'])   : [];
          ?>
          <tr>
            <td class="ps-4">
              <div class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($s['mawb'] ?? '') ?></div>
            </td>
            <td>
              <span class="badge bg-light text-dark border">
                <?= htmlspecialchars($s['customer_code'] ?? '') ?>
              </span>
            </td>
            <td>
              <div><?= htmlspecialchars($s['flight_no'] ?? '') ?></div>
              <div class="text-muted small">
                <?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?>
              </div>
            </td>
            <td>
              <span class="badge bg-<?= $stClr ?>"><?= $stLabel ?></span>
            </td>
            <td>
              <?php if ($cdCount === 0): ?>
                <span class="text-muted small">Chưa có tờ khai</span>

              <?php else: ?>
                <?php foreach ($cdIds as $i => $cdId):
                  $cdNum   = $cdNums[$i]  ?? '';
                  $cdFile  = $cdFiles[$i] ?? '';
                  $hasFile = !empty($cdFile);
                ?>
                <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">

                  <?php if ($hasFile): ?>
                    <!-- ✅ Đã có file -->
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      ✅ <?= htmlspecialchars($cdNum) ?>
                    </span>

                    {{!-- Xem file --}}
                    <a href="<?= BASE_URL . '/' . htmlspecialchars($cdFile) ?>"
                       target="_blank"
                       class="btn btn-xs btn-outline-secondary py-0 px-1"
                       style="font-size:0.73rem" title="Xem file">
                      👁️ Xem
                    </a>

                    {{!-- Xóa file (giữ số TK) --}}
                    <form method="POST"
                          action="<?= BASE_URL ?>/?page=cs.delete_customs"
                          onsubmit="return confirm('Xoá file của tờ khai <?= htmlspecialchars(addslashes($cdNum)) ?>?')"
                          class="d-inline">
                      <input type="hidden" name="cd_id"      value="<?= $cdId ?>">
                      <input type="hidden" name="shipment_id" value="<?= $s['id'] ?>">
                      <button type="submit"
                              class="btn btn-xs btn-outline-danger py-0 px-1"
                              style="font-size:0.73rem" title="Xoá file">
                        🗑️ Xoá file
                      </button>
                    </form>

                    {{!-- Đổi file --}}
                    <button class="btn btn-xs btn-outline-warning py-0 px-1"
                            style="font-size:0.73rem"
                            data-bs-toggle="modal" data-bs-target="#uploadModal"
                            data-id="<?= $s['id'] ?>"
                            data-hawb="<?= htmlspecialchars($s['hawb']) ?>"
                            data-cdnum="<?= htmlspecialchars($cdNum) ?>"
                            title="Đính kèm lại">
                      🔄 Đổi
                    </button>

                                    <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                      ❌ <?= htmlspecialchars($cdNum) ?>
                    </span>

                    <button class="btn btn-xs btn-outline-primary py-0 px-1"
                            style="font-size:0.73rem"
                            data-bs-toggle="modal" data-bs-target="#uploadModal"
                            data-id="<?= $s['id'] ?>"
                            data-hawb="<?= htmlspecialchars($s['hawb']) ?>"
                            data-cdnum="<?= htmlspecialchars($cdNum) ?>">
                      📤 Upload
                    </button>

                    <form method="POST"
                          action="<?= BASE_URL ?>/?page=cs.delete_customs_record"
                          onsubmit="return confirm('Xoá hẳn số tờ khai <?= htmlspecialchars(addslashes($cdNum)) ?>?')"
                          class="d-inline">
                      <input type="hidden" name="cd_id" value="<?= $cdId ?>">
                      <button type="submit"
                              class="btn btn-xs btn-outline-danger py-0 px-1"
                              style="font-size:0.73rem">
                        🗑️ Xoá số TK
                      </button>
                    </form>

                  <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Progress bar -->
                <?php if ($cdCount > 0): ?>
                <div class="progress mt-1" style="height:4px">
                  <div class="progress-bar bg-success"
                       style="width:<?= $cdCount > 0 ? round(($uploadedCount/$cdCount)*100) : 0 ?>%">
                  </div>
                </div>
                <div class="text-muted" style="font-size:0.7rem">
                  <?= $uploadedCount ?>/<?= $cdCount ?> file
                </div>
                <?php endif; ?>

              <?php endif; ?>
            </td>

            <td class="text-center">
              <button class="btn btn-sm btn-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#uploadModal"
                      data-id="<?= $s['id'] ?>"
                      data-hawb="<?= htmlspecialchars($s['hawb']) ?>"
                      data-cdnum="">
                📤 Thêm tờ khai
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data"
          action="<?= BASE_URL ?>/?page=cs.customs_upload">
      <div class="modal-content" style="border-radius:12px">
        <div class="modal-header border-0 pb-0">
          <h6 class="modal-title fw-bold">
            📤 Upload tờ khai —
            <span id="modalHawb" class="text-primary"></span>
          </h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="shipment_id" id="modalShipmentId">

          <div class="mb-3">
            <label class="form-label fw-semibold">Số tờ khai (CD Number)</label>
            <input type="text" name="cd_number" id="modalCdNumber"
                   class="form-control" placeholder="VD: 108106223740" required>
            <div class="form-text text-info" id="modalCdHint"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">File tờ khai</label>
            <input type="file" name="customs_file" class="form-control"
                   accept=".pdf,.jpg,.jpeg,.png" required>
            <div class="form-text text-muted">📎 PDF, JPG, PNG — tối đa 10MB</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="submit" class="btn btn-primary px-4 fw-semibold">
            💾 Lưu & Upload
          </button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Huỷ
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('uploadModal').addEventListener('show.bs.modal', function(e) {
  const btn   = e.relatedTarget;
  const id    = btn.getAttribute('data-id');
  const hawb  = btn.getAttribute('data-hawb');
  const cdNum = btn.getAttribute('data-cdnum') || '';

  document.getElementById('modalShipmentId').value = id;
  document.getElementById('modalHawb').textContent  = hawb;
  document.getElementById('modalCdNumber').value    = cdNum;

  const hint = document.getElementById('modalCdHint');
  hint.textContent = cdNum
    ? '⚠️ Đang đính kèm lại cho tờ khai: ' + cdNum
    : '';
});
</script>