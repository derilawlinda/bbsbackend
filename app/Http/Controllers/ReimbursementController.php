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
use Illuminate\Support\Facades\Auth;

class ReimbursementController extends Controller
{
    private $sapsession;
    private $sap;

    public function createReimbursement(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $Reimbursement = $this->sap->getService('ReimbursementReq');

        $count = $Reimbursement->queryBuilder()->count();
        $request["Code"] = 90000001 + $count;
        $request["U_CreatedBy"] = (int)$user->id;
        $request["U_RequestorName"] = $user->name;

        $result = $Reimbursement->create($request->all());
        return $result;
    }

    public function getReimbursements()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $BudgetReq = $this->sap->getService('ReimbursementReq');
        $BudgetReq->headers(['OData-Version' => '4.0']);
        if ($user["role_id"] == 3) {
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_CreatedBy", (int) $user["id"]))
                ->findAll();
        }
        elseif($user["role_id"] == 4){
            $result = $BudgetReq->queryBuilder()
                ->select('*')
                ->orderBy('Code', 'desc')
                ->where(new Equal("U_Status", 2))
                ->orWhere(new Equal("U_Status", 3))
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

    public function getReimbursementById(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $reimbursement = $this->sap->getService('ReimbursementReq');

        $result = $reimbursement->queryBuilder()
            ->select('*')
            ->find($request->code); // DocEntry value
        return $result;

    }

    public function approveReimbursement(Request $request)
    {
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $reimbursement = $this->sap->getService('ReimbursementReq');
        $code = $request->Code;
        if ($user["role_id"] == 5) {
            $result = $reimbursement->update($code, [
                'U_Status' => 2
            ]);
        }else{
            $result = $reimbursement->update($code, [
                'U_Status' => 3
            ]);
        }
        return $result;

    }

    public function saveReimbursement(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $ReimbursementReq = $this->sap->getService('ReimbursementReq');
        $ReimbursementReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $ReimbursementReq->update($code,$request->all(),false);
        return $result;

    }

    public function sapeReimbursement(Request $request)
    {
        $json = json_encode($request->all());

        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $user = Auth::user();
        $ReimbursementReq = $this->sap->getService('ReimbursementReq');
        $ReimbursementReq->headers(['B1S-ReplaceCollectionsOnPatch' => 'true']);
        $code = $request->Code;
        $result = $ReimbursementReq->update($code,$request->all(),false);
        return $result;
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
