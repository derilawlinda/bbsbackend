<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\AuthController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
header('OData-Version : 4.0');
Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/checkToken', [AuthController::class, 'checkToken'])->middleware('auth:sanctum');
Route::post('/budget/createBudget', [BudgetController::class, 'createBudget'])->middleware('auth:sanctum');
Route::get('/getBudget', [BudgetController::class, 'getBudget'])->middleware('auth:sanctum');
Route::get('/$metadata', [BudgetController::class, 'metadata'])->middleware('auth:sanctum');
