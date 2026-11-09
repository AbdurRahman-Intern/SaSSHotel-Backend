<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExternalIncomeController;
use App\Http\Controllers\Api\HallController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OwnHotelController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RequisitionController;
use App\Http\Controllers\Api\SalaryPaymentController;
use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| routes/api.php  -  THE MAP OF THE API  (start reading the backend here)
|--------------------------------------------------------------------------
| Laravel adds the prefix "/api" automatically, so Route::get('/plans') is  GET http://localhost:8000/api/plans
|
| Three layers of protection, from the outside in:
|   1. auth:sanctum       -> must send a valid "Authorization: Bearer <token>"          (401 otherwise)
|   2. role:xxx           -> must have the right role                                   (403 otherwise)
|   3. hotel.active       -> hotel owners only: their hotel must not be suspended       (403 otherwise)
| And inside controllers, models with BelongsToTenant only ever see their own hotel's rows.
|
| Route::apiResource('halls', HallController::class) is a shortcut that creates:
|   GET /halls (index)   POST /halls (store)   GET /halls/{id} (show)   PUT|PATCH /halls/{id} (update)   DELETE /halls/{id} (destroy)
|
| TO ADD A NEW MODULE: migration -> model -> resource -> controller -> ONE line here (+ a React page).
*/

// ---------- PUBLIC ----------
// throttle:10,1 = max 10 login attempts per minute per IP (slows down password guessing).
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// ---------- LOGGED-IN USERS (any role) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/plans', [PlanController::class, 'index']);

    // ---------- SUPER ADMIN ONLY ----------
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('hotels', HotelController::class);
        Route::get('/users', [PlatformController::class, 'users']);
        Route::get('/platform/stats', [PlatformController::class, 'stats']);
        Route::get('/platform/activity', [PlatformController::class, 'activity']);
        // TODO: plans CRUD, platform settings.
    });

    // ---------- HOTEL OWNER ONLY (and hotel not suspended) ----------
    Route::middleware(['role:hotel_owner', 'hotel.active'])->group(function () {
        Route::get('/dashboard', DashboardController::class);
        Route::get('/my-hotel', [OwnHotelController::class, 'show']);
        Route::patch('/my-hotel', [OwnHotelController::class, 'update']);

        // Bookings
        Route::apiResource('bookings', BookingController::class);
        Route::post('/bookings/{id}/payments', [BookingController::class, 'addPayment']);
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        Route::get('/bookings/{id}/requisition', [RequisitionController::class, 'preview']);
        Route::post('/bookings/{id}/requisition/issue', [RequisitionController::class, 'issue']);
        Route::apiResource('halls', HallController::class);
        Route::apiResource('menus', MenuController::class);

        // Inventory (warehouse)
        Route::apiResource('inventory-items', InventoryItemController::class);
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::post('/stock-movements/in', [StockMovementController::class, 'stockIn']);
        Route::post('/stock-movements/out', [StockMovementController::class, 'stockOut']);
        Route::post('/stock-movements/spoilage', [StockMovementController::class, 'spoilage']);

        // People & money
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('salary-payments', SalaryPaymentController::class);
        Route::apiResource('partners', PartnerController::class);
        Route::post('/partners/{id}/transactions', [PartnerController::class, 'addTransaction']);
        Route::apiResource('loans', LoanController::class);
        Route::post('/loans/{id}/repayments', [LoanController::class, 'addRepayment']);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('external-income', ExternalIncomeController::class)->parameters(['external-income' => 'id']);

        // Reports (stub)
        Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    });
});
