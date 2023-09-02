<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReceiptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/notifications' , [AdminController::class , 'get_all_notifications']);



Route::middleware(['CheckSuperAdmin', 'auth:sanctum'])->group(function () {
    Route::post('create_admin', [AdminController::class, 'create_admin']);
    Route::post('create_receiption', [AdminController::class, 'create_receiption']);
    Route::delete('/delete-expert/{expert}' , [AdminController::class , 'delete_expert']);
    Route::delete('/delete-reservation/{reservation}' , [AdminController::class , 'delete_reservation']);
    Route::delete('/delete-client/{client}' , [AdminController::class , 'delete_client']);
    Route::delete('/delete-notification/{notification}' , [AdminController::class , 'delete_notification']);
});



Route::middleware(['CheckReseiption', 'auth:sanctum'])->group(function () {
    Route::post('create_client', [ReceiptionController::class, 'create_client']);
    Route::post('create_service', [AdminController::class, 'create_service']);
    Route::post('create_reservation', [ReceiptionController::class, 'create_reservation']);
    Route::post('complete_reservation', [ReceiptionController::class, 'complete_reservation']);
    Route::post('cancle_reservation', [ReceiptionController::class, 'cancle_reservation'])->middleware('HavePermisstionCancle');
    Route::post('delay_reservation', [ReceiptionController::class, 'delay_reservation'])->middleware('RecieptionCanDelay');
    Route::get('client_reservations', [ReceiptionController::class, 'client_reservations']);
    Route::get('get_reservation/{id}', [ReceiptionController::class, 'get_reservation']);
});

Route::middleware(['CheckAdmin', 'auth:sanctum'])->group(function () {
    Route::post('create_transfer', [AdminController::class, 'create_transfer']);
    Route::post('update_transfer', [AdminController::class, 'update_transfer']);
    Route::get('transfer/{id}', [AdminController::class, 'show_transfer']);
    Route::get('list_of_transfers', [AdminController::class, 'list_of_transfers']);
    Route::get('export', [AdminController::class, 'export']);
    Route::post('create_expert', [AdminController::class, 'create_expert']);
    Route::get('list_of_receiptions', [AdminController::class, 'list_of_receiptions']);
    Route::post('create_holiday', [AdminController::class, 'create_holiday']);
    Route::post('chang_permission', [AdminController::class, 'chang_permission']);
    Route::delete('/delete-expert/{expert}' , [AdminController::class , 'delete_expert']);
    Route::delete('/delete-reservation/{reservation}' , [AdminController::class , 'delete_reservation']);
    Route::delete('/delete-client/{client}' , [AdminController::class , 'delete_client']);
    Route::delete('/delete-notification/{notification}' , [AdminController::class , 'delete_notification']);
});

Route::middleware(['CheckAdminReceiption', 'auth:sanctum'])->group(function () {
    Route::get('list_of_experts', [AdminController::class, 'list_of_experts']);
    Route::get('list_of_services', [AdminController::class, 'list_of_services']);
    Route::get('list_of_client', [ReceiptionController::class, 'list_of_client']);
});
