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
use App\Libraries\SAPb1\Filters\MoreThan;
use Illuminate\Support\Facades\Auth;

use Exception;
use Throwable;

class COAController extends Controller
{
    private $sapsession;
    private $sap;

    public function getCOAs(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=500']);
        $result = $COAReq->queryBuilder()
            ->select('Code,Name,AccountLevel')
            ->where([new StartsWith("Code", "1"),'or',
                new StartsWith("Code", "4"),'or',
                new StartsWith("Code", "5"),'or',
                new StartsWith("Code", "6")]
            )
            ->where([new MoreThan("AccountLevel", 1)])
            ->where([new Equal("ActiveAccount", 'Y')])
            ->orderBy('Code', 'desc')
            ->findAll();


        return $result;
    }

    public function getWarehouses(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $COAReq = $this->sap->getService('Warehouses');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=500']);
        if($request->company != 'KKB'){
            $result = $COAReq->queryBuilder()
            ->select('WarehouseCode,WarehouseName')
            ->where([new InArray("WarehouseCode", ['V_PE_IN','V_MK_TNG'])])
            ->orderBy('WarehouseCode', 'desc')
            ->findAll();

        }else {

            $result = $COAReq->queryBuilder()
            ->select('WarehouseCode,WarehouseName')
            ->where([new InArray("WarehouseCode", ['G_KV_BES','G_TL_AM','G_DST_GS'])])
            ->orderBy('WarehouseCode', 'desc')
            ->findAll();

        }

        return $result;
    }

    public function getCOAsByAR(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $ar_code = $request->ARCode;
        $AdvanceReq = $this->sap->getService('AdvanceReq');
        $AdvanceReq->headers(['Prefer' => 'odata.maxpagesize=100']);
        $advancereqs =  $AdvanceReq->queryBuilder()->select('ADVANCEREQLINESCollection')
                    ->find($ar_code);
        $collection = json_decode(json_encode($advancereqs), true)["ADVANCEREQLINESCollection"];
        $account_code_array = [];
        for ($i = 0; $i < count($collection); $i++)
        {
            array_push($account_code_array, (string)$collection[$i]["U_AccountCode"]);
        }
        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=10']);
        $result = $COAReq->queryBuilder()
            ->select('Code,Name')
            ->where([new InArray("Code", $account_code_array)])
            ->where([new Equal("ActiveAccount", 'Y')])
            ->orderBy('Code', 'desc')
            ->findAll();
        return $result;
    }

    public function getCOAsByBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $budget_code = $request->budgetCode;
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['Prefer' => 'odata.maxpagesize=100']);
        $budgets =  $BudgetReq->queryBuilder()->select('BUDGETREQLINESCollection')
                    ->find($budget_code);
        $collection = json_decode(json_encode($budgets), true)["BUDGETREQLINESCollection"];
        $account_code_array = [];
        for ($i = 0; $i < count($collection); $i++)
        {
            array_push($account_code_array, (string)$collection[$i]["U_AccountCode"]);
        }

        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=50']);
        $result = $COAReq->queryBuilder()
            ->select('Code,Name')
            ->where([new InArray("Code", $account_code_array)])
            ->where([new Equal("ActiveAccount", 'Y')])
            ->orderBy('Code', 'desc')
            ->findAll();

        return $result;
    }

    public function getCOAsByBudgetForMI(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $budget_code = $request->budgetCode;
        $BudgetReq = $this->sap->getService('BudgetReq');
        $BudgetReq->headers(['Prefer' => 'odata.maxpagesize=200']);
        $budgets =  $BudgetReq->queryBuilder()->select('BUDGETREQLINESCollection')
                    ->find($budget_code);
        $collection = json_decode(json_encode($budgets), true)["BUDGETREQLINESCollection"];
        $account_code_array = [];
        for ($i = 0; $i < count($collection); $i++)
        {
            array_push($account_code_array, (string)$collection[$i]["U_AccountCode"]);
        }

        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=1000']);
        $result = $COAReq->queryBuilder()
            ->select('Code,Name')
            ->where([new StartsWith("Code", "1"),'or',new StartsWith("Code", "5"),
            'or',new InArray("Code", ["60200.0400","60700.0200","60700.0500","60600.0100"])])
            ->where([new MoreThan("AccountLevel", 1)])
            ->where([new InArray("Code", $account_code_array)])
            ->where([new Equal("ActiveAccount", 'Y')])
            ->orderBy('Code', 'desc')
            ->findAll();

        return $result;
    }

    public function getCOAsForTransfer(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }

        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=50']);
        $result = $COAReq->queryBuilder()
            ->select("Code,Name")
            ->where([new Equal("U_H_BANK_TRF", "YES")])
            ->findAll();

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
