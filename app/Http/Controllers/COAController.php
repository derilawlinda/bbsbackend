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

class COAController extends Controller
{
    private $sapsession;
    private $sap;

    public function getCOAs()
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $COAReq = $this->sap->getService('ChartOfAccounts');
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=50']);
        $result = $COAReq->queryBuilder()
            ->select('*')
            ->where(new StartsWith("Code", "6"))
            ->orderBy('Code', 'desc')
            ->findAll();


        return $result;
    }

    public function getCOAsByBudget(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
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
        $COAReq->headers(['Prefer' => 'odata.maxpagesize=10']);
        $result = $COAReq->queryBuilder()
            ->select('Code,Name')
            ->where(new InArray("Code", $account_code_array))
            ->orderBy('Code', 'desc')
            ->findAll();

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
        $sap = SAPClient::createSession($config, "manager", "1234", "POS_29JUN");
        $this->sap = $sap;
        return $sap;
    }
}
