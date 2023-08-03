<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\MaterialIssueController;
use App\Http\Controllers\AdvanceRequestController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\COAController;
use App\Http\Controllers\ReimbursementController;
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
Route::get('/budget/getApprovedBudget', [BudgetController::class, 'getApprovedBudget'])->middleware('auth:sanctum');
Route::get('/budget/getBudgetById', [BudgetController::class, 'getBudgetById'])->middleware('auth:sanctum');
Route::post('/budget/saveBudget', [BudgetController::class, 'saveBudget'])->middleware('auth:sanctum');

Route::get('/materialRequest/getMaterialRequests', [MaterialRequestController::class, 'getMaterialRequests'])->middleware('auth:sanctum');
Route::get('/materialRequest/getMaterialRequestById', [MaterialRequestController::class, 'getMaterialRequestById'])->middleware('auth:sanctum');
Route::post('/materialRequest/createMaterialRequest', [MaterialRequestController::class, 'createMaterialRequest'])->middleware('auth:sanctum');
Route::post('/materialRequest/approveMR', [MaterialRequestController::class, 'approveMR'])->middleware('auth:sanctum');
Route::post('/materialRequest/saveMR', [MaterialRequestController::class, 'saveMR'])->middleware('auth:sanctum');

Route::get('/materialIssue/getMaterialIssues', [MaterialIssueController::class, 'getMaterialIssues'])->middleware('auth:sanctum');
Route::post('/materialIssue/createMaterialIssue', [MaterialIssueController::class, 'createMaterialIssue'])->middleware('auth:sanctum');
Route::get('/materialIssue/getMaterialIssueById', [MaterialIssueController::class, 'getMaterialIssueById'])->middleware('auth:sanctum');
Route::post('/materialIssue/approveMI', [MaterialIssueController::class, 'approveMI'])->middleware('auth:sanctum');
Route::post('/materialIssue/saveMI', [MaterialIssueController::class, 'saveMI'])->middleware('auth:sanctum');

Route::get('/advanceRequest/getAdvanceRequests', [AdvanceRequestController::class, 'getAdvanceRequests'])->middleware('auth:sanctum');
Route::get('/advanceRequest/getAdvanceRequestById', [AdvanceRequestController::class, 'getAdvanceRequestById'])->middleware('auth:sanctum');
Route::post('/advanceRequest/createAdvanceRequest', [AdvanceRequestController::class, 'createAdvanceRequest'])->middleware('auth:sanctum');
Route::post('/advanceRequest/approveAR', [AdvanceRequestController::class, 'approveAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/submitAdvanceRealization', [AdvanceRequestController::class, 'submitAdvanceRealization'])->middleware('auth:sanctum');
Route::post('/advanceRequest/saveAR', [AdvanceRequestController::class, 'saveAR'])->middleware('auth:sanctum');

Route::get('/coa/getCOAs', [COAController::class, 'getCOAs'])->middleware('auth:sanctum');
Route::get('/coa/getCOAsByBudget', [COAController::class, 'getCOAsByBudget'])->middleware('auth:sanctum');

Route::get('/reimbursement/getReimbursements', [ReimbursementController::class, 'getReimbursements'])->middleware('auth:sanctum');
Route::get('/reimbursement/getReimbursementById', [ReimbursementController::class, 'getReimbursementById'])->middleware('auth:sanctum');
Route::post('/reimbursement/createReimbursement', [ReimbursementController::class, 'createReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/approveReimbursement', [ReimbursementController::class, 'approveReimbursement'])->middleware('auth:sanctum');

Route::get('/items/getItemsByAccount', [ItemController::class, 'getItemsByAccount'])->middleware('auth:sanctum');


Route::get('/$metadata', [BudgetController::class, 'metadata'])->middleware('auth:sanctum');
Route::get('/getBudget2', [BudgetController::class, 'getBudget2'])->middleware('auth:sanctum');
