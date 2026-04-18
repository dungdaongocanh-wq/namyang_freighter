<?php
/**
 * Template in biên bản chuyến ghép nhiều lô
 * Dùng layout: views/layouts/print.php
 * Biến cần truyền: $trip, $items (getShipmentsByTrip), $driver
 */

ob_start();
?>
<style>
  .trip-title  { font-size: 15pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 8pt 0 4pt; }
  .trip-sub    { font-size: 10pt; text-align: center; color: #555; margin-bottom: 12pt; }
  .info-table  { width: 100%; margin-bottom: 12pt; border-collapse: collapse; font-size: 10.5pt; }
  .info-table td { padding: 3pt 6pt; vertical-align: top; }
  .info-table .label { font-weight: bold; white-space: nowrap; width: 120pt; color: #333; }
  .item-table  { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 14pt; }
  .item-table th { background: #1e3a5f; color: #fff; padding: 5pt 7pt; text-align: center; }
  .item-table td { border: 1px solid #ccc; padding: 3.5pt 7pt; }
  .item-table tr:nth-child(even) td { background: #f8fafc; }
  .sign-section { display: flex; justify-content: space-between; margin-top: 24pt; }
  .sign-box     { width: 45%; text-align: center; }
  .sign-box .sign-title { font-weight: bold; font-size: 11pt; margin-bottom: 2pt; }
  .sign-box .sign-note  { font-size: 9pt; color: #666; margin-bottom: 50pt; }
  .sign-name { font-weight: bold; font-size: 10pt; }
  hr.divider   { border: none; border-top: 1.5px solid #e2e8f0; margin: 10pt 0; }
  .status-badge { padding: 2pt 6pt; border-radius: 3pt; font-size: 8.5pt; font-weight: bold; }
</style>

<!-- Header -->
<table style="width:100%;margin-bottom:8pt">
  <tr>
    <td style="width:120pt;vertical-align:middle">
      <?php $logoPath = __DIR__ . '/../assets/logo.png'; if (file_exists($logoPath)): ?>
      <img src="<?= BASE_URL ?>/assets/logo.png" style="height:55pt" alt="Logo">
      <?php else: ?>
      <div style="font-size:14pt;font-weight:bold;color:#1e3a5f">NAM YANG</div>
      <div style="font-size:8pt;color:#64748b">FREIGHT MANAGEMENT</div>
      <?php endif; ?>
    </td>
    <td style="text-align:center;vertical-align:middle">
      <div class="trip-title">Biên bản giao hàng chuyến</div>
      <div class="trip-sub">DELIVERY TRIP REPORT</div>
    </td>
    <td style="width:120pt;text-align:right;vertical-align:top;font-size:9pt;color:#555">
      <div>Mã chuyến: <strong><?= htmlspecialchars($trip['trip_code'] ?? 'TRIP-' . $trip['id']) ?></strong></div>
      <div>Ngày: <strong><?= date('d/m/Y', strtotime($trip['trip_date'])) ?></strong></div>
    </td>
  </tr>
</table>

<hr class="divider">

<!-- Thông tin chuyến -->
<table class="info-table">
  <tr>
    <td class="label">Lái xe:</td>
    <td><strong><?= htmlspecialchars($trip['driver_name'] ?? '—') ?></strong></td>
    <td class="label">Ngày giao:</td>
    <td><?= date('d/m/Y', strtotime($trip['trip_date'])) ?></td>
  </tr>
  <tr>
    <td class="label">Xe:</td>
    <td><?= htmlspecialchars($trip['vehicle'] ?? '—') ?></td>
    <td class="label">Tổng số lô:</td>
    <td><strong><?= count($items) ?> lô</strong></td>
  </tr>
  <?php if (!empty($trip['note'])): ?>
  <tr>
    <td class="label">Ghi chú:</td>
    <td colspan="3"><?= htmlspecialchars($trip['note']) ?></td>
  </tr>
  <?php endif; ?>
</table>

<!-- Bảng danh sách lô hàng -->
<div style="font-weight:bold;font-size:10.5pt;margin-bottom:6pt">
  DANH SÁCH LÔ HÀNG / SHIPMENT LIST
</div>
<table class="item-table avoid-break">
  <thead>
    <tr>
      <th style="width:25pt">STT</th>
      <th>Mã HAWB</th>
      <th>Khách hàng</th>
      <th>Địa chỉ giao</th>
      <th style="width:50pt">Số kiện</th>
      <th style="width:60pt">Trạng thái</th>
      <th style="width:50pt">Ghi chú</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($items)): ?>
    <?php foreach ($items as $i => $item): ?>
    <?php
    $stColors = [
        'in_transit'  => ['#dbeafe', '#1d4ed8'],
        'delivered'   => ['#dcfce7', '#15803d'],
        'cancelled'   => ['#fee2e2', '#b91c1c'],
    ];
    [$stBg, $stClr] = $stColors[$item['status']] ?? ['#f1f5f9', '#334155'];
    $stLabels = [
        'in_transit' => 'Đang giao',
        'delivered'  => 'Đã giao',
        'cancelled'  => 'Đã huỷ',
        'waiting_pickup' => 'Chờ lấy',
    ];
    $stLabel = $stLabels[$item['status']] ?? $item['status'];
    ?>
    <tr>
      <td style="text-align:center"><?= $i + 1 ?></td>
      <td style="font-weight:bold"><?= htmlspecialchars($item['hawb']) ?></td>
      <td><?= htmlspecialchars($item['company_name'] ?? $item['customer_code'] ?? '—') ?></td>
      <td style="font-size:8.5pt"><?= htmlspecialchars($item['delivery_address'] ?? '—') ?></td>
      <td style="text-align:center"><?= $item['packages'] ?></td>
      <td style="text-align:center">
        <span class="status-badge" style="background:<?= $stBg ?>;color:<?= $stClr ?>">
          <?= $stLabel ?>
        </span>
      </td>
      <td style="font-size:8.5pt"><?= htmlspecialchars($item['remark'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <tr>
      <td colspan="7" style="text-align:center;color:#94a3b8">Không có lô hàng</td>
    </tr>
    <?php endif; ?>
  </tbody>
  <tfoot>
    <tr style="font-weight:bold;background:#f0f7ff">
      <td colspan="4" style="text-align:right">TỔNG CỘNG:</td>
      <td style="text-align:center"><?= array_sum(array_column($items, 'packages')) ?></td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
</table>

<hr class="divider">

<!-- Phần ký tên -->
<div class="sign-section avoid-break">
  <div class="sign-box">
    <div class="sign-title">NGƯỜI ĐIỀU PHỐI (OPS)</div>
    <div class="sign-note">Ký, ghi rõ họ tên</div>
    <div class="sign-name">___________________</div>
  </div>
  <div class="sign-box">
    <div class="sign-title">LÁI XE</div>
    <div class="sign-note">Ký, ghi rõ họ tên</div>
    <div class="sign-name"><?= htmlspecialchars($trip['driver_name'] ?? '___________________') ?></div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/print.php';
