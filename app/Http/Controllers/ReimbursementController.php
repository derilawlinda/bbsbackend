<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Session;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use App\Libraries\SAPb1\Filters\StartsWith;
use App\Libraries\SAPb1\Filters\InArray;
use App\Libraries\SAPb1\Filters\Contains;
use Illuminate\Support\Facades\Auth;

class ReimbursementController extends Controller
{
    private $sapsession;
    private $sap;

    public function createReimbursement(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }

        $Reimbursement = $this->sap->getService('ReimbursementReq');

        $count = $Reimbursement->queryBuilder()->count();


        $result = $Reimbursement->create($request->get('oProperty') + [
            'Code' => 90000001 + $count,
            'U_CreatedBy' => (int)$user->id,
            'U_RequestorName' => $user->name
        ]);
        return $result;
    }

    public function getReimbursements(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];

        $Reimbursement = $this->sap->getService('ReimbursementReq');
        $Reimbursement->headers(['OData-Version' => '4.0',
        'Prefer' => 'odata.maxpagesize=500']);
        if ($user["role_id"] == 3) {
            $result = $Reimbursement->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (int) $user["id"]));
        }elseif($user["role_id"] == 2){
            $result = $Reimbursement->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_Status", 3))
                ->orWhere(new Equal("U_Status", 5));
        }
        elseif($user["role_id"] == 4){
            $result = $Reimbursement->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_Status", 2))
                ->orWhere(new Equal("U_Status", 3));
        }
        else{
            $result = $Reimbursement->queryBuilder()
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
            $top = 1000000;
        }

        if($request->skip){
            $skip = $request->skip;
        }else{
            $skip = 0;
        }

        $result = $result->limit($top,$skip)->orderBy('Code', 'desc')->inlineCount()->findAll();

        return $result;
    }

    public function getReimbursementById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $reimbursement = $this->sap->getService('ReimbursementReq');

        $result = $reimbursement->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function transferReimbursement(Request $request)
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
            $outgoingPaymentInput["TransferSum"] = floatval($array_req["U_TotalAmount"]) + floatval($bank_adm);
            $outgoingPaymentInput["U_H_NO_REIMBURSE"] = $array_req["Code"];
            $outgoingPaymentInput["DocDate"] = $array_req["U_DisbursedAt"];

            if($bank_adm > 0){
                array_push($outgoingPaymentInput["PaymentAccounts"], (object)[
                    'AccountCode' => '80100.0100',
                    'Decription' => 'Bank Admin',
                    'SumPaid' => floatval($bank_adm),
                    'ProfitCenter' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    "ProfitCenter2" => $array_budget["U_ClassificationCode"],
                    "ProfitCenter3" => $array_budget["U_SubClassCode"],
                    "ProfitCenter4" => $array_budget["U_SubClass2Code"],

                ]);

            }


            for ($i = 0; $i < count($array_req["REIMBURSEMENTLINESCollection"]); $i++)
            {

                array_push($outgoingPaymentInput["PaymentAccounts"], (object)[
                    'AccountCode' => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_AccountCode"],
                    'SumPaid' => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_Amount"],
                    'Decription' => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_Description"],
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
            $ReimbursementReq = $this->sap->getService('ReimbursementReq');
            $code = $array_req["Code"];
            $disbursed_date = $array_req["DisbursedDate"];
            $result = $ReimbursementReq->update($code, [
                'U_DisbursedAt' => $array_req["DisbursedDate"],
                'U_Status' => 5,
                'U_TransferBy' => $user->name
            ]);
            if($result == 1){

                $BudgetReq = $this->sap->getService('BudgetReq');
                $result = $BudgetReq->update($budgetCode, [
                    "BUDGETUSEDCollection" => [
                        [
                            "U_Amount" => floatval($array_req["U_TotalAmount"]) + floatval($bank_adm),
                            "U_Source" => "Reimbursement Request",
                            "U_DocNum" => $array_req["Code"],
                            "U_UsedBy" => $array_req["U_RequestorName"]
                        ]
                    ]
                ]);

            }
        }
        return $outgoingResult;
    }

    public function approveReimbursement(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $reimbursement = $this->sap->getService('ReimbursementReq');
        $code = $request->get('oProperty')["Code"];
        if ($user["role_id"] == 5) {
            $result = $reimbursement->update($code, [
                'U_Status' => 2,
                'U_ManagerApp'=> $user->name,
                'U_ManagerAppAt' => date("Y-m-d")
            ]);
        }else{
            $result = $reimbursement->update($code, [
                'U_Status' => 3,
                'U_DirectorApp'=> $user->name,
                'U_DirectorAppAt' => date("Y-m-d")
            ]);
        }
        return $result;

    }

    public function saveReimbursement(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $ReimbursementReq = $this->sap->getService('ReimbursementReq');
        $ReimbursementReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];
        $result = $ReimbursementReq->update($code,$request->get('data'),false);
        return $result;

    }

    public function sapeReimbursement(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $ReimbursementReq = $this->sap->getService('ReimbursementReq');
        $ReimbursementReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];
        $result = $ReimbursementReq->update($code,$request->get('data'),false);
        return $result;
    }

    public function rejectReimbursement(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $user = Auth::user();
        $reimbursement = $this->sap->getService('ReimbursementReq');
        $remarks = $request->Remarks;
        $code = $request->Code;
        $result = $reimbursement->update($code, [
            'U_RejRemarks' => $remarks,
            'U_Status' => 4,
            'U_RejectedBy' => $user->name
        ]);
        return $result;

    }

    public function resubmitReimbursement(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $MaterialReq = $this->sap->getService('ReimbursementReq');
        $MaterialReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];

        $inputArray = $request->get('data');
        $inputArray["U_Status"] = 1;
        $result = $MaterialReq->update($code,$inputArray,false);
        return $result;

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
        if($company != 'TEST_DERIL'){
            $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
        }
        $this->sap = $sap;
        return $sap;
    }
}
