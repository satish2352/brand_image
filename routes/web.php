<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadm\LoginController;
use App\Http\Controllers\Superadm\DashboardController;

use App\Http\Controllers\Superadm\RoleController;
use App\Http\Controllers\Superadm\RadiusController;
use App\Http\Controllers\Superadm\MediaUtilisationReportController;
use App\Http\Controllers\Superadm\RevenueReportController;
use App\Http\Controllers\Superadm\RevenueGraphController;
use App\Http\Controllers\Superadm\VendorController;
use App\Http\Controllers\Superadm\IlluminationController;
use App\Http\Controllers\Superadm\Master\CategoryController;
use App\Http\Controllers\Superadm\AreaController;
use App\Http\Controllers\Superadm\MediaManagementController;
use App\Http\Controllers\Superadm\EmployeesController;
use App\Http\Controllers\Superadm\ChangePasswordController;
use App\Http\Controllers\Website\CartController;
use App\Http\Controllers\Website\CheckoutController;
// website
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\AuthController;
use App\Http\Controllers\Website\CampaignController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Superadm\WebsiteUserController;
use App\Http\Controllers\Superadm\ContactUsController;
use App\Http\Controllers\Superadm\UserPaymentController;
use App\Http\Controllers\Website\GoogleAuthController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Website\PaymentHistoryController;
use App\Http\Controllers\Superadm\CampaingController;
use App\Http\Controllers\Superadm\HordingBookController;
use App\Http\Controllers\Superadm\AdminNotificationController;
use App\Http\Controllers\Common\LocationController;

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');

    return "<h3> All caches cleared successfully!</h3>";
})->name('clear.cache');

Route::get('login', [LoginController::class, 'loginsuper'])->name('login');
Route::post('superlogin', [LoginController::class, 'validateSuperLogin'])->name('superlogin');



// Route::middleware('auth.both')->group(function () {
//     Route::get('/ajax/get-states', [LocationController::class, 'getStates'])->name('ajax.states');
//     Route::post('/ajax/get-districts', [LocationController::class, 'getDistricts'])->name('ajax.districts');
//     Route::post('/ajax/get-cities', [LocationController::class, 'getCities'])->name('ajax.cities');
//     Route::post('/ajax/get-areas', [LocationController::class, 'getAreas'])->name('ajax.areas');
// });
/* ============================================
   PUBLIC AJAX - NO MIDDLEWARE REQUIRED
=============================================== */
Route::get('/ajax/get-states', [LocationController::class, 'getStates'])->name('ajax.states');
Route::post('/ajax/get-districts', [LocationController::class, 'getDistricts'])->name('ajax.districts');
Route::post('/ajax/get-cities', [LocationController::class, 'getCities'])->name('ajax.cities');
Route::post('/ajax/get-areas', [LocationController::class, 'getAreas'])->name('ajax.areas');

Route::group(['middleware' => ['SuperAdmin']], function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/change-password', [ChangePasswordController::class, 'index'])->name('admin.change-password');
    Route::post('/admin/update-password', [ChangePasswordController::class, 'updatePassword'])->name('admin.update-password');
    Route::post('/admin/notifications/mark-read', [DashboardController::class, 'markNotificationsRead'])
        ->name('admin.notifications.markRead');

    /* AREA MANAGEMENT */
    Route::prefix('area')->group(function () {
        Route::get('list', [AreaController::class, 'index'])->name('area.list');
        Route::get('add', [AreaController::class, 'create'])->name('area.create');
        Route::post('add', [AreaController::class, 'store'])->name('area.store');
        Route::get('edit/{encodedId}', [AreaController::class, 'edit'])->name('area.edit');
        Route::post('update/{encodedId}', [AreaController::class, 'update'])->name('area.update');
        Route::post('delete', [AreaController::class, 'delete'])->name('area.delete');
        Route::post('update-status', [AreaController::class, 'updateStatus'])->name('area.updatestatus');
    });

    /* AREA AJAX DROPDOWNS */


    /* CATEGORY MANAGEMENT */
    Route::prefix('category')->group(function () {
        Route::get('list', [CategoryController::class, 'index'])->name('category.list');
        Route::get('add', [CategoryController::class, 'create'])->name('category.create');
        Route::post('add', [CategoryController::class, 'store'])->name('category.store');
        Route::get('edit/{encodedId}', [CategoryController::class, 'edit'])->name('category.edit');
        Route::post('update/{encodedId}', [CategoryController::class, 'update'])->name('category.update');
        Route::post('delete', [CategoryController::class, 'delete'])->name('category.delete');
        Route::post('update-status', [CategoryController::class, 'updateStatus'])->name('category.updatestatus');
    });
    Route::prefix('media')->group(function () {
        Route::get('list', [MediaManagementController::class, 'index'])->name('media.list');
        Route::get('add', [MediaManagementController::class, 'create'])->name('media.create');
        Route::post('add', [MediaManagementController::class, 'store'])->name('media.store');
        Route::get('edit/{encodedId}', [MediaManagementController::class, 'edit'])->name('media.edit');
        Route::post('update/{encodedId}', [MediaManagementController::class, 'update'])->name('media.update');
        Route::post('delete', [MediaManagementController::class, 'delete'])->name('media.delete');
        Route::post('status', [MediaManagementController::class, 'updateStatus'])->name('media.status');
        Route::get('view/{encodedId}', [MediaManagementController::class, 'view'])->name('media.view');
        Route::post('image/delete', [MediaManagementController::class, 'deleteImage'])->name('media.image.delete');
        Route::post('image/upload', [MediaManagementController::class, 'uploadImage'])->name('media.image.upload');
        Route::get('view-details/{encodedId}', [MediaManagementController::class, 'viewDetails'])->name('media.viewdetails');
    });
    /* MEDIA AJAX (LOCATION HELPERS) */
    Route::get('get-states', [MediaManagementController::class, 'getStates']);
    Route::get('get-districts/{stateId}', [MediaManagementController::class, 'getDistricts']);
    Route::get('get-cities/{districtId}', [MediaManagementController::class, 'getCities']);
    Route::get('get-areas/{cityId}', [MediaManagementController::class, 'getAreas']);
    Route::get('get-all-areas', [MediaManagementController::class, 'getAllAreas']);
    Route::get('get-area-parents/{areaId}', [MediaManagementController::class, 'getAreaParents']);
    // ===================
    Route::post('/media/update-status', [MediaManagementController::class, 'getAllAreas'])->name('media.updatestatus');
    Route::post('/media/update-status', [MediaManagementController::class, 'getAreaParents'])->name('media.updatestatus');
    Route::post('/media/update-status', [MediaManagementController::class, 'getDistricts'])->name('media.updatestatus');
    Route::post('/media/update-status', [MediaManagementController::class, 'getAreas'])->name('media.updatestatus');
    Route::prefix('website-user')->group(function () {
        Route::get('list', [WebsiteUserController::class, 'index'])->name('website-user.list');
        Route::post('delete', [WebsiteUserController::class, 'delete'])->name('website-user.delete');
        Route::post('toggle-status', [WebsiteUserController::class, 'toggleStatus'])->name('website-user.toggle-status');
    });
    Route::prefix('contact-us')->group(function () {
        Route::get('list', [ContactUsController::class, 'index'])->name('contact-us.list');
        Route::post('delete', [ContactUsController::class, 'delete'])->name('contact-us.delete');
    });
    Route::prefix('user-payment')->group(function () {
        Route::get('list', [UserPaymentController::class, 'index'])->name('user-payment.list');
        Route::get('details/{orderId}', [UserPaymentController::class, 'details'])->name('user-payment.details');
    });

    Route::get('admin-campaing/export-excel/{campaignId}', [CampaingController::class, 'exportExcel'])->name('admin.campaign.export.excel');


    Route::prefix('admin-campaing')->group(function () {
        Route::get('list', [CampaingController::class, 'index'])->name('admin-campaing.list');
        Route::post('delete', [CampaingController::class, 'delete'])->name('admin-campaing.delete');
        Route::get('/details/{campaignId}/{userId}', [CampaingController::class, 'details'])
            ->name('admin.campaign.details');

        Route::post('admin-campaing/book', [CampaingController::class, 'book'])
            ->name('admin.campaign.book');
    });
    // Radius Master
    Route::get('radius/list', [RadiusController::class, 'index'])->name('radius.list');
    Route::get('radius/create', [RadiusController::class, 'create'])->name('radius.create');
    Route::post('radius/save', [RadiusController::class, 'save'])->name('radius.save');
    Route::get('radius/edit/{encodedId}', [RadiusController::class, 'edit'])->name('radius.edit');
    Route::post('radius/update/{encodedId}', [RadiusController::class, 'update'])->name('radius.update');
    Route::post('radius/delete', [RadiusController::class, 'delete'])->name('radius.delete');
    Route::post('radius/update-status', [RadiusController::class, 'updateStatus'])->name('radius.updatestatus');
    Route::prefix('admin-booking')->group(function () {
        Route::get('/', [HordingBookController::class, 'index'])->name('admin-booking.index');
        Route::post('/search', [HordingBookController::class, 'search'])->name('admin-booking.search');
        Route::get('/admin-media-details/{mediaId}', [HordingBookController::class, 'getMediaDetailsAdmin'])->name('admin-booking.admin-media-details');
        Route::post('/admin-booking/book-media', [HordingBookController::class, 'bookMedia'])->name('admin.booking.store');
        Route::get('reports/media-utilisation', [MediaUtilisationReportController::class, 'index'])->name('reports.media.utilisation');
        Route::get('reports/media-utilisation/export/excel', [MediaUtilisationReportController::class, 'exportExcel'])->name('reports.media.utilisation.export.excel');
        Route::get('reports/media-utilisation/export/pdf', [MediaUtilisationReportController::class, 'exportPdf'])->name('reports.media.utilisation.export.pdf');
        Route::get('reports/media-utilisation/check-export', [MediaUtilisationReportController::class, 'checkExportData'])->name('reports.media.utilisation.check-export');
        Route::prefix('reports/revenue')->name('reports.revenue.')->group(function () {
            Route::get('/', [RevenueReportController::class, 'index'])->name('index');
            Route::get('/export-excel', [RevenueReportController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export-pdf', [RevenueReportController::class, 'exportPdf'])->name('export.pdf');
        });
        Route::get('reports/revenue/export/excel', [RevenueReportController::class, 'exportExcel'])->name('reports.revenue.export.excel');
        Route::get('reports/revenue/export/pdf', [RevenueReportController::class, 'exportPdf'])->name('reports.revenue.export.pdf');
        Route::get('reports/revenue/check-export', [RevenueReportController::class, 'checkExportData'])->name('reports.revenue.check-export');
        Route::get('reports/revenue/month-details', [RevenueReportController::class, 'monthDetails'])->name('reports.revenue.month.details');
        Route::get('reports/revenue/user-details', [RevenueReportController::class, 'userDetails'])->name('reports.revenue.user.details');
        Route::get('reports/revenue-graph', [RevenueGraphController::class, 'index'])->name('reports.revenue.graph');
        Route::post('/admin-booking/list-booking', [HordingBookController::class, 'bookingList'])->name('admin.booking.list-booking');
        Route::get('/admin-booking/list-booking', [HordingBookController::class, 'bookingList'])->name('admin.booking.list-booking');
        Route::get('booking-details/{orderId}', [HordingBookController::class, 'bookingDetailsList'])->name('admin-booking.booking-details');
    });
    Route::prefix('vendor')->group(function () {
        Route::get('list', [VendorController::class, 'index'])->name('vendor.list');
        Route::get('add', [VendorController::class, 'create'])->name('vendor.create');
        Route::post('add', [VendorController::class, 'store'])->name('vendor.store');
        Route::get('edit/{encodedId}', [VendorController::class, 'edit'])->name('vendor.edit');
        Route::post('update/{encodedId}', [VendorController::class, 'update'])->name('vendor.update');
        Route::post('delete', [VendorController::class, 'delete'])->name('vendor.delete');
        Route::post('update-status', [VendorController::class, 'updateStatus'])->name('vendor.updatestatus');
    });
    Route::get('vendor/export-excel', [VendorController::class, 'exportExcel'])->name('vendor.export.excel');
    Route::prefix('illumination')->group(function () {
        Route::get('list', [IlluminationController::class, 'index'])->name('illumination.list');
        Route::get('add', [IlluminationController::class, 'create'])->name('illumination.create');
        Route::post('add', [IlluminationController::class, 'store'])->name('illumination.store');
        Route::get('edit/{encodedId}', [IlluminationController::class, 'edit'])->name('illumination.edit');
        Route::post('update/{encodedId}', [IlluminationController::class, 'update'])->name('illumination.update');
        Route::post('delete', [IlluminationController::class, 'delete'])->name('illumination.delete');
        Route::post('update-status', [IlluminationController::class, 'updateStatus'])->name('illumination.updatestatus');
    });
    Route::get('media/next-code/{vendorId}', [MediaManagementController::class, 'getNextMediaCode'])->name('media.next.code');
    Route::get('admin/logout', [LoginController::class, 'logOut'])->name('admin.logout');


    Route::get('notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications');
    Route::get('notifications/read-all', [AdminNotificationController::class, 'markAllRead'])->name('admin.notifications.readAll');
    Route::get('notifications/data', [AdminNotificationController::class, 'getData'])
        ->name('admin.notifications.data');
    Route::get('notifications/read/{id}', [AdminNotificationController::class, 'read'])
        ->name('admin.notifications.read');

    Route::get('notifications/count', [AdminNotificationController::class, 'count'])
        ->name('admin.notifications.count');
});

// Website Rotes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/', [HomeController::class, 'index'])->name('website.home');
Route::get('/search', [HomeController::class, 'searchView'])->name('website.search.view');
Route::post('/search', [HomeController::class, 'search'])->name('website.search');
Route::view('/about', 'website.about')->name('website.about');
Route::get('/media-details/{mediaId}', [HomeController::class, 'getMediaDetails'])->name('website.media-details');
Route::post('/website/signup', [AuthController::class, 'signup'])->name('website.signup');
Route::post('/website/login', [AuthController::class, 'login'])->name('website.login');
Route::get('/website/logout', [AuthController::class, 'logout'])->name('website.logout');
Route::post('/website/verify-otp', [AuthController::class, 'verifyOtp'])->name('website.verify.otp');
Route::post('/website/resend-otp', [AuthController::class, 'resendOtp'])->name('website.resend.otp');

Route::middleware('auth:website')->prefix('user/dashboard')->group(function () {
    Route::get('/', function () {
        return view('website.dashboard.index');
    })->name('dashboard.home');
    Route::get('/profile', function () {
        return view('website.dashboard.profile');
    })->name('dashboard.profile');
});
Route::middleware('auth:website')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/add/{mediaId}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::get('/cart/remove/{mediaId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('cart/add-with-date', [CartController::class, 'addWithDate'])->name('cart.add.with.date')->middleware('auth:website');
    Route::post('/cart/update-dates', [CartController::class, 'updateDates'])->name('cart.update.dates')->middleware('auth:website');
    Route::get('/cart/booked-dates/{mediaId}', [CartController::class, 'getBookedDates'])->name('cart.booked.dates');
});
Route::middleware(['web'])->group(function () {
    Route::post('/campaign/store', [CampaignController::class, 'store'])->name('campaign.store');
    Route::get('/campaign-list', [CampaignController::class, 'getCampaignList'])->name('campaign.list');
    Route::get('/campaign-export-excel/{campaignId}', [CampaignController::class, 'exportExcel'])->name('campaign.export.excel');
    Route::get('/campaign-export-ppt/{campaignId}', [CampaignController::class, 'exportPpt'])->name('campaign.export.ppt');
    Route::post('/checkout/campaign/{campaignId}', [CheckoutController::class, 'placeCampaignOrder'])->name('checkout.campaign');
    Route::get('/campaign/details/{cart_item_id}', [CampaignController::class, 'viewDetails'])->name('campaign.details');
    Route::get('/payment-history', [PaymentHistoryController::class, 'paymentHistory'])->name('campaign.payment.history');
    Route::get('/campaign-invoice-payments', [PaymentHistoryController::class, 'invoicePayments'])->name('campaign.invoice.payments')->middleware('auth:website');
    Route::get('/campaign-invoice/{orderId}', [PaymentHistoryController::class, 'viewInvoice'])->name('campaign.invoice.view');
});
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
// Route::post('/checkout/create-order', [CheckoutController::class, 'createOrder'])
//     ->name('checkout.create');
Route::post('/checkout/create-order', [CheckoutController::class, 'placeOrder'])->name('checkout.create');
// ->middleware('auth:website');
Route::post('/checkout/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::post('/payment/success', [CheckoutController::class, 'success'])->name('payment.success');
// Route::get('/payment/thank-you', function () {
//     return view('website.payment-success');
// })->name('payment.thankyou');
Route::post('/payment/webhook/razorpay', [CheckoutController::class, 'razorpayWebhook']);
Route::middleware(['web'])->group(function () {
    Route::post('/campaign/store', [CampaignController::class, 'store'])
        ->name('campaign.store');
    // Route::post('/campaign-list', [CampaignController::class, 'getCampaignList'])
    //     ->name('campaign.list');
    Route::get('/campaign-list', [CampaignController::class, 'getCampaignList'])
        ->name('campaign.list');
    Route::get(
        '/campaign-export-excel/{campaignId}',
        [CampaignController::class, 'exportExcel']
    )->name('campaign.export.excel');
    Route::get(
        '/campaign-export-ppt/{campaignId}',
        [CampaignController::class, 'exportPpt']
    )->name('campaign.export.ppt');
    Route::post(
        '/checkout/campaign/{campaignId}',
        [CheckoutController::class, 'placeCampaignOrder']
    )->name('checkout.campaign');

    Route::get(
        '/campaign/details/{cart_item_id}',
        [CampaignController::class, 'viewDetails']
    )->name('campaign.details');
    Route::get('/payment-history', [PaymentHistoryController::class, 'paymentHistory'])
        ->name('campaign.payment.history');
    Route::get('/campaign-invoice-payments', [PaymentHistoryController::class, 'invoicePayments'])
        ->name('campaign.invoice.payments')
        ->middleware('auth:website');
    Route::get('/campaign-invoice/{orderId}', [PaymentHistoryController::class, 'viewInvoice'])
        ->name('campaign.invoice.view');

    Route::get('invoice/download/{id}', [PaymentHistoryController::class, 'downloadInvoice'])->name('invoice.download');
});
// Route::post('/payment/webhook/razorpay', [CheckoutController::class, 'razorpayWebhook']);

Route::get('/contact-us', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact-us', [ContactController::class, 'store'])->name('contact.store');
