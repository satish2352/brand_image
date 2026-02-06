<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\CheckoutController;

Route::any('/payment/webhook/razorpay', [CheckoutController::class, 'razorpayWebhook']);



