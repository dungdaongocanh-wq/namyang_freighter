<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-3" style="border-radius:10px">
  ✅ Đã lưu! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#custModal"
          onclick="resetCustForm()">+ Thêm khách hàng</button>
</div>

<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">Mã KH</th>
            <th>Công ty</th>
            <th>Email / Phone</th>
            <th class="text-center">Tổng lô</th>
            <th class="text-end">Công nợ hiện tại</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
          <tr>
            <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($c['customer_code']) ?></td>
            <td><?= htmlspecialchars($c['company_name'] ?? '') ?></td>
            <td>
              <div class="small"><?= htmlspecialchars($c['email'] ?? '-') ?></div>
              <div class="small text-muted"><?= htmlspecialchars($c['phone'] ?? '') ?></div>
            </td>
            <td class="text-center"><?= $c['shipment_count'] ?></td>
            <td class="text-end fw-semibold text-success">
              <?= $c['total_debt'] > 0 ? number_format($c['total_debt']).' đ' : '-' ?>
            </td>
            <td>
              <span class="badge bg-<?= $c['is_active']?'success':'danger' ?>">
                <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="pe-3">
              <button class="btn btn-sm btn-outline-primary"
                      onclick='editCust(<?= json_encode($c) ?>)'>Sửa</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="custModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="custModalTitle">Thêm khách hàng</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=admin.save_customer">
        <div class="modal-body">
          <input type="hidden" name="id" id="cId">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Mã khách hàng *</label>
              <input type="text" name="customer_code" id="cCode" class="form-control"
                     placeholder="VD: KH001" required>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Tên công ty</label>
              <input type="text" name="company_name" id="cName" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" id="cEmail" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Số điện thoại</label>
              <input type="text" name="phone" id="cPhone" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Địa chỉ</label>
              <textarea name="address" id="cAddress" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Trạng thái</label>
              <select name="is_active" id="cIsActive" class="form-select">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary px-4">💾 Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetCustForm() {
  document.getElementById('custModalTitle').textContent = 'Thêm khách hàng';
  ['cId','cCode','cName','cEmail','cPhone','cAddress'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('cIsActive').value = '1';
}
function editCust(c) {
  document.getElementById('custModalTitle').textContent = 'Sửa khách hàng';
  document.getElementById('cId').value       = c.id;
  document.getElementById('cCode').value     = c.customer_code;
  document.getElementById('cName').value     = c.company_name  || '';
  document.getElementById('cEmail').value    = c.email         || '';
  document.getElementById('cPhone').value    = c.phone         || '';
  document.getElementById('cAddress').value  = c.address       || '';
  document.getElementById('cIsActive').value = c.is_active;
  new bootstrap.Modal(document.getElementById('custModal')).show();
}
</script>