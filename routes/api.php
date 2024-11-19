<?php

use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PermissionTypeController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\AdvertisingImageController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BmCashoutPrepareController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use App\Services\PusherService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Pusher Routes
Route::post('/pusher/auth', function (Request $request, PusherService $pusherService) {
    $user = $request->user();
    $channel_name = $request->channel_name;
    $socket_id = $request->socket_id;

    $data = $pusherService->getPusherClient()->presence_auth($channel_name, $socket_id, $user->id, [
        'name' => $user->name,
    ]);

    return response()->json($data);
})->middleware('auth:sanctum');

// routes/api.php
Route::middleware('auth:sanctum')->post('/update-fcm-token', [FcmTokenController::class, 'update']);

// User routes
Route::post('/users/register', [UserController::class, 'register']);
Route::post('/users/login', [UserController::class, 'login']);
Route::post('/login', [Controller::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/logout', [UserController::class, 'logout']);
    Route::get('/users/profile', [UserController::class, 'show']);
    Route::put('/users/profile', [UserController::class, 'update']);
    Route::delete('/users/profile', [UserController::class, 'destroy']);


    // Category routes
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('/categories', [CategoryController::class, 'index']);

    // SubCategory routes
    Route::post('/subcategories/create', [SubCategoryController::class, 'store']);
    Route::get('/subcategories/{id}', [SubCategoryController::class, 'show']);
    Route::put('/subcategories/{id}', [SubCategoryController::class, 'update']);
    Route::delete('/subcategories/{id}', [SubCategoryController::class, 'destroy']);
    Route::get('/subcategories', [SubCategoryController::class, 'index']);
    Route::get('/subcategories/{id}/services', [SubCategoryController::class, 'services']);

    // ServiceRequest routes
    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
    Route::get('/service-requests/{id}', [ServiceRequestController::class, 'show']);
    Route::put('/service-requests/{id}', [ServiceRequestController::class, 'update']);
    Route::delete('/service-requests/{id}', [ServiceRequestController::class, 'destroy']);
    Route::get('/service-requests', [ServiceRequestController::class, 'index']);
    Route::put('/service-requests/{id}/status', [ServiceRequestController::class, 'updateStatus']);

    // Service routes
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services/search', [ServiceController::class, 'search']);


    // Booking routes
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/provider/work-orders', [BookingController::class, 'getProviderWorkOrders']);

    // Favourite routes
    Route::get('/favourites', [FavouriteController::class, 'index']);
    Route::get('/favourites/{id}', [FavouriteController::class, 'show']);
    Route::post('/favourites/toggle', [FavouriteController::class, 'toggle']);

    // Chat route
    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chat', [ChatController::class, 'startChat']);
    Route::get('/chat/check', [ChatController::class, 'checkChatExists']);
    Route::post('/chat/message', [ChatController::class, 'sendMessage']);
    Route::get('/chat/messages', [ChatController::class, 'getMessages']);
    Route::delete('/chat/{messageId}', [ChatController::class, 'deleteMessage']);

    // PermissionType routes
    Route::post('/permission-types', [PermissionTypeController::class, 'store']);
    Route::get('/permission-types/{id}', [PermissionTypeController::class, 'show']);
    Route::put('/permission-types/{id}', [PermissionTypeController::class, 'update']);
    Route::delete('/permission-types/{id}', [PermissionTypeController::class, 'destroy']);
    Route::get('/permission-types', [PermissionTypeController::class, 'index']);

    // UserPermission routes
    Route::post('/user-permissions', [UserPermissionController::class, 'store']);
    Route::get('/user-permissions', [UserPermissionController::class, 'index']);

    // Banks routes
    Route::get('/banks', [BankController::class, 'index']);
    Route::get('/wallets', [BankController::class, 'getWallets']);
    Route::get('/banks-only', [BankController::class, 'getBanks']);
    Route::post('/banks', [BankController::class, 'store']);
    Route::get('/banks/{id}', [BankController::class, 'show']);
    Route::put('/banks/{id}', [BankController::class, 'update']);
    Route::delete('/banks/{id}', [BankController::class, 'destroy']);

    // Cashout routes
    Route::get('bank-misr/transactions', [BmCashoutPrepareController::class, 'index']);
    Route::get('bank-misr/transactions/{id}', [BmCashoutPrepareController::class, 'show']);
    Route::post('bank-misr/prepare-transaction', [BmCashoutPrepareController::class, 'generateSignAndSendTransaction']);

    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    //advartising images
    Route::get('/advertising-images', [AdvertisingImageController::class, 'index']);
    Route::put('/advertising-images/{id}', [AdvertisingImageController::class, 'update']);
    Route::delete('/advertising-images/{id}', [AdvertisingImageController::class, 'destroy']);
    Route::post('/advertising-images', [AdvertisingImageController::class, 'store']);

    // Wallet routes
    Route::post('/wallet', [WalletController::class, 'store']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::put('/wallet', [WalletController::class, 'update']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('/notifications/unread/count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/with-count', [NotificationController::class, 'getNotificationsWithCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);


});

// Service Provider routes
Route::post('/providers/register', [ServiceProviderController::class, 'register']);
Route::post('/providers/login', [ServiceProviderController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/providers/logout', [ServiceProviderController::class, 'logout']);
    Route::get('/providers/profile', [ServiceProviderController::class, 'show']);
    Route::put('/providers/profile', [ServiceProviderController::class, 'update']);
    Route::delete('/providers/profile', [ServiceProviderController::class, 'destroy']);
});
