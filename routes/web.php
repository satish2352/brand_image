<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadm\LoginController;
use App\Http\Controllers\Superadm\DashboardController;
use App\Http\Controllers\EmpDashboardController;
use App\Http\Controllers\Superadm\RoleController;
use App\Http\Controllers\Superadm\RadiusController;
use App\Http\Controllers\Superadm\Master\CategoryController;
use App\Http\Controllers\Superadm\AreaController;
use App\Http\Controllers\Superadm\MediaManagementController;
use App\Http\Controllers\Superadm\EmployeesController;
use App\Http\Controllers\Superadm\ChangePasswordController;
use App\Http\Controllers\Superadm\EmployeeLoginController;
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

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');

    return "<h3>✅ All Laravel caches cleared successfully!</h3>";
})->name('clear.cache');


Route::get('login', [LoginController::class, 'loginsuper'])->name('login');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'loginsuper'])->name('login');
    Route::post('superlogin', [LoginController::class, 'validateSuperLogin'])->name('superlogin');

    Route::get('emp-login', [EmployeeLoginController::class, 'loginEmployee'])->name('emp.login');
    Route::post('emp-login', [EmployeeLoginController::class, 'validateEmpLogin'])->name('emp.login.submit');
});

Route::post('superlogin', [LoginController::class, 'validateSuperLogin'])->name('superlogin');

Route::get('emp-login', [EmployeeLoginController::class, 'loginEmployee'])->name('emp.login');
Route::post('emp-login', [EmployeeLoginController::class, 'validateEmpLogin'])->name('emp.login.submit');
Route::get('emp-logout', [EmployeeLoginController::class, 'logOut'])->name('emp.logout');



Route::group(['middleware' => ['SuperAdmin']], function () {



    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');


    // Route::get('/change-password', [ChangePasswordController::class, 'index'])->name('change-password');
    // Route::post('/update-password', [ChangePasswordController::class, 'updatePassword'])->name('update-password');
    Route::get('/admin/change-password', [ChangePasswordController::class, 'index'])->name('admin.change-password');
    Route::post('/admin/update-password', [ChangePasswordController::class, 'updatePassword'])->name('admin.update-password');
    // Role management routes
    Route::get('/roles/list', [RoleController::class, 'index'])->name('roles.list');
    Route::get('/roles/add', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles/add', [RoleController::class, 'save'])->name('roles.save');
    Route::get('/roles/edit/{encodedId}', [RoleController::class, 'edit'])->name('roles.edit');
    Route::post('/roles/update/{encodedId}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('/roles/delete', [RoleController::class, 'delete'])->name('roles.delete');
    Route::post('/roles/update-status', [RoleController::class, 'updateStatus'])->name('roles.updatestatus');



    /* AREA MANAGEMENT */
    Route::prefix('area')->group(function () {

        Route::get('list', [AreaController::class, 'index'])->name('area.list');

        Route::get('add', [AreaController::class, 'create'])->name('area.create');

        // ✅ THIS IS IMPORTANT
        Route::post('add', [AreaController::class, 'store'])->name('area.store');

        Route::get('edit/{encodedId}', [AreaController::class, 'edit'])->name('area.edit');

        Route::post('update/{encodedId}', [AreaController::class, 'update'])->name('area.update');

        Route::post('delete', [AreaController::class, 'delete'])->name('area.delete');

        Route::post('update-status', [AreaController::class, 'updateStatus'])->name('area.updatestatus');
    });


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

        Route::get('list', [MediaManagementController::class, 'index'])
            ->name('media.list');

        Route::get('add', [MediaManagementController::class, 'create'])
            ->name('media.create');

        Route::post('add', [MediaManagementController::class, 'store'])
            ->name('media.store');

        Route::get('edit/{encodedId}', [MediaManagementController::class, 'edit'])
            ->name('media.edit');

        Route::post('update/{encodedId}', [MediaManagementController::class, 'update'])
            ->name('media.update');

        Route::post('delete', [MediaManagementController::class, 'delete'])
            ->name('media.delete');

        // ✅ THIS FIXES YOUR ERROR
        Route::post('status', [MediaManagementController::class, 'updateStatus'])
            ->name('media.status');

        /* =========================
       👁 VIEW MEDIA IMAGES
    ========================== */
        Route::get('view/{encodedId}', [MediaManagementController::class, 'view'])
            ->name('media.view');

        /* =========================
       🗑 DELETE SINGLE IMAGE
    ========================== */
        Route::post('image/delete', [MediaManagementController::class, 'deleteImage'])
            ->name('media.image.delete');

        Route::post('image/upload', [MediaManagementController::class, 'uploadImage'])
            ->name('media.image.upload');

        Route::get('view-details/{encodedId}', [MediaManagementController::class, 'viewDetails'])
            ->name('media.viewdetails');
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
        Route::post('toggle-status', [WebsiteUserController::class, 'toggleStatus'])
            ->name('website-user.toggle-status');
    });
    Route::prefix('contact-us')->group(function () {
        Route::get('list', [ContactUsController::class, 'index'])->name('contact-us.list');
        Route::post('delete', [ContactUsController::class, 'delete'])->name('contact-us.delete');
    });
    Route::prefix('user-payment')->group(function () {
        Route::get('list', [UserPaymentController::class, 'index'])->name('user-payment.list');
        Route::get('details/{orderId}', [UserPaymentController::class, 'details'])
            ->name('user-payment.details');
    });

    // Radius Master
    Route::get('radius/list', [RadiusController::class, 'index'])->name('radius.list');
    Route::get('radius/create', [RadiusController::class, 'create'])->name('radius.create');
    Route::post('radius/save', [RadiusController::class, 'save'])->name('radius.save');
    Route::get('radius/edit/{encodedId}', [RadiusController::class, 'edit'])->name('radius.edit');
    Route::post('radius/update/{encodedId}', [RadiusController::class, 'update'])->name('radius.update');
    Route::post('radius/delete', [RadiusController::class, 'delete'])->name('radius.delete');
    Route::post('radius/update-status', [RadiusController::class, 'updateStatus'])->name('radius.updatestatus');










    // employees management routes
    Route::get('/employees/list', [EmployeesController::class, 'index'])->name('employees.list');
    Route::get('/employees/ajax-list', [EmployeesController::class, 'ajaxList'])->name('employees.ajax');
    Route::get('/employees/add', [EmployeesController::class, 'create'])->name('employees.create');
    Route::post('/employees/add', [EmployeesController::class, 'save'])->name('employees.save');
    Route::get('/employees/edit/{encodedId}', [EmployeesController::class, 'edit'])->name('employees.edit');
    Route::PUT('/employees/update/{encodedId}', [EmployeesController::class, 'update'])->name('employees.update');
    Route::post('/employees/delete', [EmployeesController::class, 'delete'])->name('employees.delete');
    Route::post('/employees/update-status', [EmployeesController::class, 'updateStatus'])->name('employees.updatestatus');
    Route::post('/employees/list-ajax', [EmployeesController::class, 'listajaxlist'])->name('employees.list-ajax');
    Route::post('/employees/update-status', [EmployeesController::class, 'updateStatus'])->name('employees.updatestatus');


    Route::get('employees/export', [EmployeesController::class, 'export'])->name('employees.export');





    // Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('admin/logout', [LoginController::class, 'logOut'])->name('admin.logout');
});


// Route::group(['middleware' => ['Employee']], function () {

//     Route::get('dashboard-emp', [EmpDashboardController::class, 'index'])->name('dashboard-emp');
//     Route::get('/change-password', [ChangePasswordController::class, 'index'])->name('change-password');
//     Route::post('/update-password', [ChangePasswordController::class, 'updatePassword'])->name('update-password');
//     Route::get('logout', [LoginController::class, 'logout'])->name('logout');

// });

Route::group(['middleware' => ['Employee']], function () {
    Route::get('dashboard-emp', [EmpDashboardController::class, 'index'])->name('dashboard-emp');
    // Route::get('/change-password', [ChangePasswordController::class, 'index'])->name('change-password');
    // Route::post('/update-password', [ChangePasswordController::class, 'updatePassword'])->name('update-password');
    Route::get('/employee/change-password', [ChangePasswordController::class, 'index'])->name('employee.change-password');
    Route::post('/employee/update-password', [ChangePasswordController::class, 'updatePassword'])->name('employee.update-password');
    // Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('emp/logout', [EmployeeLoginController::class, 'logOut'])->name('emp.logout');
});


// Website Rotes

Route::get('/', [HomeController::class, 'index'])->name('website.home');
Route::view('/about', 'website.about')->name('website.about');

Route::post('/website/signup', [AuthController::class, 'signup'])->name('website.signup');
Route::post('/website/login', [AuthController::class, 'login'])->name('website.login');
Route::get('/website/logout', [AuthController::class, 'logout'])->name('website.logout');

Route::middleware('auth:website')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/add/{mediaId}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::get('/cart/remove/{mediaId}', [CartController::class, 'remove'])->name('cart.remove');
});
// Route::get('/checkout', [CheckoutController::class, 'index'])
//     ->name('checkout.index');
// Route::post(
//     '/checkout/place-order',
//     [CheckoutController::class, 'placeOrder']
// )->name('checkout.place')
//     ->middleware('auth:website');

// Route::post('/checkout/pay', [CheckoutController::class, 'pay'])
//     ->name('checkout.pay');

// Route::post('/payment/success', [CheckoutController::class, 'success'])
//     ->name('payment.success');
// Route::post('/payment/webhook/razorpay', [CheckoutController::class, 'razorpayWebhook']);



Route::get('/checkout', [CheckoutController::class, 'index'])
    ->name('checkout.index');

Route::post('/checkout/create-order', [CheckoutController::class, 'createOrder'])
    ->name('checkout.create');


Route::post('/checkout/create-order', [CheckoutController::class, 'placeOrder'])
    ->name('checkout.create');
// ->middleware('auth:website');

Route::post('/checkout/pay', [CheckoutController::class, 'pay'])
    ->name('checkout.pay');

Route::post('/payment/success', [CheckoutController::class, 'success'])
    ->name('payment.success');
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

    Route::get('/campaign-invoice-payments', [CampaignController::class, 'invoicePayments'])
        ->name('campaign.invoice.payments')
        ->middleware('auth:website');

    Route::get(
        '/campaign/details/{cart_item_id}',
        [CampaignController::class, 'viewDetails']
    )->name('campaign.details');

    Route::get('/campaign-invoice/{orderId}', [CampaignController::class, 'viewInvoice'])
        ->name('campaign.invoice.view');
});

Route::get('/contact-us', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact-us', [ContactController::class, 'store'])->name('contact.store');
