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


class MainController extends Controller
{
    private $sapsession;
    private $sap;


    public function services(Request $request)
    {
        $user = Auth::user();
        if(is_null($this->sap)) {
            $this->sap = $this->getSession();
        }

        $mainReq = $this->sap->getService('');
        // $result = $mainReq->findAll();
        return $mainReq;
    }

    public function saveJSONPillar(Request $request){

        Storage::disk('json')->put('pillar_'.$request->get('company').'.json', json_encode($request->get('data')));

    }

    public function getPillar(Request $request){

        $this->json_path = Storage::disk('storage')->get('pillar_'.$request->company.'.json');
        $config_decoded = json_decode($this->json_path, true);
        return json_encode($config_decoded);

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
