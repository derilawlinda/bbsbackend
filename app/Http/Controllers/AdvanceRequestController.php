<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Session;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use App\Libraries\SAPb1\Filters\InArray;
use App\Libraries\SAPb1\Filters\Contains;
use Illuminate\Support\Facades\Auth;

use Exception;
use Throwable;
use PDF2;

class AdvanceRequestController extends Controller
{
    private $sapsession;
    private $sap;

    public function createAdvanceRequest(Request $request)
    {
        try{
            $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $AdvanceRequest = $this->sap->getService('AdvanceReq');
        $AdvanceRequest->headers(['OData-Version' => '4.0',
        'Prefer' => 'odata.maxpagesize=1000']);
        $count = $AdvanceRequest->queryBuilder()->count();
        $result = $AdvanceRequest->create($request->get('oProperty') + [
            'Code' => 80000001 + $count,
            'U_CreatedBy' => (int)$user->id,
            'U_RequestorName' => $user->name
        ]);
        return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }
    public function getAdvanceRequests(Request $request)
    {
        try{
            $user = Auth::user();
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $AdvanceReq->headers(['OData-Version' => '4.0',
            "B1S-CaseInsensitive" => true,
            'Prefer' => 'odata.maxpagesize=500']);

            $search = "";
            $status_array = [];

            if ($user["role_id"] == 3) {
                $result = $AdvanceReq->queryBuilder()
                    ->select('*')
                    ->where([new Equal("U_CreatedBy", (string)$user["id"])]);
            }elseif($user["role_id"] == 2){
                $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->where([new Equal("U_Status", 3),'or',new Equal("U_Status", 5)]);
            }
            else{
                $result = $AdvanceReq->queryBuilder()
                ->select('*');
            }

            if($request->search){
                $search = $request->search;
                $result->where([new Contains("Code", $search),'or',new Contains("Name",$search)]);
            }

            if($request->status){
                $req_status_array = preg_split ("/\,/", $request->status);
                foreach ($req_status_array as $value) {
                    array_push($status_array,(int)$value);
                }
                $result->where([new InArray("U_Status", $status_array)]);
            }

            if($request->top){
                $top = $request->top;
            }else{
                $top = 500;
            }

            if($request->skip){
                $skip = $request->skip;
            }else{
                $skip = 0;
            }

            $result = $result->limit($top,$skip)->orderBy('Code', 'desc')->inlineCount()->findAll();
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }

    public function getAdvanceRealizations(Request $request)
    {
        try{
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);

        $search = "";

        if ($user["role_id"] == 3) {
            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->where([new Equal("U_CreatedBy", (string)$user["id"])])
                ->where([new Equal("U_Status", 5)]);
        }elseif ($user["role_id"] == 2) {
            $result = $AdvanceReq->queryBuilder()
                ->select('*');
            }else{

            $result = $AdvanceReq->queryBuilder()
            ->select('*')
            ->where([new Equal("U_Status", 3),'or',new Equal("U_Status", 5)]);
        }

        if($request->search){
            $search = $request->search;
            $result->where([new Contains("Code", $search),'or',new Contains("Name",$search)]);
        }

        if($request->status){
            $req_status_array = preg_split ("/\,/", $request->status);
            foreach ($req_status_array as $value) {
                array_push($status_array,(int)$value);
            }
            $result->where([new InArray("U_Status", $status_array)]);
        }

        if($request->status){
            $status_array = [];
            $req_status_array = preg_split ("/\,/", $request->status);
            foreach ($req_status_array as $value) {
                array_push($status_array,(int)$value);
            }
            $result->where([new InArray("U_RealiStatus", [1,2,3,4,5])]);
        }

        if($request->top){
            $top = $request->top;
        }else{
            $top = 500;
        }

        if($request->skip){
            $skip = $request->skip;
        }else{
            $skip = 0;
        }

        $result = $result->limit($top,$skip)->orderBy('Code', 'desc')->inlineCount()->findAll();

        return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }



    public function transferAR(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }

        $user = Auth::user();

        $array_req = $request->get('oProperty');
        $code = $array_req["Code"];
        $budgetCode = (string)$array_req["U_BudgetCode"];

        $array_req = $request->get('oProperty');
        $array_req_advance = $array_req["ADVANCEREQLINESCollection"];

        $advance_account = array();


        if(count($array_req_advance) > 0){

            $advance_groupbyaccount = array_reduce($array_req_advance, function($advance_account, $advance){
                if(!isset($advance_account[$advance['U_AccountCode']])){
                    $advance_account[$advance["U_AccountCode"]] = $advance["U_Amount"];
                }
                else {
                    $advance_account[$advance["U_AccountCode"]] += $advance['U_Amount'];
                }
                return $advance_account;
            });
        }

        $budget = $this->sap->getService('BudgetReq');
        $arbudget = $budget->queryBuilder()
            ->select('*')
            ->find($budgetCode); // DocEntry value
        $array_budget = json_decode(json_encode($arbudget), true);

        $bank_adm = 0;

        if($array_req["U_BankAdm"]){
            $bank_adm = $array_req["U_BankAdm"];
        }

        try {
            $outgoingPaymentInput = array();
            $outgoingPaymentInput["PaymentAccounts"] = [];
            $outgoingPaymentInput["TransferAccount"] = $array_req["U_TransferFrom"];
            $outgoingPaymentInput["DocType"] = 'rAccount';
            $outgoingPaymentInput["DocCurrency"] = 'IDR';
            $outgoingPaymentInput["TransferSum"] = floatval($array_req["U_Amount"]) + floatval($bank_adm);
            $outgoingPaymentInput["DocDate"] = $array_req["DisbursedDate"];
            $outgoingPaymentInput["U_H_NO_ADV"] = $array_req["Code"];
            $outgoingPaymentInput["CashFlowAssignments"] = [
                "CashFlowLineItemID" => 4,
                "PaymentMeans" => "pmtBankTransfer",
                "AmountLC" => floatval($array_req["U_Amount"]) + floatval($bank_adm)
            ];

            if($bank_adm > 0){
                array_push($outgoingPaymentInput["PaymentAccounts"], (object)[
                    'AccountCode' => '80100.0100',
                    'SumPaid' => floatval($bank_adm),
                    'Decription' => 'Bank Admin',
                    'ProfitCenter' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    "ProfitCenter2" => $array_budget["U_ClassificationCode"],
                    "ProfitCenter3" => $array_budget["U_SubClassCode"],
                    "ProfitCenter4" => $array_budget["U_SubClass2Code"],

                ]);
            }

            for ($i = 0; $i < count($array_req["ADVANCEREQLINESCollection"]); $i++)
            {

                array_push($outgoingPaymentInput["PaymentAccounts"], (object)[
                    'AccountCode' => '11720.2000',
                    'SumPaid' => $array_req["ADVANCEREQLINESCollection"][$i]["U_Amount"],
                    'Decription' => $array_req["ADVANCEREQLINESCollection"][$i]["U_Description"],
                    'ProfitCenter' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    "ProfitCenter2" => $array_budget["U_ClassificationCode"],
                    "ProfitCenter3" => $array_budget["U_SubClassCode"],
                    "ProfitCenter4" => $array_budget["U_SubClass2Code"],
                ]);
            }

            $outgoing_payment = $this->sap->getService('VendorPayments');
            $outgoingResult = $outgoing_payment->create($outgoingPaymentInput);

        if($outgoingResult){

            $outgoingArray = json_decode(json_encode($outgoingResult), true);
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $result = $AdvanceReq->update($code, [
                'U_DisbursedAt' => $array_req["DisbursedDate"],
                'U_Status' => 5,
                'U_TransferBy' => $user->name,
                'U_SAP_DocNum' => $outgoingArray["DocNum"]
            ]);
            if($result == 1){

                $account_array = [];
                foreach ($outgoingArray["PaymentAccounts"] as $key => $value) {
                    array_push($account_array,$value["AccountCode"]);
                };

                $accounts_service = $this->sap->getService('ChartOfAccounts');
                $get_account_names = $accounts_service->queryBuilder()
                    ->select('Code,Name')
                    ->where([new InArray("Code", $account_array)])
                    ->findAll();
                $account_name_array = json_decode(json_encode($get_account_names), true);
                $accounts = [];
                foreach($account_name_array["value"] as $account){
                    $accounts[$account['Code']] = $account['Name'];
                };

                $budgetUsed = [];
                foreach($advance_groupbyaccount as $index => $value){
                    array_push($budgetUsed, (array)[
                        "U_Amount" => $value,
                        "U_Source" => "Advance Request",
                        "U_DocNum" => $array_req["Code"],
                        "U_UsedBy" => $array_req["U_RequestorName"],
                        "U_AccountCode" => $index,
                        "U_AccountName" => $accounts[$index]
                    ]);
                };

                $BudgetReq = $this->sap->getService('BudgetReq');
                $result = $BudgetReq->update($budgetCode, [
                    "BUDGETUSEDCollection" => $budgetUsed
                ]);

            }
        }
        $outgoingResult = $AdvanceReq->queryBuilder()
                        ->select('*')
                        ->find($code);
        return $outgoingResult;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };
    }

    public function confirmAdvanceRealization(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }

            $user = Auth::user();
            $array_req = $request->get('oProperty');
            $array_req_advance = $array_req["ADVANCEREQLINESCollection"];

            $advance_account = array();


            if(count($array_req_advance) > 0){

                $advance_groupbyaccount = array_reduce($array_req_advance, function($advance_account, $advance){
                    if(!isset($advance_account[$advance['U_AccountCode']])){
                        $advance_account[$advance["U_AccountCode"]] = $advance["U_Amount"];
                    }
                    else {
                        $advance_account[$advance["U_AccountCode"]] += $advance['U_Amount'];
                    }
                    return $advance_account;
                });
            }

            // return response()->json($advance_groupbyaccount);



            $budgetCode = (string)$array_req["U_BudgetCode"];
            $budget = $this->sap->getService('BudgetReq');
            $arbudget = $budget->queryBuilder()
                ->select('*')
                ->find($budgetCode); // DocEntry value
            $array_budget = json_decode(json_encode($arbudget), true);
            $code = $array_req["Code"];

            $journal_entry = $this->sap->getService('JournalEntries');
            $journalEntryInput = array();
            $journalEntryInput["JournalEntryLines"] = [];
            $journalEntryInput["U_H_NO_ADVANCE"] = $array_req["Code"];
            $journalEntryInput["U_H_NO_BUDGET"] = $array_req["U_BudgetCode"];
            $journalEntryInput["Memo"] = "Advance Realization ".$array_req["Code"];
            $BudgetReq = $this->sap->getService('BudgetReq');



            if($array_req["U_DifferenceAmt"] > 0){

                array_push($journalEntryInput["JournalEntryLines"], (array)[
                    'AccountCode' => $array_req["U_RealTrfBank"], //BANK
                    'Debit' => $array_req["U_DifferenceAmt"],
                    'CostingCode' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                    'CostingCode3' => $array_budget["U_SubClassCode"],
                    'CostingCode4' => $array_budget["U_SubClass2Code"],
                    'AdditionalReference' => 'Realization Difference',
                    'CashFlowAssignments' => [
                        [
                            "AmountLC" => $array_req["U_DifferenceAmt"]
                        ]
                    ]
                ]);

                array_push($journalEntryInput["JournalEntryLines"], (array)[
                    'AccountCode' => '11720.2000', //UANG MUKA
                    'Credit' => $array_req["U_DifferenceAmt"],
                    'CostingCode' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                    'CostingCode3' => $array_budget["U_SubClassCode"],
                    'CostingCode4' => $array_budget["U_SubClass2Code"],
                    'AdditionalReference' => 'Realization Difference',
                ]);
            }

            $journalEntryPreInput = [];
            $sum_fee = 0;

            for ($i = 0; $i < count($array_req["REALIZATIONREQLINESCollection"]); $i++)
            {
                $sum_fee += $array_req["REALIZATIONREQLINESCollection"][$i]["U_Amount"];
                if($array_req["REALIZATIONREQLINESCollection"][$i]["U_NPWP"] > 0){

                    array_push($journalEntryPreInput, (array)[

                        "NPWP" => $array_req["REALIZATIONREQLINESCollection"][$i]["U_NPWP"],
                        "Amount" => round($array_req["REALIZATIONREQLINESCollection"][$i]["U_Amount"]),
                        "PaymentFor" => "Fee",
                        "Account" => $array_req["REALIZATIONREQLINESCollection"][$i]["U_AccountCode"]

                    ]);

                    array_push($journalEntryPreInput, (array)[

                        "NPWP" => $array_req["REALIZATIONREQLINESCollection"][$i]["U_NPWP"],
                        "Amount" =>  round($array_req["REALIZATIONREQLINESCollection"][$i]["U_Amount"] * ($array_req["REALIZATIONREQLINESCollection"][$i]["U_NPWP"] /100)),
                        "PaymentFor" => "Tax",
                        "Account" => "21310.0000"

                    ]);

                }else{

                    array_push($journalEntryInput["JournalEntryLines"], (array)[
                        'AccountCode' => $array_req["REALIZATIONREQLINESCollection"][$i]["U_AccountCode"],
                        'Debit'=> $array_req["REALIZATIONREQLINESCollection"][$i]["U_Amount"],
                        'CostingCode' => $array_budget["U_PillarCode"],
                        'ProjectCode' => $array_budget["U_ProjectCode"],
                        'CostingCode2' => $array_budget["U_ClassificationCode"],
                        'CostingCode3' => $array_budget["U_SubClassCode"],
                        'CostingCode4' => $array_budget["U_SubClass2Code"],
                        'AdditionalReference' => $array_req["REALIZATIONREQLINESCollection"][$i]["U_Description"],
                    ]);

                }


            }

            $taxes = array();
            $sum_all_taxes = 0;

            if(count($journalEntryPreInput) > 0){

                $groupbytaxes = array_reduce($journalEntryPreInput, function($taxes, $outgoing){
                    if(!isset($taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']])){
                        $taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']] = ['PaymentFor'=> $outgoing['PaymentFor']." ".$outgoing['NPWP']."%",'Amount'=>$outgoing['Amount']];
                    }
                    else {
                        $taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']]["Amount"] += $outgoing['Amount'];
                    }
                    return $taxes;
                });


               foreach($groupbytaxes as $index => $value){

                    foreach($value as $key => $val){

                        foreach($val as $k => $v){

                            if($k == '21310.0000'){
                                $sum_all_taxes += $v["Amount"];
                                array_push($journalEntryInput["JournalEntryLines"], (array)[
                                    'AccountCode' => $k,
                                    'Credit'=> $v["Amount"],
                                    'AdditionalReference'=> $v["PaymentFor"],
                                    'CostingCode' => $array_budget["U_PillarCode"],
                                    'ProjectCode' => $array_budget["U_ProjectCode"],
                                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                                    'CostingCode3' => $array_budget["U_SubClassCode"],
                                    'CostingCode4' => $array_budget["U_SubClass2Code"]

                                ]);
                            }else{

                                array_push($journalEntryInput["JournalEntryLines"], (array)[
                                    'AccountCode' => $k,
                                    'Debit'=> $v["Amount"],
                                    'AdditionalReference'=> $v["PaymentFor"],
                                    'CostingCode' => $array_budget["U_PillarCode"],
                                    'ProjectCode' => $array_budget["U_ProjectCode"],
                                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                                    'CostingCode3' => $array_budget["U_SubClassCode"],
                                    'CostingCode4' => $array_budget["U_SubClass2Code"]

                                ]);
                            }

                        }

                    }

               }

            }

            array_push($journalEntryInput["JournalEntryLines"], (array)[
                'AccountCode' => '11720.2000', //UANG MUKA
                'Credit' => $sum_fee - $sum_all_taxes,
                'CostingCode' => $array_budget["U_PillarCode"],
                'ProjectCode' => $array_budget["U_ProjectCode"],
                'CostingCode2' => $array_budget["U_ClassificationCode"],
                'CostingCode3' => $array_budget["U_SubClassCode"],
                'CostingCode4' => $array_budget["U_SubClass2Code"],
                'AdditionalReference'=> 'Uang Muka',
            ]);



            $account_array = [];
            foreach ($journalEntryInput["JournalEntryLines"] as $value) {
                if($value["AccountCode"]){
                 array_push($account_array,$value["AccountCode"]);
                }
            };

            $unique_account = [];

            foreach($account_array as $value){
                if (!in_array($value, $unique_account))
                    $unique_account[] = $value;
            }



            $accounts = $this->sap->getService('ChartOfAccounts');
            $get_account_names = $accounts->queryBuilder()
                ->select('Code,Name')
                ->where([new InArray("Code", $unique_account)])
                ->findAll();
            $account_name_array = json_decode(json_encode($get_account_names), true);
            $accounts = [];
            foreach($account_name_array["value"] as $account){
                $accounts[$account['Code']] = $account['Name'];
            };


            $budgetUsed = [];
            foreach($journalEntryInput["JournalEntryLines"] as $index => $value){
                // if($value["AccountCode"] == $array_req["U_RealTrfBank"]){
                //     array_push($budgetUsed, (array)[
                //         "U_Amount" => $value["Credit"]* -1,
                //         "U_Source" => "Advance Request Return",
                //         "U_DocNum" => $array_req["Code"],
                //         "U_UsedBy" => $array_req["U_RequestorName"],
                //         "U_AccountCode" => '11720.2000', // UANG MUKA OPERASIONAL
                //         "U_AccountName" => $accounts[$value["AccountCode"]]
                // ]);

                // }else{
                    if(isset($value["Debit"]) &&  ($value["Debit"] > 0) && (!str_starts_with($value["AccountCode"], '1112')) && ($value["AccountCode"] != '11720.2000')){
                        if($value["AccountCode"]){
                            if($value["Debit"] - $advance_groupbyaccount[$value["AccountCode"]] != 0){
                                array_push($budgetUsed, (array)[
                                    "U_Amount" =>  $value["Debit"] - $advance_groupbyaccount[$value["AccountCode"]],
                                    "U_Source" => "Advance Realization ".$code,
                                    "U_DocNum" => $array_req["Code"],
                                    "U_UsedBy" => $array_req["U_RequestorName"],
                                    "U_AccountCode" => $value["AccountCode"],
                                    "U_AccountName" => $accounts[$value["AccountCode"]]
                                ]);
                            }
                        }
                    }
                // }
            };

            $advance_request = $this->sap->getService('AdvanceReq');

            $result = $advance_request->update($code, [
                'U_RealConfirmAt' => date("Y-m-d"),
                'U_RealiStatus' => 6,
                'U_RealConfirmBy' => $user->name
            ]);

            $BudgetReq = $this->sap->getService('BudgetReq');
            $result = $journal_entry->create($journalEntryInput);
            if($result){
                $result = $BudgetReq->update($budgetCode, [
                    "BUDGETUSEDCollection" => $budgetUsed
                ]);

                if($result == 1){
                    $result = $advance_request->queryBuilder()->select("*")->find($code);
                }
            }

            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }



    }

    public function getAdvanceRequestById(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }

            $advanceReq = $this->sap->getService('AdvanceReq');
            $result = $advanceReq->queryBuilder()
                ->select('*')
                ->find($request->code); // DocEntry value
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }



    public function approveAR(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $user = Auth::user();
            $advance_request = $this->sap->getService('AdvanceReq');
            $code = $request->get('oProperty')["Code"];

            if($user["role_id"] == 4){
                $result = $advance_request->update($code, [
                    'U_Status' => 3,
                    'U_DirectorApp'=> $user->name,
                    'U_DirectorAppAt' => date("Y-m-d")
                ]);
            }

            elseif ($user["role_id"] == 5) {
                $result = $advance_request->update($code, [
                    'U_Status' => 2,
                    'U_ManagerApp'=> $user->name,
                    'U_ManagerAppAt' => date("Y-m-d")
                ]);

            }

            if($result == 1){
                $result = $advance_request->queryBuilder()
                ->select('*')->find($code);
            };
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }

    public function saveAR(Request $request)
    {

       try{
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
            $user = Auth::user();
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $result = $AdvanceReq->update($code,$request->get('data'),false);
            return $result;

       }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
       }

    }

    public function rejectAR(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $user = Auth::user();
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $code = $request->Code;
            $remarks = $request->Remarks;
            $result = $AdvanceReq->update($code, [
                'U_Remarks' => $remarks,
                'U_Status' => 4,
                'U_RejectedBy' => $user->name
            ]);
            $result = $AdvanceReq->queryBuilder()->select("*")->find($code);
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function rejectAdvanceRealization(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $user = Auth::user();
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $code = $request->Code;
            $remarks = $request->Remarks;
            $result = $AdvanceReq->update($code, [
                'U_RealizationRemarks' => $remarks,
                'U_RealiStatus' => 5,
                'U_RejectedBy' => $user->name
            ]);
            if($result == 1){
                $result = $AdvanceReq->queryBuilder()->select("*")->find($code);
            }
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function resubmitAR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();

        try{

            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $inputArray = $request->get('data');
            $code = $inputArray["Code"];
            $inputArray["U_Status"] = 1;
            $result = $AdvanceReq->update($code,$inputArray,false);
            if($result == 1){
                $result = $AdvanceReq->queryBuilder()->select("*")->find($code);
            }
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function resubmitRealization(Request $request)
    {
        try{
            $json = json_encode($request->all());

            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }
            $user = Auth::user();
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $inputArray = $request->get('data');
            $inputArray["U_RealiStatus"] = 2;
            $result = $AdvanceReq->update($code,$inputArray,false);
            if($result == 1){
                $result = $AdvanceReq->queryBuilder()->select("*")->find($code);
            }
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function submitAdvanceRealization(Request $request)
    {
        try{
            $array_req = $request->get('oProperty');
            $code = $array_req["Code"];
            $array_req["U_RealiStatus"] = 2;

            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }
            $advance_request = $this->sap->getService('AdvanceReq');
            $result = $advance_request->update($code, $array_req);
            if($result == 1){
                $result = $advance_request->queryBuilder()->select("*")->find($code);
            }
            return $array_req;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function approveAdvanceRealization(Request $request)
    {
        try{
            $user = Auth::user();
            $code = $request->get('Code');

            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }

            $advance_request = $this->sap->getService('AdvanceReq');

            if ($user["role_id"] == 5) {
                $result = $advance_request->update($code, [
                    'U_RealiStatus' => 3,
                    'U_ManagerRealApp'=> $user->name,
                    'U_ManagerRealAppAt' => date("Y-m-d")
                ]);
            }else{
                $result = $advance_request->update($code, [
                    'U_RealiStatus' => 4,
                    'U_DirectorRealApp'=> $user->name,
                    'U_DirectorRealAppAt' => date("Y-m-d")
                ]);
            }
            if($result == 1){
                $result = $advance_request->queryBuilder()->select("*")->find($code);
            }
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function metadata()
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('AdvanceReq');
        $metadata = $BudgetReq->getMetaData();
        return $metadata;

    }

    public function printAR(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get("company"));
            }

            $AdvanceReq = $this->sap->getService('AdvanceReq');

            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->find($request->get("code"));

            $array_ar = json_decode(json_encode($result), true);
            // return $array_ar;
            $account_array = [];
            $item_array = [];

            $find_budget = $this->sap->getService('BudgetReq');
            $get_budget = $find_budget->queryBuilder()
                        ->select('*')
                        ->find(''.($array_ar["U_BudgetCode"]).'');
            $array_budget = json_decode(json_encode($get_budget), true);

            $array_ar["U_Company"] = $array_budget["U_Company"];
            $array_ar["U_Pillar"] = $array_budget["U_Pillar"];
            $array_ar["U_Classification"] = $array_budget["U_Classification"];
            $array_ar["U_SubClass"] = $array_budget["U_SubClass"];
            $array_ar["U_SubClass2"] = $array_budget["U_SubClass2"];
            $array_ar["U_Project"] = $array_budget["U_Project"];
            $array_ar["BudgetName"] = $array_budget["Name"];

            // COLLECT COA AND ITEMS
            foreach ($array_ar["ADVANCEREQLINESCollection"] as $key => $value) {
                array_push($account_array,$value["U_AccountCode"]);
                if($value["U_ItemCode"] != ''){
                    array_push($item_array,$value["U_ItemCode"]);
                }
            };

            //GET COA NAMES
            $accounts = $this->sap->getService('ChartOfAccounts');
            $get_account_names = $accounts->queryBuilder()
            ->select('Code,Name')
            ->where([new InArray("Code", $account_array)])
            ->findAll();
            $account_name_array = json_decode(json_encode($get_account_names), true);
            $accounts = [];
            foreach($account_name_array["value"] as $account){
                $accounts[$account['Code']] = $account['Name'];
            };

            //GET ITEM NAMES
            $items = $this->sap->getService('Items');
            $get_item_names = $items->queryBuilder()
            ->select('ItemCode,ItemName')
            ->where([new InArray("ItemCode", $item_array)])
            ->findAll();
            $item_name_array = json_decode(json_encode($get_item_names), true);
            $items = [];
            foreach($item_name_array["value"] as $item){
                $items[$item['ItemCode']] = $item['ItemName'];
            };

            foreach ($array_ar["ADVANCEREQLINESCollection"] as $key => $value) {
                $array_ar["ADVANCEREQLINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
                if($value["U_ItemCode"] != ''){
                    $array_ar["ADVANCEREQLINESCollection"][$key]["ItemName"] = $items[$value["U_ItemCode"]];
                }else{
                    $array_ar["ADVANCEREQLINESCollection"][$key]["ItemName"] = '-';
                }
            };

            $view = \View::make('ar_pdf',['advance_request'=>$array_ar]);
            $html = $view->render();
            $filename = 'Advance Employee #'.$request->get("code");
            $pdf = new PDF2;

            $pdf::SetTitle('Advance Employee #'.$request->get("code"));
            $pdf::AddPage();
            $pdf::writeHTML($html, true, false, true, false, '');
            return base64_encode($pdf::Output($filename, 'S'));


        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function printRealization(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get("company"));
            }

            $AdvanceReq = $this->sap->getService('AdvanceReq');

            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->find($request->get("code"));

            $array_ar = json_decode(json_encode($result), true);
            // return $array_ar;
            $account_array = [];
            $item_array = [];

            $account_array_realiazation = [];
            $item_array_realization = [];

            $find_budget = $this->sap->getService('BudgetReq');
            $get_budget = $find_budget->queryBuilder()
                        ->select('*')
                        ->find(''.($array_ar["U_BudgetCode"]).'');
            $array_budget = json_decode(json_encode($get_budget), true);

            $array_ar["U_Company"] = $array_budget["U_Company"];
            $array_ar["U_Pillar"] = $array_budget["U_Pillar"];
            $array_ar["U_Classification"] = $array_budget["U_Classification"];
            $array_ar["U_SubClass"] = $array_budget["U_SubClass"];
            $array_ar["U_SubClass2"] = $array_budget["U_SubClass2"];
            $array_ar["U_Project"] = $array_budget["U_Project"];
            $array_ar["BudgetName"] = $array_budget["Name"];

            // COLLECT COA AND ITEMS
            foreach ($array_ar["ADVANCEREQLINESCollection"] as $key => $value) {
                array_push($account_array,$value["U_AccountCode"]);
                if($value["U_ItemCode"] != ''){
                    array_push($item_array,$value["U_ItemCode"]);
                }
            };

            foreach ($array_ar["REALIZATIONREQLINESCollection"] as $key => $value) {
                array_push($account_array_realiazation,$value["U_AccountCode"]);
                if($value["U_ItemCode"] != ''){
                    array_push($item_array,$value["U_ItemCode"]);
                }
            };

            //GET COA NAMES
            $accounts = $this->sap->getService('ChartOfAccounts');
            $get_account_names = $accounts->queryBuilder()
            ->select('Code,Name')
            ->where([new InArray("Code", $account_array)])
            ->findAll();
            $account_name_array = json_decode(json_encode($get_account_names), true);
            $accounts = [];
            foreach($account_name_array["value"] as $account){
                $accounts[$account['Code']] = $account['Name'];
            };

            //GET ITEM NAMES
            $items = $this->sap->getService('Items');
            $get_item_names = $items->queryBuilder()
            ->select('ItemCode,ItemName')
            ->where([new InArray("ItemCode", $item_array)])
            ->findAll();
            $item_name_array = json_decode(json_encode($get_item_names), true);
            $items = [];
            foreach($item_name_array["value"] as $item){
                $items[$item['ItemCode']] = $item['ItemName'];
            };

            foreach ($array_ar["ADVANCEREQLINESCollection"] as $key => $value) {
                $array_ar["ADVANCEREQLINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
                if($value["U_ItemCode"] != ''){
                    $array_ar["ADVANCEREQLINESCollection"][$key]["ItemName"] = $items[$value["U_ItemCode"]];
                }else{
                    $array_ar["ADVANCEREQLINESCollection"][$key]["ItemName"] = '-';
                }
            };

            foreach ($array_ar["REALIZATIONREQLINESCollection"] as $key => $value) {
                $array_ar["REALIZATIONREQLINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
                if($value["U_ItemCode"] != ''){
                    $array_ar["REALIZATIONREQLINESCollection"][$key]["ItemName"] = $items[$value["U_ItemCode"]];
                }else{
                    $array_ar["REALIZATIONREQLINESCollection"][$key]["ItemName"] = '-';
                }
            };

            $view = \View::make('realization_pdf',['advance_request'=>$array_ar]);
            $html = $view->render();
            $filename = 'Realization Advance Employee #'.$request->get("code");
            $pdf = new PDF2;

            $pdf::SetTitle('Realization Advance Employee #'.$request->get("code"));
            $pdf::AddPage();
            $pdf::writeHTML($html, true, false, true, false, '');
            return base64_encode($pdf::Output($filename, 'S'));


        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function getSession($company)
    {
        $config = [
            "https" => true,
            "host" => env('SAP_URL'),
            "port" => 50000,
            "version" => 2,
            "sslOptions" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false
            ]
        ];
        try{
            if($company != 'TEST_DERIL'){
                $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
            }else{
                $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company);
            }
            $this->sap = $sap;
            return $sap;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }
}
