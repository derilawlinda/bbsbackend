<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Session;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use App\Libraries\SAPb1\Filters\Contains;
use Illuminate\Support\Facades\Auth;

class MaterialRequestController extends Controller
{
    private $sapsession;
    private $sap;

    public function createMaterialRequest(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $MaterialReq = $this->sap->getService('MaterialReq');

        $count = $MaterialReq->queryBuilder()->count();
        $request["Code"] = 60000001 + $count;
        $request["U_CreatedBy"] = (int)$user->id;
        $request["U_RequestorName"] = $user->name;

        $result = $MaterialReq->create($request->all());
        return $result;
    }
    public function getMaterialRequests(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $search = "";
        if($request->search){
            $search = $request->search;
        }

        $MaterialReq = $this->sap->getService('MaterialReq');
        $MaterialReq->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);
        if ($user["role_id"] == 3) {
            $result = $MaterialReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (int) $user["id"]))
                ->where(new Contains("Code", $search))
                ->orWhere(new Contains("Name",$search))
                ->inlineCount();

        }else{
            $result = $MaterialReq->queryBuilder()
            ->select('*')
            ->where(new Contains("Code", $search))
            ->orWhere(new Contains("Name",$search))
            ->orderBy('Code', 'desc')
            ->inlineCount();

        }

        if($request->top){
            $top = $request->top;
        }else{
            $top = 20;
        }

        if($request->skip){
            $skip = $request->skip;
        }else{
            $skip = 0;
        }

        $result = $result->limit($top,$skip)->findAll();


        return $result;
    }

    public function getMaterialRequestById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $budgets = $this->sap->getService('MaterialReq');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function approveMR(Request $request)
    {
        $json = json_encode($request->all());
        $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
        $jsonString = str_replace(utf8_encode("U_Qty"),"Quantity",$jsonString);
        $request_array = json_decode($jsonString,true);
        $array_req = $request->all();
        $code = $array_req["Code"];
        $budgetCode = (string)$array_req["U_BudgetCode"];

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budget = $this->sap->getService('BudgetReq');
        $mrbudget = $budget->queryBuilder()
            ->select('*')
            ->find($budgetCode); // DocEntry value
        $array_budget = json_decode(json_encode($mrbudget), true);


        $MaterialReq = $this->sap->getService('MaterialReq');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $MaterialReq->update($code, [
                'U_Status' => 2
            ]);

        }
        else{
            $result = $MaterialReq->update($code, [
                'U_Status' => 3
            ]);
            if($result == 1){

                for($i = 0; $i < count($request_array["MATERIALREQLINESCollection"]); ++$i) {
                    $request_array["MATERIALREQLINESCollection"][$i]['ProjectCode'] = $array_budget["U_ProjectCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['U_H_NO_BUDGET'] = $request_array["U_BudgetCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode'] = $array_budget["U_PillarCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode2'] = $array_budget["U_ClassificationCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode3'] = $array_budget["U_SubClassCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode4'] = $array_budget["U_SubClass2Code"];
                }
                $purchaseReqInput = array(
                    "RequriedDate" => $request_array["CreateDate"],
                    'DocumentLines' => $request_array["MATERIALREQLINESCollection"],
                    "U_H_NO_MR" => $request_array["Code"],
                    "U_H_NO_BUDGET" => $request_array["U_BudgetCode"],
                    'Project' => $array_budget["U_ProjectCode"]
                );
                $purchase_req = $this->sap->getService('PurchaseRequests');
                $result = $purchase_req->create($purchaseReqInput);

            }
        }
        return $result;

    }

    public function saveMR(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $MaterialReq = $this->sap->getService('MaterialReq');
        $MaterialReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $MaterialReq->update($code,$request->all(),false);
        return $result;

    }

    public function resubmitMR(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $MaterialReq = $this->sap->getService('MaterialReq');
        $MaterialReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $request["U_Status"] = 1;
        $result = $MaterialReq->update($code,$request->all(),false);
        return $result;

    }



    public function rejectMR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budgets = $this->sap->getService('MaterialReq');
        $remarks = $request->Remarks;
        $code = $request->Code;
        $result = $budgets->update($code, [
            'U_Remarks' => $remarks,
            'U_Status' => 4,
            'U_RejectedBy' => $user->name
        ]);
        return $result;

    }

    public function metadata()
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('BudgetReq');
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
