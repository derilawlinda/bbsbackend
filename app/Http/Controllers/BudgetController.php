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

class BudgetController extends Controller
{

    private $sapsession;
    private $sap;

    public function createBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->U_Company);
        }

        $BudgetReq = $this->sap->getService('BudgetReq');

        $count = $BudgetReq->queryBuilder()->count();
        $request["Code"] = 50000003 + $count;
        $request["U_CreatedBy"] = $user->id;
        $request["U_RequestorName"] = $user->name;;

        $result = $BudgetReq->create($request->all());
        return $result;
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
                ->where(new Equal("U_CreatedBy", (string) $user["id"]))
                ->orderBy('Code', 'desc')
                ->inlineCount();
        }elseif($user["role_id"] == 4){
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_Status", 2))
                ->orWhere(new Equal("U_Status", 3))
                ->inlineCount();
        }
        else{
            $result = $BudgetReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->inlineCount();
        }
        if($request->search){
            $search = $request->search;
            $result->where(new Contains("Code", $search))
                    ->orWhere(new Contains("Name",$search));
        }

        if($request->status){
            $req_status_array = preg_split ("/\,/", $request->status);
            foreach ($req_status_array as $value) {
                array_push($status_array,(int)$value);
            }
            $result->where(new InArray("U_Status", $status_array));
        }

        if($request->top){
            $top = $request->top;
        }else{
            $top = 100;
        }

        if($request->skip){
            $skip = $request->skip;
        }else{
            $skip = 0;
        }

        $result = $result->limit($top,$skip)->findAll();

        return $result;
    }

    public function getApprovedBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['OData-Version' => '4.0',
        'Prefer' => 'odata.maxpagesize=500']);
        $result = $BudgetReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->where(new Equal("U_Status", 3))
            ->findAll();


        return $result;
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

    public function approveBudget(Request $request)
    {
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
        return $result;

    }

    public function rejectBudget(Request $request)
    {
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
        return $result;

    }

    public function closeBudget(Request $request)
    {
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
        return $result;

    }

    public function cancelBudget(Request $request)
    {
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
        return $result;

    }


    public function saveBudget(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->U_Company);
        }
        $user = Auth::user();
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $BudgetReq->update($code,$request->all(),false);
        return $result;

    }

    public function resubmitBudget(Request $request)
    {

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
        $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE" );
        $this->sap = $sap;
        return $sap;
    }
}
