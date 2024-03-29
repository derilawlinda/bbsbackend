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
            $this->sap = $this->getSession($request->get('company'));
        }

        $MaterialIssue = $this->sap->getService('MaterialIssue');

        $count = $MaterialIssue->queryBuilder()->count();

        $result = $MaterialIssue->create($request->get('oProperty') + [
            'Code' => 70000001 + $count,
            'U_CreatedBy' => (int)$user->id,
            'U_RequestorName' => $user->name
        ]);
        return $result;
    }
    public function getMaterialIssues(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $MaterialIssueReq = $this->sap->getService('MaterialIssue');
        $MaterialIssueReq->headers(['OData-Version' => '4.0']);
        if ($user["role_id"] == 3) {
            $result = $MaterialIssueReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (int) $user["id"]))
                ->findAll();
        }else{
            $result = $MaterialIssueReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->findAll();
        }


        return $result;
    }

    public function getMaterialIssueById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }

        $budgets = $this->sap->getService('MaterialIssue');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function approveMI(Request $request)
    {
        $json = json_encode($request->get('oProperty'));
        $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
        $jsonString = str_replace(utf8_encode("U_Qty"),"Quantity",$jsonString);
        $jsonString = str_replace(utf8_encode("U_AccountCode"),"AccountCode",$jsonString);
        $request_array = json_decode($jsonString,true);
        $code = $request_array["Code"];
        $budgetCode = (string)$request_array["U_BudgetCode"];
        // $goodIssueInput = array();
        // $goodIssueInput["DocDate"] = $request_array["U_DocDate"];
        // $goodIssueInput["U_H_NO_BUDGET"] = $request_array["U_BudgetCode"];
        // $goodIssueInput["DocumentLines"] = $request_array["MATERIALISSUELINESCollection"];
        $mrbudget = $budget->queryBuilder()
            ->select('*')
            ->find($budgetCode); // DocEntry value
         $array_budget = json_decode(json_encode($mrbudget), true);
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $user = Auth::user();
        $material_request = $this->sap->getService('MaterialIssue');

        if ($user["role_id"] == 5) {
            $result = $material_request->update($code, [
                'U_Status' => 2,
                'U_ManagerApp'=> $user->name,
                'U_ManagerAppAt' => date("Y-m-d")
            ]);

        }
        else{
            $result = $material_request->update($code, [
                'U_Status' => 3,
                'U_DirectorApp'=> $user->name,
                'U_DirectorAppAt' => date("Y-m-d")
            ]);
            if($result == 1){
                for($i = 0; $i < count($request_array["MATERIALISSUEINESCollection"]); ++$i) {
                    $request_array["MATERIALISSUEINESCollection"][$i]['ProjectCode'] = $array_budget["U_ProjectCode"];
                    $request_array["MATERIALISSUEINESCollection"][$i]['U_H_NO_BUDGET'] = $request_array["U_BudgetCode"];
                    $request_array["MATERIALISSUEINESCollection"][$i]['CostingCode'] = $array_budget["U_PillarCode"];
                    $request_array["MATERIALISSUEINESCollection"][$i]['CostingCode2'] = $array_budget["U_ClassificationCode"];
                    $request_array["MATERIALISSUEINESCollection"][$i]['CostingCode3'] = $array_budget["U_SubClassCode"];
                    $request_array["MATERIALISSUEINESCollection"][$i]['CostingCode4'] = $array_budget["U_SubClass2Code"];
                }
                $goodIssueInput = array(
                    "DocDate" => $request_array["U_DocDate"],
                    "RequriedDate" => $request_array["CreateDate"],
                    'DocumentLines' => $request_array["MATERIALISSUEINESCollection"],
                    "U_H_NO_BUDGET" => $request_array["U_BudgetCode"],
                    'Project' => $array_budget["U_ProjectCode"]
                );

                $good_issue = $this->sap->getService('InventoryGenExits');
                $result = $good_issue->create($goodIssueInput);
            }
        }
        return $result;

    }

    public function saveMI(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $MaterialIssue = $this->sap->getService('MaterialIssue');
        $MaterialIssue->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];
        $result = $MaterialIssue->update($code,$request->get('data'),false);
        return $result;

    }

    public function resubmitMI(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        $MaterialIssue = $this->sap->getService('MaterialIssue');
        $MaterialIssue->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->get('data')["Code"];

        $inputArray = $request->get('data');
        $inputArray["U_Status"] = 1;

        $result = $MaterialIssue->update($code,$inputArray,false);
        return $result;

    }



    public function rejectMI(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $user = Auth::user();
        $MaterialIssue = $this->sap->getService('MaterialIssue');
        $remarks = $request->Remarks;
        $code = $request->Code;
        $result = $MaterialIssue->update($code, [
            'U_Remarks' => $remarks,
            'U_Status' => 4,
            'U_RejectedBy' => $user->name
        ]);
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
        $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
        $this->sap = $sap;
        return $sap;
    }
}
