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

class AdvanceRequestController extends Controller
{
    private $sapsession;
    private $sap;

    public function createAdvanceRequest(Request $request)
    {
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
    public function getAdvanceRequests(Request $request)
    {
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
                ->where(new Equal("U_CreatedBy", (string)$user["id"]));
        }elseif($user["role_id"] == 2){
            $result = $AdvanceReq->queryBuilder()
            ->select('*')
            ->where(new Equal("U_Status", 3))
            ->orWhere(new Equal("U_Status", 5));
        }
        else{
            $result = $AdvanceReq->queryBuilder()
            ->select('*');
        }

        if($request->search){
            $search = $request->search;
            $result->where(new Contains("Code", $search))
                    ->orWhere(new Contains("Name",$search));
        }

        if($request->status){
            $req_status_array = preg_split ("/\,/", $request->status);
            foreach ($req_status_array as $value) {
                array_push($status_array,(int)$value);
            }
            $result->where(new InArray("U_Status", $status_array));
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

    public function getAdvanceRealizations(Request $request)
    {
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
                ->where(new Equal("U_CreatedBy", (string)$user["id"]))
                ->where(new Equal("U_Status", 5));
        }elseif ($user["role_id"] == 2) {
            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->where(new Equal("U_RealiStatus", 4))
                ->orWhere(new Equal("U_RealiStatus", 6));
        }else{

            $result = $AdvanceReq->queryBuilder()
            ->select('*')
            ->where(new Equal("U_Status", 3))
            ->orWhere(new Equal("U_Status", 5));
        }

        if($request->search){
            $search = $request->search;
            $result->where(new Contains("Code", $search))
                    ->orWhere(new Contains("Name",$search));
        }

        if($request->status){
            $req_status_array = preg_split ("/\,/", $request->status);
            foreach ($req_status_array as $value) {
                array_push($status_array,(int)$value);
            }
            $result->where(new InArray("U_RealiStatus", $status_array));
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



    public function transferAR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }

        $user = Auth::user();

        $array_req = $request->get('oProperty');
        $code = $array_req["Code"];
        $budgetCode = (string)$array_req["U_BudgetCode"];

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
            $outgoingPaymentInput["TransferAccount"] = '11120.1001';
            $outgoingPaymentInput["DocType"] = 'rAccount';
            $outgoingPaymentInput["DocCurrency"] = 'IDR';
            $outgoingPaymentInput["TransferSum"] = floatval($array_req["U_Amount"]) + floatval($bank_adm);
            $outgoingPaymentInput["DocDate"] = $array_req["U_DisbursedAt"];
            $outgoingPaymentInput["U_H_NO_ADV"] = $array_req["Code"];

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

        }catch(Exception $e) {

            return response()->json(['message' => 'Error inserting data to SAP'], 500);

        };


        if($outgoingResult){

            $outgoingArray = json_decode(json_encode($outgoingResult), true);
            $AdvanceReq = $this->sap->getService('AdvanceReq');
            $result = $AdvanceReq->update($code, [
                'U_DisbursedAt' => $array_req["DisbursedDate"],
                'U_Status' => 5,
                'U_TransferBy' => $user->name
            ]);
            if($result == 1){

                $BudgetReq = $this->sap->getService('BudgetReq');
                $result = $BudgetReq->update($budgetCode, [
                    "BUDGETUSEDCollection" => [
                        [
                            "U_Amount" => floatval($array_req["U_Amount"]) + floatval($bank_adm),
                            "U_Source" => "Advance Request",
                            "U_DocNum" => $array_req["Code"],
                            "U_UsedBy" => $array_req["U_RequestorName"]
                        ]
                    ]
                ]);

            }
        }
        return $outgoingResult;
    }

    public function confirmAdvanceRealization(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }

        $user = Auth::user();
        $array_req = $request->get('oProperty');

        $budgetCode = (string)$array_req["U_BudgetCode"];
        $budget = $this->sap->getService('BudgetReq');
        $arbudget = $budget->queryBuilder()
            ->select('*')
            ->find($budgetCode); // DocEntry value
        $array_budget = json_decode(json_encode($arbudget), true);

        if($array_req["U_DifferenceAmt"] > 0){


            try {
            $incomingPaymentInput = array();
            $incomingPaymentInput["PaymentAccounts"] = [];
            $incomingPaymentInput["ReferenceDate"] = $array_req["U_RealizationDate"];
            $incomingPaymentInput["TransferAccount"] = $array_req["U_TransferFrom"];
            $incomingPaymentInput["DocType"] = 'rAccount';
            $incomingPaymentInput["DocCurrency"] = 'IDR';
            $incomingPaymentInput["TransferSum"] = $array_req["U_RealizationAmt"];
            $incomingPaymentInput["U_H_NO_ADV"] = $array_req["Code"];
            $incomingPaymentInput["Remarks"] = "Advance Realization ".$array_req["Code"];

            array_push($incomingPaymentInput["PaymentAccounts"], (object)[
                'AccountCode' => '11720.2000',
                'SumPaid' => $array_req["U_DifferenceAmt"],
                'ProfitCenter' => $array_budget["U_PillarCode"],
                'ProjectCode' => $array_budget["U_ProjectCode"],
                "ProfitCenter2" => $array_budget["U_ClassificationCode"],
                "ProfitCenter3" => $array_budget["U_SubClassCode"],
                "ProfitCenter4" => $array_budget["U_SubClass2Code"]
            ]);

            $incoming_payment = $this->sap->getService('IncomingPayments');
            $incomingResult = $incoming_payment->create($incomingPaymentInput);

            if($incomingResult){

                $BudgetReq = $this->sap->getService('BudgetReq');
                $result = $BudgetReq->update($budgetCode, [
                    "BUDGETUSEDCollection" => [
                        [
                            "U_Amount" => $array_req["U_DifferenceAmt"] * -1,
                            "U_Source" => "Advance Request Return",
                            "U_DocNum" => $array_req["Code"],
                            "U_UsedBy" => $array_req["U_RequestorName"]
                        ]
                    ]
                ]);

            }

            }catch(Exception $e) {

                return response()->json(['message' => 'Error inserting data to SAP'], 500);

            };


        }

        $journal_entry = $this->sap->getService('JournalEntries');
        $journalEntryInput = array();
        $journalEntryInput["JournalEntryLines"] = [];
        $journalEntryInput["Memo"] = "Advance Realization ".$array_req["Code"];;

        array_push($journalEntryInput["JournalEntryLines"], (object)[
            'AccountCode' => '11120.1001', //BANK BCA
            'Credit' => $array_req["U_RealizationAmt"],
            'CostingCode' => $array_budget["U_PillarCode"],
            'ProjectCode' => $array_budget["U_ProjectCode"],
            'CostingCode2' => $array_budget["U_ClassificationCode"],
            'CostingCode3' => $array_budget["U_SubClassCode"],
            'CostingCode4' => $array_budget["U_SubClass2Code"],
            'CashFlowAssignments' => [
                [
                    "AmountLC" => $array_req["U_RealizationAmt"]
                ]
            ]
        ]);

        for ($i = 0; $i < count($array_req["REALIZATIONREQLINESCollection"]); $i++)
        {
            array_push($journalEntryInput["JournalEntryLines"], (object)[
                'AccountCode' => $array_req["REALIZATIONREQLINESCollection"][$i]["U_AccountCode"],
                'Debit'=> $array_req["REALIZATIONREQLINESCollection"][$i]["U_Amount"],
                'CostingCode' => $array_budget["U_PillarCode"],
                'ProjectCode' => $array_budget["U_ProjectCode"],
                'CostingCode2' => $array_budget["U_ClassificationCode"],
                'CostingCode3' => $array_budget["U_SubClassCode"],
                'CostingCode4' => $array_budget["U_SubClass2Code"]
            ]);
        }

        $result = $journal_entry->create($journalEntryInput);
        $advance_request = $this->sap->getService('AdvanceReq');
        $array_req["U_RealiStatus"] = 6;
        $code = $array_req["Code"];
        $result = $advance_request->update($code, $array_req);
        return $array_req;

    }

    public function getAdvanceRequestById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }

        $budgets = $this->sap->getService('AdvanceReq');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }



    public function approveAR(Request $request)
    {

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


        return $result;

    }

    public function saveAR(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];
        $result = $AdvanceReq->update($code,$request->get('data'),false);
        return $result;

    }

    public function rejectAR(Request $request)
    {
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
        return $result;

    }

    public function rejectAdvanceRealization(Request $request)
    {
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
        return $result;
    }

    public function resubmitAR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $inputArray = $request->get('data');
        $code = $inputArray["Code"];
        $inputArray["U_Status"] = 2;
        $result = $AdvanceReq->update($code,$inputArray,false);
        return $result;
    }

    public function resubmitRealization(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];
        $inputArray = $request->get('data');
        $inputArray["U_RealiStatus"] = 1;
        $result = $AdvanceReq->update($code,$inputArray,false);
        return $result;
    }

    public function submitAdvanceRealization(Request $request)
    {
        $array_req = $request->get('oProperty');
        $code = $array_req["Code"];
        $array_req["U_RealiStatus"] = 2;

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $advance_request = $this->sap->getService('AdvanceReq');
        $result = $advance_request->update($code, $array_req);
        return $array_req;
    }

    public function approveAdvanceRealization(Request $request)
    {
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
        return $result;

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
        if(env('ENVIRONMENT') == "prod"){
            $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
        }else{
            $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), "TEST_DERIL");
        }
        $this->sap = $sap;
        return $sap;
    }
}
