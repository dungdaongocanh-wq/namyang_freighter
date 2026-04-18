<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-3" style="border-radius:10px">
  ✅ Đã lưu! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newQModal">
    + Tạo báo giá mới
  </button>
</div>

<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">Khách hàng</th>
            <th>Tên báo giá</th>
            <th>Hiệu lực</th>
            <th class="text-center">Số dòng</th>
            <th class="text-end">Tổng giá trị</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quotations as $q): ?>
          <tr>
            <td class="ps-4">
              <?php if ($q['customer_id']): ?>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($q['customer_code']) ?></span>
                <div class="small text-muted"><?= htmlspecialchars($q['company_name'] ?? '') ?></div>
              <?php else: ?>
                <span class="badge bg-info text-white">🌐 Báo giá chung</span>
                <div class="small text-muted">Áp dụng cho tất cả KH không có báo giá riêng</div>
              <?php endif; ?>
            </td>
            <td class="fw-semibold"><?= htmlspecialchars($q['name'] ?? 'Báo giá') ?></td>
            <td class="small">
              <?php if ($q['valid_from'] && $q['valid_to']): ?>
                <?= date('d/m/Y', strtotime($q['valid_from'])) ?> →
                <?= date('d/m/Y', strtotime($q['valid_to'])) ?>
              <?php else: ?>
                <span class="text-muted">Không giới hạn</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="badge bg-primary"><?= $q['item_count'] ?> dòng</span>
            </td>
            <td class="text-end fw-semibold text-success">
              <?= $q['total_amount'] > 0 ? number_format($q['total_amount']) . ' đ' : '-' ?>
            </td>
            <td>
              <span class="badge bg-<?= $q['is_active'] ? 'success' : 'secondary' ?>">
                <?= $q['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="pe-3">
              <a href="<?= BASE_URL ?>/?page=admin.quotation_detail&id=<?= $q['id'] ?>"
                 class="btn btn-sm btn-outline-primary">✏️ Chi tiết</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($quotations)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-5">
              <div style="font-size:2.5rem">📋</div>
              <p class="mt-2">Chưa có báo giá nào</p>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal tạo mới (chỉ chọn KH, điền header) -->
<div class="modal fade" id="newQModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">+ Tạo báo giá mới</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=admin.save_quotation">
        <input type="hidden" name="id" value="0">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Khách hàng</label>
            <select name="customer_id" class="form-select">
              <option value="">— Áp dụng cho tất cả KH không có báo giá riêng —</option>
              <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['customer_code']) ?> — <?= htmlspecialchars($c['company_name'] ?? '') ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên báo giá</label>
            <input type="text" name="name" class="form-control"
                   value="Báo giá dịch vụ Forwarder" placeholder="VD: Báo giá Q1/2026">
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-semibold">Hiệu lực từ</label>
              <input type="date" name="valid_from" class="form-control"
                     value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Đến ngày</label>
              <input type="date" name="valid_to" class="form-control"
                     value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary px-4">Tạo & nhập chi tiết →</button>
        </div>
      </form>
    </div>
  </div>
</div>