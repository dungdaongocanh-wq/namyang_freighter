<?php
$ROLE_PERMISSIONS = [
    'admin' => [
        'manage_users', 'manage_customers', 'manage_quotations',
        'view_all_shipments', 'view_reports', 'manage_settings',
        'upload_shipments', 'view_accounting', 'view_ops',
    ],
    'cs' => [
        'upload_shipments', 'view_shipments', 'upload_customs',
        'edit_shipments',
    ],
    'ops' => [
        'view_shipments', 'update_shipment_status', 'upload_photos',
        'download_customs', 'create_trips', 'view_trips',
    ],
    'driver' => [
        'view_assigned_trips', 'confirm_delivery', 'sign_delivery',
    ],
    'accounting' => [
        'view_delivered_shipments', 'review_costs', 'push_to_customer',
        'manage_debts', 'generate_invoices', 'view_all_costs',
    ],
    'customer' => [
        'view_own_shipments', 'approve_costs', 'reject_costs',
        'view_debt', 'add_notes',
    ],
];

function hasPermission(string $role, string $action): bool {
    global $ROLE_PERMISSIONS;
    return isset($ROLE_PERMISSIONS[$role]) && in_array($action, $ROLE_PERMISSIONS[$role]);
}

/**
 * Hỗ trợ cả 2 kiểu gọi:
 * requireRole('admin', 'cs')
 * requireRole(['admin', 'cs'])
 */
function requireRole(...$roles): void {
    // Flatten — hỗ trợ cả array lẫn string
    $flat = [];
    foreach ($roles as $r) {
        if (is_array($r)) $flat = array_merge($flat, $r);
        else $flat[] = $r;
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/?page=login');
        exit;
    }

    if (!empty($flat) && !in_array($_SESSION['role'] ?? '', $flat)) {
        http_response_code(403);
        echo '<div style="font-family:sans-serif;text-align:center;padding:4rem">
                <h1 style="color:#dc2626">403</h1>
                <p>Bạn không có quyền truy cập trang này.</p>
                <a href="' . BASE_URL . '/" style="color:#2563eb">← Quay lại</a>
              </div>';
        exit;
    }
}

function getRoleLabel(string $role): string {
    $labels = [
        'admin'      => 'Quản trị viên',
        'cs'         => 'CS',
        'ops'        => 'OPS',
        'driver'     => 'Lái xe',
        'accounting' => 'Kế toán',
        'customer'   => 'Khách hàng',
    ];
    return $labels[$role] ?? $role;
}