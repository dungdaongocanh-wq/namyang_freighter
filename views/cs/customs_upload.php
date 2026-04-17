<?php
$statusMap = [
    'pending_customs'  => ['Chờ TK',        'danger'],
    'cleared'          => ['Đã TQ',          'success'],
    'waiting_pickup'   => ['Chờ lấy hàng',  'warning'],
    'delivered'        => ['Đã giao',        'secondary'],
    'pending_approval' => ['Chờ duyệt',      'info'],
];
?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">📂 Upload tờ khai hải quan</h6>
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
            <th>MAWB</th>
            <th>Flight / ETA</th>
            <th>Trạng thái</th>
            <th>Tờ khai</th>
            <th class="text-center">Upload</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s):
            [$stLabel, $stClr] = $statusMap[$s['status']] ?? ['?', 'secondary'];
            $uploadedCount = (int)$s['uploaded_count'];
            $cdCount       = (int)$s['cd_count'];
          ?>
          <tr>
            <td class="ps-4 fw-semibold text-primary">
              <?= htmlspecialchars($s['hawb']) ?>
            </td>
            <td>
              <span class="badge bg-light text-dark border">
                <?= htmlspecialchars($s['customer_code'] ?? '') ?>
              </span>
            </td>
            <td class="text-muted small"><?= htmlspecialchars($s['mawb'] ?? '') ?></td>
            <td>
              <div><?= htmlspecialchars($s['flight_no'] ?? '') ?></div>
              <div class="text-muted small">
                <?= $s['eta'] ? date('d/m/Y', strtotime($s['eta'])) : '-' ?>
              </div>
            </td>
            <td><span class="badge bg-<?= $stClr ?>"><?= $stLabel ?></span></td>
            <td class="small">
              <?php if ($cdCount > 0): ?>
                <?= htmlspecialchars($s['cd_numbers']) ?>
                <div class="text-muted">
                  <?= $uploadedCount ?>/<?= $cdCount ?> đã upload
                </div>
              <?php else: ?>
                <span class="text-muted">Chưa có</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <!-- Modal trigger -->
              <button class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#uploadModal"
                      data-id="<?= $s['id'] ?>"
                      data-hawb="<?= htmlspecialchars($s['hawb']) ?>"
                      data-cdnumbers="<?= htmlspecialchars($s['cd_numbers'] ?? '') ?>">
                📤 Upload
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
        <div class="modal-header">
          <h6 class="modal-title fw-bold">📤 Upload tờ khai — <span id="modalHawb"></span></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="shipment_id" id="modalShipmentId">

          <div class="mb-3">
            <label class="form-label fw-semibold">Số tờ khai (CD Number)</label>
            <input type="text" name="cd_number" id="modalCdNumber"
                   class="form-control" placeholder="VD: 108106223740" required>
            <div class="form-text text-muted" id="modalCdHint"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">File tờ khai</label>
            <input type="file" name="customs_file" class="form-control"
                   accept=".pdf,.jpg,.jpeg,.png" required>
            <div class="form-text">PDF, JPG, PNG — tối đa 10MB</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary px-4">💾 Upload</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('uploadModal').addEventListener('show.bs.modal', function(e) {
  const btn      = e.relatedTarget;
  const id       = btn.getAttribute('data-id');
  const hawb     = btn.getAttribute('data-hawb');
  const cdNumbers = btn.getAttribute('data-cdnumbers');

  document.getElementById('modalShipmentId').value = id;
  document.getElementById('modalHawb').textContent  = hawb;
  document.getElementById('modalCdNumber').value    = '';

  if (cdNumbers) {
    document.getElementById('modalCdHint').textContent = 'Tờ khai hiện có: ' + cdNumbers;
  } else {
    document.getElementById('modalCdHint').textContent = '';
  }
});
</script>