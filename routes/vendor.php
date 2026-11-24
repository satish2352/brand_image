<?php

/*
|--------------------------------------------------------------------------
| Vendor Inventory Management — routes
|--------------------------------------------------------------------------
|
| Loaded from the end of routes/web.php.
|
| /vendor-panel/*  the Vendor Admin web portal (guard: vendor)
| /admin-vendor/*  the Super Admin side of the module (SuperAdmin middleware)
| /{vendorSlug}    each vendor's public showcase website — registered LAST,
|                  so every existing one-segment route (/login, /search, ...)
|                  still wins.
|
| The admin Vendor master already owns /vendor/*, hence /vendor-panel.
*/

use App\Http\Controllers\Superadm\VendorPortalController;
use App\Http\Controllers\Vendor;
use App\Http\Controllers\Website\VendorSiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor-panel')->name('vendor.')->group(function () {
    Route::get('login', [Vendor\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Vendor\AuthController::class, 'login'])->name('login.submit')->middleware('throttle:10,1');

    Route::middleware('vendor.auth')->group(function () {
        Route::post('logout', [Vendor\AuthController::class, 'logout'])->name('logout');
        Route::get('/', fn () => redirect()->route('vendor.dashboard'));
        Route::get('dashboard', [Vendor\DashboardController::class, 'index'])->name('dashboard');

        // Inventory list & search
        Route::get('inventory', [Vendor\InventoryController::class, 'index'])->name('inventory');
        Route::get('inventory/export', [Vendor\InventoryController::class, 'export'])->name('inventory.export');

        // Media add / view / edit / delete (shared admin form)
        Route::get('media/create', [Vendor\MediaController::class, 'create'])->name('media.create');
        Route::post('media', [Vendor\MediaController::class, 'store'])->name('media.store');
        Route::get('media/next-code', [Vendor\MediaController::class, 'nextCode'])->name('media.next-code');
        Route::get('media/{id}', [Vendor\MediaController::class, 'show'])->whereNumber('id')->name('media.show');
        Route::get('media/{id}/edit', [Vendor\MediaController::class, 'edit'])->whereNumber('id')->name('media.edit');
        Route::post('media/{id}', [Vendor\MediaController::class, 'update'])->whereNumber('id')->name('media.update');
        Route::post('media/{id}/delete', [Vendor\MediaController::class, 'destroy'])->whereNumber('id')->name('media.destroy');
        Route::post('media/{id}/toggle', [Vendor\MediaController::class, 'toggle'])->whereNumber('id')->name('media.toggle');
        Route::post('media/{id}/images', [Vendor\MediaController::class, 'uploadImages'])->whereNumber('id')->name('media.images.upload');
        Route::post('media/{id}/images/{imageId}/delete', [Vendor\MediaController::class, 'deleteImage'])->whereNumber(['id', 'imageId'])->name('media.images.delete');

        // Availability & status
        Route::get('availability', [Vendor\InventoryController::class, 'availability'])->name('availability');
        Route::get('media/{id}/availability', [Vendor\InventoryController::class, 'availabilityForm'])->whereNumber('id')->name('availability.form');
        Route::post('media/{id}/availability', [Vendor\InventoryController::class, 'updateAvailability'])->whereNumber('id')->name('availability.update');
        Route::post('media/{id}/availability/{rangeId}/remove', [Vendor\InventoryController::class, 'removeRange'])->whereNumber(['id', 'rangeId'])->name('availability.range.remove');

        // Bookings (read-only) and Operations
        Route::get('bookings', [Vendor\InventoryController::class, 'bookings'])->name('bookings');
        Route::get('operations', [Vendor\OperationsController::class, 'index'])->name('operations');
        Route::post('operations/{id}/photo', [Vendor\OperationsController::class, 'uploadPhoto'])->whereNumber('id')->name('operations.photo');
        Route::post('operations/{id}/share', [Vendor\OperationsController::class, 'share'])->whereNumber('id')->name('operations.share');
        Route::post('operations/{id}/complete', [Vendor\OperationsController::class, 'complete'])->whereNumber('id')->name('operations.complete');

        // Reports
        Route::get('reports/utilisation', [Vendor\ReportController::class, 'utilisation'])->name('reports.utilisation');
        Route::get('reports/utilisation/export/{format}', [Vendor\ReportController::class, 'export'])->whereIn('format', ['excel', 'pdf'])->name('reports.utilisation.export');

        // Notifications & activity log
        Route::get('notifications', [Vendor\NotificationController::class, 'index'])->name('notifications');
        Route::post('notifications/read-all', [Vendor\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{id}/open', [Vendor\NotificationController::class, 'open'])->whereNumber('id')->name('notifications.open');

        // Enquiries from the vendor website's Contact page
        Route::get('enquiries', [Vendor\EnquiryController::class, 'index'])->name('enquiries');
        Route::post('enquiries/{id}/status', [Vendor\EnquiryController::class, 'updateStatus'])->whereNumber('id')->name('enquiries.status');

        // Website branding & slider
        Route::get('website', [Vendor\WebsiteController::class, 'index'])->name('website');
        Route::post('website/logo', [Vendor\WebsiteController::class, 'updateLogo'])->name('website.logo');
        Route::post('website/logo/remove', [Vendor\WebsiteController::class, 'removeLogo'])->name('website.logo.remove');
        Route::post('website/slides', [Vendor\WebsiteController::class, 'storeSlide'])->name('website.slides.store');
        Route::post('website/slides/{id}', [Vendor\WebsiteController::class, 'updateSlide'])->whereNumber('id')->name('website.slides.update');
        Route::post('website/slides/{id}/edit', [Vendor\WebsiteController::class, 'editSlide'])->whereNumber('id')->name('website.slides.edit');
        Route::post('website/slides/{id}/delete', [Vendor\WebsiteController::class, 'destroySlide'])->whereNumber('id')->name('website.slides.destroy');
    });
});

Route::middleware('SuperAdmin')->prefix('admin-vendor')->name('admin.vendor.')->group(function () {
    Route::get('approvals', [VendorPortalController::class, 'approvals'])->name('approvals');
    Route::get('approvals/{id}', [VendorPortalController::class, 'review'])->whereNumber('id')->name('approvals.show');
    Route::post('approvals/{id}/approve', [VendorPortalController::class, 'approve'])->whereNumber('id')->name('approvals.approve');
    Route::post('approvals/{id}/reject', [VendorPortalController::class, 'reject'])->whereNumber('id')->name('approvals.reject');

    Route::get('inventory', [VendorPortalController::class, 'inventory'])->name('inventory');
    Route::post('inventory/assign', [VendorPortalController::class, 'assign'])->name('inventory.assign');
    Route::post('inventory/remove', [VendorPortalController::class, 'unassign'])->name('inventory.remove');

    Route::get('activity', [VendorPortalController::class, 'activity'])->name('activity');
    Route::get('notifications', [VendorPortalController::class, 'notifications'])->name('notifications');
    Route::post('notifications/read-all', [VendorPortalController::class, 'readAllNotifications'])->name('notifications.read-all');
    Route::get('notifications/{id}/open', [VendorPortalController::class, 'openNotification'])->whereNumber('id')->name('notifications.open');

    Route::post('vendors/{id}/credentials', [VendorPortalController::class, 'sendCredentials'])->whereNumber('id')->name('credentials');
});

// Public vendor showcase: brand-adda.co.in/{vendor-slug}
Route::prefix('{vendorSlug}')->where(['vendorSlug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->name('vendor-site.')->group(function () {
    Route::get('/', [VendorSiteController::class, 'show'])->name('show');
    Route::post('otp/send', [VendorSiteController::class, 'sendOtp'])->name('otp.send')->middleware('throttle:5,10');
    Route::post('otp/verify', [VendorSiteController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:10,10');
    Route::get('media/{mediaId}', [VendorSiteController::class, 'media'])->whereNumber('mediaId')->name('media');
    Route::get('contact', [VendorSiteController::class, 'contact'])->name('contact');
    // Generous on attempts (fixing typos must not lock anyone out); sent
    // enquiries are limited separately in the controller.
    Route::post('contact', [VendorSiteController::class, 'submitEnquiry'])->name('contact.submit')->middleware('throttle:30,10');
});
