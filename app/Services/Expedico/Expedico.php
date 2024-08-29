<?php
namespace App\Services\Expedico;
use App\Services\Expedico\Parcel;
use Illuminate\Support\Facades\Http;
 class Expedico extends Parcel
{
    public function fetch ($trackingNumber){
       
        $credential = $this->credentials;
        $url =" https://expedico.eu/api/v2/parcels/";

        $response = Http::withBasicAuth($credential['username'], $credential['password'])

       ->get('https://expedico.eu/api/v2/parcels/'.$trackingNumber.'/tracking')
         ->json();
       return $response;
       


    }
 

}