<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($viewTitle ?? 'In tài liệu') ?> — <?= APP_NAME ?></title>
  <style>
    /* ── Reset & Base ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 12pt;
      color: #000;
      background: #fff;
      margin: 0;
      padding: 0;
    }

    /* ── Khung trang A4 (chỉ trên màn hình) ── */
    @media screen {
      body { background: #e5e7eb; padding: 24px; }
      #print-page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: #fff;
        box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        padding: 20mm 20mm 18mm;
        position: relative;
      }
    }

    /* ── In A4 ── */
    @media print {
      body { background: #fff; padding: 0; margin: 0; }
      #print-page { padding: 15mm 15mm 12mm; width: 100%; }
      .no-print { display: none !important; }
      @page { size: A4 portrait; margin: 0; }
    }

    /* ── Nút điều khiển (chỉ hiện trên màn hình) ── */
    .print-actions {
      position: fixed;
      top: 16px;
      right: 16px;
      display: flex;
      gap: 8px;
      z-index: 100;
    }
    .print-actions button,
    .print-actions a {
      padding: 8px 20px;
      border-radius: 8px;
      border: none;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-print {
      background: #1e3a5f;
      color: #fff;
    }
    .btn-print:hover { background: #2d6a9f; }
    .btn-close-print {
      background: #f1f5f9;
      color: #334155;
      border: 1px solid #cbd5e1;
    }
    .btn-close-print:hover { background: #e2e8f0; }

    /* ── Typography in ấn ── */
    h1 { font-size: 16pt; }
    h2 { font-size: 14pt; }
    h3 { font-size: 13pt; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 5pt 7pt; }

    /* ── Page break ── */
    .page-break { page-break-after: always; break-after: page; }
    .avoid-break { page-break-inside: avoid; break-inside: avoid; }
  </style>
</head>
<body>

<!-- Nút điều khiển (ẩn khi in) -->
<div class="print-actions no-print">
  <button class="btn-print" onclick="window.print()">🖨️ In trang</button>
  <a href="javascript:window.close()" class="btn-close-print">✕ Đóng</a>
</div>

<!-- Nội dung trang in -->
<div id="print-page">
  <?= $content ?? '' ?>
</div>

<script>
// Tự mở hộp thoại in nếu URL có ?autoprint=1
if (new URLSearchParams(location.search).get('autoprint') === '1') {
  window.onload = function() { window.print(); };
}
</script>
</body>
</html>
