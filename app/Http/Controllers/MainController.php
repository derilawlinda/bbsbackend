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

        Storage::disk('storage')->put('pillar_'.$request->get('company').'.json', json_encode($request->get('data')));

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
        try{
            if($company != 'TEST_DERIL'){
                if($company == 'BBS'){
                    $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE_LIVE");
                }else{
                    $sap = SAPClient::createSession($config, env('SAP_USERNAME'), env('SAP_PASSWORD'), $company."_LIVE");
                }
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
