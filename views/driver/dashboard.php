<!-- Stats -->
<div class="row g-2 mb-3">
  <div class="col-4">
    <div class="mobile-card card text-center">
      <div class="card-body py-3 px-2">
        <div class="fw-bold" style="font-size:1.6rem;color:#1d4ed8"><?= $stats['today_trips'] ?></div>
        <div style="font-size:0.7rem;color:#64748b">Chuyến hôm nay</div>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="mobile-card card text-center">
      <div class="card-body py-3 px-2">
        <div class="fw-bold" style="font-size:1.6rem;color:#c2410c"><?= $stats['pending'] ?></div>
        <div style="font-size:0.7rem;color:#64748b">Chờ giao</div>
      </div>
    </div>
  </div>
  <div class="col-4">
    <div class="mobile-card card text-center">
      <div class="card-body py-3 px-2">
        <div class="fw-bold" style="font-size:1.6rem;color:#15803d"><?= $stats['delivered_today'] ?></div>
        <div style="font-size:0.7rem;color:#64748b">Đã giao</div>
      </div>
    </div>
  </div>
</div>

<!-- Chuyến hôm nay -->
<h6 class="fw-bold mb-2">📅 Chuyến hôm nay</h6>

<?php if (empty($todayTrips)): ?>
<div class="mobile-card card mb-3">
  <div class="card-body text-center py-4">
    <div style="font-size:2.5rem">😴</div>
    <p class="text-muted small mt-2">Chưa có chuyến nào hôm nay</p>
  </div>
</div>
<?php else: ?>
<?php foreach ($todayTrips as $t):
  $progress = $t['total_items'] > 0 ? ($t['delivered_items'] / $t['total_items'] * 100) : 0;
  $isDone   = $t['status'] === 'completed';
?>
<a href="<?= BASE_URL ?>/?page=driver.trip_detail&id=<?= $t['id'] ?>"
   class="text-decoration-none">
  <div class="mobile-card card mb-3"
       style="border-left:4px solid <?= $isDone ? '#15803d' : '#1d4ed8' ?>">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <div class="fw-bold text-primary">Chuyến #<?= $t['id'] ?></div>
          <div style="font-size:0.78rem;color:#64748b">
            OPS: <?= htmlspecialchars($t['ops_name'] ?? 'N/A') ?>
          </div>
        </div>
        <span class="badge <?= $isDone ? 'bg-success' : 'bg-primary' ?>">
          <?= $isDone ? '✅ Hoàn thành' : '🚚 Đang giao' ?>
        </span>
      </div>

      <!-- Progress bar -->
      <div class="d-flex align-items-center gap-2">
        <div class="progress flex-grow-1" style="height:8px;border-radius:4px">
          <div class="progress-bar <?= $isDone ? 'bg-success' : 'bg-primary' ?>"
               style="width:<?= $progress ?>%"></div>
        </div>
        <small class="text-muted fw-semibold" style="white-space:nowrap">
          <?= $t['delivered_items'] ?>/<?= $t['total_items'] ?> lô
        </small>
      </div>

      <?php if ($t['note']): ?>
      <div class="mt-2 small text-muted">📝 <?= htmlspecialchars($t['note']) ?></div>
      <?php endif; ?>

      <div class="mt-2 text-end" style="font-size:0.75rem;color:#2d6a9f">
        Xem chi tiết ›
      </div>
    </div>
  </div>
</a>
<?php endforeach; ?>
<?php endif; ?>

<!-- Chuyến còn dở (ngoài hôm nay) -->
<?php
$otherActive = array_filter($activeTrips, fn($t) => $t['trip_date'] !== date('Y-m-d'));
if (!empty($otherActive)):
?>
<h6 class="fw-bold mb-2 mt-3">⏳ Chuyến chưa hoàn thành</h6>
<?php foreach ($otherActive as $t): ?>
<a href="<?= BASE_URL ?>/?page=driver.trip_detail&id=<?= $t['id'] ?>"
   class="text-decoration-none">
  <div class="mobile-card card mb-2" style="border-left:4px solid #f97316">
    <div class="card-body py-2 px-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold small text-primary">Chuyến #<?= $t['id'] ?></div>
          <div style="font-size:0.72rem;color:#64748b">
            <?= date('d/m/Y', strtotime($t['trip_date'])) ?> ·
            <?= $t['delivered_items'] ?>/<?= $t['total_items'] ?> lô
          </div>
        </div>
        <span class="badge bg-warning text-dark" style="font-size:0.7rem">Chưa xong</span>
      </div>
    </div>
  </div>
</a>
<?php endforeach; ?>
<?php endif; ?>