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
    public function getMaterialRequests()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('MaterialReq');
        $BudgetReq->headers(['OData-Version' => '4.0']);
        if ($user["role_id"] == 3) {
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (int) $user["id"]))
                ->findAll();
        }else{
            $result = $BudgetReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->findAll();
        }


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
        $purchaseReqInput = array();
        $purchaseReqInput["RequriedDate"] = $request_array["CreateDate"];
        $purchaseReqInput["DocumentLines"] = $request_array["METERIALREQLINESCollection"];

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budgets = $this->sap->getService('MaterialReq');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $budgets->update($code, [
                'U_Status' => 2
            ]);

        }
        else{
            $result = $budgets->update($code, [
                'U_Status' => 3
            ]);
            if($result == 1){
                $purchase_req = $this->sap->getService('PurchaseRequests');
                $result = $purchase_req->create($purchaseReqInput);
            }
        }
        return $result;

    }

    public function rejectMR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budgets = $this->sap->getService('MaterialReq');
        $code = $request->Code;
        $result = $budgets->update($code, [
            'U_Status' => 4
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
        $sap = SAPClient::createSession($config, "manager", "1234", "POS_29JUN");
        $this->sap = $sap;
        return $sap;
    }
}
