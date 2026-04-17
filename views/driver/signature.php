<!-- Shipment info mini -->
<div class="p-3 mb-3 rounded-3" style="background:#f0f7ff;border-left:4px solid #2d6a9f">
  <div class="fw-bold text-primary"><?= htmlspecialchars($shipment['hawb']) ?></div>
  <div style="font-size:0.8rem;color:#64748b">
    <?= htmlspecialchars($shipment['customer_code']) ?> ·
    <?= htmlspecialchars($shipment['company_name'] ?? '') ?>
  </div>
  <?php if ($shipment['customer_address']): ?>
  <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px">
    📍 <?= htmlspecialchars($shipment['customer_address']) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Signature card -->
<div class="mobile-card card">
  <div class="card-body">
    <h6 class="fw-bold mb-3 text-center">✍️ Chữ ký người nhận hàng</h6>

    <!-- Tên người ký -->
    <div class="mb-3">
      <label class="form-label fw-semibold small">👤 Tên người ký nhận *</label>
      <input type="text" id="signerName" class="form-control"
             placeholder="Nhập tên người nhận hàng..."
             style="border-radius:10px;font-size:1rem;padding:12px">
    </div>

    <!-- Canvas chữ ký -->
    <div class="mb-3">
      <label class="form-label fw-semibold small">🖊️ Chữ ký *</label>
      <div style="border:2px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;position:relative">
        <canvas id="signatureCanvas"
                style="width:100%;display:block;touch-action:none;cursor:crosshair">
        </canvas>
        <div id="sigPlaceholder"
             style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">
          <span style="color:#cbd5e1;font-size:0.85rem">Ký tên tại đây</span>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-2">
        <button type="button" onclick="clearSignature()"
                class="btn btn-outline-danger btn-sm">
          🗑️ Xóa
        </button>
        <button type="button" onclick="undoSignature()"
                class="btn btn-outline-secondary btn-sm">
          ↩️ Hoàn tác
        </button>
      </div>
    </div>

    <!-- Error message -->
    <div id="sigError" class="alert alert-danger d-none py-2 mb-3" style="border-radius:8px;font-size:0.85rem"></div>

    <!-- Submit -->
    <button type="button" id="submitBtn"
            onclick="submitSignature()"
            class="btn btn-mobile-lg btn-success w-100">
      ✅ Xác nhận giao hàng
    </button>

    <a href="<?= BASE_URL ?>/?page=driver.trip_detail&id=<?= (int)($_GET['trip_id'] ?? 0) ?>"
       class="btn btn-outline-secondary w-100 mt-2">
      ← Quay lại
    </a>
  </div>
</div>

<!-- signature_pad.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
const canvas = document.getElementById('signatureCanvas');
const placeholder = document.getElementById('sigPlaceholder');

// Set canvas size
function resizeCanvas() {
  const ratio  = Math.max(window.devicePixelRatio || 1, 1);
  const width  = canvas.offsetWidth;
  const height = Math.max(200, Math.floor(width * 0.45));
  canvas.height = height * ratio;
  canvas.width  = width * ratio;
  canvas.style.height = height + 'px';
  canvas.getContext('2d').scale(ratio, ratio);
  signaturePad.clear();
}

const signaturePad = new SignaturePad(canvas, {
  minWidth: 1.5,
  maxWidth: 3,
  penColor: '#1e3a5f',
  backgroundColor: 'rgba(0,0,0,0)',
});

window.addEventListener('resize', resizeCanvas);
resizeCanvas();

// Ẩn placeholder khi bắt đầu ký
signaturePad.addEventListener('beginStroke', () => {
  placeholder.style.display = 'none';
});

// Undo (xóa stroke cuối)
const strokes = [];
signaturePad.addEventListener('endStroke', () => {
  strokes.push(signaturePad.toData());
});

function undoSignature() {
  const data = signaturePad.toData();
  if (data.length > 0) {
    data.pop();
    signaturePad.fromData(data);
    if (data.length === 0) {
      placeholder.style.display = 'flex';
    }
  }
}

function clearSignature() {
  signaturePad.clear();
  placeholder.style.display = 'flex';
  strokes.length = 0;
}

function showError(msg) {
  const el = document.getElementById('sigError');
  el.textContent = msg;
  el.classList.remove('d-none');
  setTimeout(() => el.classList.add('d-none'), 4000);
}

function submitSignature() {
  const name = document.getElementById('signerName').value.trim();
  if (!name) {
    showError('⚠️ Vui lòng nhập tên người ký nhận!');
    document.getElementById('signerName').focus();
    return;
  }
  if (signaturePad.isEmpty()) {
    showError('⚠️ Vui lòng ký tên trước khi xác nhận!');
    return;
  }

  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.textContent = '⏳ Đang lưu...';

  const signatureData = signaturePad.toDataURL('image/png');

  const formData = new FormData();
  formData.append('shipment_id',   '<?= (int)($_GET['shipment_id'] ?? 0) ?>');
  formData.append('trip_id',       '<?= (int)($_GET['trip_id'] ?? 0) ?>');
  formData.append('signed_name',   name);
  formData.append('signature_data', signatureData);

  fetch('<?= BASE_URL ?>/?page=driver.save_signature', {
    method: 'POST',
    body:   formData,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Hiện tick xanh trước khi redirect
      btn.textContent = '✅ Đã lưu!';
      btn.classList.replace('btn-success', 'btn-outline-success');
      setTimeout(() => {
        window.location.href = data.redirect;
      }, 800);
    } else {
      showError('❌ ' + (data.message || 'Lỗi không xác định!'));
      btn.disabled = false;
      btn.textContent = '✅ Xác nhận giao hàng';
    }
  })
  .catch(() => {
    showError('❌ Lỗi kết nối, vui lòng thử lại!');
    btn.disabled = false;
    btn.textContent = '✅ Xác nhận giao hàng';
  });
}

// Prevent page scroll khi ký trên mobile
canvas.addEventListener('touchmove', e => e.preventDefault(), { passive: false });
</script>