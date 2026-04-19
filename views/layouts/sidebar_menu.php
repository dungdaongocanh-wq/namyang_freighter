<?php
$role        = $_SESSION['role'] ?? '';
$currentPage = $_GET['page'] ?? '';

function sidebarLink($page, $icon, $label, $currentPage) {
    $active = ($currentPage === $page) ? 'active' : '';
    $url    = BASE_URL . '/?page=' . $page;
    echo "<a href=\"$url\" class=\"nav-link $active\"><i class=\"bi bi-$icon\"></i> $label</a>";
}
?>

<?php if ($role === 'cs' || $role === 'admin'): ?>
<div class="nav-label">CS - Chứng từ</div>
<?php sidebarLink('cs.dashboard',       'speedometer2',        'Dashboard',        $currentPage); ?>
<?php sidebarLink('cs.upload',          'cloud-upload',        'Upload lô hàng',   $currentPage); ?>
<?php sidebarLink('cs.list',            'table',               'Danh sách lô',     $currentPage); ?>
<?php sidebarLink('cs.customs_upload',  'file-earmark-text',   'Upload tờ khai',   $currentPage); ?>
<?php sidebarLink('shared.delivery_board', 'kanban',           'Bảng giao hàng',   $currentPage); ?>
<?php sidebarLink('statement.index',    'bar-chart-line',      'Bảng kê chi tiết', $currentPage); ?>
<?php sidebarLink('report.export',      'file-earmark-arrow-down', 'Xuất báo cáo', $currentPage); ?>
<?php sidebarLink('report.shipment',    'file-earmark-bar-graph',  'BC Lô đã xác nhận', $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'ops' || $role === 'admin'): ?>
<div class="nav-label">OPS - Vận hành</div>
<?php sidebarLink('ops.dashboard',         'speedometer2', 'Dashboard',        $currentPage); ?>
<?php sidebarLink('ops.shipment_list',     'boxes',        'Danh sách lô',     $currentPage); ?>
<?php sidebarLink('ops.download_customs',  'download',     'Tải tờ khai',      $currentPage); ?>
<?php sidebarLink('ops.trip',              'truck',        'Tạo chuyến',       $currentPage); ?>
<?php sidebarLink('ops.costs',             'calculator',   'Chi phí',          $currentPage); ?>
<?php sidebarLink('shared.delivery_board', 'kanban',       'Bảng giao hàng',   $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'driver'): ?>
<div class="nav-label">Lái xe</div>
<?php sidebarLink('driver.dashboard', 'truck', 'Chuyến của tôi', $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'accounting' || $role === 'admin'): ?>
<div class="nav-label">Kế toán</div>
<?php sidebarLink('accounting.dashboard', 'speedometer2',    'Dashboard',         $currentPage); ?>
<?php sidebarLink('accounting.review',    'clipboard-check', 'Xét duyệt chi phí', $currentPage); ?>
<?php sidebarLink('accounting.rejected',  'x-circle',        'KH từ chối',        $currentPage); ?>
<?php sidebarLink('accounting.debt',      'wallet2',         'Công nợ KH',        $currentPage); ?>
<?php sidebarLink('accounting.invoice',   'receipt',         'Hoá đơn',           $currentPage); ?>
<?php sidebarLink('quotation.index',      'tags',            'Báo giá',           $currentPage); ?>
<?php sidebarLink('statement.index',      'bar-chart-line',  'Bảng kê chi tiết',  $currentPage); ?>
<?php sidebarLink('shared.delivery_board','kanban',          'Bảng giao hàng',    $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'accounting' || $role === 'admin'): ?>
<div class="nav-label">📊 Báo cáo</div>
<?php sidebarLink('report.shipment', 'file-earmark-bar-graph', 'BC Lô đã xác nhận', $currentPage); ?>
<?php sidebarLink('report.ops_costs', 'people', 'BC Chi phí OPS', $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'customer'): ?>
<div class="nav-label">Khách hàng</div>
<?php sidebarLink('customer.dashboard',        'house',          'Tổng quan',      $currentPage); ?>
<?php sidebarLink('customer.shipment_list',    'boxes',          'Lô hàng của tôi',$currentPage); ?>
<?php sidebarLink('customer.pending_approval', 'bell',           'Duyệt chi phí',  $currentPage); ?>
<?php sidebarLink('customer.history',          'clock-history',  'Lịch sử',        $currentPage); ?>
<?php sidebarLink('customer.debt',             'wallet2',        'Công nợ',         $currentPage); ?>
<?php sidebarLink('debt.index',                'credit-card',    'Thanh toán',      $currentPage); ?>
<?php sidebarLink('statement.index',           'bar-chart-line', 'Bảng kê chi phí',$currentPage); ?>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<div class="nav-label">Báo cáo</div>
<?php sidebarLink('report.export',    'file-earmark-arrow-down', 'Xuất báo cáo',         $currentPage); ?>
<?php sidebarLink('report.shipment',  'file-earmark-bar-graph',  'BC Lô đã xác nhận',    $currentPage); ?>
<?php sidebarLink('report.ops_costs', 'people',                  'BC Chi phí OPS',        $currentPage); ?>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<div class="nav-label">Quản trị</div>
<?php sidebarLink('admin.dashboard',   'speedometer2', 'Dashboard',       $currentPage); ?>
<?php sidebarLink('admin.users',       'people',       'Quản lý Users',   $currentPage); ?>
<?php sidebarLink('admin.customers',   'building',     'Khách hàng',      $currentPage); ?>
<?php sidebarLink('admin.quotation',   'tags',         'Báo giá',         $currentPage); ?>
<?php sidebarLink('admin.cost_groups', 'bookmark',     'Nhóm chi phí',    $currentPage); ?>
<?php sidebarLink('admin.settings',    'gear',         'Cài đặt',         $currentPage); ?>
<?php endif; ?>