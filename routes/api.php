<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\MaterialIssueController;
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
header('OData-Version : 4.0');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/checkToken', [AuthController::class, 'checkToken'])->middleware('auth:sanctum');

Route::post('/budget/createBudget', [BudgetController::class, 'createBudget'])->middleware('auth:sanctum');
Route::post('/budget/approveBudget', [BudgetController::class, 'approveBudget'])->middleware('auth:sanctum');
Route::get('/getBudget', [BudgetController::class, 'getBudget'])->middleware('auth:sanctum');
Route::get('/budget/getBudgetById', [BudgetController::class, 'getBudgetById'])->middleware('auth:sanctum');

// Route::post('/budget/createBudget', [BudgetController::class, 'createBudget'])->middleware('auth:sanctum');
// Route::post('/budget/approveBudget', [BudgetController::class, 'approveBudget'])->middleware('auth:sanctum');
Route::get('/materialRequest/getMaterialRequests', [MaterialRequestController::class, 'getMaterialRequests'])->middleware('auth:sanctum');
Route::get('/materialRequest/getMaterialRequestById', [MaterialRequestController::class, 'getMaterialRequestById'])->middleware('auth:sanctum');
Route::post('/materialRequest/createMaterialRequest', [MaterialRequestController::class, 'createMaterialRequest'])->middleware('auth:sanctum');
Route::post('/materialRequest/approveMR', [MaterialRequestController::class, 'approveMR'])->middleware('auth:sanctum');
// Route::get('/budget/getBudgetById', [BudgetController::class, 'getBudgetById'])->middleware('auth:sanctum');

// Route::post('/budget/createBudget', [BudgetController::class, 'createBudget'])->middleware('auth:sanctum');
// Route::post('/budget/approveBudget', [BudgetController::class, 'approveBudget'])->middleware('auth:sanctum');
Route::get('/materialIssue/getMaterialIssues', [MaterialIssueController::class, 'getMaterialIssues'])->middleware('auth:sanctum');
Route::post('/materialIssue/createMaterialIssue', [MaterialIssueController::class, 'createMaterialIssue'])->middleware('auth:sanctum');
Route::get('/materialIssue/getMaterialIssueById', [MaterialIssueController::class, 'getMaterialIssueById'])->middleware('auth:sanctum');
Route::post('/materialIssue/approveMI', [MaterialIssueController::class, 'approveMI'])->middleware('auth:sanctum');

Route::get('/$metadata', [BudgetController::class, 'metadata'])->middleware('auth:sanctum');
Route::get('/getBudget2', [BudgetController::class, 'getBudget2'])->middleware('auth:sanctum');
