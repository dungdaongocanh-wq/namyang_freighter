<?php
require_once __DIR__ . '/config/app.php';

// Nếu chưa đăng nhập, chuyển về trang login
$page = $_GET['page'] ?? '';
$action = $_GET['action'] ?? '';

if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: ' . BASE_URL . '/?page=login');
    exit;
}

$role = $_SESSION['role'] ?? '';

// Router chính theo page và role
switch ($page) {
    case 'login':
        $controller = new AuthController();
        $controller->login();
        break;

    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;

  // ===== CS =====
case 'cs.dashboard':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->csDashboard();
    break;

case 'cs.upload':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->uploadExcel();
    break;

case 'cs.list':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->listShipments();
    break;

case 'cs.customs_upload':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->customsUpload();
    break;
case 'cs.delete_customs':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->deleteCustoms();
    break;

case 'cs.edit_shipment':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->editShipment();
    break;

case 'cs.update_shipment':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->updateShipment();
    break;

case 'cs.delete_shipment':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->deleteShipment();
    break;
case 'cs.delete_customs_record':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->deleteCustomsRecord();
    break;

case 'cs.cancel':
    requireRole(['cs', 'admin']);
    (new ShipmentController())->cancelShipment();
    break;

       // ===== OPS =====
    case 'ops.dashboard':
        requireRole(['ops', 'admin']);
        (new OpsController())->dashboard();
        break;

    case 'ops.pickup':
        requireRole(['ops', 'admin']);
        (new OpsController())->pickup();
        break;

    case 'ops.shipment_list':          // ← THÊM
        requireRole(['ops', 'admin']);
        (new OpsController())->shipmentList();
        break;

    case 'ops.trip':
        requireRole(['ops', 'admin']);
        (new OpsController())->trip();
        break;

    case 'ops.create_trip':            // ← THÊM
        requireRole(['ops', 'admin']);
        (new OpsController())->createTrip();
        break;

    case 'ops.costs':
        requireRole(['ops', 'admin']);
        (new OpsController())->costs();
        break;

    case 'ops.complete':
        requireRole(['ops', 'admin']);
        (new OpsController())->complete();
        break;

    case 'ops.print_delivery_note':
        requireRole(['ops', 'admin']);
        (new OpsController())->printDeliveryNote();
        break;

    case 'ops.download_customs':
        requireRole(['ops', 'admin']);
        (new OpsController())->downloadCustoms();
        break;

    case 'ops.shipments_by_customer':
        requireRole(['ops', 'admin']);
        (new OpsController())->shipmentsByCustomer();
        break;

    case 'ops.print_multi_delivery_note':
        requireRole(['ops', 'admin']);
        (new OpsController())->printMultiDeliveryNote();
        break;

    case 'ops.delete_trip':
        requireRole(['ops', 'admin']);
        (new OpsController())->deleteTrip();
        break;

    case 'ops.save_costs_modal':
        requireRole(['ops', 'admin']);
        (new OpsController())->saveCostsModal();
        break;

    // ===== Driver =====
    case 'driver.dashboard':
        requireRole('driver', 'admin');
        $controller = new DriverController();
        $controller->dashboard();
        break;

    case 'driver.trip_detail':
        requireRole('driver', 'admin');
        $controller = new DriverController();
        $controller->tripDetail();
        break;

    case 'driver.signature':
        requireRole('driver', 'admin');
        $controller = new DriverController();
        $controller->signature();
        break;

    case 'driver.save_signature':
        requireRole('driver', 'admin');
        $controller = new DriverController();
        $controller->saveSignature();
        break;

    case 'driver.delivery_confirm':
        requireRole('driver', 'admin');
        $controller = new DriverController();
        $controller->deliveryConfirm();
        break;

    // ===== Accounting =====
    case 'accounting.dashboard':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->dashboard();
        break;

    case 'accounting.review':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->review();
        break;

    case 'accounting.save_costs':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->saveCosts();
        break;

    case 'accounting.push_customer':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->pushToCustomer();
        break;

    case 'accounting.rejected':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->rejected();
        break;

    case 'accounting.debt':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->debt();
        break;

    case 'accounting.invoice':
        requireRole('accounting', 'admin');
        $controller = new AccountingController();
        $controller->invoice();
        break;

    // ===== Customer =====
    case 'customer.dashboard':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->dashboard();
        break;

    case 'customer.shipment_list':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->shipmentList();
        break;

    case 'customer.shipment_detail':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->shipmentDetail();
        break;

    case 'customer.pending_approval':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->pendingApproval();
        break;

    case 'customer.approve':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->approve();
        break;

    case 'customer.reject':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->reject();
        break;

    case 'customer.history':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->history();
        break;

    case 'customer.debt':
        requireRole(['customer', 'cs', 'admin']);
        $controller = new CustomerController();
        $controller->debt();
        break;

    // ===== Admin =====
case 'admin.dashboard':
    requireRole('admin');
    (new AdminController())->dashboard();
    break;

case 'admin.users':
    requireRole('admin');
    (new AdminController())->users();
    break;

case 'admin.save_user':
    requireRole('admin');
    (new AdminController())->saveUser();
    break;

case 'admin.customers':
    requireRole('admin');
    (new AdminController())->customers();
    break;

case 'admin.save_customer':
    requireRole('admin');
    (new AdminController())->saveCustomer();
    break;

case 'admin.quotation':
    requireRole('admin');
    (new AdminController())->quotation();
    break;

case 'admin.quotation_detail':
    requireRole('admin');
    (new AdminController())->quotationDetail();
    break;

case 'admin.save_quotation':
    requireRole('admin');
    (new AdminController())->saveQuotation();
    break;

case 'admin.settings':
    requireRole('admin');
    $viewTitle = 'Cài đặt';
    $viewFile  = __DIR__ . '/views/admin/settings.php';
    include __DIR__ . '/views/layouts/main.php';
    break;

case 'admin.cost_groups':
    requireRole('admin');
    (new AdminController())->costGroups();
    break;

case 'admin.save_cost_group':
    requireRole('admin');
    (new AdminController())->saveCostGroup();
    break;

case 'admin.delete_cost_group':
    requireRole('admin');
    (new AdminController())->deleteCostGroup();
    break;

    // ===== Reports =====
    case 'report.export':
        requireRole('admin', 'accounting', 'cs');
        $controller = new ReportController();
        $controller->export();
        break;

    case 'report.shipment':
        requireRole(['admin', 'accounting', 'cs']);
        (new ReportController())->shipmentReport();
        break;

    case 'report.ops_costs':
        requireRole(['admin', 'accounting']);
        (new ReportController())->opsCostReport();
        break;

    // ===== Quotation =====
    case 'quotation.index':
    case 'quotation.create':
    case 'quotation.store':
    case 'quotation.show':
    case 'quotation.edit':
    case 'quotation.update':
    case 'quotation.delete':
        requireRole(['admin', 'accounting']);
        (new QuotationController())->dispatch();
        break;

    // ===== Debt =====
    case 'debt.index':
        requireRole(['admin', 'accounting', 'customer']);
        (new DebtController())->index();
        break;

    case 'debt.show':
        requireRole(['admin', 'accounting', 'customer']);
        (new DebtController())->show();
        break;

    case 'debt.mark_paid':
        requireRole(['admin', 'accounting']);
        (new DebtController())->markPaid();
        break;

    case 'debt.export':
        requireRole(['admin', 'accounting']);
        (new DebtController())->export();
        break;

    // ===== Shipment Modal (AJAX partial) =====
    case 'shipment.modal':
        (new ShipmentController())->modal();
        break;

    // ===== Notifications =====
    case 'notifications.read':
        $controller = new NotificationController();
        $controller->markRead();
        break;

    case 'notifications.mark_all_read':
        $controller = new NotificationController();
        $controller->markAllRead();
        break;

    // ===== Shared: Delivery Board =====
    case 'shared.delivery_board':
        requireRole(['cs', 'ops', 'accounting', 'admin']);
        (new DeliveryBoardController())->index();
        break;

    case 'shared.delivery_board_cost_form':
        requireRole(['cs', 'ops', 'accounting', 'admin']);
        (new DeliveryBoardController())->costForm();
        break;

    case 'shared.delivery_board_save_costs':
        requireRole(['cs', 'ops', 'accounting', 'admin']);
        (new DeliveryBoardController())->saveCosts();
        break;

    case 'shared.delivery_board_confirm':
        requireRole(['ops', 'accounting', 'admin']);
        (new DeliveryBoardController())->confirmDelivered();
        break;

    // ===== Statement =====
    case 'statement.index':
        requireRole(['cs', 'admin', 'customer']);
        (new StatementController())->index();
        break;

    case 'statement.export':
        requireRole(['cs', 'admin', 'customer']);
        (new StatementController())->export();
        break;

    default:
        // Redirect về dashboard theo role
        if (isset($_SESSION['role'])) {
            $redirectMap = [
                'admin'      => 'admin.dashboard',
                'cs'         => 'cs.dashboard',
                'ops'        => 'ops.dashboard',
                'driver'     => 'driver.dashboard',
                'accounting' => 'accounting.dashboard',
                'customer'   => 'customer.dashboard',
            ];
            $dest = $redirectMap[$_SESSION['role']] ?? 'login';
            header('Location: ' . BASE_URL . '/?page=' . $dest);
            exit;
        }
        header('Location: ' . BASE_URL . '/?page=login');
        exit;
}
