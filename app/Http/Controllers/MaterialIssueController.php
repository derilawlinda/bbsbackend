<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Session;
use Exception;
use Throwable;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use App\Libraries\SAPb1\Filters\InArray;
use App\Libraries\SAPb1\Filters\Contains;
use Illuminate\Support\Facades\Auth;

class MaterialIssueController extends Controller
{
    private $sapsession;
    private $sap;

    public function createMaterialIssue(Request $request)
    {
        $user = Auth::user();
        try{
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
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }
    public function getMaterialIssues(Request $request)
    {


        $user = Auth::user();

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $MaterialIssue = $this->sap->getService('MaterialIssue');
            $MaterialIssue->headers(['OData-Version' => '4.0',
            "B1S-CaseInsensitive" => true,
            'Prefer' => 'odata.maxpagesize=500']);

            $search = "";
            $status_array = [];

            $result = $MaterialIssue->queryBuilder()->select('*');


            if ($user["role_id"] == 3) {
                $result = $result->where([new Equal("U_CreatedBy", (int) $user["id"])]);
            }elseif($user["role_id"] == 4){
                $result = $result->where([new Equal("U_Status", 2),'or',new Equal("U_Status", 3),'or',new Equal("U_Status", 4)]);
            }elseif($user["role_id"] == 5){
                $result = $result->where([new Equal("U_Status", 1),'or',new Equal("U_Status", 2),'or',new Equal("U_Status", 4)]);

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

    public function getMaterialIssueById(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }

            $budgets = $this->sap->getService('MaterialIssue');

            $result = $budgets->queryBuilder()
                ->select('*')
                ->find($request->code); // DocEntry value
            return $result;
        } catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function approveMI(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }

            $json = json_encode($request->get('oProperty'));
            $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
            $jsonString = str_replace(utf8_encode("U_Qty"),"Quantity",$jsonString);
            $jsonString = str_replace(utf8_encode("U_AccountCode"),"AccountCode",$jsonString);
            $request_array = json_decode($jsonString,true);
            $code = $request_array["Code"];
            $budgetCode = (string)$request_array["U_BudgetCode"];

            $budget = $this->sap->getService('BudgetReq');
            $mrbudget = $budget->queryBuilder()
                ->select('*')
                ->find($budgetCode); // DocEntry value
             $array_budget = json_decode(json_encode($mrbudget), true);
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $user = Auth::user();
            $material_issue = $this->sap->getService('MaterialIssue');

            if ($user["role_id"] == 5) {
                $result = $material_issue->update($code, [
                    'U_Status' => 2,
                    'U_ManagerApp'=> $user->name,
                    'U_ManagerAppAt' => date("Y-m-d")
                ]);

            }
            else{

                for($i = 0; $i < count($request_array["MATERIALISSUELINESCollection"]); ++$i) {
                    $request_array["MATERIALISSUELINESCollection"][$i]['ProjectCode'] = $array_budget["U_ProjectCode"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['U_H_NO_BUDGET'] = $request_array["U_BudgetCode"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['U_H_KET'] = $request_array["MATERIALISSUELINESCollection"][$i]['U_Description'];
                    $request_array["MATERIALISSUELINESCollection"][$i]['U_COA'] = $request_array["MATERIALISSUELINESCollection"][$i]['AccountCode'];
                    $request_array["MATERIALISSUELINESCollection"][$i]['U_H_NO_MR'] = $request_array["Code"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['CostingCode'] = $array_budget["U_PillarCode"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['CostingCode2'] = $array_budget["U_ClassificationCode"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['CostingCode3'] = $array_budget["U_SubClassCode"];
                    $request_array["MATERIALISSUELINESCollection"][$i]['CostingCode4'] = $array_budget["U_SubClass2Code"];
                }
                $goodIssueInput = array(
                    "DocDate" => $request_array["U_DocDate"],
                    "RequriedDate" => $request_array["CreateDate"],
                    'DocumentLines' => $request_array["MATERIALISSUELINESCollection"],
                    "U_H_NO_BUDGET" => $request_array["U_BudgetCode"],
                    'Project' => $array_budget["U_ProjectCode"]
                );

                $good_issue = $this->sap->getService('InventoryGenExits');
                $result = $good_issue->create($goodIssueInput);
                if($result){
                    $result = $material_issue->update($code, [
                        'U_Status' => 3,
                        'U_DirectorApp'=> $user->name,
                        'U_DirectorAppAt' => date("Y-m-d")
                    ]);
                }


            }
            if($result == 1){
                $result = $material_issue->queryBuilder()
                ->select('*')->find($code);
            };
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function saveMI(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        try{
            $MaterialIssue = $this->sap->getService('MaterialIssue');
            $MaterialIssue->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $result = $MaterialIssue->update($code,$request->get('data'),false);
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function resubmitMI(Request $request)
    {
        try{
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

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }



    public function rejectMI(Request $request)
    {
        try{
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
