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

class MaterialIssueController extends Controller
{
    private $sapsession;
    private $sap;

    public function createMaterialIssue(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $MaterialIssue = $this->sap->getService('MaterialIssue');

        $count = $MaterialIssue->queryBuilder()->count();
        $request["Code"] = 70000001 + $count;
        $request["U_CreatedBy"] = (int)$user->id;
        $request["U_RequestorName"] = $user->name;

        $result = $MaterialIssue->create($request->all());
        return $result;
    }
    public function getMaterialIssues()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('MaterialIssue');
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

    public function getMaterialIssueById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $budgets = $this->sap->getService('MaterialIssue');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function approveMI(Request $request)
    {
        $json = json_encode($request->all());
        $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
        $jsonString = str_replace(utf8_encode("U_Qty"),"Quantity",$jsonString);
        $jsonString = str_replace(utf8_encode("U_AccountCode"),"AccountCode",$jsonString);
        $request_array = json_decode($jsonString,true);
        $array_req = $request->all();
        $code = $array_req["Code"];
        $goodIssueInput = array();
        $goodIssueInput["U_H_NO_BUDGET"] = $request_array["U_BudgetCode"];
        $goodIssueInput["DocumentLines"] = $request_array["MATERIALISSUELINESCollection"];

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $material_request = $this->sap->getService('MaterialIssue');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $material_request->update($code, [
                'U_Status' => 2
            ]);

        }
        else{
            $result = $material_request->update($code, [
                'U_Status' => 3
            ]);
            if($result == 1){
                $good_issue = $this->sap->getService('InventoryGenExits');
                $result = $good_issue->create($goodIssueInput);
            }
        }
        return $result;

    }

    public function saveMI(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $MaterialIssue = $this->sap->getService('MaterialIssue');
        $MaterialIssue->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $MaterialIssue->update($code,$request->all(),false);
        return $result;

    }

    public function rejectMI(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budgets = $this->sap->getService('MaterialIssue');
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
        $BudgetReq = $this->sap->getService('MaterialIssue');
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
