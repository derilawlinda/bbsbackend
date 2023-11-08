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
use PDF2;

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
                    $request_array["MATERIALISSUELINESCollection"][$i]['WarehouseCode'] =$request_array["MATERIALISSUELINESCollection"][$i]['U_WhsCode'];
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
                    'Project' => $array_budget["U_ProjectCode"],
                    "U_H_NO_MI" => $request_array["Code"]

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
            if($result == 1){
                $result = $MaterialIssue->queryBuilder()->select("*")->find($code);
            }
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
            if($result == 1){
                $result = $MaterialIssue->queryBuilder()->select("*")->find($code);
            }
            return $result;

        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function printMI(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get("company"));
            }

            $MaterialIssue = $this->sap->getService('MaterialIssue');

            $result = $MaterialIssue->queryBuilder()
                ->select('*')
                ->find($request->get("code"));

            $array_mi = json_decode(json_encode($result), true);
            $account_array = [];
            $item_array = [];
            $warehouse_array = [];

            $find_budget = $this->sap->getService('BudgetReq');
            $get_budget = $find_budget->queryBuilder()
                        ->select('*')
                        ->find($array_mi["U_BudgetCode"]);
            $array_budget = json_decode(json_encode($get_budget), true);

            $array_mi["U_Company"] = $array_budget["U_Company"];
            $array_mi["U_Pillar"] = $array_budget["U_Pillar"];
            $array_mi["U_Classification"] = $array_budget["U_Classification"];
            $array_mi["U_SubClass"] = $array_budget["U_SubClass"];
            $array_mi["U_SubClass2"] = $array_budget["U_SubClass2"];
            $array_mi["U_Project"] = $array_budget["U_Project"];
            $array_mi["BudgetName"] = $array_budget["Name"];


            foreach ($array_mi["MATERIALISSUELINESCollection"] as $key => $value) {
                array_push($account_array,$value["U_AccountCode"]);
                if($value["U_WhsCode"] != ''){
                    array_push($warehouse_array,$value["U_WhsCode"]);
                }
                if($value["U_ItemCode"] != ''){
                    array_push($item_array,$value["U_ItemCode"]);
                }
            };


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

            if(count($warehouse_array) > 0){
                $warehouses = $this->sap->getService('Warehouses');
                $get_warehouse_names = $warehouses->queryBuilder()
                ->select('WarehouseCode,WarehouseName')
                ->where([new InArray("WarehouseCode", $warehouse_array)])
                ->findAll();
                $warehouse_name_array = json_decode(json_encode($get_warehouse_names), true);
                $warehouses = [];
                foreach($warehouse_name_array["value"] as $warehouse){
                    $warehouses[$warehouse['WarehouseCode']] = $warehouse['WarehouseName'];
                };
            }


            foreach ($array_mi["MATERIALISSUELINESCollection"] as $key => $value) {

                $array_mi["MATERIALISSUELINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
                if($value["U_WhsCode"] != ''){
                    $array_mi["MATERIALISSUELINESCollection"][$key]["WarehouseName"] = $warehouses[$value["U_WhsCode"]];
                }else{
                    $array_mi["MATERIALISSUELINESCollection"][$key]["WarehouseName"] = '-';
                }
                if($value["U_ItemCode"] != ''){
                    $array_mi["MATERIALISSUELINESCollection"][$key]["ItemName"] = $items[$value["U_ItemCode"]];
                }else{
                    $array_mi["MATERIALISSUELINESCollection"][$key]["ItemName"] = '-';
                }
            };

            $view = \View::make('mi_pdf',['material_issue'=>$array_mi]);
            $html = $view->render();
            $filename = 'Material Issue #'.$request->get("code");
            $pdf = new PDF2;

            $pdf::SetTitle('Material Issue #'.$request->get("code"));
            $pdf::AddPage();
            $pdf::writeHTML($html, true, false, true, false, '');

            // $pdf::Output(public_path($filename), 'F');

            // $pdf = PDF::loadview('mr_pdf',['material_request'=>$array_mr]);
            // $pdf->setPaper('A4', 'portrait');
            // $pdf->getDomPDF()->set_option("enable_php", true);
            return base64_encode($pdf::Output($filename, 'S'));
            // echo base64_encode($pdf->output());
            // return $array_mr;

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
