<?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  ✅ Upload tờ khai thành công! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error ?? null): ?>
<div class="alert alert-danger mb-4" style="border-radius:10px">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">📂 Upload tờ khai hải quan</h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">✅</div>
      <p class="mt-2">Không có lô nào cần upload tờ khai</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>KH</th>
            <th>Active date</th>
            <th>CD Numbers</th>
            <th>Tiến độ</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s):
            $db2 = getDB();
            $cds = $db2->prepare("SELECT * FROM shipment_customs WHERE shipment_id=? ORDER BY id");
            $cds->execute([$s['id']]);
            $cdList = $cds->fetchAll();
          ?>
          <tr>
            <td class="ps-4">
              <span class="fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></span>
            </td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['customer_code']) ?></span></td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td>
              <?php if (empty($cdList)): ?>
              <span class="text-muted small">Chưa có CD</span>
              <?php else: ?>
              <?php foreach ($cdList as $cd): ?>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-light text-dark border" style="font-size:0.75rem">
                  <?= htmlspecialchars($cd['cd_number'] ?? 'CD') ?>
                  <?php if ($cd['cd_status'] === 'TQ'): ?>
                  <span class="badge bg-danger ms-1" style="font-size:0.6rem">TQ</span>
                  <?php endif; ?>
                </span>
                <?php if ($cd['file_path']): ?>
                <span class="text-success small">✅ Đã có</span>
                <a href="<?= BASE_URL ?>/<?= $cd['file_path'] ?>" target="_blank"
                   class="btn btn-xs btn-outline-primary" style="font-size:0.7rem;padding:1px 6px">Xem</a>
                <?php else: ?>
                <span class="text-danger small">⏳ Chưa có</span>
                <!-- Upload inline -->
                <form method="POST" enctype="multipart/form-data"
                      action="<?= BASE_URL ?>/?page=cs.customs_upload"
                      class="d-inline" onsubmit="showLoading(this)">
                  <input type="hidden" name="shipment_id" value="<?= $s['id'] ?>">
                  <input type="hidden" name="cd_id" value="<?= $cd['id'] ?>">
                  <input type="file" name="customs_file" accept=".pdf,.jpg,.jpeg,.png"
                         class="form-control form-control-sm d-inline-block"
                         style="width:180px;font-size:0.75rem"
                         onchange="this.form.submit()">
                </form>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($s['cd_count'] > 0): ?>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px;border-radius:4px;width:80px">
                  <div class="progress-bar bg-success"
                       style="width:<?= $s['cd_count'] > 0 ? ($s['uploaded_count']/$s['cd_count']*100) : 0 ?>%">
                  </div>
                </div>
                <small class="text-muted"><?= $s['uploaded_count'] ?>/<?= $s['cd_count'] ?></small>
              </div>
              <?php else: ?>
              <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($s['uploaded_count'] >= $s['cd_count'] && $s['cd_count'] > 0): ?>
              <span class="badge bg-success">✅ Hoàn thành</span>
              <?php else: ?>
              <span class="badge bg-warning text-dark">⏳ Chờ upload</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function showLoading(form) {
  const btn = form.querySelector('input[type=file]');
  if (btn) btn.disabled = true;
}
</script>