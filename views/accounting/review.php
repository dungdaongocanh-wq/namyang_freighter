<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  <?= $_GET['msg']==='pushed' ? '✅ Đã đẩy sang khách hàng!' : '✅ Đã lưu chi phí!' ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="alert alert-danger alert-dismissible mb-4" style="border-radius:10px">
  <?= $_GET['err']==='no_cost' ? '❌ Vui lòng nhập ít nhất 1 chi phí trước khi đẩy sang KH!' : '❌ Lô không tồn tại!' ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Danh sách -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
       style="border-radius:12px 12px 0 0">
    <h6 class="mb-0 fw-bold">
      🔍 Lô chờ xét duyệt
      <span class="badge bg-warning text-dark ms-1"><?= count($shipments) ?></span>
    </h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($shipments)): ?>
    <div class="text-center text-muted py-5">
      <div style="font-size:3rem">🎉</div>
      <p class="mt-2">Không có lô nào chờ xét duyệt!</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">HAWB</th>
            <th>Khách hàng</th>
            <th>Kiện / KG</th>
            <th>Active date</th>
            <th class="text-end">Chi phí</th>
            <th>Số dòng</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($shipments as $s): ?>
          <tr>
            <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($s['hawb']) ?></td>
            <td>
              <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['customer_code']) ?></span>
              <div class="small text-muted"><?= htmlspecialchars($s['company_name'] ?? '') ?></div>
            </td>
            <td><?= $s['packages'] ?> / <?= number_format($s['weight'],1) ?> kg</td>
            <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
            <td class="text-end">
              <?php if ($s['total_cost'] > 0): ?>
              <span class="fw-semibold text-success"><?= number_format($s['total_cost']) ?> đ</span>
              <?php else: ?>
              <span class="text-danger small">Chưa có</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?= $s['cost_count']>0?'success':'danger' ?>">
                <?= $s['cost_count'] ?> dòng
              </span>
            </td>
            <td class="pe-3">
              <a href="<?= BASE_URL ?>/?page=accounting.review&id=<?= $s['id'] ?>"
                 class="btn btn-sm btn-warning fw-semibold">
                Xét duyệt →
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>