<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GRNNoteController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductImagesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReturnItemsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SupplierAdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DailyExpensesController;
use App\Http\Controllers\ProductReturnController;
use App\Http\Controllers\SupplierPaymentController;

// Cashier Routes
Route::prefix('cashiers')->group(function () {
    Route::get('/', [CashierController::class, 'index']);
    Route::post('/', [CashierController::class, 'store']);
    Route::get('/{id}', [CashierController::class, 'show']);
    Route::put('/{id}', [CashierController::class, 'update']);
    Route::delete('/{id}', [CashierController::class, 'destroy']);
    Route::post('/{id}/image', [CashierController::class, 'updateImage']);
    Route::post('/change-password', [CashierController::class, 'updatePassword']);
});

// supplier
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
Route::get('/suppliers/products/{id}', [SupplierController::class, 'showProduct']);

// admin
Route::get('/admin', function (Request $request) {
    return $request->admin();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/admin/register/{userId}', [AdminController::class, 'store']);
Route::get('/check-auth', [UserController::class, 'checkAuth']);

Route::post('/forgot-password', [AuthController::class, 'sendResetEmailLink']);
Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// customer
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});

//feature/product-management
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::post('/products/images', [ProductImagesController::class, 'store']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/inventory/{id}', [ProductController::class, 'getByInventoryId']);
Route::post('/products/update-stock', [ProductController::class, 'updateStock']);

// product image
Route::get('/products/images', [ProductImagesController::class, 'index']);
Route::post('/products/images', [ProductImagesController::class, 'store']);
Route::delete('/products/images/{id}', [ProductImagesController::class, 'destroy']);
Route::get('/products/images/{id}', [ProductImagesController::class, 'show']);
Route::put('/products/images/{id}', [ProductImagesController::class, 'update']);
Route::get('/products/images/product/{id}', [ProductImagesController::class, 'showByProduct']);

// feedback
Route::get('/feedbacks', [FeedbackController::class, 'index']);
Route::post('/feedbacks', [FeedbackController::class, 'store']);
Route::get('/feedbacks/average', [FeedbackController::class, 'averageRating']);
Route::get('/feedbacks/positive', [FeedbackController::class, 'positiveFeedback']);
Route::get('/feedbacks/negative', [FeedbackController::class, 'negativeFeedback']);
Route::get('/feedbacks/{id}', [FeedbackController::class, 'show']);
Route::put('/feedbacks/{id}', [FeedbackController::class, 'update']);
Route::delete('/feedbacks/{id}', [FeedbackController::class, 'destroy']);

// inventory
Route::get('/inventory/export-data', [InventoryController::class, 'exportData']);
Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);
Route::get('/inventory/out-of-stock', [InventoryController::class, 'outOfStock']);
Route::get('/inventory/in-stock', [InventoryController::class, 'inStock']);
Route::get('/inventory/restocks', [InventoryController::class, 'stockChanges']);
Route::apiResource('inventory', InventoryController::class);

//role_base_permission
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::delete('/roles', [RoleController::class, 'destory']);
});

Route::post('/roles-assign', [UserController::class, 'assignRole']);

// sales
Route::get('/sales', [SalesController::class, 'index']);
Route::post('/sales', [SalesController::class, 'store']);
Route::get('/sales/{id}', [SalesController::class, 'show']); // Changed from 'view' to 'show'
Route::put('/sales/{id}', [SalesController::class, 'update']);
Route::get('/sales/customer/{id}', [SalesController::class, 'customerSales']);
Route::post('/sales/{id}/complete-payment', [SalesController::class, 'completePayment']);

// return sales
Route::get('/return', [ReturnItemsController::class, 'index']);
Route::get('/return/{id}', [ReturnItemsController::class, 'show']);
Route::post('/return/sales/{id}', [SalesController::class, 'return']);

//promotions
Route::get('/promotions', [PromotionController::class, 'index']);
Route::post('/promotions', [PromotionController::class, 'store']);
Route::put('/promotions/{id}', [PromotionController::class, 'update']);
Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);
Route::get('/promotions/{id}', [PromotionController::class, 'show']);
Route::get('/promotions/product/{id}', [PromotionController::class, 'showByProduct']);

// reports
Route::middleware('auth:sanctum')->get("/reports/sales", [SalesController::class, 'salesReports']);
Route::middleware('auth:sanctum')->get("/reports/sales/today", [SalesController::class, 'salesReportToday']);
Route::middleware('auth:sanctum')->get("/reports/products/best-selling", [SalesController::class, 'bestSelling']);

//Supplier_Admin
Route::post('/supplier/message', [SupplierAdminController::class, 'store']);
Route::get('/supplier/message', [SupplierAdminController::class, 'index']);
Route::get('/supplier/message/{id}', [SupplierAdminController::class, 'show']);

//Sales_Reports
Route::get('reports/sales', [SalesController::class, 'salesReports']);
Route::get('reports/sales/today', [SalesController::class, 'salesReportToday']);
Route::get('reports/products/best-selling', [SalesController::class, 'bestSelling']);

Route::get('reports/products/return', [SalesController::class, 'turnOverProducts']);
Route::get('reports/sales/payment', [SalesController::class, 'paymentDistribution']);

// GRN Notes Routes
Route::get('/grn-notes', [GRNNoteController::class, 'index']);
Route::post('/grn-notes', [GRNNoteController::class, 'store']);
Route::get('/grn-notes/{id}', [GRNNoteController::class, 'show']);

// loans
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/{id}', [LoanController::class, 'show']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::put('/loans/{id}', [LoanController::class, 'update']);
    Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
});

// investments
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/investments', [InvestmentController::class, 'index']);
    Route::get('/investments/{id}', [InvestmentController::class, 'show']);
    Route::post('/investments', [InvestmentController::class, 'store']);
    Route::put('/investments/{id}', [InvestmentController::class, 'update']);
    Route::delete('/investments/{id}', [InvestmentController::class, 'destroy']);
});

//assets
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/{id}', [AssetController::class, 'show']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::put('/assets/{id}', [AssetController::class, 'update']);
    Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
});

// Daily Expenses Routes
Route::apiResource('daily-expenses', DailyExpensesController::class);

Route::prefix('api')->group(function () {
    Route::get('/daily-expenses', [DailyExpensesController::class, 'index']);
    Route::post('/daily-expenses', [DailyExpensesController::class, 'store']);
    Route::put('/daily-expenses/{id}', [DailyExpensesController::class, 'update']);
    Route::delete('/daily-expenses/{id}', [DailyExpensesController::class, 'destroy']);
});

// product returns
Route::get('/product-returns', [ProductReturnController::class, 'index']);
Route::post('/product-returns', [ProductReturnController::class, 'store']);
Route::get('/product-returns/{id}', [ProductReturnController::class, 'show']);
Route::put('/product-returns/{id}', [ProductReturnController::class, 'update']);
Route::delete('/product-returns/{id}', [ProductReturnController::class, 'destroy']);

// Supplier Payments
Route::get('/supplier-payments', [SupplierPaymentController::class, 'index']);
Route::post('/supplier-payments', [SupplierPaymentController::class, 'store']);

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working'
    ]);
});
