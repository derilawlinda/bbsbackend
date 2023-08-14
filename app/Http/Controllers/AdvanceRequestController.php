<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Session;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use Illuminate\Support\Facades\Auth;

class AdvanceRequestController extends Controller
{
    private $sapsession;
    private $sap;

    public function createAdvanceRequest(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $AdvanceRequest = $this->sap->getService('AdvanceReq');

        $count = $AdvanceRequest->queryBuilder()->count();
        $request["Code"] = 80000001 + $count;
        $request["U_CreatedBy"] = (int)$user->id;
        $request["U_RequestorName"] = $user->name;

        $result = $AdvanceRequest->create($request->all());
        return $result;
    }
    public function getAdvanceRequests()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $AdvanceReq = $this->sap->getService('AdvanceReq');

        // $result = $AdvanceReq->queryBuilder()
        //         ->select('*')
        //         ->findAll();
        if ($user["role_id"] == 3) {
            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (string)$user["id"]))
                ->findAll();
        }else{
            $result = $AdvanceReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->findAll();
        }


        return $result;
    }

    public function getAdvanceRealizations()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $AdvanceReq = $this->sap->getService('AdvanceReq');

        // $result = $AdvanceReq->queryBuilder()
        //         ->select('*')
        //         ->findAll();
        if ($user["role_id"] == 3) {
            $result = $AdvanceReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (string)$user["id"]))
                ->findAll();
        }else{

            $result = $AdvanceReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->where(new Equal("U_Status", 3))
            ->findAll();
        }


        return $result;
    }

    public function getAdvanceRequestById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $budgets = $this->sap->getService('AdvanceReq');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function approveAR(Request $request)
    {
        $json = json_encode($request->all());
        $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
        $jsonString = str_replace(utf8_encode("U_Amount"),"Amount",$jsonString);
        $jsonString = str_replace(utf8_encode("U_AccountCode"),"AccountCode",$jsonString);
        $request_array = json_decode($jsonString,true);
        $array_req = $request->all();
        $code = $array_req["Code"];
        $outgoingPaymentInput = array();
        // $outgoingPaymentInput["U_H_NO_BUDGET"] = $request_array["U_BudgetCode"];
        $outgoingPaymentInput["PaymentAccounts"] = [];
        $outgoingPaymentInput["TransferAccount"] = '11120.1001';
        $outgoingPaymentInput["DocType"] = 'rAccount';
        $outgoingPaymentInput["DocCurrency"] = 'IDR';
        $outgoingPaymentInput["TransferSum"] = $array_req["U_Amount"];

        for ($i = 0; $i < count($request_array["ADVANCEREQLINESCollection"]); $i++)
        {

            array_push($outgoingPaymentInput["PaymentAccounts"], (object)[
                'AccountCode' => '11720.2000',
                'SumPaid' => $request_array["ADVANCEREQLINESCollection"][$i]["Amount"],
                // 'ProfitCenter' => $request_array["budgeting"]["U_Pillar"],
                // 'ProjectCode' => $request_array["budgeting"]["U_Project"],
                // "ProfitCenter2" => $request_array["budgeting"]["U_Classification"],
                // "ProfitCenter3" => $request_array["budgeting"]["U_SubClass"],
                // "ProfitCenter4" => $request_array["budgeting"]["U_SubClass2"],

            ]);
        }

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $advance_request = $this->sap->getService('AdvanceReq');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $advance_request->update($code, [
                'U_Status' => 2
            ]);

        }
        else{
            $result = $advance_request->update($code, [
                'U_Status' => 3
            ]);
            if($result == 1){
                $outgoing_payment = $this->sap->getService('VendorPayments');
                $result = $outgoing_payment->create($outgoingPaymentInput);
            }
        }
        return $outgoingPaymentInput;

    }

    public function saveAR(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $AdvanceReq->update($code,$request->all(),false);
        return $result;

    }

    public function rejectAR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
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
            $this->sap = $this->getSession();
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
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $request["U_Status"] = 1;
        $result = $AdvanceReq->update($code,$request->all(),false);
        return $result;
    }

    public function resubmitRealization(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $request["U_RealiStatus"] = 1;
        $result = $AdvanceReq->update($code,$request->all(),false);
        return $result;
    }

    public function submitAdvanceRealization(Request $request)
    {
        $array_req = $request->all();
        $code = $array_req["Code"];
        $array_req["U_RealiStatus"] = 2;

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $advance_request = $this->sap->getService('AdvanceReq');
        $result = $advance_request->update($code, $array_req);
        return $result;
    }

    public function approveAdvanceRealization(Request $request)
    {

        $array_req = $request->all();
        $code = $array_req["Code"];

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $advance_request = $this->sap->getService('AdvanceReq');
        $result = $advance_request->update($code, $array_req);
        if ($user["role_id"] == 5) {
            $array_req["U_RealiStatus"] = 3;
        }else{
            $array_req["U_RealiStatus"] = 4;
        }
        $result = $budgets->update($code, $array_req);
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

    public function getSession()
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
        $sap = SAPClient::createSession($config, "manager", "1234", env('SAP_DB'));
        $this->sap = $sap;
        return $sap;
    }
}
