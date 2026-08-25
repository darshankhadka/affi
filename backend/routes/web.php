<?php

use App\Http\Controllers\Api\V1\AffiliateClickController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'ARIKARTECH API',
        'status' => 'operational',
        'version' => '1.0.0',
    ]);
});

Route::get('/go/{offerId}', [AffiliateClickController::class, 'out'])
    ->name('affiliate.go')
    ->middleware('throttle:60,1');

Route::get('/go/offer/{offerId}', [AffiliateClickController::class, 'out'])
    ->middleware('throttle:60,1');

Route::get('/affiliates/out/{offerId}', [AffiliateClickController::class, 'out'])
    ->name('affiliate.out')
    ->middleware('throttle:60,1');
