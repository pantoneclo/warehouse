<?php
namespace App\Services\Parcel;

use App\Services\Parcel\Parcel;
use Illuminate\Support\Facades\Http;

class GlsParcel extends Parcel
{

    public function create($data)
    {
        // dd ( $data['count']);

        $crendentials = $this->credentials;
        $pickupAddress = $this->pickupAddress;
        $deliverAddress = $this->deliverAddress;
        $reference = $this->reference;
        // dd ($crendentials);
        // dd ($clientReference['ClientReference']);

        $parcels = array(
            "Username" => "khair@matrixapparels.com",
            "Password" => $crendentials['password'],

            "ParcelList" => array(
                array(
                    "ClientNumber" => 492380936,

                    "ClientReference" => "TEST PARCEL",
                    "CODAmount" => 100,
                    "CODReference" => "COD REFERENCE",
                    "Content" => "CONTENT",

                    "DeliveryAddress" => array(
                        'City' => $deliverAddress->city,
                        'ContactEmail' => $deliverAddress->contactEmail,
                        'ContactName' => $deliverAddress->contactName,
                        'ContactPhone' => $deliverAddress->contactPhone,
                        'CountryIsoCode' => $deliverAddress->countryIsoCode,
                        'HouseNumber' => $deliverAddress->houseNumber,
                        'HouseNumberInfo' => $deliverAddress->houseNumberInfo,
                        'Street' => $deliverAddress->street,
                        'ZipCode' => $deliverAddress->zipCode,
                        'Name' => $deliverAddress->name,
                    ),
                    "PickupAddress" => array(
                        'City' => $pickupAddress->city,
                        'ContactEmail' => $pickupAddress->contactEmail,
                        'ContactName' => $pickupAddress->contactName,
                        'ContactPhone' => $pickupAddress->contactPhone,
                        'CountryIsoCode' => $pickupAddress->countryIsoCode,
                        'HouseNumber' => $pickupAddress->houseNumber,
                        'HouseNumberInfo' => $pickupAddress->houseNumberInfo,
                        'Street' => $pickupAddress->street,
                        'ZipCode' => $pickupAddress->zipCode,
                        'Name' => $pickupAddress->name,
                    ),
                    "PickupDate" => $data['pickupDate'],
                    "ServiceList" => array(
                        array(
                            "Code" => "PSD",
                            "PSDParameter" => array(
                                "StringValue" => "2351-CSOMAGPONT",
                            ),
                        ),
                    ),
                ),
            ),
        );

// dd ($parcels);

        $url = 'https://api.mygls.si/ParcelService.svc/json/PrintLabels';

        $response = Http::post($url, $parcels, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        return $response;

    }

    private function performApiRequest($apiUrl, $requestData)
    {

        $response = Http::post($apiUrl, $requestData, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        if ($response->successful()) {

            $responseData = $response->json();
        } else {
            $responseData = $this->sendError('Failed to retrieve data from the API', $response->status());
        }

    }

    public function fetch($trackingNumber,$url)
    {
        
        $crendentials = $this->credentials;

       
        
       
        $requestData = [
            "Username" => $crendentials['username'],
            "Password" =>[236, 60, 201, 17, 65, 11, 89, 141, 116, 175, 136, 77, 77, 132, 247, 208, 35, 82, 83, 125, 57, 162, 64, 111, 26, 164, 175, 186, 204, 103, 180, 70, 198, 231, 107, 252, 157, 158, 129, 222, 26, 17, 167, 115, 153, 59, 43, 131, 82, 209, 203, 181, 46, 245, 38, 95, 171, 193, 196, 139, 77, 208, 203, 153],
            "ParcelNumber" => $trackingNumber,
            "LanguageIsoCode" => "si",
            "ReturnPOD" => "false",
        ];

        $response = Http::post($url, $requestData, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
      
        return $response;

     

    }

    // Implementing the remaining abstract methods
    public function delete($trackingNumber, $data)
    {
        // Implementation for the delete method
        // ...
    }

    public function save($trackingNumber, $data)
    {
        // Implementation for the save method
        // ...
    }
}
