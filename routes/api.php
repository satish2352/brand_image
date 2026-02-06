<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\CheckoutController;

Route::post('/api/payment/webhook/razorpay', [CheckoutController::class, 'razorpayWebhook']);



