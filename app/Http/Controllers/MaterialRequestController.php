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
use App\Libraries\SAPb1\Filters\InArray;
use App\Exceptions\CustomValidationException;

use Exception;
use Throwable;

class MaterialRequestController extends Controller
{
    private $sapsession;
    private $sap;

    public function createMaterialRequest(Request $request)
    {


        $user = Auth::user();

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }

        try{
            $MaterialReq = $this->sap->getService('MaterialReq');

            $count = $MaterialReq->queryBuilder()->count();

            $result = $MaterialReq->create($request->get('oProperty') + [
                'Code' => 60000001 + $count,
                'U_CreatedBy' => (int)$user->id,
                'U_RequestorName' => $user->name
            ]);
            return $result;

        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };

    }
    public function getMaterialRequests(Request $request)
    {
        $user = Auth::user();

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $MaterialReq = $this->sap->getService('MaterialReq');
            // $MaterialReq->headers(['OData-Version' => '4.0',
            // "B1S-CaseInsensitive" => true,
            // 'Prefer' => 'odata.maxpagesize=500']);
            // $search = "";
            // $status_array = [];
            // $result = $MaterialReq->queryBuilder()->select('*');
            // if($request->search){
            //     $search = $request->search;
            //     $result->where([new Contains("Code", $search),'or',new Contains("Name", $search)]);
            // }

            // if ($user["role_id"] == 3) {
            //     $result->where([new Equal("U_CreatedBy", (int) $user["id"])]);
            // }
            // elseif($user["role_id"] == 4){
            //     $result = $result->where([new Equal("U_Status", 2),'or',new Equal("U_Status", 3)]);
            // }elseif($user["role_id"] == 5){
            //     $result = $result->select('*')->where([new Equal("U_Status", 1),'or',new Equal("U_Status", 2)]);
            // }
            // else{
            //     $result = $result->select('*');
            // }



            // if($request->status){
            //     $req_status_array = preg_split ("/\,/", $request->status);
            //     foreach ($req_status_array as $value) {
            //         array_push($status_array,(int)$value);
            //     }
            //     $result->where([new InArray("U_Status", $status_array)]);
            // }

            // if($request->top){
            //     $top = $request->top;
            // }else{
            //     $top = 500;
            // }

            // if($request->skip){
            //     $skip = $request->skip;
            // }else{
            //     $skip = 0;
            // }

            // $result = $result->limit($top,$skip)->orderBy('Code', 'desc')->inlineCount()->findAll();
            $result = $MaterialReq->queryBuilder()->maxcode();
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function getMaterialRequestById(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }

            $budgets = $this->sap->getService('MaterialReq');

            $result = $budgets->queryBuilder()
                ->select('*')
                ->find($request->code); // DocEntry value
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function approveMR(Request $request)
    {

        $json = json_encode($request->get('oProperty'));
        $jsonString = str_replace(utf8_encode("U_ItemCode"),"ItemCode",$json);
        $jsonString = str_replace(utf8_encode("U_Qty"),"Quantity",$jsonString);
        $request_array = json_decode($jsonString,true);
        $code = $request_array["Code"];
        $budgetCode = (string)$request_array["U_BudgetCode"];

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        try{
            $budget = $this->sap->getService('BudgetReq');
            $mrbudget = $budget->queryBuilder()
                ->select('*')
                ->find($budgetCode); // DocEntry value
            $array_budget = json_decode(json_encode($mrbudget), true);


            $MaterialReq = $this->sap->getService('MaterialReq');

            $docdate = date("Y-m-d");
            if($request_array["U_DocDate"] != ''){
                $docdate = $request_array["U_DocDate"];
            }

            if ($user["role_id"] == 5) {
                $result = $MaterialReq->update($code, [
                    'U_Status' => 2,
                    'U_ManagerApp'=> $user->name,
                    'U_ManagerAppAt' => date("Y-m-d")
                ]);

            }

            else{

                for($i = 0; $i < count($request_array["MATERIALREQLINESCollection"]); ++$i) {
                    $request_array["MATERIALREQLINESCollection"][$i]['ProjectCode'] = $array_budget["U_ProjectCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['U_H_KET'] = $request_array["MATERIALREQLINESCollection"][$i]['U_Description'];
                    $request_array["MATERIALREQLINESCollection"][$i]['U_H_NO_BUDGET'] = $request_array["U_BudgetCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['U_H_COA'] = $request_array["MATERIALREQLINESCollection"][$i]['U_AccountCode'];
                    $request_array["MATERIALREQLINESCollection"][$i]['U_H_NO_MR'] = $request_array["Code"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode'] = $array_budget["U_PillarCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode2'] = $array_budget["U_ClassificationCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode3'] = $array_budget["U_SubClassCode"];
                    $request_array["MATERIALREQLINESCollection"][$i]['CostingCode4'] = $array_budget["U_SubClass2Code"];

                }
                $purchaseReqInput = array(
                    "DocDate" => $docdate,
                    "Comments" => $request_array["Name"],
                    "RequriedDate" => $request_array["CreateDate"],
                    'DocumentLines' => $request_array["MATERIALREQLINESCollection"],
                    "U_H_NO_MR" => $request_array["Code"],
                    "U_H_NO_BUDGET" => $request_array["U_BudgetCode"],
                    'Project' => $array_budget["U_ProjectCode"]
                );
                $purchase_req = $this->sap->getService('PurchaseRequests');
                $result = $purchase_req->create($purchaseReqInput);

                if($result){
                    $result = $MaterialReq->update($code, [
                        'U_Status' => 3,
                        'U_DirectorApp'=> $user->name,
                        'U_DirectorAppAt' => date("Y-m-d")
                    ]);

                }

                return $result;

            }


        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);

        }
    }

    public function saveMR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();
        try{
            $MaterialReq = $this->sap->getService('MaterialReq');
            $MaterialReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $result = $MaterialReq->update($code,$request->get('data'),false);
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }

    public function resubmitMR(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->get('company'));
        }
        $user = Auth::user();

        try{

            $MaterialReq = $this->sap->getService('MaterialReq');
            $MaterialReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $inputArray = $request->get('data');
            $inputArray["U_Status"] = 1;
            $result = $MaterialReq->update($code,$inputArray,false);
            return $result;

        } catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }
    }



    public function rejectMR(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
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
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function getSession(string $company)
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
