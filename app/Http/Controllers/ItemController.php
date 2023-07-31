<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Session;
use App\Libraries\SAPb1\SAPClient;
use App\Libraries\SAPb1\Filters\Equal;
use App\Libraries\SAPb1\Filters\StartsWith;
use App\Libraries\SAPb1\Filters\InArray;
use App\Libraries\SAPb1\Filters\Raw;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{

    private $sapsession;
    private $sap;

    public function getItemsByAccount(Request $request) {

        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }
        $account_code = (string) $request->accountCode;
        $BudgetReq = $this->sap->getService('BudgetReq');
        $items = [];

        $itemsQuery = $this->sap->query("Items,ItemGroups,ChartOfAccounts",['Prefer' => 'odata.maxpagesize=5000']);
        $result = $itemsQuery->expand('Items($select=ItemCode,ItemName)')
        ->where(new Raw("Items/ItemsGroupCode eq ItemGroups/Number"))
        ->where(new Raw("ItemGroups/InventoryAccount eq ChartOfAccounts/Code"))
        ->where(new Raw("ChartOfAccounts/Code eq ".strval($account_code)))
        ->findAll();
        $results = json_decode(json_encode($result),true);
        for ($i = 0; $i < count($results["value"]); $i++)
        {

            array_push($items, (object)[
                'ItemCode' => $results["value"][$i]["Items"]["ItemCode"],
                'ItemName' => $results["value"][$i]["Items"]["ItemName"],
                // 'ProfitCenter' => $request_array["budgeting"]["U_Pillar"],
                // 'ProjectCode' => $request_array["budgeting"]["U_Project"],
                // "ProfitCenter2" => $request_array["budgeting"]["U_Classification"],
                // "ProfitCenter3" => $request_array["budgeting"]["U_SubClass"],
                // "ProfitCenter4" => $request_array["budgeting"]["U_SubClass2"],

            ]);
        }

        return json_encode($items);
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
