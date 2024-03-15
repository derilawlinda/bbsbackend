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
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;

use Exception;
use Throwable;


class ProfitCenterController extends Controller
{
    private $sapsession;
    private $sap;


    public function getPillars(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];


        $ProfitCenter = $this->sap->getService('ProfitCenters');
        $ProfitCenter->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);

        $result = $ProfitCenter->queryBuilder()
            ->select('CenterCode,CenterName')
            ->where([new Equal("InWhichDimension", 1)])
            ->findAll();

        return $result;
    }

    public function getClassifications(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];


        $ProfitCenter = $this->sap->getService('ProfitCenters');
        $ProfitCenter->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);

        $result = $ProfitCenter->queryBuilder()
            ->select('CenterCode,CenterName')
            ->where([new Equal("InWhichDimension", 2)])
            ->findAll();

        return $result;
    }

    public function getSubClass(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];


        $ProfitCenter = $this->sap->getService('ProfitCenters');
        $ProfitCenter->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);

        $result = $ProfitCenter->queryBuilder()
            ->select('CenterCode,CenterName')
            ->where([new Equal("InWhichDimension", 3)])
            ->findAll();

        return $result;
    }

    public function getSubClass2(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $search = "";
        $status_array = [];


        $ProfitCenter = $this->sap->getService('ProfitCenters');
        $ProfitCenter->headers(['OData-Version' => '4.0',
        "B1S-CaseInsensitive" => true,
        'Prefer' => 'odata.maxpagesize=500']);

        $result = $ProfitCenter->queryBuilder()
            ->select('CenterCode,CenterName')
            ->where([new Equal("InWhichDimension", 4)])
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
        if($company != 'TEST_DERIL'){
            $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");

        }else{
            $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company);
        }
        $this->sap = $sap;
        return $sap;
    }
}
