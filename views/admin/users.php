<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-3" style="border-radius:10px">
  ✅ Đã lưu! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"
          onclick="resetForm()">+ Thêm người dùng</button>
</div>

<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:0.85rem">
        <thead style="background:#f8fafc;color:#64748b;font-size:0.78rem">
          <tr>
            <th class="ps-4">Username</th>
            <th>Họ tên</th>
            <th>Role</th>
            <th>Khách hàng</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="ps-4 fw-semibold"><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['full_name'] ?? '') ?></td>
            <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
            <td><?= htmlspecialchars($u['customer_code'] ?? '-') ?></td>
            <td>
              <span class="badge bg-<?= $u['is_active']?'success':'danger' ?>">
                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="pe-3">
              <button class="btn btn-sm btn-outline-primary"
                      onclick='editUser(<?= json_encode($u) ?>)'>Sửa</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="userModalTitle">Thêm người dùng</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=admin.save_user">
        <div class="modal-body">
          <input type="hidden" name="id" id="uId">
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Username *</label>
              <input type="text" name="username" id="uUsername" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Mật khẩu</label>
              <input type="password" name="password" id="uPassword" class="form-control"
                     placeholder="Để trống = không đổi">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Họ tên</label>
              <input type="text" name="full_name" id="uFullName" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Role</label>
              <select name="role" id="uRole" class="form-select" onchange="toggleCustomer()">
                <?php foreach (['admin','cs','ops','driver','accounting','customer'] as $r): ?>
                <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6" id="customerField">
              <label class="form-label small fw-semibold">Khách hàng</label>
              <select name="customer_id" id="uCustomerId" class="form-select">
                <option value="">-- Chọn --</option>
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['customer_code']) ?> - <?= htmlspecialchars($c['company_name'] ?? '') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Trạng thái</label>
              <select name="is_active" id="uIsActive" class="form-select">
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
function resetForm() {
  document.getElementById('userModalTitle').textContent = 'Thêm người dùng';
  document.getElementById('uId').value       = '';
  document.getElementById('uUsername').value = '';
  document.getElementById('uPassword').value = '';
  document.getElementById('uFullName').value = '';
  document.getElementById('uRole').value     = 'cs';
  document.getElementById('uIsActive').value = '1';
  document.getElementById('uCustomerId').value = '';
  toggleCustomer();
}

function editUser(u) {
  document.getElementById('userModalTitle').textContent = 'Sửa người dùng';
  document.getElementById('uId').value         = u.id;
  document.getElementById('uUsername').value   = u.username;
  document.getElementById('uPassword').value   = '';
  document.getElementById('uFullName').value   = u.full_name || '';
  document.getElementById('uRole').value       = u.role;
  document.getElementById('uIsActive').value   = u.is_active;
  document.getElementById('uCustomerId').value = u.customer_id || '';
  toggleCustomer();
  new bootstrap.Modal(document.getElementById('userModal')).show();
}

function toggleCustomer() {
  const role = document.getElementById('uRole').value;
  document.getElementById('customerField').style.display =
    role === 'customer' ? 'block' : 'none';
}
toggleCustomer();
</script>