<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card" style="border:none;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
  <div class="card-header bg-white py-3 px-4" style="border-radius:16px 16px 0 0">
    <h6 class="mb-0 fw-bold">✏️ Sửa lô hàng — <?= htmlspecialchars($shipment['hawb']) ?></h6>
  </div>
  <div class="card-body px-4 py-4">
    <form method="POST" action="<?= BASE_URL ?>/?page=cs.update_shipment">
      <input type="hidden" name="id" value="<?= $shipment['id'] ?>">

      <div class="row g-3">
        <!-- HAWB -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">HAWB <span class="text-danger">*</span></label>
          <input type="text" name="hawb" class="form-control"
                 value="<?= htmlspecialchars($shipment['hawb']) ?>" required>
        </div>

        <!-- Khách hàng -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Khách hàng</label>
          <select name="customer_id" class="form-select">
            <option value="">-- Chọn KH --</option>
            <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= $c['id'] == $shipment['customer_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['customer_code'] . ' — ' . $c['company_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- MAWB -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">MAWB</label>
          <input type="text" name="mawb" class="form-control"
                 value="<?= htmlspecialchars($shipment['mawb'] ?? '') ?>">
        </div>

        <!-- Flight -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Flight</label>
          <input type="text" name="flight_no" class="form-control"
                 value="<?= htmlspecialchars($shipment['flight_no'] ?? '') ?>">
        </div>

        <!-- ETA -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">ETA</label>
          <input type="date" name="eta" class="form-control"
                 value="<?= $shipment['eta'] ?? '' ?>">
        </div>

        <!-- Kiện -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Số kiện</label>
          <input type="number" name="packages" class="form-control" min="0"
                 value="<?= (int)$shipment['packages'] ?>">
        </div>

        <!-- KG -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Cân nặng (kg)</label>
          <input type="number" name="weight" class="form-control" step="0.1" min="0"
                 value="<?= (float)$shipment['weight'] ?>">
        </div>

        <!-- Trạng thái -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Trạng thái</label>
          <select name="status" class="form-select">
            <?php
            $statuses = [
              'pending_customs'  => 'Chờ thông quan',
              'cleared'          => 'Đã thông quan',
              'waiting_pickup'   => 'Chờ lấy hàng',
              'delivered'        => 'Đã giao',
              'debt'             => 'Công nợ',
              'pending_approval' => 'Chờ duyệt',
            ];
            foreach ($statuses as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $shipment['status'] === $val ? 'selected' : '' ?>>
              <?= $lbl ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- CD Status -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">CD Status</label>
          <input type="text" name="cd_status" class="form-control"
                 value="<?= htmlspecialchars($shipment['cd_status'] ?? '') ?>">
        </div>

        <!-- Remark -->
        <div class="col-12">
          <label class="form-label fw-semibold">Ghi chú</label>
          <textarea name="remark" class="form-control" rows="2"><?=
            htmlspecialchars($shipment['remark'] ?? '')
          ?></textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-success px-4 fw-semibold">
          💾 Lưu thay đổi
        </button>
        <a href="<?= BASE_URL ?>/?page=cs.list" class="btn btn-outline-secondary">
          ← Quay lại
        </a>
      </div>
    </form>
  </div>
</div>

</div>
</div>