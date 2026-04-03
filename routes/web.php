<?php
// Update workflow stage customer
Route::post('/customer/{id}/workflow', [App\Http\Controllers\CustomerController::class, 'updateWorkflow'])->name('customer.updateWorkflow');
// Update workflow stage customer via AJAX (LeadWorkflowController)
Route::post('/customer/{id}/workflow-stage', [App\Http\Controllers\LeadWorkflowController::class, 'updateCustomer'])->name('customer.workflow-stage');
// Marketing: Lead Conversion Summary
Route::get('/marketing/lead-summary', [App\Http\Controllers\CustomerController::class, 'leadSummary'])->name('marketing.lead-summary');

// Marketing: Manajemen Promo App
Route::middleware('auth')->prefix('marketing/promos')->name('marketing.promos.')->group(function () {
    Route::get('/',              [App\Http\Controllers\AppPromoController::class, 'index'])->name('index');
    Route::get('/create',        [App\Http\Controllers\AppPromoController::class, 'create'])->name('create');
    Route::post('/',             [App\Http\Controllers\AppPromoController::class, 'store'])->name('store');
    Route::get('/{promo}/edit',  [App\Http\Controllers\AppPromoController::class, 'edit'])->name('edit');
    Route::put('/{promo}',       [App\Http\Controllers\AppPromoController::class, 'update'])->name('update');
    Route::delete('/{promo}',    [App\Http\Controllers\AppPromoController::class, 'destroy'])->name('destroy');
    Route::post('/{promo}/toggle', [App\Http\Controllers\AppPromoController::class, 'toggleActive'])->name('toggle');
    Route::post('/upload-media',   [App\Http\Controllers\AppPromoController::class, 'uploadMedia'])->name('upload-media');
});

// Lead Workflow Settings
Route::prefix('settings/lead-workflow')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\LeadWorkflowController::class, 'index'])->name('lead-workflow.index');
    Route::post('/', [App\Http\Controllers\LeadWorkflowController::class, 'store'])->name('lead-workflow.store');
    Route::put('/{id}', [App\Http\Controllers\LeadWorkflowController::class, 'update'])->name('lead-workflow.update');
    Route::delete('/{id}', [App\Http\Controllers\LeadWorkflowController::class, 'destroy'])->name('lead-workflow.destroy');
    Route::post('/reorder', [App\Http\Controllers\LeadWorkflowController::class, 'reorder'])->name('lead-workflow.reorder');
});

// Per-customer workflow steps (AJAX)
Route::prefix('customer/{id}/steps')->group(function () {
    Route::post('/start',   [App\Http\Controllers\LeadWorkflowController::class, 'startSteps'])->name('customer.steps.start');
    Route::post('/add',     [App\Http\Controllers\LeadWorkflowController::class, 'addStep'])->name('customer.steps.add');
    Route::post('/delete',  [App\Http\Controllers\LeadWorkflowController::class, 'deleteStep'])->name('customer.steps.delete');
    Route::post('/move',    [App\Http\Controllers\LeadWorkflowController::class, 'moveStep'])->name('customer.steps.move');
    Route::post('/reorder', [App\Http\Controllers\LeadWorkflowController::class, 'reorderSteps'])->name('customer.steps.reorder');
});


// AJAX: Lead Update History Timeline
Route::get('/customer/{id}/lead-history', [App\Http\Controllers\CustomerController::class, 'leadHistory']);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProbeController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\GitHubSyncController;
use App\Http\Controllers\Admin\LicensePlanController;
use App\Http\Controllers\Admin\AdminMigrateController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\SalesAuthController;
use App\Http\Controllers\AppPortalController;
use App\Http\Controllers\AppPromoController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Customer Portal Routes
Route::prefix('tagihan')->group(function() {
    // Public routes (login & activation)
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
    Route::get('/activate', [CustomerAuthController::class, 'showActivate'])->name('customer.activate');
    Route::post('/activate', [CustomerAuthController::class, 'activate'])->name('customer.activate.submit');

    // SSO Bridge untuk Android App WebView
    // GET /tagihan/app-login?token={bearer}&redirect={path}
    Route::get('/app-login', [CustomerAuthController::class, 'appLogin'])->name('customer.app-login');

    // Force logout: hapus web session, kemudian redirect ke login dengan marker
    // Digunakan saat admin merevoke token dari panel
    Route::get('/app-force-logout', [CustomerAuthController::class, 'appForceLogout'])->name('customer.app-force-logout');

    // API publik: badge count notifikasi (langsung via token, tidak butuh session)
    Route::get('/app/notif-badge', [AppPortalController::class, 'notifBadge'])->name('app.notif-badge');

    // Protected routes (requires customer authentication)
    Route::middleware('auth:customer')->group(function() {
        Route::get('/', [CustomerAuthController::class, 'index'])->name('customer.index');
        Route::get('/select-customer', [CustomerAuthController::class, 'selectCustomer'])->name('customer.select');
        Route::get('/view-invoice/{customerId}', [CustomerAuthController::class, 'viewInvoice'])->name('customer.view-invoice');
        Route::get('/tickets/{customerId}', [CustomerAuthController::class, 'viewTickets'])->name('customer.tickets');
        Route::post('/addons/order/{customerId}', [CustomerAuthController::class, 'orderAddons'])->name('customer.addons.order');
        Route::get('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

        // ── App-only pages (WebView Android) ──────────────────────────────
        Route::prefix('app')->name('app.')->group(function () {
            Route::get('/home',    [AppPortalController::class, 'home'])->name('home');
            Route::get('/tagihan', [AppPortalController::class, 'tagihan'])->name('tagihan');
            Route::get('/laporan', [AppPortalController::class, 'laporan'])->name('laporan');
            Route::get('/notif',   [AppPortalController::class, 'notif'])->name('notif');
        });
    });
});

// Sales Portal Routes
Route::prefix('sales')->group(function() {
    // Public routes (login & activation)
    Route::get('/login', [SalesAuthController::class, 'showLogin'])->name('sales.login');
    Route::post('/login', [SalesAuthController::class, 'login'])->name('sales.login.submit');
    Route::get('/activate', [SalesAuthController::class, 'showActivate'])->name('sales.activate');
    Route::post('/activate', [SalesAuthController::class, 'activate'])->name('sales.activate.submit');
    
    // Protected routes (requires sales authentication)
    Route::middleware('auth:sales')->group(function() {
        Route::get('/', [SalesAuthController::class, 'index'])->name('sales.index');
        Route::get('/table-customer', [SalesAuthController::class, 'table_customer_sales'])->name('sales.table-customer');
        Route::get('/customer/create', [SalesAuthController::class, 'showCreateCustomer'])->name('sales.customer.create');
        Route::post('/customer/store', [SalesAuthController::class, 'storeCustomer'])->name('sales.customer.store')->middleware('check.license');
        Route::get('/customer/{id}', [SalesAuthController::class, 'showCustomer'])->name('sales.customer.detail');
        Route::get('/customer/{id}/edit', [SalesAuthController::class, 'showEditCustomer'])->name('sales.customer.edit');
        Route::post('/customer/{id}/update', [SalesAuthController::class, 'updateCustomer'])->name('sales.customer.update');
        Route::post('/customer/{id}/stage', [SalesAuthController::class, 'updateCustomerStage'])->name('sales.customer.stage');
        Route::get('/logout', [SalesAuthController::class, 'logout'])->name('sales.logout');
    });
});

// Admin Authentication Routes
Route::prefix('admin')->middleware('admin')->group(function() {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Tenant Management Routes (Protected by admin guard)
Route::middleware(['admin', 'auth:admin'])->prefix('admin')->group(function() {
    // Tenant Management
    Route::get('/tenants', [TenantManagementController::class, 'index'])->name('admin.tenants.index');
    Route::get('/tenants/create', [TenantManagementController::class, 'create'])->name('admin.tenants.create');
    Route::post('/tenants', [TenantManagementController::class, 'store'])->name('admin.tenants.store');
    Route::get('/tenants/{id}', [TenantManagementController::class, 'show'])->name('admin.tenants.show');
    Route::get('/tenants/{id}/edit', [TenantManagementController::class, 'edit'])->name('admin.tenants.edit');
    Route::put('/tenants/{id}', [TenantManagementController::class, 'update'])->name('admin.tenants.update');
    Route::post('/tenants/{id}/backup', [TenantManagementController::class, 'backupDatabase'])->name('admin.tenants.backup');
    Route::get('/tenants/{id}/backups', [TenantManagementController::class, 'backups'])->name('admin.tenants.backups');
    Route::get('/tenants/{id}/backups/download/{filename}', [TenantManagementController::class, 'downloadBackup'])->name('admin.tenants.backups.download');
    Route::delete('/tenants/{id}/backups/{filename}', [TenantManagementController::class, 'deleteBackup'])->name('admin.tenants.backups.delete');
    Route::get('/tenants/{id}/customers', [TenantManagementController::class, 'customers'])->name('admin.tenants.customers');
    Route::get('/tenants/{id}/customers/data', [TenantManagementController::class, 'customersData'])->name('admin.tenants.customers.data');

    // Tenant Users Management
    Route::get('/tenants/{id}/users', [TenantManagementController::class, 'tenantUsers'])->name('admin.tenants.users');
    Route::post('/tenants/{id}/users', [TenantManagementController::class, 'storeTenantUser'])->name('admin.tenants.users.store');
    Route::put('/tenants/{id}/users/{userId}', [TenantManagementController::class, 'updateTenantUser'])->name('admin.tenants.users.update');
    Route::delete('/tenants/{id}/users/{userId}', [TenantManagementController::class, 'destroyTenantUser'])->name('admin.tenants.users.destroy');
    Route::post('/tenants/{id}/users/{userId}/reset-password', [TenantManagementController::class, 'resetTenantUserPassword'])->name('admin.tenants.users.reset-password');
    Route::get('/tenants/{id}/transactions', [TenantManagementController::class, 'transactions'])->name('admin.tenants.transactions');
    Route::post('/tenants/{id}/transactions/data', [TenantManagementController::class, 'transactionsData'])->name('admin.tenants.transactions.data');
    Route::delete('/tenants/{id}', [TenantManagementController::class, 'destroy'])->name('admin.tenants.destroy');
    Route::post('/tenants/{id}/toggle', [TenantManagementController::class, 'toggleStatus'])->name('admin.tenants.toggle');
    Route::post('/tenants/{id}/upload-assets', [TenantManagementController::class, 'uploadAssets'])->name('admin.tenants.upload-assets');
    Route::get('/tenants/{id}/queue-status', [TenantManagementController::class, 'queueStatus'])->name('admin.tenants.queue-status');
    Route::post('/tenants/{id}/queue-restart', [TenantManagementController::class, 'queueRestart'])->name('admin.tenants.queue-restart');
    Route::post('/tenants/{id}/queue-config', [TenantManagementController::class, 'queueConfig'])->name('admin.tenants.queue-config');
    Route::post('/tenants/{id}/license', [TenantManagementController::class, 'updateLicense'])->name('admin.tenants.license.update');

    // License Plans Management
    Route::get('/license-plans', [LicensePlanController::class, 'index'])->name('admin.license-plans.index');
    Route::get('/license-plans/create', [LicensePlanController::class, 'create'])->name('admin.license-plans.create');
    Route::post('/license-plans', [LicensePlanController::class, 'store'])->name('admin.license-plans.store');
    Route::get('/license-plans/{id}/edit', [LicensePlanController::class, 'edit'])->name('admin.license-plans.edit');
    Route::put('/license-plans/{id}', [LicensePlanController::class, 'update'])->name('admin.license-plans.update');
    Route::delete('/license-plans/{id}', [LicensePlanController::class, 'destroy'])->name('admin.license-plans.destroy');

    // Admin User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{id}/toggle', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');

    // Payment Gateway Configuration
    Route::get('/tenants/{id}/payment-gateway', [TenantManagementController::class, 'paymentGatewayConfig'])->name('admin.tenants.payment-gateway');
    Route::post('/tenants/{id}/payment-gateway', [TenantManagementController::class, 'updatePaymentGatewayConfig'])->name('admin.tenants.payment-gateway.update');

    // Payment Points (Bumdes / Lokasi Bayar)
    Route::get('/tenants/{id}/payment-points', [TenantManagementController::class, 'paymentPoints'])->name('admin.tenants.payment-points');
    Route::post('/tenants/{id}/payment-points', [TenantManagementController::class, 'storePaymentPoint'])->name('admin.tenants.payment-points.store');
    Route::put('/tenants/{id}/payment-points/{pointId}', [TenantManagementController::class, 'updatePaymentPoint'])->name('admin.tenants.payment-points.update');
    Route::delete('/tenants/{id}/payment-points/{pointId}', [TenantManagementController::class, 'destroyPaymentPoint'])->name('admin.tenants.payment-points.destroy');

    // Log Viewer
    Route::get('/logs', [TenantManagementController::class, 'logIndex'])->name('admin.logs.index');
    Route::get('/logs/view', [TenantManagementController::class, 'logView'])->name('admin.logs.view');
    Route::get('/tenants/{id}/log', [TenantManagementController::class, 'tenantLog'])->name('admin.tenants.log');

    // Documentation
    Route::get('/documentation', function() {
        return view('admin.documentation.index');
    })->name('admin.documentation');
    
    Route::get('/documentation/{doc}', function($doc) {
        $docFiles = [
            'merchant' => 'MERCHANT_MANAGEMENT_GUIDE.md',
            'tenant' => 'TENANT_MANAGEMENT_UI_GUIDE.md',
            'payment-gateway' => 'PAYMENT_GATEWAY_ADMIN_PANEL.md',
            'env-variables' => 'ENV_VARIABLES_GUIDE.md',
            'admin-user' => 'ADMIN_USER_MANAGEMENT_GUIDE.md',
            'database' => 'DATABASE_TENANT_GUIDE.md',
            'quick-start' => 'QUICK_START_TENANT_UI.md',
            'automatic-backup' => 'AUTOMATIC_BACKUP_GUIDE.md',
            'readme' => 'README.md',
            'env-changelog' => 'CHANGELOG_ENV_VARIABLES.md',
            'css-guide' => 'CSS_CENTRALIZATION_GUIDE.md',
            'financial-changelog' => 'CHANGELOG_FINANCIAL_REPORTS.md',
            'report-analysis' => 'ANALYSIS_PERHITUNGAN_LAPORAN.md',
            'financial-styling' => 'FINANCIAL_REPORTS_STYLING_SUMMARY.md',
            'wa-provider' => 'WA_PROVIDER_GUIDE.md',
            'add-payment-gateway' => 'CARA_TAMBAH_PAYMENT_GATEWAY.md',
            'github-sync' => 'GITHUB_SYNC_GUIDE.md',
            'migration-guide' => 'ADMIN_MIGRATION_GUIDE.md',
        ];
        
        $titles = [
            'merchant'          => 'Merchant Management Guide',
            'tenant'            => 'Tenant Management Guide',
            'payment-gateway'   => 'Payment Gateway Guide',
            'env-variables'     => 'ENV Variables Guide',
            'admin-user'        => 'Admin User Management Guide',
            'database'          => 'Database Management Guide',
            'quick-start'       => 'Quick Start Guide',
            'automatic-backup'  => 'Automatic Backup Guide',
            'readme'            => 'README',
            'env-changelog'     => 'ENV Variables Changelog',
            'css-guide'         => 'CSS Centralization Guide',
            'financial-changelog' => 'Financial Reports Changelog',
            'report-analysis'   => 'Report Calculation Analysis',
            'financial-styling' => 'Financial Report Styling',
            'wa-provider'       => 'WhatsApp Provider Guide',
            'add-payment-gateway' => 'Cara Tambah Payment Gateway',
            'github-sync'       => 'GitHub Sync Guide',
            'migration-guide'   => 'Tenant Migration Guide',
        ];

        if (!isset($docFiles[$doc])) {
            abort(404);
        }
        
        $filePath = base_path($docFiles[$doc]);
        if (!file_exists($filePath)) {
            abort(404);
        }
        
        $content = file_get_contents($filePath);
        
        return view('admin.documentation.show', [
            'title' => $titles[$doc] ?? ucwords(str_replace('-', ' ', $doc)),
            'content' => $content
        ]);
    })->name('admin.documentation.show');
    
    // GitHub Sync Management
    Route::get('/github-sync', [GitHubSyncController::class, 'index'])->name('admin.github-sync');
    Route::post('/github-sync/pull', [GitHubSyncController::class, 'pull'])->name('admin.github-sync.pull');
    Route::post('/github-sync/push', [GitHubSyncController::class, 'push'])->name('admin.github-sync.push');
    Route::get('/github-sync/refresh', [GitHubSyncController::class, 'refresh'])->name('admin.github-sync.refresh');
    Route::get('/github-sync/changes', [GitHubSyncController::class, 'getChanges'])->name('admin.github-sync.changes');
    Route::post('/github-sync/token', [GitHubSyncController::class, 'saveToken'])->name('admin.github-sync.token');

    // Tenant Database Migration
    Route::get('/migrate', [AdminMigrateController::class, 'index'])->name('admin.migrate.index');
    Route::post('/migrate/run', [AdminMigrateController::class, 'run'])->name('admin.migrate.run');
});

//s
// Route::get('/', function () {
//     return view('/home');
// });
Route::get('/winpay/findwinpayva/{id}','SuminvoiceController@findWinpayVA');
Route::get('/winpay','SuminvoiceController@winpay');
Route::post('/create-winpay-va','SuminvoiceController@createWinpayVA');
Route::post('/create-duitku-va','SuminvoiceController@createDuitkuVA');  // ← LANGKAH 3: tambah route provider baru
Route::post('/duitku/reset','SuminvoiceController@resetDuitkuVA');
Route::post('/payment/reset','SuminvoiceController@resetPaymentPending');
Route::post('/bundle/cancel','SuminvoiceController@cancelBundle');
Route::post('/invoice/bundle-pay','SuminvoiceController@createBundlePayment');



Route::get('/', [App\Http\Controllers\HomeController::class, 'network'])->name('home.network');

// Route::get('/', 'HomeController@index')->name('home');
Route::middleware('dashboard.pref')->group(function () {
    Route::get('/home-v2', 'HomeController@index')->name('home.v2');
    Route::get('/home-v3', 'HomeController@index')->name('home.v3');
    Route::get('/home-v4', 'HomeController@index')->name('home.v4');
    Route::get('/home-v5', 'HomeController@index')->name('home.v5');
    Route::get('/home-admin', 'HomeController@index')->name('home.admin');
});
Route::get('/admin-status', 'AdminStatusController@index')->name('admin.status');
Route::get('/warestart', 'HomeController@warestart');


// Route::get('/schedule', 'HomeController@schedule');
Route::get('/schedule-refresh', 'HomeController@scheduleRefresh');

Route::get('/homex', 'HomeController@mikrotik_addsecreate');
Route::get('/homey', 'HomeController@mikrotik_disablesecreate');
Route::get('/homez', 'HomeController@mikrotik_status');
Route::get('/homexy', 'HomeController@wa');
Route::get('/xendit', 'HomeController@xendit');
Route::get('/halo', 'PagesController@halo');
Route::get('/customer/mapdata', 'CustomerController@mapData');
Route::patch('/customer/restore/{id}','CustomerController@restore');
Route::post('/customer/table_customer','CustomerController@table_customer');
Route::post('/customer/table_customermerchant','CustomerController@table_customermerchant');
Route::post('/customer/table_unpaid_customer','CustomerController@table_unpaid_customer');
Route::post('/customer/table_isolir_customer','CustomerController@table_isolir_customer');
Route::post('/customer/table_plan_group','CustomerController@table_plan_group');
Route::get('/customer/trash/data','CustomerController@trashData')->name('trash.data');
Route::get('/customer/trash','CustomerController@trash');
Route::get('/customer','CustomerController@index');
Route::get('/customer/log/{id}','CustomerController@log');
Route::get('/customer/unpaid','CustomerController@unpaid');
Route::get('/customer/isolir','CustomerController@isolir');
Route::get('/customer/create','CustomerController@create');
Route::get('/customer/search','CustomerController@search');
Route::post('/customer/filter','CustomerController@filter');
Route::post('/customer','CustomerController@store')->middleware('check.license');
Route::post('/customer/{id}/file','CustomerController@uploadFile');
Route::post('/customer/wa','CustomerController@wa_customer');
Route::patch('/customer/update/status','CustomerController@update_status');
Route::patch('/customer/update/status_2','CustomerController@update_status_2');
Route::get('/customer/{id}','CustomerController@show');
Route::get('/customer/{id}/edit','CustomerController@edit');
Route::patch('/customer/{id}','CustomerController@update');
Route::delete('/customer/{id}','CustomerController@destroy');
Route::post('/customer/searchforjurnal', 'CustomerController@searchforjurnal');
Route::get('/customermerchant', [CustomerController::class, 'customermerchant'])->name('customer.merchant');
Route::post('/customer/createtunnel', 'CustomerController@createtunnel');
Route::put('/customer/{id}/update-lead', 'CustomerController@updateLead');
Route::post('/customer/{id}/convert-to-active', 'CustomerController@convertToActive');
Route::post('/customer/{id}/mark-as-lost', 'CustomerController@markAsLost');
Route::post('/customer/{id}/reopen-lead', 'CustomerController@reopenLead');
Route::post('/customer/{id}/reset-password', 'CustomerController@resetPassword')->name('customer.reset-password');
Route::post('/customer/{id}/app-logout', 'CustomerController@appLogout')->name('customer.app-logout');

Route::get('/customer/{id}/router-status', 'CustomerController@ajaxRouterStatus');
// Update status tiket (klik step)
Route::post('/ticket/{ticket}/update-step', 'TicketController@updateStep')
->name('ticket.updateStep');

Route::post('/ticket/{ticket}/workflow/add', [TicketController::class, 'addStep'])->name('ticket.workflow.add');
Route::post('/ticket/{ticket}/workflow/reorder', [TicketController::class, 'reorder'])->name('ticket.workflow.reorder');
Route::post('/ticket/{id}/workflow/move', [App\Http\Controllers\TicketController::class, 'moveStep'])
->name('ticket.workflow.move');
Route::post('/ticket/{id}/workflow/start', [TicketController::class, 'startWorkflow']);

Route::post('/ticket/{ticket}/workflow/delete', [TicketController::class, 'workflowDelete']);


Route::get('/schedule', [TicketController::class, 'tvwall'])->name('ticket.tvwall');
Route::get('/ticket/tvwall/data', [TicketController::class, 'tvwallData'])->name('ticket.tvwall.data');



Route::get('/subscribe/{customerId}', 'CustomerController@subscribeform');
Route::get('/pendaftaran/pdf/{id}', 'CustomerController@cetakPDF');
Route::post('/pendaftaran', 'CustomerController@generatePDF');


Route::get('/ticket/datamap', 'TicketController@datamap');

Route::get('/vendorticket','VendorController@vendorticket');
Route::post('/ticket/table_vendorticket_list','VendorController@table_vendorticket_list');
Route::get('/vendorticket/{id}','VendorController@vendorshow');

Route::get('/ticket/report','TicketController@report');
Route::post('/ticket/reportsrc','TicketController@report');

// ── WebView SSO Bridge (Flutter App → web session) ──────────────────────────
// Flutter mengirim Sanctum token → backend login via session → redirect ke /ticket
Route::get('/webview/ticket-auth', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token');
    if (!$token) {
        return redirect('/login');
    }
    try {
        $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$pat || !$pat->tokenable) {
            return redirect('/login');
        }
        $user = $pat->tokenable;
        \Illuminate\Support\Facades\Auth::login($user, true);
        return redirect($request->query('redirect', '/ticket'));
    } catch (\Exception $e) {
        return redirect('/login');
    }
})->name('webview.ticket.auth');
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/ticket','TicketController@index');
Route::get('/ticket/groupticket','TicketController@groupticket');
Route::get('/ticket/vendorgroupticket','VendorController@groupticket');
Route::get('/myticket','TicketController@myticket');
Route::post('/ticket/{ticket}/update-step', [TicketController::class, 'updateStep'])->name('ticket.updateStep');

Route::get('/uncloseticket','TicketController@uncloseticket');
Route::post('/ticket/table_myticket_list','TicketController@table_myticket_list');

Route::post('/ticket/table_ticket_list','TicketController@table_ticket_list');
Route::post('/ticket/table_groupticket_list','TicketController@table_groupticket_list');
Route::post('/ticket/table_vendorgroupticket_list','VendorController@table_vendorgroupticket_list');

Route::get('/ticket/{id}/create','TicketController@create');
Route::get('/ticket/{id}/edit','TicketController@edit');

Route::get('/ticket/{id}','TicketController@show');

Route::get('/ticket/view/{id}','TicketController@view');
Route::get('/ticket/print/{id}','TicketController@print_ticket');


Route::get('/sale','SaleController@index');
Route::get('/sale/create','SaleController@create');
Route::post('/sale','SaleController@store');
Route::get('/sale/{id}/edit','SaleController@edit');
Route::patch('/sale/{id}','SaleController@update');
Route::patch('/sale/customer/{id}','SaleController@customer');
Route::get('/sale/{id}','SaleController@show');
Route::delete('/sale/{id}','SaleController@destroy');
Route::post('/sale/table_sale_customer','SaleController@table_sale_customer');



Route::post('/tag/store','TagController@store');
Route::get('/tag',              'TagController@index')->name('tag.index');
Route::post('/tag/{id}/update', 'TagController@update')->name('tag.update');
Route::delete('/tag/{id}',      'TagController@destroy')->name('tag.destroy');
Route::post('/tag/{id}/restore','TagController@restore')->name('tag.restore');
Route::delete('/tag/{id}/force','TagController@forceDestroy')->name('tag.force-destroy');
Route::post('/customer/{id}/tags', 'CustomerController@updateTags')->name('customer.tags.update');



Route::post('/ticket','TicketController@store');
Route::get('/ticket/{parentId}/create-child','TicketController@createChild');
Route::post('/ticket/{parentId}/store-child','TicketController@storeChild');
Route::post('/ticket/{ticketId}/convert-to-parent','TicketController@convertToParent');
Route::post('/ticket/{ticketId}/check-parent-close','TicketController@checkParentAutoClose');
Route::post('/ticket/search','TicketController@search');
Route::patch('/ticket/{id}/vendoreditticket','VendorController@vendoreditticket');
Route::patch('/ticket/{id}/editticket','TicketController@editticket');
Route::patch('/ticket/{id}/assign','TicketController@updateassign');
Route::post('/ticket/wa_ticket','TicketController@wa_ticket');
Route::post('/ticket/notify','TicketController@notifyTicket');


Route::post('/ticketdetail','TicketdetailController@store');
Route::post('/invoice/mounthlyfee','InvoiceController@createmounthlyinv');
Route::get('/invoice/bulk','InvoiceController@invoicehandle');
Route::get('/invoice/createinv','CustomerController@createinv');
// Route::get('/invoice/make','InvoiceController@index');

// Public route - Customer invoice view (no auth required)
Route::get('/invoice/cst/{id}','InvoiceController@custinv');

Route::get('/payment','PaymentController@search');
Route::post('/payment/show','PaymentController@show');
Route::post('/payment/mytransaction', 'PaymentController@mytransaction');
Route::get('/payment/mytransaction', 'PaymentController@mytransaction');
Route::get('payment/mytransaction/pdf', 'PaymentController@exportPdf')->name('payment.export.pdf');
Route::get('payment/mytransaction/excel', 'PaymentController@exportExcel')->name('payment.export.excel');
Route::get('/payment/{id}','PaymentController@countershow');
Route::get('/payment/{id}/print','PaymentController@print');
Route::get('/payment/{id}/dotmatrix','PaymentController@dotmatrix');


Route::post('suminvoice/remainderinv/{id}','SuminvoiceController@send_reminder_inv');

Route::post('/invoice/table_invoice_list','InvoiceController@table_invoice_list');
Route::post('/customer/table_invoice','CustomerController@table_invoice');
Route::get('/invoice','InvoiceController@index');
Route::get('/invoice/{id}','InvoiceController@show');
Route::get('/invoice/{id}/edit','InvoiceController@edit');
Route::get('/invoice/{id}/create','InvoiceController@create');
Route::post('/invoice','InvoiceController@store');
Route::post('/invoice/table_invoice','InvoiceController@table_invoice');

Route::get('/invoice/{id}/delete/{cid}','InvoiceController@destroy');
Route::post('/suminvoice/table_transaction_list','SuminvoiceController@table_transaction_list');
Route::get('/suminvoice/notification','SuminvoiceController@notification');
Route::get('/suminvoice/invoicenotif','SuminvoiceController@invoicenotif');
Route::post('/suminvoice/createinvoice','InvoiceController@createinvoice');
Route::get('/suminvoice','SuminvoiceController@index');
Route::get('/suminvoice/transaction','SuminvoiceController@transaction');
Route::post('/suminvoice/transaction','SuminvoiceController@searchtransaction');
Route::get('/suminvoice/mytransaction','SuminvoiceController@mytransaction');
Route::post('/suminvoice/mytransaction','SuminvoiceController@searchmytransaction');
Route::post('/suminvoice/verify/{id}','SuminvoiceController@verify');

Route::get('/suminvoice/testinv','SuminvoiceController@invtest');

Route::get('/suminvoice/{id}','SuminvoiceController@show');

Route::get('/testwa','SuminvoiceController@testwa');

Route::get('/suminvoice/{id}/print','SuminvoiceController@print');


Route::get('/suminvoice/{id}/viewinvoice','SuminvoiceController@print');
Route::get('/suminvoice/{id}/dotmatrix','SuminvoiceController@dotmatrix');
Route::post('/suminvoice','SuminvoiceController@store');
Route::post('/suminvoice/search','SuminvoiceController@search');
Route::post('/suminvoice/find','SuminvoiceController@searchinv');
Route::patch('/suminvoice/{id}','SuminvoiceController@update');
Route::patch('/suminvoice/{id}/faktur','SuminvoiceController@faktur');
Route::delete('/suminvoice/{id}','SuminvoiceController@cancelInvoice');
//Route::post('/suminvoice/xendit',function (){})->middleware(['xenditauth']);
Route::post('/xenditcallback/invoice','XenditCallbackController@update')->middleware(['xenditauth']);
Route::post('/tripay/callback','XenditCallbackController@update_tripay');
Route::post('/tripay/create','SuminvoiceController@tripay');

Route::post('/winpay/callback','XenditCallbackController@update_winpay');
Route::post('/duitku/callback','XenditCallbackController@update_duitku');


//Jobs
Route::post('/jobs/notifinv','SuminvoiceController@notifinvJob');
Route::get('/jobs/customerblockednotifjob','SuminvoiceController@customerblockednotifJob');
Route::post('/jobs/customerisolirjob','SuminvoiceController@customerisolirJob');
Route::post('/jobs/customerinvjob','SuminvoiceController@createinvmonthlyJob');
Route::get('/jobs/isolirdata','SuminvoiceController@isolirData');
Route::post('/jobs/getSelectedcustomermerchant','SuminvoiceController@getSelectedcustomermerchant');
Route::get('/jobs/getSelectedblocknotif','SuminvoiceController@getSelectedblocknotif');
Route::get('/jobs/getSelectedunpaidnotif','SuminvoiceController@getSelectedunpaidnotif');
Route::get('/jobs/queuecount','SuminvoiceController@queueCount');
Route::post('/jobs/canceljobs','SuminvoiceController@cancelJobs');

// MikroTik Sync Failures
Route::get('/mikrotik-sync','MikrotikSyncController@index')->name('mikrotik-sync.index');
Route::post('/mikrotik-sync/{id}/retry','MikrotikSyncController@retry')->name('mikrotik-sync.retry');
Route::post('/mikrotik-sync/{id}/resolve','MikrotikSyncController@resolve')->name('mikrotik-sync.resolve');
Route::post('/mikrotik-sync/resolve-all','MikrotikSyncController@resolveAll')->name('mikrotik-sync.resolve-all');

// ── Attendance & Shift (Admin Panel) ─────────────────────────────────────
// Dashboard
Route::get ('/attendance/dashboard',                      'AttendanceAdminController@dashboard')->name('attendance.dashboard');
// Lokasi Absen
Route::get ('/attendance/locations',                       'AttendanceAdminController@locations')->name('attendance.locations');
Route::get ('/attendance/locations/create',                'AttendanceAdminController@locationCreate');
Route::post('/attendance/locations',                       'AttendanceAdminController@locationStore');
Route::get ('/attendance/locations/{location}/edit',       'AttendanceAdminController@locationEdit');
Route::patch('/attendance/locations/{location}',           'AttendanceAdminController@locationUpdate');
Route::delete('/attendance/locations/{location}',         'AttendanceAdminController@locationDestroy');
// Shift
Route::get ('/attendance/shifts',                         'AttendanceAdminController@shifts')->name('attendance.shifts');
Route::get ('/attendance/shifts/create',                  'AttendanceAdminController@shiftCreate');
Route::post('/attendance/shifts',                         'AttendanceAdminController@shiftStore');
Route::get ('/attendance/shifts/{shift}/edit',            'AttendanceAdminController@shiftEdit');
Route::patch('/attendance/shifts/{shift}',                'AttendanceAdminController@shiftUpdate');
Route::delete('/attendance/shifts/{shift}',               'AttendanceAdminController@shiftDestroy');
// Jadwal
Route::get ('/attendance/schedule',                       'AttendanceAdminController@schedule')->name('attendance.schedule');
Route::post('/attendance/schedule',                       'AttendanceAdminController@scheduleStore');
// Rekap & Laporan
Route::get ('/attendance/report',                         'AttendanceAdminController@report')->name('attendance.report');
Route::get ('/attendance/daily',                          'AttendanceAdminController@daily')->name('attendance.daily');
// Karyawan (supervisor, employee_id)
Route::get ('/attendance/employees',                      'AttendanceAdminController@employees')->name('attendance.employees');
Route::patch('/attendance/employees/{user}',              'AttendanceAdminController@employeeUpdate');

// Pengajuan Izin/Cuti/Sakit & Lembur
Route::get ('/leave',                'LeaveOvertimeAdminController@leaveIndex')->name('leave.index');
Route::post('/leave/{id}/approve',   'LeaveOvertimeAdminController@leaveApprove')->name('leave.approve');
Route::get ('/overtime',             'LeaveOvertimeAdminController@overtimeIndex')->name('overtime.index');
Route::post('/overtime/{id}/approve','LeaveOvertimeAdminController@overtimeApprove')->name('overtime.approve');

// Pengajuan mandiri karyawan (semua privilege)
Route::get ('/my-pengajuan',          'MyLeaveController@index')->name('my.pengajuan');
Route::post('/my-pengajuan/leave',    'MyLeaveController@leaveStore')->name('my.leave.store');
Route::post('/my-pengajuan/overtime', 'MyLeaveController@overtimeStore')->name('my.overtime.store');

// Absensi & jadwal pribadi
Route::get('/my-attendance', 'MyAttendanceController@index')->name('my.attendance');

// My Team — tampilkan bawahan supervisor
Route::get('/my-team', 'MyTeamController@index')->name('my.team');

Route::get('/tool/burstcalc','ToolController@burstcalc');
Route::post('tool/macvendor', 'ToolController@maclookup');
Route::get('tool/macvendor', 'ToolController@macvendor');
Route::post('tool/ipcalc', 'ToolController@ipcalc');
Route::get('tool/ipcalc', 'ToolController@showipcalc');
// routes/web.php

//Supplier
Route::post('/contact/table_contact_list','ContactController@table_contact_list');
Route::get('/contact','ContactController@index');
Route::get('/contact/getcontactinfo/{id}','ContactController@getcontactinfo');
Route::get('/contact/create','ContactController@create');
Route::post('/contact','ContactController@store');
Route::get('/contact/{id}/edit','ContactController@edit');
Route::patch('/contact/{id}','ContactController@update');
Route::get('/contact/{id}','ContactController@show');
Route::delete('/contact/{id}','ContactController@destroy');
//Route::get('/gettotalakun/{id} ', 'ContactController@gettotalakun');
Route::post('/contact/searchforjurnal', 'ContactController@searchforjurnal');

//Merchant
Route::post('/merchant/table_merchant_list','MerchantController@table_merchant_list');
Route::get('/merchant','MerchantController@index');
Route::get('/merchant/getmerchantinfo/{id}','MerchantController@getmerchantinfo');
Route::get('/merchant/create','MerchantController@create');
Route::post('/merchant','MerchantController@store');
Route::get('/merchant/{id}/edit','MerchantController@edit');
Route::patch('/merchant/{id}','MerchantController@update');
Route::get('/merchant/{id}','MerchantController@show');
Route::delete('/merchant/{id}','MerchantController@destroy');
Route::get('/gettotalakun/{id} ', 'MerchantController@gettotalakun');

// Add-ons
Route::get('/addon','AddonController@index')->name('addon.index');
Route::get('/addon/create','AddonController@create')->name('addon.create');
Route::post('/addon','AddonController@store')->name('addon.store');
Route::get('/addon/{id}/edit','AddonController@edit')->name('addon.edit');
Route::patch('/addon/{id}','AddonController@update')->name('addon.update');
Route::delete('/addon/{id}','AddonController@destroy')->name('addon.destroy');
Route::post('/addon/{id}/restore','AddonController@restore')->name('addon.restore');

//User

Route::get('/user','UserController@index');
Route::get('/user/create','UserController@create');
Route::post('/user','UserController@store');
Route::get('/user/log','UserController@log');
Route::get('/user/log/read','UserController@logRead');
Route::get('/user/{id}/edit','UserController@edit');
Route::patch('/user/{id}','UserController@update');
Route::delete('/user/{id}','UserController@destroy');
Route::post('/user/{id}/toggle-active','UserController@toggleActive')->name('user.toggle-active');
Route::get('/user/{id}/myprofile','UserController@myprofile');
Route::post('/user/searchforjurnal', 'UserController@searchforjurnal');

Route::get('/probe', [ProbeController::class, 'index']);
Route::get('/probe/data', [ProbeController::class, 'data']);
Route::delete('/probe/delete', [ProbeController::class, 'delete'])->name('probe.delete');

Route::view('/probe/alerts', 'probe.alerts');
Route::view('/probe/chart', 'probe.chart');

//Ovetime

// Route::get('/overtime','OvertimeController@index');
// Route::get('/overtime/create','OvertimeController@create');
// Route::get('/overtime/{id}/edit','OvertimeController@edit');
// Route::get('/overtime/{id}','OvertimeController@null');
// Route::post('/overtime','OvertimeController@store');
// Route::delete('/overtime/{id}','OvertimeController@destroy');
// Route::patch('/overtime/{id}','OvertimeController@update');

//Plan

Route::get('/plan','PlanController@index');
Route::get('/plan/create','PlanController@create');
Route::get('/plan/{id}/edit','PlanController@edit');
Route::get('/plan/{id}','PlanController@null');
Route::post('/plan','PlanController@store');
Route::delete('/plan/{id}','PlanController@destroy');
Route::patch('/plan/{id}','PlanController@update');

Route::get('/distpoint/map', 'DistpointController@showMap');
Route::get('/distpoint/data', 'DistpointController@getODPData');

// Map Layers API
Route::get('/map/layers', 'DistpointController@getMapLayers');
Route::post('/map/layers', 'DistpointController@saveMapLayer');
Route::patch('/map/layers/{id}', 'DistpointController@updateMapLayer');
Route::delete('/map/layers/{id}', 'DistpointController@deleteMapLayer');

Route::post('/distpoint/table_distpoint_list','DistpointController@table_distpoint_list');
Route::get('/distpoint','DistpointController@index');
Route::get('/distpoint/create','DistpointController@create');
Route::post('/distpoint','DistpointController@store');
Route::get('/distpoint/{id}/edit','DistpointController@edit');
Route::patch('/distpoint/{id}','DistpointController@update');
Route::get('/distpoint/{id}','DistpointController@show');
Route::delete('/distpoint/{id}','DistpointController@destroy');

Route::post('/distpointgroup/table_distpointgroup_list','DistpointgroupController@table_distpointgroup_list');
Route::get('/distpointgroup','DistpointgroupController@index');
Route::get('/distpointgroup/create','DistpointgroupController@create');
Route::post('/distpointgroup','DistpointgroupController@store');
Route::get('/distpointgroup/{id}/edit','DistpointgroupController@edit');
Route::patch('/distpointgroup/{id}','DistpointgroupController@update');
Route::get('/distpointgroup/{id}','DistpointgroupController@show');
Route::delete('/distpointgroup/{id}','DistpointgroupController@destroy');


Route::get('/distrouter','DistrouterController@index');
Route::get('/pppoe-monitor','DistrouterController@pppoeMonitor')->name('pppoe.monitor');
Route::get('/pppoe-monitor/data','DistrouterController@pppoeMonitorData')->name('pppoe.monitor.data');
Route::get('/pppoe-map','DistrouterController@pppoeMap')->name('pppoe.map');
Route::get('/pppoe-map/data','DistrouterController@pppoeMapData')->name('pppoe.map.data');
Route::post('/distrouter/executeCommand','DistrouterController@executeCommand');

Route::get('/distrouter/logs/{id}', 'DistrouterController@getMikrotikLogs');
Route::get('/distrouter/getPppoeUsers/{id}/{status}', 'DistrouterController@getPppoeUsers');
Route::post('/distrouter/prepare-pppoe', 'DistrouterController@preparePppoeForRegistration');
Route::get('/distrouter/getrouterinfo/{id}','DistrouterController@getrouterinfo');
Route::get('/distrouter/getrouterinterfaces/{id}','DistrouterController@getrouterinterfaces');
Route::get('/distrouter/interface_monitor/{id}','DistrouterController@interfacemonitor');
Route::get('/distrouter/backupconfig/{id}','DistrouterController@backupsconfig');
Route::get('/distrouter/import-ppp-profiles/{id}','DistrouterController@importPppProfiles');


Route::get('/distrouter/create','DistrouterController@create');
Route::post('/distrouter','DistrouterController@store');
Route::get('/distrouter/{id}/edit','DistrouterController@edit');
Route::patch('/distrouter/{id}','DistrouterController@update');
Route::get('/distrouter/{id}','DistrouterController@show');
Route::delete('/distrouter/{id}','DistrouterController@destroy');

Route::get('bank','BankController@index');
Route::get('/bank/create','BankController@create');
Route::get('/bank/{id}/edit','BankController@edit');
Route::get('/bank/{id}','BankController@null');
Route::post('/bank','BankController@store');
Route::delete('/bank/{id}','BankController@destroy');
Route::patch('/bank/{id}','BankController@update');

Route::get('device','DeviceController@null');
Route::get('/device/{id}','DeviceController@index');
Route::post('/device','DeviceController@store');
Route::patch('/device/{id}','DeviceController@update');
Route::delete('/device/{cust}/{id}','DeviceController@destroy');

Route::get('accounting','AccountingController@index');
Route::post('/accounting','AccountingController@store');
Route::patch('/accounting/{id}','AccountingController@update');

Route::delete('/accounting/{cust}/{id}','AccountingController@destroy');


//Route::get('jurnal/kas', 'JurnalController@kas');
Route::post('jurnal/kasmasuktransaction', 'JurnalController@kasmasuktransaction');
Route::post('jurnal/kaskeluartransaction', 'JurnalController@kaskeluartransaction');
Route::post('jurnal/transferkastransaction', 'JurnalController@transferkastransaction');
Route::post('jurnal/generaltransaction', 'JurnalController@generaltransaction');
Route::get('jurnal/kasbank', 'JurnalController@kasbank');
Route::get('jurnal/general', 'JurnalController@general');

Route::get('jurnal/kasmasuk', 'JurnalController@kasmasuk');
Route::get('jurnal/kaskeluar', 'JurnalController@kaskeluar');
Route::get('jurnal/transferkas', 'JurnalController@transferkas');
Route::get('jurnal/laporanneraca', 'JurnalController@laporanNeraca');
Route::get('/jurnal/show/{code}', 'JurnalController@show');
Route::get('/jurnal/arus-kas', 'JurnalController@laporanArusKas');

Route::get('/jurnal/arus-kas/pdf', 'JurnalController@exportArusKasPdf');
Route::get('/jurnal/arus-kas/excel','JurnalController@exportArusKasExcel');


Route::get('/customer/{id}/jurnal', 'JurnalController@customerJurnal');
Route::get('/contact/{id}/jurnal', 'JurnalController@contactJurnal');
Route::get('/laporan/rasio-keuangan', 'RasioKeuanganController@index');



Route::get('/get-types/{group}', function ($group) {
	$types = \App\Akun::where('group', $group)
	->distinct()
	->pluck('type');
	return response()->json($types);
});
Route::get('/get-categories/{type}', function ($type) {
	$categories = \App\Akun::where('type', $type)
	->distinct()
	->pluck('category');
	return response()->json($categories);
});
Route::get('/akun/{id}/edit','AkunController@edit');

Route::delete('/akun/{id}','AkunController@destroy');
Route::get('akun/filter-parents/{category}', 'AkunController@filterParents');

Route::get('akun', 'AkunController@index');
Route::post('akun', 'AkunController@store');
Route::get('/akun/{parrent}/children', 'AkunController@getChildren');
Route::get('jurnal', 'JurnalController@jurnal');
Route::get('/jurnal/create','JurnalController@transaksi');

Route::post('jurnal/getjurnaldata', 'JurnalController@getjurnaldata');
Route::post('jurnal/getbukubesardata', 'JurnalController@getbukubesardata');
Route::post('jurnal', 'JurnalController@jurnal');
Route::get('jurnal/bukubesar', 'JurnalController@bukubesar');
Route::post('jurnal/bukubesar', 'JurnalController@bukubesar');
Route::get('jurnal/report', 'JurnalController@index');
// Route::get('jurnal/rugilaba', 'JurnalController@laporanRugiLaba');
// Route::post('jurnal/rugilaba', 'JurnalController@rugilaba');
Route::delete('/jurnal/{id}','JurnalController@destroy');
Route::get('jurnal/neraca', 'JurnalController@neraca');
Route::get('jurnal/neraca/pdf', 'JurnalController@neracaPdf');
Route::get('jurnal/neraca/excel', 'JurnalController@neracaExcel');
Route::get('jurnal/labarugi', 'JurnalController@labaRugi');
Route::get('jurnal/labarugi/pdf', 'JurnalController@labaRugiPdf');
Route::get('jurnal/labarugi/excel', 'JurnalController@labaRugiExcel');
Route::get('jurnal/neracasaldo', 'JurnalController@neracaSaldo');

// Laporan Perubahan Modal
Route::get('jurnal/perubahan-modal', 'JurnalController@perubahanModal');
Route::get('jurnal/perubahan-modal/pdf', 'JurnalController@perubahanModalPdf');
Route::get('jurnal/perubahan-modal/excel', 'JurnalController@perubahanModalExcel');

Route::get('jurnal/ccreate', 'JurnalController@ccreate');
Route::get('jurnal/generaldel/{id}', 'JurnalController@generaldel');
Route::post('/jurnal/store','JurnalController@store');
Route::post('/jurnal/cupdate','JurnalController@cupdate');
Route::post('/jurnal/trxstore','JurnalController@trxstore');
Route::get('/jurnal/trxcreate','JurnalController@trxcreate');
Route::post('/jurnal/trxupdate','JurnalController@trxupdate');
Route::get('/jurnal/closed','JurnalController@closed');
Route::post('/jurnal/closed','JurnalController@closestore');
Route::post('/jurnal/closeupdate','JurnalController@closeupdate');
Route::get('/jurnal/jpenutup','JurnalController@jpenutup');
Route::post('/jurnal/penutup','JurnalController@penutup');

Route::get('/jurnal/neracasaldo/export/excel', 'JurnalController@exportExcel');
Route::get('/jurnal/neracasaldo/export/pdf', 'JurnalController@exportPDF');

Route::get('/jurnal/neraca-formatted', 'JurnalController@neracaFormatted');

// (opsional) kalau mau export:
Route::get('/jurnal/neraca-formatted/export/pdf','JurnalController@neracaFormattedPDF');
Route::get('/jurnal/neraca-formatted/export/excel','JurnalController@neracaFormattedExcel');

Route::get('/jurnal/laba-rugi', 'JurnalController@labaRugiFormatted');
Route::get('/jurnal/laba-rugi/export/pdf', 'JurnalController@labaRugiFormattedPDF');
Route::get('/jurnal/laba-rugi/export/excel', 'JurnalController@labaRugiFormattedExcel');

Route::post('/jurnal/cstore','JurnalController@cstore');
Route::get('/jurnal/cstore','JurnalController@ccreate');
Route::post('/jurnal/create','JurnalController@create');

Route::post('/distrouter/client_monitor','DistrouterController@client_monitor');

Route::get('/olt/coba','OltController@coba');
Route::post('/olt/ont_status','OltController@ont_status');
Route::post('/olt/onu_detail','OltController@onu_detail');
Route::get('/olt','OltController@index');
Route::get('/olt/getemptyonuid','OltController@getemptyonuid');
Route::get('/olt/addonu/{customerid}/{oltis}','OltController@addonu');
Route::get('/olt/addonu/{oltis}','OltController@addonucustome');
Route::POST('/olt/getolt/onu','OltController@getOltOnu');
Route::post('/olt/onuregister','OltController@configure');
Route::post('/olt/onuregistercst','OltController@configurecst');

Route::delete('/olt/delete/{oltId}/{oltPonIndex}/{onuId}','OltController@onudelete');
Route::post('/olt/reboot/{oltId}/{oltPonIndex}/{onuId}','OltController@onureboot');
Route::post('/olt/reset/{oltId}/{oltPonIndex}/{onuId}','OltController@onureset');



Route::post('/olt/table_onu_unconfig','OltController@table_onu_unconfig');

Route::get('/olt/getFreeOnuId','OltController@getFreeOnuId');

Route::get('/olt/unconfig','OltController@unconfig');
Route::post('/olt/table_olt_list','OltController@table_olt_list');
Route::get('/olt/create','OltController@create');
Route::post('/olt','OltController@store');
Route::get('/olt/{id}/edit','OltController@edit');
Route::patch('/olt/{id}','OltController@update');
Route::delete('/olt/{id}','OltController@destroy');
Route::get('/olt/getoltinfo/{id}','OltController@getOltInfo');
Route::get('/olt/getoltpon/{id}','OltController@getOltPon');
Route::get('/olt/{id}','OltController@show');




Route::post('/sale/table_sales','SaleController@table_sales');
Route::get('/sale','SaleController@index');
















Route::get('/file/backup','FileController@backup');
Route::get('/file/download/{filename}', 'FileController@download')->name('file.download');
Route::delete('/file/{filename}', 'FileController@delete')->name('file.delete');
Route::post('/file','FileController@store');
Route::delete('/file/customer/{id}','FileController@destroy');

Route::post('/whatsapp/wa_ticket', [WhatsappController::class, 'wa_ticket']);

Route::prefix('wa')->group(function () {

    // ====== API Gateway ======
	Route::post('webhook', [WhatsappController::class, 'webhook']);
	Route::post('{session}/send', [WhatsappController::class, 'send']);
	Route::post('{session}/ack', [WhatsappController::class, 'ack']);

    // ====== Session Management ======
	Route::get('{session}/qr', [WhatsappController::class, 'showQr']);
	Route::post('{session}/logout', [WhatsappController::class, 'logout']);
	Route::post('{session}/restart', [WhatsappController::class, 'restart']);
	Route::post('{session}/force-logout', [WhatsappController::class, 'forceLogout']);
	Route::post('{session}/clean', [WhatsappController::class, 'cleanSession']);
	Route::delete('{session}/delete', [WhatsappController::class, 'delete'])->name('wa.delete');
	Route::get('{session}/session-status', [WhatsappController::class, 'sessionStatus']);

    // ====== Groups & Chats ======
	Route::get('{session}/groups', [WhatsappController::class, 'getGroups']);
	Route::get('{session}/chats', [WhatsappController::class, 'chats'])->name('wa.chats');
	Route::get('{session}/history', [WhatsappController::class, 'history'])->name('wa.history');

    // ====== QR Status ======
	Route::get('{session}/qr-status', [WhatsappController::class, 'qrStatus']);

    // ====== Dashboard ======
	Route::get('dashboard', fn() => view('wa.dashboard'))->name('wa.dashboard');

    // ====== Chat Selector & Chat Interface ======
	Route::get('chat', [WhatsappController::class, 'chatSelector'])->name('wa.chatSelector');
	Route::get('chat/{session}', [WhatsappController::class, 'chat'])->name('wa.chat');

    // ====== Logs ======
	Route::get('logs', [WhatsappController::class, 'logs']);
	Route::post('logs/table', [WhatsappController::class, 'logsTable']);

    // ====== Health & Session Start ======
	Route::post('start', function (\Illuminate\Http\Request $request) {
		$session = $request->input('session');
		$gateway = rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/') . '/api';
		$response = Http::post("{$gateway}/start", [ "session" => $session ]);
		return response()->json($response->json());
	});

	Route::get('status', function() {
		$gateway = rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/') . '/api';
		return response()->json(Http::get("{$gateway}/health")->json());
	});
	
	Route::get('{session}/status', function($session) {
		$gateway = rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/') . '/api';
		return response()->json(Http::get("{$gateway}/{$session}/qr")->json());
	});

	Route::get('{session}/stats', fn($session) =>
		response()->json(['count' => \App\Helpers\WaGatewayHelper::countSentMessagesBySession($session)])
	);
});

//Site

Route::get('/xx', function(){
	$config = array();
	$config['center'] = 'auto';
	$config['onboundschanged'] = 'if (!centreGot) {
		var mapCentre = map.getCenter();
		marker_0.setOptions({
			position: new google.maps.LatLng(mapCentre.lat(), mapCentre.lng())
			});
		}
		centreGot = true;';

		app('map')->initialize($config);

    // set up the marker ready for positioning
    // once we know the users location
		$marker = array();
		app('map')->add_marker($marker);

		$map = app('map')->create_map();
		echo "<html><head><script type=text/javascript>var centreGot = false;</script>".$map['js']."</head><body>".$map['html']."</body></html>";
	});

Route::get('/oltonuprofile/olt/{id}','OltonuprofileController@index');
Route::get('/oltonuprofile/create/{olt}','OltonuprofileController@create');
// Route::get('/oltonuprofile/{id}/show','OltonuprofileController@show');
// Route::get('/oltonuprofile/{id}/edit','OltonuprofileController@edit');
// Route::get('/oltonuprofile/{id}','OltonuprofileController@null');
Route::post('/oltonuprofile','OltonuprofileController@store');
Route::delete('/oltonuprofile/{id}/{olt}','OltonuprofileController@destroy');
// Route::patch('/oltonuprofile/{id}','OltonuprofileController@update');

Route::get('/oltonutype/olt/{id}','OltonutypeController@index');
Route::get('/oltonutype/create/{olt}','OltonutypeController@create');
Route::post('/oltonutype','OltonutypeController@store');
Route::delete('/oltonutype/{id}/{olt}','OltonutypeController@destroy');

Route::get('/maps','SiteController@maps');
Route::get('/site','SiteController@index');
Route::get('/site/create','SiteController@create');
Route::get('/site/{id}/show','SiteController@show');
Route::get('/site/{id}/edit','SiteController@edit');
Route::get('/site/{id}','SiteController@null');
Route::post('/site','SiteController@store');
Route::delete('/site/{id}','SiteController@destroy');
Route::patch('/site/{id}','SiteController@update');

// Ticket Categories Routes
Route::get('/ticketcategories','TicketcategorieController@index')->name('ticketcategories.index');
Route::get('/ticketcategories/create','TicketcategorieController@create')->name('ticketcategories.create');
Route::post('/ticketcategories','TicketcategorieController@store')->name('ticketcategories.store');
Route::get('/ticketcategories/{id}/edit','TicketcategorieController@edit')->name('ticketcategories.edit');
Route::put('/ticketcategories/{id}','TicketcategorieController@update')->name('ticketcategories.update');
Route::delete('/ticketcategories/{id}','TicketcategorieController@destroy')->name('ticketcategories.destroy');
Route::get('/ticketcategories/trashed/list','TicketcategorieController@trashed')->name('ticketcategories.trashed');
Route::post('/ticketcategories/{id}/restore','TicketcategorieController@restore')->name('ticketcategories.restore');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/jobschedule-ajax', [\App\Http\Controllers\HomeController::class, 'jobScheduleAjax'])->name('jobschedule.ajax');

// Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

