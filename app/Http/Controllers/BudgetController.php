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

class BudgetController extends Controller
{

    private $sapsession;
    private $sap;

    public function createBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $BudgetReq = $this->sap->getService('BudgetReq');

        $count = $BudgetReq->queryBuilder()->count();
        $request["Code"] = 50000001 + $count;
        $request["U_CreatedBy"] = $user->id;
        $request["U_RequestorName"] = $user->name;;

        $result = $BudgetReq->create($request->all());
        return $result;
    }
    public function getBudget()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['OData-Version' => '4.0']);
        if ($user["role_id"] == 3) {
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (string) $user["id"]))
                ->findAll();
        }elseif($user["role_id"] == 4){
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_Status", 2))
                ->orWhere(new Equal("U_Status", 3))
                ->orWhere(new Equal("U_Status", 4))
                ->findAll();
        }
        else{
            $result = $BudgetReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->findAll();
        }


        return $result;
    }

    public function getApprovedBudget()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['OData-Version' => '4.0']);
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
            $this->sap = $this->getSession();
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
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $budgets = $this->sap->getService('BudgetReq');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $budgets->update($code, [
                'U_Status' => 2
            ]);
        }else{
            $result = $budgets->update($code, [
                'U_Status' => 3
            ]);
        }
        return $result;

    }

    public function rejectBudget(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
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


    public function saveBudget(Request $request)
    {

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
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
            $this->sap = $this->getSession();
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
