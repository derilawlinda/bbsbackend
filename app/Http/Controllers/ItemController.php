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
            $this->sap = $this->getSession($request->company);
        }

        $account_code = (string) $request->accountCode;
        $account_code_array = explode (",", $request->accountCode);
        // $itemsQuery = $this->sap->query("Items,ItemGroups,ChartOfAccounts",['Prefer' => 'odata.maxpagesize=50000']);
        // $itemsQuery->expand('Items($select=ItemCode,ItemName,ItemsGroupCode),ItemGroups($select=Number)')
        // ->where(new Raw("ItemGroups/Number eq Items/ItemsGroupCode"))
        // ->where(new Raw("ItemGroups/InventoryAccount eq ChartOfAccounts/Code"))
        // ->where(new Raw("ChartOfAccounts/Code eq '11520.0000'"))
        // ->orWhere(new Raw("ChartOfAccounts/Code eq '11530.0000'"));


        // $itemsQuery =  $this->sap->query("ItemGroups,Items,ChartOfAccounts",['Prefer' => 'odata.maxpagesize=50000']);
        // $itemsQuery->expand('Items($select=ItemCode,ItemName)')
        // ->where(new Raw("ItemGroups/Number eq Items/ItemsGroupCode"))
        // ->where(new Raw("ItemGroups/InventoryAccount eq ChartOfAccounts/Code"))
        // ->where(new Raw("ChartOfAccounts/Code eq '11520.0000'"));

        $itemsQuery = $this->sap->getService('ItemGroups');
        $itemsQuery->headers(['Prefer' => 'odata.maxpagesize=100000']);
        $result = $itemsQuery->queryBuilder()
            ->expand('Items($select=ItemCode,ItemName)')
            ->where([new InArray("InventoryAccount", $account_code_array),'or',
            new InArray("ExpensesAccount", $account_code_array)])
            ->findAll();

        $results = json_decode(json_encode($result),true);
        $items = array();

        foreach ($results["value"] as &$values) {

            foreach ($values["Items"] as &$value) {
                array_push($items, (object)[
                    'ItemCode' => $value["ItemCode"],
                    'ItemName' => $value["ItemName"],
                    // 'ProfitCenter' => $request_array["budgeting"]["U_Pillar"],
                    // 'ProjectCode' => $request_array["budgeting"]["U_Project"],
                    // "ProfitCenter2" => $request_array["budgeting"]["U_Classification"],
                    // "ProfitCenter3" => $request_array["budgeting"]["U_SubClass"],
                    // "ProfitCenter4" => $request_array["budgeting"]["U_SubClass2"],

                ]);
            }
        }

        return json_encode($items);
    }

    // public function getItemsByAccount(Request $request) {

    //     $user = Auth::user();
    //     if(is_null($this->sap)) {
    //         $this->sap = $this->getSession($request->company);
    //     }

    //     $itemsQuery = $this->sap->getService('Items');
    //     $itemsQuery->headers(['Prefer' => 'odata.maxpagesize=50']);
    //     $result = $itemsQuery->queryBuilder()
    //         ->where(new InArray("InventoryAccount", 'Y'))
    //         ->findAll();


    //     return $result;
    // }

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
