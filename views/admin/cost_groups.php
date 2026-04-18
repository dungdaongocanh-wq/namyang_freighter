<?php
$errMap = [
    'empty_name' => 'Tên nhóm không được để trống!',
    'in_use'     => 'Không thể xóa nhóm đang được sử dụng trong báo giá!',
    'invalid'    => 'ID không hợp lệ!',
];
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible mb-4" style="border-radius:10px">
  <?= $_GET['msg'] === 'saved' ? '✅ Đã lưu nhóm chi phí!' : '✅ Đã xóa nhóm chi phí!' ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="alert alert-danger alert-dismissible mb-4" style="border-radius:10px">
  ❌ <?= htmlspecialchars($errMap[$_GET['err']] ?? $_GET['err']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold">🏷️ Quản lý nhóm chi phí</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#groupModal"
          onclick="openModal()">+ Thêm nhóm</button>
</div>

<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:0.88rem">
        <thead class="table-light">
          <tr>
            <th class="ps-4" style="width:60px">ID</th>
            <th>Tên nhóm</th>
            <th class="text-center" style="width:120px">Sort order</th>
            <th class="text-center" style="width:100px">Active</th>
            <th class="text-center" style="width:140px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($costGroups)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-5">Chưa có nhóm nào</td>
          </tr>
          <?php else: ?>
          <?php foreach ($costGroups as $g): ?>
          <tr>
            <td class="ps-4 text-muted"><?= $g['id'] ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($g['name']) ?></td>
            <td class="text-center"><?= $g['sort_order'] ?></td>
            <td class="text-center">
              <?php if ($g['is_active']): ?>
                <span class="badge bg-success">✓ Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">✗ Inactive</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <button class="btn btn-sm btn-outline-primary"
                        onclick="openModal(<?= $g['id'] ?>, <?= json_encode($g['name']) ?>, <?= $g['sort_order'] ?>, <?= $g['is_active'] ?>)"
                        data-bs-toggle="modal" data-bs-target="#groupModal">✏️ Sửa</button>
                <form method="POST" action="<?= BASE_URL ?>/?page=admin.delete_cost_group"
                      onsubmit="return confirm('Xóa nhóm ' + <?= json_encode($g['name']) ?> + '?')">
                  <input type="hidden" name="id" value="<?= $g['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Xóa</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal thêm/sửa -->
<div class="modal fade" id="groupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px">
      <form method="POST" action="<?= BASE_URL ?>/?page=admin.save_cost_group">
        <input type="hidden" name="id" id="groupId" value="">
        <div class="modal-header">
          <h6 class="modal-title fw-bold" id="groupModalTitle">Thêm nhóm chi phí</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Tên nhóm <span class="text-danger">*</span></label>
            <input type="text" name="name" id="groupName" class="form-control" required
                   placeholder="Ví dụ: Phí hải quan, Phí vận chuyển...">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Thứ tự sắp xếp</label>
            <input type="number" name="sort_order" id="groupSortOrder" class="form-control"
                   value="0" min="0">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="is_active" id="groupIsActive" class="form-select">
              <option value="1">✓ Active</option>
              <option value="0">✗ Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary px-4">💾 Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModal(id, name, sortOrder, isActive) {
  if (id) {
    document.getElementById('groupModalTitle').textContent = 'Sửa nhóm chi phí';
    document.getElementById('groupId').value = id;
    document.getElementById('groupName').value = name;
    document.getElementById('groupSortOrder').value = sortOrder;
    document.getElementById('groupIsActive').value = isActive ? '1' : '0';
  } else {
    document.getElementById('groupModalTitle').textContent = 'Thêm nhóm chi phí';
    document.getElementById('groupId').value = '';
    document.getElementById('groupName').value = '';
    document.getElementById('groupSortOrder').value = '0';
    document.getElementById('groupIsActive').value = '1';
  }
}
</script>
