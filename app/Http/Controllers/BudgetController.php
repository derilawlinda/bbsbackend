<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Session;

class BudgetController extends Controller
{


    public function createBudget($a=1)
    {
        //
        if(!Session::has('sapcookies')){
            $this->getCookie();
        }
        $cookie = Session::get('sapcookies');
        $queryMaxCode = Http::withoutVerifying()
        ->withOptions([
            'cookies' => $cookie,
            'verify'=>false
        ])
        ->withHeaders ([
            'content-Type' => 'application/json'
        ])->get('https://'.env('SAP_URL').':50000/b1s/v2/BudgetReq?$apply=aggregate(Code with max as MaxCode)');
        $contentMaxCode = $queryMaxCode->getBody();
        $arrayResponse = json_decode($contentMaxCode, true);
        if(array_key_exists("error",$arrayResponse)){
            $code = $arrayResponse['error']['code'];
            if($code == 301){ //if request timed out
                $this->getCookie();
                $this->createBduget();
            }
        }
        $maxCode = (int)$arrayResponse["value"][0]["MaxCode"];
        $currentCode = $maxCode + 1;
        $data = '{
            "Code" : '.$currentCode.',
            "Name" : "TestBudget2",
            "U_Project" : "BLIF 2023",
            "U_Pillar" : "Distribution",
            "U_Classification" : "Distribution 2023",
            "U_SubClass" : "Sponsorship 2023",
            "U_SubClass2" : "CSR Indomie 2023"
        }';
        $response = Http::withoutVerifying()
        ->withOptions([
            'cookies' => $cookie,
            'verify'=>false
        ])
        ->withBody($data,'application/json')
        ->withHeaders ([
            'content-Type' => 'application/json'
        ])->post('https://'.env('SAP_URL').':50000/b1s/v2/BudgetReq');

        $content = $response->getBody();
        $array = json_decode($content, true);
        if(array_key_exists("error",$array)){
            $code = $array['error']['code'];
            if($code == 301){ //if request timed out
                $this->getCookie();
                $this->createBduget();
            }
        }
        echo $response;
    }

    public function getBudget($skip=20)
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
            'content-Type' => 'application/json',
            'OData-Version' => '4.0'

        ])->get('https://'.env('SAP_URL').':50000/b1s/v2/BudgetReq?$select=Code,Name,U_Project,U_Pillar,U_Classification,U_SubClass,U_SubClass2&$skip='.$skip);

        $content = $response->getBody();
        $array = json_decode($content, true);
        if(array_key_exists("error",$array)){
            $code = $array['error']['code'];
            if($code == 301){ //if request timed out
                $this->getCookie();
                $this->getBudget();
            }
        }
        echo $response;
    }

    public function metadata()
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
            'content-Type' => 'application/json'
        ])->get('https://'.env('SAP_URL').':50000/b1s/v2/$metadata');

        $content = $response->getBody();
        header('Access-Control-Allow-Origin : *');
        echo $response;
    }

    public function getCookie()
    {
        $response = Http::withoutVerifying()
        ->withOptions(["verify"=>false])
        ->withHeaders ([
            'content-Type' => 'application/json'
        ])
        ->post('https://'.env('SAP_URL').':50000/b1s/v2/Login', [
            'CompanyDB' => 'POS_29JUN',
            'UserName' => 'manager',
            'Password' => '1234'
        ]);
        $cookies = $response->cookies();
        Session::put('sapcookies', $cookies);
        return $cookies;
    }
}
