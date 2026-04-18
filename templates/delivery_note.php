<?php
/**
 * Template in biên bản giao nhận hàng hóa — đơn lô
 * Dùng layout: views/layouts/print.php
 * Biến cần truyền: $shipment, $items (danh sách hàng), $signature (chữ ký), $photos
 */

ob_start();
?>
<style>
  .dn-logo    { height: 60px; }
  .dn-title   { font-size: 16pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 8pt 0 4pt; }
  .dn-sub     { font-size: 10pt; text-align: center; color: #555; margin-bottom: 12pt; }
  .info-table { width: 100%; margin-bottom: 12pt; border-collapse: collapse; font-size: 10.5pt; }
  .info-table td { padding: 3pt 6pt; vertical-align: top; }
  .info-table .label { font-weight: bold; white-space: nowrap; width: 140pt; color: #333; }
  .item-table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 14pt; }
  .item-table th { background: #1e3a5f; color: #fff; padding: 5pt 8pt; text-align: center; }
  .item-table td { border: 1px solid #ccc; padding: 4pt 8pt; }
  .item-table tr:nth-child(even) td { background: #f8fafc; }
  .sign-section { display: flex; justify-content: space-between; margin-top: 20pt; }
  .sign-box { width: 45%; text-align: center; }
  .sign-box .sign-title { font-weight: bold; font-size: 11pt; margin-bottom: 2pt; }
  .sign-box .sign-note  { font-size: 9pt; color: #666; margin-bottom: 50pt; }
  .sign-img  { max-height: 60pt; max-width: 150pt; margin: 0 auto 6pt; display: block; }
  .sign-name { font-weight: bold; font-size: 10pt; }
  .sig-img-box { border: 1px solid #ccc; border-radius: 4pt; padding: 4pt; min-height: 60pt;
                 display: flex; align-items: center; justify-content: center; margin-bottom: 4pt; }
  hr.divider  { border: none; border-top: 1.5px solid #e2e8f0; margin: 10pt 0; }
</style>

<!-- Header -->
<table style="width:100%;margin-bottom:8pt">
  <tr>
    <td style="width:120pt;vertical-align:middle">
      <!-- Logo: nếu tồn tại thì hiện, không thì hiện tên text -->
      <?php
      $logoPath = __DIR__ . '/../assets/logo.png';
      if (file_exists($logoPath)):
      ?>
      <img src="<?= BASE_URL ?>/assets/logo.png" class="dn-logo" alt="Logo">
      <?php else: ?>
      <div style="font-size:14pt;font-weight:bold;color:#1e3a5f">NAM YANG</div>
      <div style="font-size:8pt;color:#64748b">FREIGHT MANAGEMENT</div>
      <?php endif; ?>
    </td>
    <td style="text-align:center;vertical-align:middle">
      <div class="dn-title">Biên bản giao nhận hàng hóa</div>
      <div class="dn-sub">CARGO DELIVERY RECEIPT</div>
    </td>
    <td style="width:120pt;text-align:right;vertical-align:top;font-size:9pt;color:#555">
      <div>Số: <strong><?= htmlspecialchars($deliveryNote['note_code'] ?? '—') ?></strong></div>
      <div>Ngày: <strong><?= date('d/m/Y') ?></strong></div>
    </td>
  </tr>
</table>

<hr class="divider">

<!-- Thông tin lô hàng -->
<table class="info-table">
  <tr>
    <td class="label">Mã HAWB:</td>
    <td><strong><?= htmlspecialchars($shipment['hawb']) ?></strong></td>
    <td class="label">MAWB:</td>
    <td><?= htmlspecialchars($shipment['mawb'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="label">Khách hàng:</td>
    <td><?= htmlspecialchars($shipment['company_name'] ?? $shipment['customer_code'] ?? '—') ?></td>
    <td class="label">Ngày giao:</td>
    <td><?= date('d/m/Y') ?></td>
  </tr>
  <tr>
    <td class="label">Số kiện:</td>
    <td><?= $shipment['packages'] ?> kiện</td>
    <td class="label">Trọng lượng:</td>
    <td><?= number_format($shipment['weight'], 2) ?> kg</td>
  </tr>
  <tr>
    <td class="label">Địa chỉ giao:</td>
    <td colspan="3"><?= htmlspecialchars($deliveryNote['delivery_address'] ?? '—') ?></td>
  </tr>
  <?php if (!empty($deliveryNote['recipient_name'])): ?>
  <tr>
    <td class="label">Người nhận:</td>
    <td><?= htmlspecialchars($deliveryNote['recipient_name']) ?></td>
    <td class="label">SĐT:</td>
    <td><?= htmlspecialchars($deliveryNote['recipient_phone'] ?? '—') ?></td>
  </tr>
  <?php endif; ?>
  <?php if (!empty($shipment['remark'])): ?>
  <tr>
    <td class="label">Ghi chú:</td>
    <td colspan="3"><?= htmlspecialchars($shipment['remark']) ?></td>
  </tr>
  <?php endif; ?>
</table>

<!-- Bảng danh sách hàng hóa -->
<div style="font-weight:bold;font-size:10.5pt;margin-bottom:6pt">
  DANH SÁCH HÀNG HÓA / GOODS LIST
</div>
<table class="item-table avoid-break">
  <thead>
    <tr>
      <th style="width:30pt">STT</th>
      <th>Tên hàng hóa</th>
      <th style="width:60pt;text-align:right">Số lượng</th>
      <th style="width:80pt">Đơn vị</th>
      <th>Ghi chú</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($items)): ?>
    <?php foreach ($items as $i => $item): ?>
    <tr>
      <td style="text-align:center"><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($item['name'] ?? $item['description'] ?? '') ?></td>
      <td style="text-align:right"><?= $item['quantity'] ?? 1 ?></td>
      <td><?= htmlspecialchars($item['unit'] ?? 'Kiện') ?></td>
      <td><?= htmlspecialchars($item['note'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php else: ?>
    <tr>
      <td style="text-align:center">1</td>
      <td><?= htmlspecialchars($shipment['hawb']) ?></td>
      <td style="text-align:right"><?= $shipment['packages'] ?></td>
      <td>Kiện</td>
      <td></td>
    </tr>
    <?php endif; ?>
  </tbody>
  <tfoot>
    <tr style="font-weight:bold;background:#f0f7ff">
      <td colspan="2" style="text-align:right">TỔNG CỘNG:</td>
      <td style="text-align:right">
        <?= !empty($items) ? array_sum(array_column($items, 'quantity')) : $shipment['packages'] ?>
      </td>
      <td>Kiện</td>
      <td></td>
    </tr>
  </tfoot>
</table>

<!-- Ảnh chữ ký điện tử nếu có -->
<?php if (!empty($signature)): ?>
<div style="margin-bottom:12pt">
  <div style="font-size:9.5pt;color:#555;margin-bottom:4pt">✍️ Chữ ký điện tử người nhận:</div>
  <div class="sig-img-box" style="width:180pt;display:inline-block">
    <img src="<?= BASE_URL ?>/<?= $signature['signature_path'] ?>" class="sign-img" alt="Chữ ký">
  </div>
  <div style="font-size:9pt;color:#555;margin-top:3pt">
    Ký bởi: <strong><?= htmlspecialchars($signature['signed_by_name'] ?? '—') ?></strong> —
    <?= date('d/m/Y H:i', strtotime($signature['signed_at'])) ?>
  </div>
</div>
<?php endif; ?>

<hr class="divider">

<!-- Phần ký tên -->
<div class="sign-section avoid-break">
  <div class="sign-box">
    <div class="sign-title">BÊN GIAO</div>
    <div class="sign-note">Ký, ghi rõ họ tên</div>
    <div class="sign-name"><?= htmlspecialchars($deliveryNote['created_by_name'] ?? '—') ?></div>
  </div>
  <div class="sign-box">
    <div class="sign-title">BÊN NHẬN</div>
    <div class="sign-note">Ký, ghi rõ họ tên</div>
    <?php if (!empty($signature)): ?>
    <img src="<?= BASE_URL ?>/<?= $signature['signature_path'] ?>"
         style="max-height:50pt;max-width:140pt;display:block;margin:0 auto 4pt">
    <?php endif; ?>
    <div class="sign-name"><?= htmlspecialchars($deliveryNote['recipient_name'] ?? '—') ?></div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/print.php';
