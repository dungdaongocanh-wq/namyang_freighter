<?php
$statusLabel = [
    'pending_customs'  => ['Chờ tờ khai',   '#fee2e2','#b91c1c'],
    'cleared'          => ['Đã thông quan',  '#fef9c3','#92400e'],
    'waiting_pickup'   => ['Chờ lấy hàng',  '#ffedd5','#c2410c'],
    'in_transit'       => ['Đang vận chuyển','#dbeafe','#1d4ed8'],
    'delivered'        => ['Đã giao',        '#dcfce7','#15803d'],
    'kt_reviewing'     => ['KT đang duyệt',  '#e0f2fe','#0369a1'],
    'pending_approval' => ['Chờ bạn duyệt',  '#f3e8ff','#7e22ce'],
    'rejected'         => ['Đã từ chối',     '#f1f5f9','#334155'],
    'debt'             => ['Công nợ',         '#f0fdf4','#15803d'],
    'invoiced'         => ['Đã xuất HĐ',     '#f0fdf4','#15803d'],
];
[$sl, $sbg, $sclr] = $statusLabel[$shipment['status']] ?? [$shipment['status'],'#f1f5f9','#334155'];

// Timeline map
$timelineSteps = [
    'pending_customs'  => 1,
    'cleared'          => 2,
    'waiting_pickup'   => 3,
    'in_transit'       => 4,
    'delivered'        => 5,
    'kt_reviewing'     => 5,
    'pending_approval' => 5,
    'debt'             => 6,
    'invoiced'         => 7,
];
$currentStep = $timelineSteps[$shipment['status']] ?? 1;
?>

<div class="row g-4">

<!-- Cột trái -->
<div class="col-lg-4">

  <!-- Status card -->
  <div class="card mb-3"
       style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);border-left:5px solid <?= $sclr ?>">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($shipment['hawb']) ?></h5>
          <small class="text-muted"><?= htmlspecialchars($shipment['mawb'] ?? '') ?></small>
        </div>
        <span class="badge fs-6 px-3 py-2"
              style="background:<?= $sbg ?>;color:<?= $sclr ?>">
          <?= $sl ?>
        </span>
      </div>

      <div class="row g-2 mt-2 small">
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">CHUYẾN BAY</div>
          <div class="fw-semibold"><?= htmlspecialchars($shipment['flight_no'] ?? '-') ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">ETA</div>
          <div class="fw-semibold">
            <?= $shipment['eta'] ? date('d/m/Y', strtotime($shipment['eta'])) : '-' ?>
          </div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">SỐ KIỆN</div>
          <div class="fw-semibold"><?= $shipment['packages'] ?> kiện</div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">TRỌNG LƯỢNG</div>
          <div class="fw-semibold"><?= number_format($shipment['weight'],1) ?> kg</div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">ACTIVE DATE</div>
          <div class="fw-semibold"><?= date('d/m/Y', strtotime($shipment['active_date'])) ?></div>
        </div>
        <div class="col-6">
          <div class="text-muted" style="font-size:0.7rem">POL</div>
          <div class="fw-semibold"><?= htmlspecialchars($shipment['pol'] ?? '-') ?></div>
        </div>
      </div>

      <?php if ($shipment['remark']): ?>
      <div class="mt-2 p-2 rounded-2 small" style="background:#fffbeb">
        📝 <?= htmlspecialchars($shipment['remark']) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chữ ký -->
  <?php if ($signature): ?>
  <div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-body">
      <h6 class="fw-bold mb-3">✍️ Chữ ký nhận hàng</h6>
      <div class="text-center p-2 rounded-2 mb-2" style="background:#f8fafc;border:1px solid #e2e8f0">
        <img src="<?= BASE_URL ?>/<?= $signature['signature_path'] ?>"
             style="max-height:80px;max-width:100%;object-fit:contain">
      </div>
      <div class="small text-muted">
        👤 <strong><?= htmlspecialchars($signature['signed_by_name']) ?></strong><br>
        🕐 <?= date('d/m/Y H:i', strtotime($signature['signed_at'])) ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Ảnh lô -->
  <?php if (!empty($photos)): ?>
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-body">
      <h6 class="fw-bold mb-3">📷 Ảnh lô hàng</h6>
      <div class="row g-2">
        <?php foreach ($photos as $ph): ?>
        <div class="col-4">
          <a href="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>" target="_blank">
            <img src="<?= BASE_URL ?>/<?= $ph['photo_path'] ?>"
                 class="img-fluid rounded-2"
                 style="height:80px;width:100%;object-fit:cover">
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- Cột phải -->
<div class="col-lg-8">

  <!-- Timeline -->
  <div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-body px-4 py-3">
      <h6 class="fw-bold mb-3">🗺️ Hành trình lô hàng</h6>
      <?php
      $steps = [
          ['Nhập hệ thống',    '📥', 1],
          ['Thông quan',       '📂', 2],
          ['Chờ lấy hàng',    '📦', 3],
          ['Đang giao',        '🚚', 4],
          ['Đã giao / KT duyệt','✅', 5],
          ['Công nợ',          '💰', 6],
          ['Hoàn tất',         '🎉', 7],
      ];
      ?>
      <div class="d-flex justify-content-between align-items-center" style="position:relative">
        <!-- Line -->
        <div style="position:absolute;top:20px;left:5%;right:5%;height:3px;background:#e2e8f0;z-index:0"></div>
        <div style="position:absolute;top:20px;left:5%;height:3px;width:<?= min(95, ($currentStep-1)/6*90) ?>%;background:#2d6a9f;z-index:1;transition:width 0.5s"></div>

        <?php foreach ($steps as [$label, $icon, $step]): ?>
        <?php $done = $step <= $currentStep; $active = $step === $currentStep; ?>
        <div class="text-center" style="position:relative;z-index:2;flex:1">
          <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle mb-1"
               style="width:40px;height:40px;font-size:<?= $active?'1.2rem':'1rem' ?>;
                      background:<?= $done?'#2d6a9f':($active?'#2d6a9f':'#e2e8f0') ?>;
                      color:<?= $done?'#fff':'#94a3b8' ?>;
                      box-shadow:<?= $active?'0 0 0 4px rgba(45,106,159,0.2)':'' ?>;
                      transition:all 0.3s">
            <?= $icon ?>
          </div>
          <div style="font-size:0.62rem;color:<?= $done?'#1e3a5f':'#94a3b8' ?>;font-weight:<?= $done?'600':'400' ?>">
            <?= $label ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Chi phí + Duyệt -->
  <?php if ($shipment['status'] === 'pending_approval'): ?>
  <div class="card mb-3"
       style="border:none;border-radius:12px;box-shadow:0 4px 20px rgba(124,58,237,0.15);border:2px solid #c4b5fd">
    <div class="card-header py-3 px-4" style="background:#faf5ff;border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold" style="color:#7e22ce">⚠️ Cần xác nhận chi phí</h6>
    </div>
    <div class="card-body">
      <table class="table table-sm mb-3" style="font-size:0.85rem">
        <thead class="table-light">
          <tr>
            <th>Tên chi phí</th>
            <th class="text-end">Số tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($costs as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['cost_name']) ?></td>
            <td class="text-end fw-semibold"><?= number_format($c['amount']) ?> đ</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#f0fdf4">
          <tr>
            <td class="fw-bold">TỔNG CỘNG</td>
            <td class="text-end fw-bold text-success fs-6">
              <?= number_format($shipment['total_cost']) ?> đ
            </td>
          </tr>
        </tfoot>
      </table>

      <div class="d-flex gap-3">
        <!-- Approve form -->
        <form method="POST" action="<?= BASE_URL ?>/?page=customer.approve" class="flex-grow-1">
          <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
          <button type="submit" class="btn btn-success w-100 fw-semibold py-2"
                  onclick="return confirm('Xác nhận đồng ý chi phí này?')">
            ✅ Đồng ý chi phí
          </button>
        </form>

        <!-- Reject button -->
        <button class="btn btn-outline-danger flex-grow-1 fw-semibold py-2"
                data-bs-toggle="modal" data-bs-target="#rejectModal">
          ❌ Từ chối
        </button>
      </div>
    </div>
  </div>

  <?php elseif (!empty($costs)): ?>
  <!-- Chi phí (chỉ xem) -->
  <div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">💰 Chi phí vận chuyển</h6>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0" style="font-size:0.85rem">
        <tbody>
          <?php foreach ($costs as $c): ?>
          <tr>
            <td class="ps-4"><?= htmlspecialchars($c['cost_name']) ?></td>
            <td class="text-end pe-4 fw-semibold"><?= number_format($c['amount']) ?> đ</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot style="background:#f0fdf4">
          <tr>
            <td class="ps-4 fw-bold">TỔNG</td>
            <td class="text-end pe-4 fw-bold text-success">
              <?= number_format($shipment['total_cost']) ?> đ
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Lịch sử trạng thái -->
  <?php if (!empty($logs)): ?>
  <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07)">
    <div class="card-header bg-white py-3 px-4" style="border-radius:12px 12px 0 0">
      <h6 class="mb-0 fw-bold">📜 Lịch sử lô hàng</h6>
    </div>
    <div class="card-body">
      <div style="position:relative;padding-left:20px">
        <div style="position:absolute;left:7px;top:0;bottom:0;width:2px;background:#e2e8f0"></div>
        <?php foreach ($logs as $log): ?>
        <div class="d-flex gap-3 mb-3" style="position:relative">
          <div style="position:absolute;left:-16px;top:3px;width:10px;height:10px;
                      background:#2d6a9f;border-radius:50%;border:2px solid #fff;
                      box-shadow:0 0 0 2px #2d6a9f"></div>
          <div>
            <div class="fw-semibold small"><?= htmlspecialchars($log['action'] ?? '') ?></div>
            <?php if ($log['note'] ?? ''): ?>
            <div class="text-muted small"><?= htmlspecialchars($log['note']) ?></div>
            <?php endif; ?>
            <div style="font-size:0.72rem;color:#94a3b8">
              <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
              <?php if ($log['full_name'] ?? ''): ?>
              · <?= htmlspecialchars($log['full_name']) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h6 class="modal-title fw-bold text-danger">❌ Từ chối chi phí</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>/?page=customer.reject">
        <div class="modal-body">
          <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
          <div class="alert alert-warning py-2 small mb-3" style="border-radius:8px">
            ⚠️ Lô: <strong><?= htmlspecialchars($shipment['hawb']) ?></strong> —
            Chi phí: <strong><?= number_format($shipment['total_cost']) ?> đ</strong>
          </div>
          <label class="form-label fw-semibold small">Lý do từ chối *</label>
          <textarea name="reason" class="form-control" rows="3" required
                    placeholder="Nêu rõ lý do bạn không đồng ý với chi phí này..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger px-4 fw-semibold">
            ❌ Xác nhận từ chối
          </button>
        </div>
      </form>
    </div>
  </div>
</div>