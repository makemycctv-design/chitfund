<?php

use App\Http\Controllers\Admin\AuctionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ChittyController;
use App\Http\Controllers\Admin\ChittySchemeController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KycDocumentController;
use App\Http\Controllers\Admin\ReconciliationController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Portal\AuctionController as PortalAuctionController;
use App\Http\Controllers\Portal\BankAccountController;
use App\Http\Controllers\Portal\ChittyController as PortalChittyController;
use App\Http\Controllers\Portal\SupportController as PortalSupportController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\KycController as PortalKycController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

/*
 * Neutral landing route — sends staff to the admin dashboard and customers to
 * the portal so a single `dashboard` name works everywhere.
 */
Route::middleware(['auth'])->get('dashboard', function () {
    return redirect()->route(request()->user()->homeRoute());
})->name('dashboard');

/*
 * ---------------------------------------------------------------------------
 * Back-office (staff) area — auth + verified + staff type. Per-action
 * authorization is enforced by policies / permission checks in controllers.
 * ---------------------------------------------------------------------------
 */
Route::middleware(['auth', 'verified', 'user.type:staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        // Chitties
        Route::get('chitties', [ChittyController::class, 'index'])->name('chitties.index');
        Route::get('chitties/create', [ChittyController::class, 'create'])->name('chitties.create');
        Route::post('chitties', [ChittyController::class, 'store'])->name('chitties.store');
        Route::get('chitties/{chitty}', [ChittyController::class, 'show'])->name('chitties.show');
        Route::get('chitties/{chitty}/edit', [ChittyController::class, 'edit'])->name('chitties.edit');
        Route::put('chitties/{chitty}', [ChittyController::class, 'update'])->name('chitties.update');
        Route::post('chitties/{chitty}/transition', [ChittyController::class, 'transition'])->name('chitties.transition');
        Route::post('chitties/{chitty}/enroll', [ChittyController::class, 'enroll'])->name('chitties.enroll');
        Route::post('chitties/{chitty}/generate-schedule', [ChittyController::class, 'generateSchedule'])->name('chitties.generate-schedule');

        // Schemes
        Route::get('schemes', [ChittySchemeController::class, 'index'])->name('schemes.index');
        Route::post('schemes', [ChittySchemeController::class, 'store'])->name('schemes.store');
        Route::put('schemes/{scheme}', [ChittySchemeController::class, 'update'])->name('schemes.update');
        Route::delete('schemes/{scheme}', [ChittySchemeController::class, 'destroy'])->name('schemes.destroy');

        // Customers + KYC review
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('customers/{customer}/approve', [CustomerController::class, 'approveRegistration'])->name('customers.approve');
        Route::post('customers/{customer}/reject', [CustomerController::class, 'rejectRegistration'])->name('customers.reject');
        Route::post('customers/{customer}/verify-kyc', [CustomerController::class, 'verifyKyc'])->name('customers.verify-kyc');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::post('kyc-documents/{document}/approve', [KycDocumentController::class, 'approve'])->name('kyc.approve');
        Route::post('kyc-documents/{document}/reject', [KycDocumentController::class, 'reject'])->name('kyc.reject');
        Route::get('kyc-documents/{document}/download', [KycDocumentController::class, 'download'])->name('kyc.download');

        // Collections
        Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
        Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');

        // Reconciliation
        Route::get('reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
        Route::post('reconciliation/{transaction}/approve', [ReconciliationController::class, 'approve'])->name('reconciliation.approve');

        // Reports
        Route::get('reports/collections', [ReportsController::class, 'collections'])->name('reports.collections');
        Route::get('reports/collections/export', [ReportsController::class, 'exportCollections'])->name('reports.collections.export');
        Route::get('reports/overdue', [ReportsController::class, 'overdue'])->name('reports.overdue');
        Route::get('reports/overdue/export', [ReportsController::class, 'exportOverdue'])->name('reports.overdue.export');

        // Auctions
        Route::get('auctions', [AuctionController::class, 'index'])->name('auctions.index');
        Route::get('auctions/create', [AuctionController::class, 'create'])->name('auctions.create');
        Route::post('auctions', [AuctionController::class, 'store'])->name('auctions.store');
        Route::get('auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
        Route::get('auctions/{auction}/state', [AuctionController::class, 'state'])->name('auctions.state');
        Route::post('auctions/{auction}/start', [AuctionController::class, 'start'])->name('auctions.start');
        Route::post('auctions/{auction}/pause', [AuctionController::class, 'pause'])->name('auctions.pause');
        Route::post('auctions/{auction}/resume', [AuctionController::class, 'resume'])->name('auctions.resume');
        Route::post('auctions/{auction}/cancel', [AuctionController::class, 'cancel'])->name('auctions.cancel');
        Route::post('auctions/{auction}/finalize', [AuctionController::class, 'finalize'])->name('auctions.finalize');

        // Prize payouts (maker-checker)
        Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('payouts/{payout}/approve', [PayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('payouts/{payout}/pay', [PayoutController::class, 'markPaid'])->name('payouts.pay');
        Route::post('payouts/{payout}/reject', [PayoutController::class, 'reject'])->name('payouts.reject');

        // Staff management
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::post('staff/{staff}/suspend', [StaffController::class, 'suspend'])->name('staff.suspend');
        Route::post('staff/{staff}/reactivate', [StaffController::class, 'reactivate'])->name('staff.reactivate');

        // Notification templates + logs
        Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
        Route::post('notification-templates', [NotificationTemplateController::class, 'store'])->name('notification-templates.store');
        Route::put('notification-templates/{template}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');
        Route::get('notification-logs', [NotificationLogController::class, 'index'])->name('notification-logs.index');

        // Audit logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Support
        Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
        Route::post('support/{ticket}/status', [AdminSupportController::class, 'updateStatus'])->name('support.status');
    });

/*
 * ---------------------------------------------------------------------------
 * Customer portal — auth + customer type.
 * ---------------------------------------------------------------------------
 */
Route::middleware(['auth', 'user.type:customer'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('dashboard', PortalDashboardController::class)->name('dashboard');

        Route::get('profile', [PortalProfileController::class, 'show'])->name('profile');
        Route::patch('profile', [PortalProfileController::class, 'update'])->name('profile.update');

        Route::post('kyc-documents', [PortalKycController::class, 'store'])->name('kyc.store');
        Route::get('kyc-documents/{document}/download', [PortalKycController::class, 'download'])->name('kyc.download');
        Route::delete('kyc-documents/{document}', [PortalKycController::class, 'destroy'])->name('kyc.destroy');

        Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::post('bank-accounts/{bankAccount}/primary', [BankAccountController::class, 'primary'])->name('bank-accounts.primary');
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        Route::get('chitties', [PortalChittyController::class, 'index'])->name('chitties.index');
        Route::get('chitties/{chitty}', [PortalChittyController::class, 'show'])->name('chitties.show');

        Route::get('payments', [PortalPaymentController::class, 'history'])->name('payments.history');
        Route::post('payments/initiate', [PortalPaymentController::class, 'initiate'])->name('payments.initiate');
        Route::get('receipts/{receipt}', [PortalPaymentController::class, 'receipt'])->name('receipts.download');

        // Auctions (live bidding)
        Route::get('auctions', [PortalAuctionController::class, 'index'])->name('auctions.index');
        Route::get('auctions/{auction}', [PortalAuctionController::class, 'show'])->name('auctions.show');
        Route::get('auctions/{auction}/state', [PortalAuctionController::class, 'state'])->name('auctions.state');
        Route::post('auctions/{auction}/bid', [PortalAuctionController::class, 'bid'])
            ->middleware('throttle:30,1')
            ->name('auctions.bid');

        // Support
        Route::get('support', [PortalSupportController::class, 'index'])->name('support.index');
        Route::post('support', [PortalSupportController::class, 'store'])->name('support.store');
        Route::get('support/{ticket}', [PortalSupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/message', [PortalSupportController::class, 'message'])->name('support.message');
    });

/*
 * Shared authenticated routes (staff + customers): in-app notification center.
 */
Route::middleware(['auth'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::put('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
