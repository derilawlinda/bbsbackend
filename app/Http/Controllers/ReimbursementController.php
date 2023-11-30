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
use PDF2;

use Exception;
use Throwable;

class ReimbursementController extends Controller
{
    private $sapsession;
    private $sap;

    public function createReimbursement(Request $request)
    {

        try{
            $user = Auth::user();
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }

            $Reimbursement = $this->sap->getService('ReimbursementReq');

            $count = $Reimbursement->queryBuilder()->maxcode();


            $result = $Reimbursement->create($request->get('oProperty') + [
                'Code' => $count + 1,
                'U_CreatedBy' => (int)$user->id,
                'U_RequestorName' => $user->name
            ]);
            return $result;
        }catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);

        };

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
                ->where([new Equal("U_CreatedBy", (int) $user["id"])]);
        }elseif($user["role_id"] == 2){
            $result = $Reimbursement->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where([new Equal("U_Status", 3),'or',new Equal("U_Status", 5)]);
        }
        elseif($user["role_id"] == 4){
            $result = $Reimbursement->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where([new Equal("U_Status", 2),'or',new Equal("U_Status", 3)]);        }
        else{
            $result = $Reimbursement->queryBuilder()
            ->select('*');
        }
        if($request->search){
            $search = $request->search;
            $result->where([new Contains("Code", $search),'or',new Contains("Name",$search),'or',new Contains("U_RequestorName",$search)]);
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



        try {
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

            $journal_entry = $this->sap->getService('JournalEntries');
            $journalEntryInput = array();
            $journalEntryInput["ReferenceDate"] = $array_req["U_DisbursedAt"];
            $journalEntryInput["U_H_NO_REMBES"] = $array_req["Code"];
            $journalEntryInput["U_H_NO_BUDGET"] = $array_req["U_BudgetCode"];
            $journalEntryInput["JournalEntryLines"] = [];
            $journalEntryInput["Memo"] = "Reimbursement ".$array_req["Code"];
            $BudgetReq = $this->sap->getService('BudgetReq');

            if($bank_adm > 0){

                array_push($journalEntryInput["JournalEntryLines"], (array)[
                    'AccountCode' => '80100.0100',
                    'AdditionalReference'=> "Bank Admin",
                    'Debit' => floatval($bank_adm),
                    'CostingCode' => $array_budget["U_PillarCode"],
                    'ProjectCode' => $array_budget["U_ProjectCode"],
                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                    'CostingCode3' => $array_budget["U_SubClassCode"],
                    'CostingCode4' => $array_budget["U_SubClass2Code"]

                ]);

            }

            $journalEntryPreInput = [];

            $sum_fee = 0;

            for ($i = 0; $i < count($array_req["REIMBURSEMENTLINESCollection"]); $i++)
            {
                $sum_fee += $array_req["REIMBURSEMENTLINESCollection"][$i]["U_Amount"];
                if($array_req["REIMBURSEMENTLINESCollection"][$i]["U_NPWP"] > 0){
                    array_push($journalEntryPreInput, (array)[

                        "NPWP" => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_NPWP"],
                        "Amount" => round($array_req["REIMBURSEMENTLINESCollection"][$i]["U_Amount"]),
                        "PaymentFor" => "Fee",
                        "Account" => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_AccountCode"]

                    ]);

                    array_push($journalEntryPreInput, (array)[

                        "NPWP" => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_NPWP"],
                        "Amount" =>  round($array_req["REIMBURSEMENTLINESCollection"][$i]["U_Amount"] * ($array_req["REIMBURSEMENTLINESCollection"][$i]["U_NPWP"] /100)),
                        "PaymentFor" => "Tax",
                        "Account" => "21310.0000"

                    ]);

                }else{
                    array_push($journalEntryInput["JournalEntryLines"], (array)[
                        'AccountCode' => $array_req["REIMBURSEMENTLINESCollection"][$i]["U_AccountCode"],
                        'Debit'=> $array_req["REIMBURSEMENTLINESCollection"][$i]["U_Amount"],
                        'AdditionalReference'=> $array_req["REIMBURSEMENTLINESCollection"][$i]["U_Description"],
                        'CostingCode' => $array_budget["U_PillarCode"],
                        'ProjectCode' => $array_budget["U_ProjectCode"],
                        'CostingCode2' => $array_budget["U_ClassificationCode"],
                        'CostingCode3' => $array_budget["U_SubClassCode"],
                        'CostingCode4' => $array_budget["U_SubClass2Code"]
                    ]);

                }


            }

            $taxes = array();
            $sum_all_taxes = 0;

            if(count($journalEntryPreInput) > 0){

                $groupbytaxes = array_reduce($journalEntryPreInput, function($taxes, $outgoing){
                    if(!isset($taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']])){
                        $taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']] = ['PaymentFor'=> $outgoing['PaymentFor']." ".$outgoing['NPWP']."%",'Amount'=>$outgoing['Amount']];
                    }
                    else {
                        $taxes[$outgoing['PaymentFor']][$outgoing['NPWP']][$outgoing['Account']]["Amount"] += $outgoing['Amount'];
                    }
                    return $taxes;
                });


               foreach($groupbytaxes as $index => $value){

                    foreach($value as $key => $val){

                        foreach($val as $k => $v){

                            if($k == '21310.0000'){
                                $sum_all_taxes += $v["Amount"];
                                array_push($journalEntryInput["JournalEntryLines"], (array)[
                                    'AccountCode' => $k,
                                    'Credit'=> $v["Amount"],
                                    'AdditionalReference'=> $v["PaymentFor"],
                                    'CostingCode' => $array_budget["U_PillarCode"],
                                    'ProjectCode' => $array_budget["U_ProjectCode"],
                                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                                    'CostingCode3' => $array_budget["U_SubClassCode"],
                                    'CostingCode4' => $array_budget["U_SubClass2Code"]

                                ]);
                            }else{
                                array_push($journalEntryInput["JournalEntryLines"], (array)[
                                    'AccountCode' => $k,
                                    'Debit'=> $v["Amount"],
                                    'AdditionalReference'=> $v["PaymentFor"],
                                    'CostingCode' => $array_budget["U_PillarCode"],
                                    'ProjectCode' => $array_budget["U_ProjectCode"],
                                    'CostingCode2' => $array_budget["U_ClassificationCode"],
                                    'CostingCode3' => $array_budget["U_SubClassCode"],
                                    'CostingCode4' => $array_budget["U_SubClass2Code"]

                                ]);
                            }


                        }

                    }

               }

            }


            array_push($journalEntryInput["JournalEntryLines"], (array)[
                'AccountCode' => $array_req["U_TransferFrom"],
                'Credit'=> $sum_fee - $sum_all_taxes + floatval($bank_adm),
                'AdditionalReference'=> "Bank Transfer",
                'CostingCode' => $array_budget["U_PillarCode"],
                'ProjectCode' => $array_budget["U_ProjectCode"],
                'CostingCode2' => $array_budget["U_ClassificationCode"],
                'CostingCode3' => $array_budget["U_SubClassCode"],
                'CostingCode4' => $array_budget["U_SubClass2Code"],
                'CashFlowAssignments' => [
                    [
                        "AmountLC" => $sum_fee - $sum_all_taxes + floatval($bank_adm)
                    ]
                ]

            ]);


            $account_array = [];
            foreach ($journalEntryInput["JournalEntryLines"] as $value) {
                array_push($account_array,$value["AccountCode"]);
            };


            $result = $journal_entry->create($journalEntryInput);

            $journalArray = json_decode(json_encode($result), true);
            $ReimbursementReq = $this->sap->getService('ReimbursementReq');
            $code = $array_req["Code"];
            $disbursed_date = $array_req["DisbursedDate"];
            $result = $ReimbursementReq->update($code, [
                'U_Status' => 5,
                'U_TransferBy' => $user->name,
                'U_DisbursedAt' => $array_req["U_DisbursedAt"],
                "U_SAP_DocNum" => $journalArray["Number"],
            ]);
            if($result == 1){

                $account_array = [];
                foreach ($journalArray["JournalEntryLines"] as $key => $value) {
                    array_push($account_array,$value["AccountCode"]);
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

                $budgetUsed = [];
                foreach($journalArray["JournalEntryLines"] as $index => $value){

                    if($value["Debit"] > 0){
                        array_push($budgetUsed, (array)[
                            "U_Amount" => $value["Debit"],
                            "U_Source" => "Reimbursement",
                            "U_DocNum" => $array_req["Code"],
                            "U_UsedBy" => $array_req["U_RequestorName"],
                            "U_AccountCode" => $value["AccountCode"],
                            "U_AccountName" => $accounts[$value["AccountCode"]]
                        ]);
                    }

                };

                $BudgetReq = $this->sap->getService('BudgetReq');
                $result = $BudgetReq->update($budgetCode,
                [
                    "BUDGETUSEDCollection" => $budgetUsed
                ]);

                $result = $ReimbursementReq->queryBuilder()->select("*")->find($array_req["Code"]);
                return $result;

            }

        }catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);

        };

    }

    public function approveReimbursement(Request $request)
    {

        try{
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
            $result = $reimbursement->queryBuilder()->select("*")->find($code);
            return $result;
        }catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };


    }

    public function saveReimbursement(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }
            $user = Auth::user();
            $ReimbursementReq = $this->sap->getService('ReimbursementReq');
            $ReimbursementReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];
            $result = $ReimbursementReq->update($code,$request->get('data'),false);
            return $result;

        }catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };
    }

    public function sapeReimbursement(Request $request)
    {
        try{
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
        catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };

    }

    public function rejectReimbursement(Request $request)
    {
        try{
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
            $result =  $reimbursement->queryBuilder()->select("*")->find($code);
            return $result;
        }
        catch(Exception $e) {

            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };
    }

    public function resubmitReimbursement(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get('company'));
            }
            $user = Auth::user();
            $reimbursement = $this->sap->getService('ReimbursementReq');
            $reimbursement->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->get('data')["Code"];

            $inputArray = $request->get('data');
            $inputArray["U_Status"] = 1;
            $result = $reimbursement->update($code,$inputArray,false);
            $result = $reimbursement->queryBuilder()->select("*")->find($code);
            return $result;
        } catch(Exception $e) {
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        };

    }

    public function printReimbursement(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get("company"));
            }

            $ReimbursementReq = $this->sap->getService('ReimbursementReq');

            $result = $ReimbursementReq->queryBuilder()
                ->select('*')
                ->find($request->get("code"));

            $array_reimbursement = json_decode(json_encode($result), true);

            $find_budget = $this->sap->getService('BudgetReq');
            $get_budget = $find_budget->queryBuilder()
                        ->select('*')
                        ->find($array_reimbursement["U_BudgetCode"]);
            $array_budget = json_decode(json_encode($get_budget), true);

            $array_reimbursement["U_Company"] = $array_budget["U_Company"];
            $array_reimbursement["U_Pillar"] = $array_budget["U_Pillar"];
            $array_reimbursement["U_Classification"] = $array_budget["U_Classification"];
            $array_reimbursement["U_SubClass"] = $array_budget["U_SubClass"];
            $array_reimbursement["U_SubClass2"] = $array_budget["U_SubClass2"];
            $array_reimbursement["U_Project"] = $array_budget["U_Project"];
            $array_reimbursement["BudgetName"] = $array_budget["Name"];

            $account_array = [];
            foreach ($array_reimbursement["REIMBURSEMENTLINESCollection"] as $key => $value) {
                if(!is_null($value["U_AccountCode"]) || $value["U_AccountCode"] != ''){
                    array_push($account_array,$value["U_AccountCode"]);
                };
            };


            $accounts = $this->sap->getService('ChartOfAccounts');
            $get_account_names = $accounts->queryBuilder()
            ->select('Code,Name')
            ->where([new InArray("Code", $account_array)])
            ->findAll();
            $account_name_array = json_decode(json_encode($get_account_names), true);
            $accounts = [];
            foreach($account_name_array["value"] as $account){
                if(!is_null($account['Code']) || $account['Code'] =! ''){
                    $accounts[$account['Code']] = $account['Name'];
                }
            };

            foreach ($array_reimbursement["REIMBURSEMENTLINESCollection"] as $key => $value) {
                if($value["U_AccountCode"] != '' || !is_null($value["U_AccountCode"])){
                    $array_reimbursement["REIMBURSEMENTLINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
                }
            };
            $view = \View::make('reimbursement_pdf',['reimbursement'=>$array_reimbursement]);
            $html = $view->render();
            $filename = 'Reimbursement #'.$request->get("code");
            $pdf = new PDF2;

            $pdf::SetTitle('Reimbursement #'.$request->get("code"));
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
