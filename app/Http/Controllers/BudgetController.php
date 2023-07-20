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
        }else{
            $result = $BudgetReq->queryBuilder()
            ->select('*')
            ->orderBy('Code', 'desc')
            ->findAll();
        }


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

    public function getBudget2($skip=20)
    {
        if(!Session::has('sapcookies')){
            $this->getCookie();
        }
        $cookie = Session::get('sapcookies');
        $data = '';
        $response = Http::withoutVerifying()
        ->withOptions([
            'cookies' => $cookie,
            'verify'=>false
        ])
        ->withBody($data,'application/json')
        ->withHeaders ([
            'Content-Type' => 'application/json; odata.metadata=minimal',
            'OData-Version' => '4.0'

        ])->get('https://'.env('SAP_URL').':50000/b1s/v2/BudgetReq?$select=Code,Name,U_Project,U_Pillar,U_Classification,U_SubClass,U_SubClass2,U_Status&$skip='.$skip);

        $content = $response->getBody();
        $array = json_decode($content, true);

        if(array_key_exists("@odata.context",$array)){
           $array["@odata.context"] = stripslashes("http://localhost:8000/api/".'$metadata'."#BudgetReq");

        }

        if(array_key_exists("error",$array)){
            $code = $array['error']['code'];
            if($code == 301){ //if request timed out
                $this->getCookie();
                $this->getBudget();
            }
        }
        $contents = json_encode($array,JSON_UNESCAPED_SLASHES);
        $response = Response::make($contents, 200);

        $response->withHeaders([
            'Content-Type' => 'application/json; odata.metadata=minimal',
            'OData-Version' => '4.0'

        ]);
        return $response;
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
        $sap = SAPClient::createSession($config, "manager", "1234", "POS_29JUN");
        $this->sap = $sap;
        return $sap;
    }
}
