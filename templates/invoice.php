<?php
/**
 * Template in hoá đơn dịch vụ
 * Dùng layout: views/layouts/print.php
 * Biến cần truyền: $customer, $shipments (danh sách lô + chi phí), $month, $invoiceNo, $vatRate
 */

ob_start();

$vatRate     = $vatRate ?? 0;          // % VAT, mặc định 0
$invoiceNo   = $invoiceNo ?? '—';
$totalAmount = array_sum(array_column($shipments ?? [], 'total_cost'));
$vatAmount   = $totalAmount * $vatRate / 100;
$grandTotal  = $totalAmount + $vatAmount;
?>
<style>
  .inv-title  { font-size: 16pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 8pt 0 4pt; }
  .inv-sub    { font-size: 9.5pt; text-align: center; color: #555; margin-bottom: 12pt; }
  .info-table { width: 100%; margin-bottom: 12pt; border-collapse: collapse; font-size: 10.5pt; }
  .info-table td { padding: 3pt 6pt; vertical-align: top; }
  .info-table .label { font-weight: bold; white-space: nowrap; width: 120pt; color: #333; }
  .item-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 10pt; }
  .item-table th { background: #1e3a5f; color: #fff; padding: 5pt 7pt; text-align: center; }
  .item-table td { border: 1px solid #ccc; padding: 4pt 7pt; }
  .item-table tr:nth-child(even) td { background: #f8fafc; }
  .total-table { width: 280pt; margin-left: auto; border-collapse: collapse; font-size: 10.5pt; margin-bottom: 14pt; }
  .total-table td { padding: 3pt 8pt; }
  .total-table .t-label { color: #333; }
  .total-table .t-val   { text-align: right; font-weight: bold; }
  .grand-total { background: #1e3a5f; color: #fff; font-size: 12pt; }
  .sign-section { display: flex; justify-content: space-between; margin-top: 20pt; }
  .sign-box     { width: 45%; text-align: center; }
  .sign-box .sign-title { font-weight: bold; font-size: 11pt; margin-bottom: 2pt; }
  .sign-box .sign-note  { font-size: 9pt; color: #666; margin-bottom: 48pt; }
  .sign-name { font-weight: bold; font-size: 10pt; }
  hr.divider  { border: none; border-top: 1.5px solid #e2e8f0; margin: 10pt 0; }
  .note-box   { border: 1px dashed #ccc; padding: 6pt 10pt; border-radius: 4pt;
                font-size: 9.5pt; color: #555; margin-bottom: 12pt; }
</style>

<!-- Header -->
<table style="width:100%;margin-bottom:8pt">
  <tr>
    <td style="width:130pt;vertical-align:middle">
      <?php $logoPath = __DIR__ . '/../assets/logo.png'; if (file_exists($logoPath)): ?>
      <img src="<?= BASE_URL ?>/assets/logo.png" style="height:55pt" alt="Logo">
      <?php else: ?>
      <div style="font-size:14pt;font-weight:bold;color:#1e3a5f">NAM YANG</div>
      <div style="font-size:8pt;color:#64748b">FREIGHT MANAGEMENT</div>
      <?php endif; ?>
      <div style="font-size:8.5pt;color:#555;margin-top:4pt;line-height:1.4">
        <?= defined('COMPANY_ADDRESS') ? nl2br(htmlspecialchars(COMPANY_ADDRESS)) : '' ?>
      </div>
    </td>
    <td style="text-align:center;vertical-align:middle">
      <div class="inv-title">Hoá đơn dịch vụ</div>
      <div class="inv-sub">SERVICE INVOICE</div>
    </td>
    <td style="width:130pt;text-align:right;vertical-align:top;font-size:9pt;color:#333">
      <div>Số HĐ: <strong><?= htmlspecialchars($invoiceNo) ?></strong></div>
      <div>Ngày: <strong><?= date('d/m/Y') ?></strong></div>
      <div>Tháng: <strong><?= htmlspecialchars($month ?? date('Y-m')) ?></strong></div>
    </td>
  </tr>
</table>

<hr class="divider">

<!-- Thông tin khách hàng -->
<table class="info-table">
  <tr>
    <td class="label">Khách hàng:</td>
    <td><strong><?= htmlspecialchars($customer['company_name'] ?? '—') ?></strong></td>
    <td class="label">Mã KH:</td>
    <td><?= htmlspecialchars($customer['customer_code'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="label">Địa chỉ:</td>
    <td colspan="3"><?= htmlspecialchars($customer['address'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="label">Email:</td>
    <td><?= htmlspecialchars($customer['email'] ?? '—') ?></td>
    <td class="label">Điện thoại:</td>
    <td><?= htmlspecialchars($customer['phone'] ?? '—') ?></td>
  </tr>
  <?php if (!empty($customer['tax_code'])): ?>
  <tr>
    <td class="label">Mã số thuế:</td>
    <td colspan="3"><?= htmlspecialchars($customer['tax_code']) ?></td>
  </tr>
  <?php endif; ?>
</table>

<!-- Bảng chi tiết dịch vụ / lô hàng -->
<div style="font-weight:bold;font-size:10.5pt;margin-bottom:6pt">
  CHI TIẾT DỊCH VỤ / SERVICE DETAILS
</div>
<table class="item-table avoid-break">
  <thead>
    <tr>
      <th style="width:25pt">STT</th>
      <th>Mã HAWB / Dịch vụ</th>
      <th style="width:60pt">Ngày</th>
      <th style="width:40pt;text-align:right">Kiện</th>
      <th style="width:60pt;text-align:right">KG</th>
      <th style="width:90pt;text-align:right">Thành tiền (đ)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($shipments ?? [] as $i => $s):
      // Phân tách chi tiết nếu có cost_detail
      $costLines = [];
      if (!empty($s['cost_detail'])) {
          foreach (explode('|', $s['cost_detail']) as $line) {
              [$cname, $camount] = explode(':', $line, 2) + ['', '0'];
              $costLines[] = ['name' => $cname, 'amount' => (float)$camount];
          }
      }
    ?>
    <tr>
      <td style="text-align:center"><?= $i + 1 ?></td>
      <td style="font-weight:bold"><?= htmlspecialchars($s['hawb']) ?></td>
      <td><?= date('d/m/Y', strtotime($s['active_date'])) ?></td>
      <td style="text-align:right"><?= $s['packages'] ?></td>
      <td style="text-align:right"><?= number_format($s['weight'], 2) ?></td>
      <td style="text-align:right"><?= number_format($s['total_cost']) ?></td>
    </tr>
    <?php if (!empty($costLines)): ?>
    <?php foreach ($costLines as $cl): ?>
    <tr style="font-size:8.5pt;color:#64748b">
      <td></td>
      <td style="padding-left:18pt">↳ <?= htmlspecialchars($cl['name']) ?></td>
      <td></td><td></td><td></td>
      <td style="text-align:right"><?= number_format($cl['amount']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Tổng cộng -->
<table class="total-table avoid-break">
  <tr>
    <td class="t-label">Tổng tiền dịch vụ:</td>
    <td class="t-val"><?= number_format($totalAmount) ?> đ</td>
  </tr>
  <?php if ($vatRate > 0): ?>
  <tr>
    <td class="t-label">VAT (<?= $vatRate ?>%):</td>
    <td class="t-val"><?= number_format($vatAmount) ?> đ</td>
  </tr>
  <?php endif; ?>
  <tr class="grand-total">
    <td style="padding:5pt 8pt;font-weight:bold">TỔNG THANH TOÁN:</td>
    <td style="text-align:right;padding:5pt 8pt;font-weight:bold;font-size:13pt">
      <?= number_format($grandTotal) ?> đ
    </td>
  </tr>
</table>

<!-- Ghi chú thanh toán -->
<div class="note-box avoid-break">
  <strong>Thông tin thanh toán:</strong><br>
  <?php if (defined('BANK_ACCOUNT')): ?>
  Tên tài khoản: <?= htmlspecialchars(BANK_ACCOUNT_NAME ?? '') ?><br>
  Số tài khoản: <?= htmlspecialchars(BANK_ACCOUNT ?? '') ?><br>
  Ngân hàng: <?= htmlspecialchars(BANK_NAME ?? '') ?>
  <?php else: ?>
  Vui lòng liên hệ kế toán để biết thông tin thanh toán.
  <?php endif; ?>
</div>

<hr class="divider">

<!-- Phần ký tên -->
<div class="sign-section avoid-break">
  <div class="sign-box">
    <div class="sign-title">KẾ TOÁN</div>
    <div class="sign-note">Ký, ghi rõ họ tên</div>
    <div class="sign-name">___________________</div>
  </div>
  <div class="sign-box">
    <div class="sign-title">KHÁCH HÀNG XÁC NHẬN</div>
    <div class="sign-note">Ký, đóng dấu (nếu có)</div>
    <div class="sign-name">___________________</div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/print.php';
