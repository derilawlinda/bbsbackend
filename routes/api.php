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
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ProfitCenterController;

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
Route::post('/budget/rejectBudget', [BudgetController::class, 'rejectBudget'])->middleware('auth:sanctum');
Route::post('/budget/resubmitBudget', [BudgetController::class, 'resubmitBudget'])->middleware('auth:sanctum');
Route::post('/budget/closeBudget', [BudgetController::class, 'closeBudget'])->middleware('auth:sanctum');
Route::post('/budget/cancelBudget', [BudgetController::class, 'cancelBudget'])->middleware('auth:sanctum');
Route::post('/budget/printBudget', [BudgetController::class, 'printBudget'])->middleware('auth:sanctum');

Route::get('/materialRequest/getMaterialRequests', [MaterialRequestController::class, 'getMaterialRequests'])->middleware('auth:sanctum');
Route::get('/materialRequest/getMaterialRequestById', [MaterialRequestController::class, 'getMaterialRequestById'])->middleware('auth:sanctum');
Route::post('/materialRequest/createMaterialRequest', [MaterialRequestController::class, 'createMaterialRequest'])->middleware('auth:sanctum');
Route::post('/materialRequest/approveMR', [MaterialRequestController::class, 'approveMR'])->middleware('auth:sanctum');
Route::post('/materialRequest/saveMR', [MaterialRequestController::class, 'saveMR'])->middleware('auth:sanctum');
Route::post('/materialRequest/rejectMR', [MaterialRequestController::class, 'rejectMR'])->middleware('auth:sanctum');
Route::post('/materialRequest/resubmitMR', [MaterialRequestController::class, 'resubmitMR'])->middleware('auth:sanctum');
Route::post('/materialRequest/printMR', [MaterialRequestController::class, 'printMR'])->middleware('auth:sanctum');

Route::get('/materialIssue/getMaterialIssues', [MaterialIssueController::class, 'getMaterialIssues'])->middleware('auth:sanctum');
Route::post('/materialIssue/createMaterialIssue', [MaterialIssueController::class, 'createMaterialIssue'])->middleware('auth:sanctum');
Route::get('/materialIssue/getMaterialIssueById', [MaterialIssueController::class, 'getMaterialIssueById'])->middleware('auth:sanctum');
Route::post('/materialIssue/approveMI', [MaterialIssueController::class, 'approveMI'])->middleware('auth:sanctum');
Route::post('/materialIssue/saveMI', [MaterialIssueController::class, 'saveMI'])->middleware('auth:sanctum');
Route::post('/materialIssue/rejectMI', [MaterialIssueController::class, 'rejectMI'])->middleware('auth:sanctum');
Route::post('/materialIssue/resubmitMI', [MaterialIssueController::class, 'resubmitMI'])->middleware('auth:sanctum');
Route::post('/materialIssue/printMI', [MaterialIssueController::class, 'printMI'])->middleware('auth:sanctum');

Route::get('/advanceRequest/getAdvanceRequests', [AdvanceRequestController::class, 'getAdvanceRequests'])->middleware('auth:sanctum');
Route::get('/advanceRequest/getAdvanceRequestById', [AdvanceRequestController::class, 'getAdvanceRequestById'])->middleware('auth:sanctum');
Route::post('/advanceRequest/createAdvanceRequest', [AdvanceRequestController::class, 'createAdvanceRequest'])->middleware('auth:sanctum');
Route::post('/advanceRequest/approveAR', [AdvanceRequestController::class, 'approveAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/submitAdvanceRealization', [AdvanceRequestController::class, 'submitAdvanceRealization'])->middleware('auth:sanctum');
Route::post('/advanceRequest/saveAR', [AdvanceRequestController::class, 'saveAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/rejectAR', [AdvanceRequestController::class, 'rejectAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/resubmitAR', [AdvanceRequestController::class, 'resubmitAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/rejectAdvanceRealization', [AdvanceRequestController::class, 'rejectAdvanceRealization'])->middleware('auth:sanctum');
Route::post('/advanceRequest/resubmitRealization', [AdvanceRequestController::class, 'resubmitRealization'])->middleware('auth:sanctum');
Route::get('/advanceRequest/getAdvanceRealizations', [AdvanceRequestController::class, 'getAdvanceRealizations'])->middleware('auth:sanctum');
Route::post('/advanceRequest/transferAR', [AdvanceRequestController::class, 'transferAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/approveAdvanceRealization', [AdvanceRequestController::class, 'approveAdvanceRealization'])->middleware('auth:sanctum');
Route::post('/advanceRequest/advanceRealizationIsClear', [AdvanceRequestController::class, 'advanceRealizationIsClear'])->middleware('auth:sanctum');
Route::post('/advanceRequest/confirmAdvanceRealization', [AdvanceRequestController::class, 'confirmAdvanceRealization'])->middleware('auth:sanctum');
Route::post('/advanceRequest/printAR', [AdvanceRequestController::class, 'printAR'])->middleware('auth:sanctum');
Route::post('/advanceRequest/printRealization', [AdvanceRequestController::class, 'printRealization'])->middleware('auth:sanctum');

Route::get('/coa/getCOAs', [COAController::class, 'getCOAs'])->middleware('auth:sanctum');
Route::get('/coa/getCOAsByBudget', [COAController::class, 'getCOAsByBudget'])->middleware('auth:sanctum');
Route::get('/coa/getCOAsByBudgetForMI', [COAController::class, 'getCOAsByBudgetForMI'])->middleware('auth:sanctum');
Route::get('/coa/getCOAsByAR', [COAController::class, 'getCOAsByAR'])->middleware('auth:sanctum');
Route::get('/coa/getCOAsForTransfer', [COAController::class, 'getCOAsForTransfer'])->middleware('auth:sanctum');

Route::get('/reimbursement/getReimbursements', [ReimbursementController::class, 'getReimbursements'])->middleware('auth:sanctum');
Route::get('/reimbursement/getReimbursementById', [ReimbursementController::class, 'getReimbursementById'])->middleware('auth:sanctum');
Route::post('/reimbursement/createReimbursement', [ReimbursementController::class, 'createReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/approveReimbursement', [ReimbursementController::class, 'approveReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/saveReimbursement', [ReimbursementController::class, 'sapeReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/rejectReimbursement', [ReimbursementController::class, 'rejectReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/transferReimbursement', [ReimbursementController::class, 'transferReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/resubmitReimbursement', [ReimbursementController::class, 'resubmitReimbursement'])->middleware('auth:sanctum');
Route::post('/reimbursement/printReimbursement', [ReimbursementController::class, 'printReimbursement'])->middleware('auth:sanctum');


Route::get('/profitCenter/getPillars', [ProfitCenterController::class, 'getPillars'])->middleware('auth:sanctum');
Route::get('/profitCenter/getClassifications', [ProfitCenterController::class, 'getClassifications'])->middleware('auth:sanctum');
Route::get('/profitCenter/getSubClass', [ProfitCenterController::class, 'getSubClass'])->middleware('auth:sanctum');
Route::get('/profitCenter/getSubClass2', [ProfitCenterController::class, 'getSubClass2'])->middleware('auth:sanctum');


Route::get('/items/getItemsByAccount', [ItemController::class, 'getItemsByAccount'])->middleware('auth:sanctum');

Route::get('/project/getProjects', [ProjectController::class, 'getProjects'])->middleware('auth:sanctum');

Route::post('/main/saveJSONPillar', [MainController::class, 'saveJSONPillar'])->middleware('auth:sanctum');
Route::get('/main/getPillar', [MainController::class, 'getPillar'])->middleware('auth:sanctum');


Route::get('/$metadata', [BudgetController::class, 'metadata'])->middleware('auth:sanctum');
Route::get('/services', [MainController::class, 'services'])->middleware('auth:sanctum');
Route::get('/getBudget2', [BudgetController::class, 'getBudget2'])->middleware('auth:sanctum');
