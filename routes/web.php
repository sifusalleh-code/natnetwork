<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AffiliateSettingsController as AdminAffiliateSettingsController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\BillingSettingsController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SchedulingController as AdminSchedulingController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\QuotationController as AdminQuotationController;
use App\Http\Controllers\AffiliateAuthController;
use App\Http\Controllers\AffiliatePortalController;
use App\Http\Controllers\AffiliateProfileController;
use App\Http\Controllers\BillplzController;
use App\Http\Controllers\Client\BillingController as ClientBillingController;
use App\Http\Controllers\Client\PortalController as ClientPortalController;
use App\Http\Controllers\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Client\QuotationController as ClientQuotationController;
use App\Http\Controllers\Admin\AccountsController as AdminAccountsController;
use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Partner\PartnerAuthController;
use App\Http\Controllers\Partner\PartnerPortalController;
use App\Http\Controllers\Admin\ChangeRequestController as AdminChangeRequestController;
use App\Http\Controllers\Admin\EmailSettingsController as AdminEmailSettingsController;
use App\Http\Controllers\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Client\SlotController as ClientSlotController;
use App\Http\Controllers\Client\SupportController as ClientSupportController;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\MasterSpecificationController;
use App\Http\Controllers\ProjectBuilderController;
use App\Http\Controllers\PublicWebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicWebsiteController::class, 'home'])->name('home');
Route::post('/_nn/ping', [\App\Http\Controllers\WebAnalyticsController::class, 'ping'])->middleware('throttle:60,1,nn-ping')->name('analytics.ping');
Route::post('/_nn/event', [\App\Http\Controllers\WebAnalyticsController::class, 'event'])->middleware('throttle:60,1,nn-event')->name('analytics.event');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/seo/image/{file}', [\App\Http\Controllers\SeoController::class, 'image'])->where('file', '[a-f0-9]{40}\.jpg')->name('seo.image');
Route::get('/services', [PublicWebsiteController::class, 'services'])->name('services');
Route::permanentRedirect('/pricing', '/services');
Route::get('/peluang', [PublicWebsiteController::class, 'opportunity'])->name('opportunity');
Route::get('/contact', [PublicWebsiteController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicWebsiteController::class, 'sendContact'])->middleware('throttle:5,1,contact')->name('contact.send');
Route::view('/demo', 'public.gallery-examples')->name('gallery.examples');
Route::view('/register', 'client.register')->name('register');

Route::middleware('auth:client')->prefix('start-project/specification')->name('specification.')->group(function (): void {
    Route::get('/', [MasterSpecificationController::class, 'show'])->name('show');
    Route::post('/generate', [MasterSpecificationController::class, 'generate'])->name('generate');
    Route::post('/{specification}/approve', [MasterSpecificationController::class, 'approve'])->name('approve');
});

Route::prefix('start-project')->name('builder.')->group(function (): void {
    Route::get('/', [ProjectBuilderController::class, 'show'])->name('start');
    Route::post('/entry', [ProjectBuilderController::class, 'chooseEntry'])->name('entry');
    Route::post('/answers', [ProjectBuilderController::class, 'save'])->name('save');
    Route::post('/files', [ProjectBuilderController::class, 'uploadFile'])->middleware('throttle:20,1,builder-files')->name('files.store');
    Route::get('/files/{file}', [ProjectBuilderController::class, 'showFile'])->whereNumber('file')->name('files.show');
    Route::delete('/files/{file}', [ProjectBuilderController::class, 'deleteFile'])->whereNumber('file')->name('files.destroy');
    Route::post('/identity', [ProjectBuilderController::class, 'requestVerification'])->middleware('throttle:5,1,builder-identity')->name('identity');
    Route::post('/verify', [ProjectBuilderController::class, 'verify'])->name('verify');
    Route::post('/reset', [ProjectBuilderController::class, 'reset'])->middleware('client.portal')->name('reset');
    Route::post('/new', [ProjectBuilderController::class, 'newProject'])->middleware('client.portal')->name('new');
});

Route::prefix('client')->name('client.')->group(function (): void {
    Route::middleware('guest:client')->group(function (): void {
        Route::get('/login', [ClientAuthController::class, 'create'])->name('login');
        Route::post('/login/code', [ClientAuthController::class, 'sendCode'])->name('otp.send');
        Route::post('/login/verify', [ClientAuthController::class, 'verify'])->name('otp.verify');
    });
    Route::post('/logout', [ClientAuthController::class, 'destroy'])->middleware('auth:client')->name('logout');

    Route::middleware('client.portal')->group(function (): void {
        Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/projects', [ClientProjectController::class, 'index'])->name('projects');
        Route::get('/projects/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
        Route::post('/content/{item}/info', [ClientProjectController::class, 'submitInfo'])->name('content.info');
        Route::post('/content/{item}/file', [ClientProjectController::class, 'submitFile'])->middleware('throttle:20,1,client-upload')->name('content.file');
        Route::post('/content/{item}/help', [ClientProjectController::class, 'requestHelp'])->name('content.help');
        Route::post('/projects/{project}/changes', [ClientProjectController::class, 'requestChange'])->middleware('throttle:10,1,client-change')->name('projects.changes.store');
        Route::post('/changes/{changeRequest}/decision', [ClientProjectController::class, 'decideChange'])->name('changes.decide');
        Route::get('/quotations', [ClientQuotationController::class, 'index'])->name('quotations');
        Route::get('/quotations/{quotation}', [ClientQuotationController::class, 'show'])->name('quotations.show');
        Route::post('/quotations/{quotation}/accept', [ClientQuotationController::class, 'accept'])->name('quotations.accept');
        Route::get('/quotations/{quotation}/slot', [ClientSlotController::class, 'show'])->name('quotations.slot');
        Route::post('/quotations/{quotation}/slot', [ClientSlotController::class, 'hold'])->middleware('throttle:10,1,client-slot')->name('quotations.slot.hold');
        Route::get('/billing', [ClientBillingController::class, 'index'])->name('billing');
        Route::get('/billing/invoices/{invoice}', [ClientBillingController::class, 'invoice'])->name('billing.invoice');
        Route::post('/billing/invoices/{invoice}/pay', [ClientBillingController::class, 'pay'])->middleware('throttle:10,1,client-pay')->name('billing.pay');
        Route::get('/billing/receipts/{receipt}', [ClientBillingController::class, 'receipt'])->name('billing.receipt');
        Route::get('/files', [ClientProjectController::class, 'files'])->name('files');
        Route::get('/files/{file}', [ClientProjectController::class, 'download'])->name('files.download');
        Route::get('/support', [ClientSupportController::class, 'index'])->name('support');
        Route::post('/support', [ClientSupportController::class, 'store'])->middleware('throttle:10,1,client-support')->name('support.store');
        Route::get('/support/{ticket}', [ClientSupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [ClientSupportController::class, 'reply'])->middleware('throttle:20,1,client-support-reply')->name('support.reply');
        Route::get('/notifications', [ClientNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [ClientNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}', [ClientNotificationController::class, 'open'])->name('notifications.open');
        Route::get('/profile', [ClientProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
    });
});

Route::prefix('affiliate')->name('affiliate.')->group(function (): void {
    Route::get('/register', [AffiliateAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AffiliateAuthController::class, 'register'])->middleware('throttle:5,1,affiliate-register')->name('register.send');
    Route::post('/register/verify', [AffiliateAuthController::class, 'verifyRegistration'])->name('register.verify');
    Route::get('/login', [AffiliateAuthController::class, 'showLogin'])->name('login');
    Route::post('/login/code', [AffiliateAuthController::class, 'sendCode'])->middleware('throttle:5,1,affiliate-login')->name('otp.send');
    Route::post('/login/verify', [AffiliateAuthController::class, 'verify'])->name('otp.verify');

    Route::middleware('affiliate.access')->group(function (): void {
        Route::post('/logout', [AffiliateAuthController::class, 'destroy'])->name('logout');
        Route::get('/profile', [AffiliateProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [AffiliateProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/avatar', [AffiliateProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        Route::get('/profile/avatar', [AffiliateProfileController::class, 'avatar'])->name('profile.avatar');
    });

    Route::middleware('affiliate.access:profile')->group(function (): void {
        Route::get('/dashboard', [AffiliatePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/pelanggan', [AffiliatePortalController::class, 'customers'])->name('customers');
        Route::get('/studio-poster', [AffiliatePortalController::class, 'studioPoster'])->name('studio-poster');
        Route::get('/wallet', [AffiliatePortalController::class, 'wallet'])->name('wallet');
        Route::post('/wallet/withdrawals', [AffiliatePortalController::class, 'requestWithdrawal'])->middleware('throttle:10,1,affiliate-withdraw')->name('withdrawals.store');
        Route::get('/studio-poster/{poster}/image', [AffiliatePortalController::class, 'posterImage'])->whereNumber('poster')->name('posters.image');
        Route::post('/studio-poster/{poster}/share', [AffiliatePortalController::class, 'share'])->whereNumber('poster')->middleware('throttle:40,1,affiliate-share')->name('posters.share');
    });
});

Route::prefix('partner')->name('partner.')->group(function (): void {
    Route::get('/register', [PartnerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [PartnerAuthController::class, 'register'])->middleware('throttle:5,1,partner-register')->name('register.send');
    Route::post('/register/verify', [PartnerAuthController::class, 'verifyRegistration'])->name('register.verify');
    Route::post('/register/cancel', [PartnerAuthController::class, 'cancelRegistration'])->name('register.cancel');
    Route::get('/login', [PartnerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login/code', [PartnerAuthController::class, 'sendCode'])->middleware('throttle:5,1,partner-login')->name('otp.send');
    Route::post('/login/verify', [PartnerAuthController::class, 'verify'])->name('otp.verify');

    // Langkah 3–4: bayaran modal & lengkapkan profil
    Route::middleware('partner.access')->group(function (): void {
        Route::post('/logout', [PartnerAuthController::class, 'destroy'])->name('logout');
        Route::get('/onboarding', [PartnerPortalController::class, 'onboarding'])->name('onboarding');
        Route::post('/onboarding/profile', [PartnerPortalController::class, 'completeProfile'])->name('profile.complete');
        Route::post('/capital', [PartnerPortalController::class, 'storeCapital'])->middleware('throttle:10,1,partner-capital')->name('capital.store');
        Route::post('/capital/{capital}/cancel', [PartnerPortalController::class, 'cancelCapital'])->name('capital.cancel');
        Route::get('/invoices/{invoice}', [PartnerPortalController::class, 'invoice'])->name('invoice');
        Route::post('/invoices/{invoice}/pay', [PartnerPortalController::class, 'pay'])->middleware('throttle:10,1,partner-pay')->name('pay');
    });

    // Langkah 5: portal penuh
    Route::middleware('partner.access:onboarded')->group(function (): void {
        Route::get('/dashboard', [PartnerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/capital', [PartnerPortalController::class, 'capital'])->name('capital');
        Route::get('/returns', [PartnerPortalController::class, 'returns'])->name('returns');
        Route::get('/profile', [PartnerPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [PartnerPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/notifications', [PartnerPortalController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/read-all', [PartnerPortalController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{notification}', [PartnerPortalController::class, 'openNotification'])->name('notifications.open');
    });
});

Route::prefix('billing/billplz')->name('billing.billplz.')->group(function (): void {
    Route::post('/callback', [BillplzController::class, 'callback'])->middleware('throttle:120,1')->name('callback');
    Route::get('/return', [BillplzController::class, 'return'])->name('return');
    Route::get('/status', [BillplzController::class, 'status'])->middleware('throttle:60,1')->name('status');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1,admin-login')->name('login.attempt');
    Route::get('/two-factor/setup', [AdminAuthController::class, 'showSetup'])->name('two-factor.setup');
    Route::post('/two-factor/setup', [AdminAuthController::class, 'confirmSetup'])->middleware('throttle:5,1,admin-2fa')->name('two-factor.setup.confirm');
    Route::get('/two-factor', [AdminAuthController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor', [AdminAuthController::class, 'challenge'])->middleware('throttle:5,1,admin-2fa')->name('two-factor.verify');

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/web-stats', [\App\Http\Controllers\Admin\WebStatsController::class, 'index'])->name('web-stats');
        Route::get('/web-stats/live', [\App\Http\Controllers\Admin\WebStatsController::class, 'live'])->name('web-stats.live');
        Route::get('/web-stats/export', [\App\Http\Controllers\Admin\WebStatsController::class, 'export'])->name('web-stats.export');

        Route::prefix('sales')->name('sales.')->group(function (): void {
            Route::get('/quotations', [AdminQuotationController::class, 'index'])->name('quotations');
            Route::get('/quotations/{quotation}', [AdminQuotationController::class, 'show'])->name('quotation');
            Route::post('/quotations/{quotation}/send', [AdminQuotationController::class, 'send'])->name('quotation.send');
            Route::post('/quotations/{quotation}/confirm-payment', [AdminQuotationController::class, 'confirmPayment'])->name('quotation.confirm-payment');
        });

        Route::get('/orders', [AdminProjectController::class, 'orders'])->name('orders');
        Route::prefix('projects')->name('projects.')->group(function (): void {
            Route::get('/', [AdminProjectController::class, 'index'])->name('index');
            Route::get('/queue', [AdminSchedulingController::class, 'queue'])->name('queue');
            Route::put('/queue/settings', [AdminSchedulingController::class, 'updateSettings'])->name('queue.settings');
            Route::get('/files/{file}', [AdminProjectController::class, 'download'])->name('files.download');
            Route::post('/milestones/{milestone}/complete', [AdminProjectController::class, 'completeMilestone'])->name('milestones.complete');
            Route::post('/content/{item}/review', [AdminProjectController::class, 'reviewContent'])->name('content.review');
            Route::get('/{project}', [AdminProjectController::class, 'show'])->name('show');
            Route::post('/{project}/start', [AdminProjectController::class, 'start'])->name('start');
            Route::post('/{project}/transition', [AdminProjectController::class, 'transition'])->name('transition');
            Route::post('/{project}/complete', [AdminProjectController::class, 'complete'])->name('complete');
            Route::post('/{project}/content', [AdminProjectController::class, 'requestContent'])->name('content.request');
            Route::post('/{project}/files', [AdminProjectController::class, 'upload'])->name('files.upload');
            Route::put('/{project}/milestones', [AdminProjectController::class, 'updateMilestones'])->name('milestones.update');
            Route::post('/{project}/reschedule', [AdminProjectController::class, 'reschedule'])->name('reschedule');
            Route::post('/{project}/refund', [AdminProjectController::class, 'refund'])->name('refund');
        });
        Route::get('/clients', [AdminAccountsController::class, 'clients'])->name('clients.index');
        Route::post('/clients/bulk', [AdminAccountsController::class, 'bulkClients'])->name('clients.bulk');
        Route::get('/affiliates', [AdminAccountsController::class, 'affiliates'])->name('affiliates.index');
        Route::post('/affiliates/bulk', [AdminAccountsController::class, 'bulkAffiliates'])->name('affiliates.bulk');
        Route::prefix('partners')->name('partners.')->group(function (): void {
            Route::get('/', [AdminPartnerController::class, 'index'])->name('index');
            Route::get('/pool', [AdminPartnerController::class, 'pool'])->name('pool');
            Route::post('/bulk', [AdminPartnerController::class, 'bulk'])->name('bulk');
            Route::put('/settings', [AdminPartnerController::class, 'updateSettings'])->name('settings');
            Route::get('/{partner}', [AdminPartnerController::class, 'show'])->name('show');
            Route::post('/{partner}/review', [AdminPartnerController::class, 'review'])->name('review');
            Route::post('/{partner}/payout', [AdminPartnerController::class, 'payout'])->name('payout');
        });
        Route::post('/impersonate/{type}/{id}', [AdminImpersonationController::class, 'start'])->whereNumber('id')->name('impersonate.start');
        Route::post('/impersonate/stop', [AdminImpersonationController::class, 'stop'])->name('impersonate.stop');
        Route::get('/impersonate/stop', [AdminImpersonationController::class, 'stop'])->name('impersonate.stop.get');
        Route::get('/change-requests', [AdminChangeRequestController::class, 'index'])->name('change-requests.index');
        Route::post('/change-requests/{changeRequest}/assess', [AdminChangeRequestController::class, 'assess'])->name('change-requests.assess');
        Route::get('/builder-files/{file}', [\App\Http\Controllers\Admin\BuilderFileController::class, 'show'])->whereNumber('file')->name('builder-files.show');
        Route::get('/settings/seo', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'show'])->name('seo');
        Route::put('/settings/seo', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'updateSettings'])->name('seo.settings');
        Route::put('/settings/seo/pages/{page}', [\App\Http\Controllers\Admin\SeoSettingsController::class, 'updatePage'])->where('page', '[a-z.]+')->name('seo.page');
        Route::get('/settings/email', [AdminEmailSettingsController::class, 'show'])->name('communication.email');
        Route::put('/settings/email', [AdminEmailSettingsController::class, 'update'])->name('communication.email.update');
        Route::post('/settings/email/test', [AdminEmailSettingsController::class, 'test'])->middleware('throttle:5,1,admin-email-test')->name('communication.email.test');
        Route::prefix('support')->name('support.')->group(function (): void {
            Route::get('/', [AdminSupportController::class, 'index'])->name('index');
            Route::get('/{ticket}', [AdminSupportController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('reply');
        });

        Route::prefix('affiliate')->name('affiliate.')->group(function (): void {
            Route::get('/settings', [AdminAffiliateSettingsController::class, 'show'])->name('settings');
            Route::put('/settings/rates', [AdminAffiliateSettingsController::class, 'updateRates'])->name('settings.rates');
            Route::put('/settings/cookie', [AdminAffiliateSettingsController::class, 'updateCookie'])->name('settings.cookie');
            Route::put('/settings/task', [AdminAffiliateSettingsController::class, 'updateTask'])->name('settings.task');
            Route::get('/posters', [\App\Http\Controllers\Admin\AffiliatePosterController::class, 'index'])->name('posters');
            Route::post('/posters', [\App\Http\Controllers\Admin\AffiliatePosterController::class, 'store'])->name('posters.store');
            Route::put('/posters/{poster}', [\App\Http\Controllers\Admin\AffiliatePosterController::class, 'update'])->whereNumber('poster')->name('posters.update');
            Route::get('/posters/{poster}/image', [\App\Http\Controllers\Admin\AffiliatePosterController::class, 'image'])->whereNumber('poster')->name('posters.image');
            Route::get('/withdrawals', [\App\Http\Controllers\Admin\AffiliateWithdrawalController::class, 'index'])->name('withdrawals');
            Route::post('/withdrawals/bulk-reject', [\App\Http\Controllers\Admin\AffiliateWithdrawalController::class, 'bulkReject'])->name('withdrawals.bulk-reject');
            Route::post('/withdrawals/{withdrawal}/paid', [\App\Http\Controllers\Admin\AffiliateWithdrawalController::class, 'paid'])->whereNumber('withdrawal')->name('withdrawals.paid');
            Route::post('/withdrawals/{withdrawal}/reject', [\App\Http\Controllers\Admin\AffiliateWithdrawalController::class, 'reject'])->whereNumber('withdrawal')->name('withdrawals.reject');
        });

        Route::prefix('billing')->name('billing.')->group(function (): void {
            Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
            Route::get('/invoices/{invoice}', [BillingController::class, 'invoice'])->name('invoice');
            Route::post('/invoices/{invoice}/pay', [BillingController::class, 'payInvoice'])->name('invoice.pay');
            Route::get('/payments', [BillingController::class, 'payments'])->name('payments');
            Route::get('/test', [BillingController::class, 'testFlow'])->name('test');
            Route::post('/test', [BillingController::class, 'startTestFlow'])->middleware('throttle:10,1,billing-test')->name('test.start');
            Route::post('/sandbox/purge', [BillingController::class, 'purgeSandbox'])->name('sandbox.purge');
            Route::get('/settings', [BillingSettingsController::class, 'show'])->name('settings');
            Route::put('/settings/credentials/{mode}', [BillingSettingsController::class, 'updateCredentials'])->name('settings.credentials');
            Route::put('/settings/mode', [BillingSettingsController::class, 'switchMode'])->name('settings.mode');
            Route::put('/settings/reward', [BillingSettingsController::class, 'updateReward'])->name('settings.reward');
        });
    });
});
