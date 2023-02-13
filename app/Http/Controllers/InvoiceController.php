<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Session;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * post Invoice to SAP
     *
     * @return \Illuminate\Http\Response
     */
    
    public function postInvoice($a=1)
    {
        //
        if(!Session::has('sapcookies')){
            $this->getCookie();
        }
        $cookie = Session::get('sapcookies');
        $data = '
        {
            "CardCode": "CA00000010",
            "Comments": "Tahap II (20%) O&M GI UPT Manado Bulan Agustus 2022",
            "U_PUNYA_REGION": "SUL1",
            "DocDate": "2023-01-20T00:00:00Z",
            "TaxDate": "2023-01-05T00:00:00Z",
            "DocDueDate": "2023-01-05T00:00:00Z",
            "NumAtCard": "030.003-23.83877729",
            "DocumentLines": [
                {
                    "ItemCode": "OPGI_MDO",
                    "Quantity": "1",
                    "VatGroup": "TO-WAPU",
                    "UnitPrice": "95288840",
                    "ProjectCode": "SUL1_2500_08_0005",
                    "CostingCode": "SUL1",
                    "WarehouseCode": "SUL1"
                }
            ],
            "WithholdingTaxDataCollection": [
                {
                    "WTCode": "C23",
                    "WTAmountSys": 0.0,
                    "WTAmountFC": 0.0,
                    "WTAmount": 1905777.0,
                    "WithholdingType": "",
                    "TaxableAmountinSys": 0.0,
                    "TaxableAmountFC": 0.0,
                    "TaxableAmount": 95288840,
                    "RoundingType": "C",
                    "Rate": 2.0
                }
            ]
        }
        ';
        $response = Http::withoutVerifying()
        ->withOptions([
            'cookies' => $cookie,
            'verify'=>false
        ])
        ->withBody($data,'application/json')
        ->withHeaders ([
            'content-Type' => 'application/json'
        ])->post('https://103.145.180.54:50000/b1s/v2/Invoices');
   
        $content = $response->getBody();
        $array = json_decode($content, true);
        if(array_key_exists("error",$array)){
            $code = $array['error']['code'];
            if($code == 301){ //if request timed out 
                $this->getCookie();
                $this->postInvoice();
            }
        }
        echo $response;
    }

    public function getCookie() 
    {
        $response = Http::withoutVerifying()
        ->withOptions(["verify"=>false])
        ->withHeaders ([
            'content-Type' => 'application/json'
        ])
        ->post('https://103.145.180.54:50000/b1s/v2/Login', [
            'CompanyDB' => 'SPNOTIF24JAN',
            'UserName' => 'manager',
            'Password' => '1234'
        ]);
        $cookies = $response->cookies();
        Session::put('sapcookies', $cookies);
        return $cookies;
    }
}
