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
use PDF;
use PDF2;


use Exception;
use Throwable;

class MYPDF extends PDF2 {

    //Page header
    // public function Header() {
    //     // Logo
    //     $image_file = K_PATH_IMAGES.'logo_example.jpg';
    //     $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    //     // Set font
    //     $this->SetFont('helvetica', 'B', 20);
    //     // Title
    //     $this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    // }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

class BudgetController extends Controller
{

    private $sapsession;
    private $sap;


    public function createBudget(Request $request)
    {
        try{

            $user = Auth::user();
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->U_Company);
            }

            $BudgetReq = $this->sap->getService('BudgetReq');

            $maxcode = $BudgetReq->queryBuilder()->maxcode();
            $request["Code"] = $maxcode + 1;
            $request["U_CreatedBy"] = $user->id;
            $request["U_RequestorName"] = $user->name;
            $result = $BudgetReq->create($request->all());
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }
    public function getBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];


        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);
        if ($user["role_id"] == 3) {
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->where([new Equal("U_CreatedBy", (string) $user["id"])]);
        }elseif($user["role_id"] == 4){
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->where([new Equal("U_Status", 2),'or',new Equal("U_Status", 3)]);
        }
        else{
            $result = $BudgetReq->queryBuilder()
            ->select('*');
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

    public function getApprovedBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        try{

            $BudgetReq = $this->sap->getService('BudgetReq');
            $BudgetReq->headers(['OData-Version' => '4.0',
            'Prefer' => 'odata.maxpagesize=500']);
            $result = $BudgetReq->queryBuilder()
                ->select('Code,Name')
                ->orderBy('Code', 'desc')
                ->where([new Equal("U_Status", 3)])
                ->findAll();


            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }

    public function getBudgetById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }

        $budgets = $this->sap->getService('BudgetReq');

        $result = $budgets->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function printBudget(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->get("U_Company"));
            }

            $budgets = $this->sap->getService('BudgetReq');

            $result = $budgets->queryBuilder()
                ->select('*')
                ->find($request->get("Code"));

            $array_budget = json_decode(json_encode($result), true);
            $account_array = [];
            foreach ($array_budget["BUDGETREQLINESCollection"] as $key => $value) {
                array_push($account_array,$value["U_AccountCode"]);
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

            foreach ($array_budget["BUDGETREQLINESCollection"] as $key => $value) {
                $array_budget["BUDGETREQLINESCollection"][$key]["AccountName"] = $accounts[$value["U_AccountCode"]];
            };

            // $pdf = PDF::loadview('budget_pdf',['budget'=>$array_budget]);
            // $pdf->setPaper('A4', 'portrait');
            // $pdf->getDomPDF()->set_option("enable_php", true);
            // echo base64_encode($pdf->output());
            // return $array_budget;


            $view = \View::make('budget_pdf',['budget'=>$array_budget]);
            $html = $view->render();
            $filename = 'Budget Request #'.$request->get("code");
            $pdf = new MYPDF;

            $pdf::SetTitle('Budget Request #'.$request->get("code"));
            $pdf::AddPage();
            $pdf::setFooterFont(Array('dejavusans', '', '8'));
            $pdf::SetFooterMargin(5);
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

    public function print()
    {
        return view('budget_pdf',['Code'=>'1213233']);

    }



    public function approveBudget(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->company);
            }
            $user = Auth::user();
            $budgets = $this->sap->getService('BudgetReq');
            $code = $request->Code;
            if ($user["role_id"] == 5) {
                $result = $budgets->update($code, [
                    'U_Status' => 2,
                    'U_ManagerApp'=> $user->name,
                    'U_ManagerAppAt' => date("Y-m-d")
                ]);
            }else{
                $result = $budgets->update($code, [
                    'U_Status' => 3,
                    'U_DirectorApp'=> $user->name,
                    'U_DirectorAppAt' => date("Y-m-d")
                ]);
            }
            if($result == 1){
                $result = $budgets->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            }
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);

        }


    }

    public function rejectBudget(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->Company);
            }
            $user = Auth::user();
            $budgets = $this->sap->getService('BudgetReq');
            $code = $request->Code;
            $remarks = $request->Remarks;
            $result = $budgets->update($code, [
                'U_Status' => 4,
                'U_Remarks' => $remarks,
                'U_RejectedBy' => $user->name
            ]);
            if($result == 1){
                $result = $budgets->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            }
            return $result;
        }catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function closeBudget(Request $request)
    {
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->Company);
            }
            $user = Auth::user();
            $budgets = $this->sap->getService('BudgetReq');
            $code = $request->Code;

            $result = $budgets->update($code, [
                'U_Status' => 5,
                'U_ClosedBy'=> $user->name,
                'U_ClosedAt' => date("Y-m-d")

            ]);
            if($result == 1){
                $result = $budgets->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            }
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function cancelBudget(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->Company);
            }
            $user = Auth::user();
            $budgets = $this->sap->getService('BudgetReq');
            $code = $request->Code;

            $result = $budgets->update($code, [
                'U_Status' => 99,
                'U_CancelledBy'=> $user->name,
                'U_CancelledAt' => date("Y-m-d")
            ]);
            if($result == 1){
                $result = $budgets->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            }
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }

    }


    public function saveBudget(Request $request)
    {

        // return $request->all();
        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->U_Company);
            }
            $user = Auth::user();
            $BudgetReq = $this->sap->getService('BudgetReq');
            $BudgetReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->Code;
            $result = $BudgetReq->update($code,$request->all(),false);
            if($result == 1){
                $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            };
            return $result;
        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


    }

    public function resubmitBudget(Request $request)
    {

        try{
            if(is_null($this->sap)) {
                $this->sap = $this->getSession($request->U_Company);
            }
            $user = Auth::user();
            $BudgetReq = $this->sap->getService('BudgetReq');
            $BudgetReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
            $code = $request->Code;
            $request["U_Status"] = 1;
            $result = $BudgetReq->update($code,$request->all(),false);
            if($result == 1){
                $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->find($code); // DocEntry value
            }
            return $result;

        }
        catch(Exception $e){
            return response()->json(array('status'=>'error', 'msg'=>$e->getMessage()), 500);
        }


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
                if($company == 'BBS'){
                    $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE_LIVE");
                }else{
                    $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
                }
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
