<?php
/**
 * Template in Biên Bản Giao Nhận Hàng Hóa — nhiều lô
 * Biến cần truyền: $trip, $shipments, $customer, $carrier, $viewTitle
 */

ob_start();
?>
<style>
  body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; }
  .dn-company-header { text-align: center; margin-bottom: 4pt; }
  .dn-company-name   { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
  .dn-company-sub    { font-size: 8.5pt; color: #333; }
  .dn-title          { font-size: 14pt; font-weight: bold; text-align: center; text-transform: uppercase; margin: 8pt 0 2pt; }
  .dn-date           { text-align: center; font-size: 10pt; margin-bottom: 10pt; }

  .section-title     { font-weight: bold; font-size: 10.5pt; margin: 8pt 0 3pt; border-bottom: 1px solid #000; padding-bottom: 2pt; }
  .info-row          { display: flex; gap: 8pt; margin-bottom: 3pt; font-size: 10pt; }
  .info-label        { font-weight: bold; white-space: nowrap; min-width: 120pt; }

  .carrier-line      { margin: 6pt 0; font-size: 10.5pt; }
  .carrier-box       { display: inline-block; width: 12pt; height: 12pt; border: 1.5px solid #000;
                       text-align: center; line-height: 11pt; margin-right: 3pt; font-size: 10pt; }

  .content-table     { width: 100%; border-collapse: collapse; margin: 8pt 0; font-size: 9.5pt; }
  .content-table th  { border: 1px solid #000; padding: 4pt 5pt; text-align: center;
                       background: #fff; font-weight: bold; white-space: nowrap; }
  .content-table td  { border: 1px solid #000; padding: 3pt 5pt; text-align: center; }
  .content-table td.left { text-align: left; }

  .condition-section { margin: 8pt 0; font-size: 10pt; }
  .condition-row     { display: flex; gap: 20pt; margin-bottom: 4pt; }
  .check-box         { display: inline-block; width: 11pt; height: 11pt; border: 1.5px solid #000;
                       text-align: center; line-height: 10pt; margin-right: 3pt; font-size: 9pt;
                       vertical-align: middle; }

  .sign-section      { display: flex; justify-content: space-between; margin-top: 18pt; }
  .sign-box          { width: 30%; text-align: center; }
  .sign-box .sign-title { font-weight: bold; font-size: 10.5pt; margin-bottom: 2pt; }
  .sign-box .sign-note  { font-size: 8.5pt; color: #333; margin-bottom: 50pt; font-style: italic; }

  hr.divider { border: none; border-top: 1px solid #000; margin: 6pt 0; }

  @media print {
    body { margin: 0; padding: 0; }
    #print-page { padding: 10mm 12mm 10mm !important; }
    @page { size: A4 portrait; margin: 0; }
  }
</style>

<!-- Header công ty -->
<div class="dn-company-header">
  <div class="dn-company-name">CÔNG TY NAMYANG GLOBAL</div>
  <div class="dn-company-sub">
    Tầng 11, Tòa nhà Detech II, Số 107, Đường Nguyễn Phong Sắc, Phường Dịch Vọng Hậu, Quận Cầu Giấy, Hà Nội
  </div>
  <div class="dn-company-sub">Mã Số Thuế: 106507110</div>
</div>

<hr class="divider">

<div class="dn-title">BIÊN BẢN GIAO NHẬN HÀNG HÓA</div>
<div class="dn-date">Date: <?= date('d-M-y') ?></div>

<!-- BÊN NHẬN HÀNG -->
<div class="section-title">BÊN NHẬN HÀNG :</div>
<div style="font-size:10pt;margin-bottom:6pt">
  <div><strong><?= htmlspecialchars($customer['company_name'] ?? '—') ?></strong></div>
  <?php if (!empty($customer['address'])): ?>
  <div><?= htmlspecialchars($customer['address']) ?></div>
  <?php endif; ?>
  <div style="margin-top:3pt">
    Người đại diện: <strong>_______________________</strong>
    &nbsp;&nbsp;&nbsp;
    Số liên hệ: <strong><?= htmlspecialchars($customer['phone'] ?? '—') ?></strong>
  </div>
</div>

<!-- BÊN GIAO HÀNG -->
<div class="section-title">BÊN GIAO HÀNG :</div>
<div style="font-size:10pt;margin-bottom:3pt">
  <div>
    <strong>CÔNG TY NAMYANG GLOBAL</strong>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Mã Số Thuế: 106507110
  </div>
  <div>Tầng 11, Tòa nhà Detech II, Số 107, Đường Nguyễn Phong Sắc, Phường Dịch Vọng Hậu, Quận Cầu Giấy, Hà Nội</div>
</div>
<div style="font-size:10pt;margin-top:4pt">
  <div><strong>OPS (KEN):</strong></div>
  <table style="margin-left:16pt;font-size:10pt;border-collapse:collapse">
    <tr>
      <td style="padding:1pt 10pt 1pt 0;min-width:140pt">Đào Ngọc Anh Dũng</td>
      <td>Số Liên Hệ: 036 6666 322</td>
    </tr>
    <tr>
      <td style="padding:1pt 10pt 1pt 0">Nguyễn Tiến Thành</td>
      <td>Số Liên Hệ: 037 5486 116</td>
    </tr>
    <tr>
      <td style="padding:1pt 10pt 1pt 0">Nguyễn Mạnh Hưng</td>
      <td>Số Liên Hệ: 0979.179.085</td>
    </tr>
    <tr>
      <td style="padding:1pt 10pt 1pt 0">Lưu Văn Lương</td>
      <td>Số Liên Hệ: 0981.041.889</td>
    </tr>
  </table>
</div>

<!-- BÊN VẬN CHUYỂN -->
<?php
$carriers = ['Tâm Việt', 'Gia Bảo', 'KEN'];
?>
<div class="carrier-line" style="margin-top:6pt">
  <strong>BÊN VẬN CHUYỂN :</strong>
  &nbsp;&nbsp;&nbsp;
  <?php foreach ($carriers as $c): ?>
  <span class="carrier-box"><?= (trim($carrier) === $c) ? '✓' : '' ?></span><?= htmlspecialchars($c) ?>
  &nbsp;&nbsp;&nbsp;&nbsp;
  <?php endforeach; ?>
</div>

<hr class="divider">

<!-- NỘI DUNG -->
<div style="font-weight:bold;font-size:10.5pt;margin-bottom:4pt">NỘI DUNG :</div>
<?php
$totalRows = 8;
$dataRows  = min(count($shipments), $totalRows);
$emptyRows = $totalRows - $dataRows;
?>
<table class="content-table">
  <thead>
    <tr>
      <th style="width:28pt">Stt</th>
      <th>Vận Đơn Thứ Cấp</th>
      <th colspan="2">No.of pkgs</th>
      <th colspan="2">G.W</th>
      <th>Trọng tải</th>
    </tr>
  </thead>
  <tbody>
    <?php for ($i = 0; $i < $totalRows; $i++): ?>
    <?php if ($i < $dataRows): $s = $shipments[$i]; ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td class="left"><?= htmlspecialchars($s['hawb']) ?></td>
      <td><?= $s['packages'] ?></td>
      <td>Kiện</td>
      <td><?= number_format($s['weight'], 0) ?></td>
      <td>Kgs</td>
      <td></td>
    </tr>
    <?php else: ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>Kiện</td>
      <td>&nbsp;</td>
      <td>Kgs</td>
      <td>&nbsp;</td>
    </tr>
    <?php endif; ?>
    <?php endfor; ?>
    <tr style="font-weight:bold">
      <td colspan="2" style="text-align:right">TỔNG CỘNG:</td>
      <td><?= array_sum(array_column($shipments, 'packages')) ?></td>
      <td>Kiện</td>
      <td><?= number_format(array_sum(array_column($shipments, 'weight')), 0) ?></td>
      <td>Kgs</td>
      <td></td>
    </tr>
  </tbody>
</table>

<!-- TÌNH TRẠNG HÀNG HÓA -->
<div style="font-weight:bold;font-size:10.5pt;margin-bottom:4pt">TÌNH TRẠNG HÀNG HÓA :</div>
<div class="condition-section">
  <div class="condition-row">
    <span><span class="check-box">☐</span> Nguyên vẹn trong tình trạng tốt</span>
    <span><span class="check-box">☐</span> Hàng có biên bản bất thường</span>
  </div>
  <div class="condition-row">
    <span><span class="check-box">☐</span> Bẹp rách</span>
    <span><span class="check-box">☐</span> Ướt</span>
  </div>
  <div style="margin-top:4pt">
    Ghi Chú Khác (Nếu Có): ......................................................................
  </div>
</div>

<hr class="divider">

<!-- Ký tên -->
<div class="sign-section">
  <div class="sign-box">
    <div class="sign-title">Người giao hàng</div>
    <div class="sign-note">(Ghi rõ họ tên số điện thoại)</div>
  </div>
  <div class="sign-box">
    <div class="sign-title">Đại diện hãng vận tải</div>
    <div class="sign-note">(Ghi rõ họ tên số điện thoại)</div>
  </div>
  <div class="sign-box">
    <div class="sign-title">Người nhận hàng</div>
    <div class="sign-note">(Ghi rõ họ tên số điện thoại)</div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/print.php';
