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

class ProjectController extends Controller
{
    private $sapsession;
    private $sap;

    public function getProjects(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession($request->company);
        }
        $Projects = $this->sap->getService('Projects');
        $Projects->headers(['Prefer' => 'odata.maxpagesize=200']);
        $result = $Projects->queryBuilder()
            ->select('Code,Name',)
            ->orderBy('Code', 'desc')
            ->findAll();


        return $result;
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
        $sap = SAPClient::createSession($config, "manager", "Admin@23", $company."_LIVE");
        $this->sap = $sap;
        return $sap;
    }
}
