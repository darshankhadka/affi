<?php

use App\Http\Controllers\Api\V1\Admin\AffiliateAdminController;
use App\Http\Controllers\Api\V1\Admin\AutomationAdminController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OfferAdminController;
use App\Http\Controllers\Api\V1\Admin\ProductAdminController;
use App\Http\Controllers\Api\V1\Admin\SeoAdminController;
use App\Http\Controllers\Api\V1\Admin\UserAdminController;
use App\Http\Controllers\Api\V1\AffiliateClickController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (/api/v1/...)
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', [HealthController::class, 'check']);

// Authentication
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

// Markets & Currencies
Route::get('/markets', [MarketController::class, 'index']);
Route::get('/markets/detect', [MarketController::class, 'detect']);
Route::get('/markets/{code}', [MarketController::class, 'show']);

// Categories & Brands
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{slug}', [BrandController::class, 'show']);

// Product Catalog & Discovery
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/compare', [ProductController::class, 'compare']);

// Offers & Price History
Route::get('/products/{productId}/offers', [OfferController::class, 'index']);
Route::get('/products/{productId}/price-history', [OfferController::class, 'priceHistory']);

// Outbound Affiliate Click Tracker
Route::get('/affiliates/out/{offerId}', [AffiliateClickController::class, 'out'])
    ->middleware('throttle:60,1');
Route::get('/go/{offerId}', [AffiliateClickController::class, 'out'])
    ->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Customers & Admins)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Admin & Editorial Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:Super Admin|Admin|Editor|Analyst'])->prefix('admin')->group(function () {
    // Dashboard Overview
    Route::get('/dashboard', [DashboardController::class, 'overview']);

    // Catalog & Product Matching
    Route::get('/products', [ProductAdminController::class, 'index']);
    Route::post('/products', [ProductAdminController::class, 'store'])->middleware('role:Super Admin|Admin|Editor');
    Route::get('/products/{id}', [ProductAdminController::class, 'show']);
    Route::put('/products/{id}', [ProductAdminController::class, 'update'])->middleware('role:Super Admin|Admin|Editor');
    Route::delete('/products/{id}', [ProductAdminController::class, 'destroy'])->middleware('role:Super Admin|Admin');
    Route::post('/products/match', [ProductAdminController::class, 'match']);

    // Offers & Prices
    Route::get('/offers', [OfferAdminController::class, 'index']);
    Route::post('/offers', [OfferAdminController::class, 'store'])->middleware('role:Super Admin|Admin|Editor');
    Route::put('/offers/{id}', [OfferAdminController::class, 'update'])->middleware('role:Super Admin|Admin|Editor');
    Route::delete('/offers/{id}', [OfferAdminController::class, 'destroy'])->middleware('role:Super Admin|Admin');
    Route::post('/offers/recalculate/{productId}', [OfferAdminController::class, 'recalculate'])->middleware('role:Super Admin|Admin|Editor');

    // Affiliates & Retailers
    Route::get('/affiliates/providers', [AffiliateAdminController::class, 'providers']);
    Route::put('/affiliates/providers/{id}', [AffiliateAdminController::class, 'updateProvider'])->middleware('role:Super Admin|Admin');
    Route::post('/affiliates/providers/{id}/test', [AffiliateAdminController::class, 'testProviderConnection'])->middleware('role:Super Admin|Admin');
    Route::post('/affiliates/providers/{id}/sync', [AffiliateAdminController::class, 'triggerSync'])->middleware('role:Super Admin|Admin');
    Route::post('/affiliates/amazon/validate-url', [AffiliateAdminController::class, 'validateAmazonUrl'])->middleware('role:Super Admin|Admin|Editor');
    Route::post('/affiliates/amazon/import', [AffiliateAdminController::class, 'importAmazonProduct'])->middleware('role:Super Admin|Admin|Editor');
    Route::get('/affiliates/retailers', [AffiliateAdminController::class, 'retailers']);
    Route::post('/affiliates/retailers', [AffiliateAdminController::class, 'storeRetailer'])->middleware('role:Super Admin|Admin');
    Route::put('/affiliates/retailers/{id}', [AffiliateAdminController::class, 'updateRetailer'])->middleware('role:Super Admin|Admin');
    Route::post('/affiliates/retailers/{id}/test', [AffiliateAdminController::class, 'testRetailer'])->middleware('role:Super Admin|Admin');

    // Automation & Ingestion
    Route::get('/automation/jobs', [AutomationAdminController::class, 'jobs']);
    Route::post('/automation/batch', [AutomationAdminController::class, 'triggerBatch'])->middleware('role:Super Admin|Admin');

    // SEO & Redirects
    Route::get('/seo/overview', [SeoAdminController::class, 'overview']);
    Route::get('/seo/redirects', [SeoAdminController::class, 'redirects']);
    Route::post('/seo/redirects', [SeoAdminController::class, 'storeRedirect'])->middleware('role:Super Admin|Admin|Editor');
    Route::delete('/seo/redirects/{id}', [SeoAdminController::class, 'destroyRedirect'])->middleware('role:Super Admin|Admin');

    // Analytics & Intelligence
    Route::get('/analytics/search-intelligence', [\App\Http\Controllers\Api\V1\Admin\AnalyticsAdminController::class, 'searchIntelligence']);
    Route::get('/analytics/conversion', [\App\Http\Controllers\Api\V1\Admin\AnalyticsAdminController::class, 'conversionOverview']);

    // Taxonomy & Brands
    Route::get('/categories', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'categories']);
    Route::post('/categories', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'storeCategory'])->middleware('role:Super Admin|Admin|Editor');
    Route::put('/categories/{id}', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'updateCategory'])->middleware('role:Super Admin|Admin|Editor');
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'destroyCategory'])->middleware('role:Super Admin|Admin');

    Route::get('/brands', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'brands']);
    Route::post('/brands', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'storeBrand'])->middleware('role:Super Admin|Admin|Editor');
    Route::put('/brands/{id}', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'updateBrand'])->middleware('role:Super Admin|Admin|Editor');
    Route::delete('/brands/{id}', [\App\Http\Controllers\Api\V1\Admin\TaxonomyAdminController::class, 'destroyBrand'])->middleware('role:Super Admin|Admin');

    // Markets & Currencies
    Route::get('/markets', [\App\Http\Controllers\Api\V1\Admin\MarketAdminController::class, 'markets']);
    Route::put('/markets/{id}', [\App\Http\Controllers\Api\V1\Admin\MarketAdminController::class, 'updateMarket'])->middleware('role:Super Admin|Admin');
    Route::get('/currencies', [\App\Http\Controllers\Api\V1\Admin\MarketAdminController::class, 'currencies']);

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\V1\Admin\SettingsAdminController::class, 'index']);
    Route::post('/settings', [\App\Http\Controllers\Api\V1\Admin\SettingsAdminController::class, 'update'])->middleware('role:Super Admin|Admin');

    // User & RBAC Management (Super Admin only)
    Route::get('/users', [UserAdminController::class, 'index'])->middleware('role:Super Admin');
    Route::post('/users', [UserAdminController::class, 'store'])->middleware('role:Super Admin');
    Route::put('/users/{id}', [UserAdminController::class, 'update'])->middleware('role:Super Admin');
});
