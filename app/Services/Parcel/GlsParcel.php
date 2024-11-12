<?php
namespace App\Services\Parcel;

use Illuminate\Support\Facades\Http;

class GlsParcel
{
    protected $credentials;
    protected $pickupAddress;
    protected $deliverAddress;
    protected $reference;

    public function __construct($credentials, $pickupAddress = null, $deliverAddress = null, $reference = null)
    {
        $this->credentials = $credentials;
        $this->pickupAddress = $pickupAddress;
        $this->deliverAddress = $deliverAddress;
        $this->reference = $reference;
    }

    public function create($data)
    {
        $parcels = [
            "Username" => $this->credentials['username'],
            "Password" => $this->credentials['password'],
            "ParcelList" => [
                [
                    "ClientNumber" => env('MYGLS_CLIENT_NUMBER'),
                    "ClientReference" => $this->reference ?? "TEST PARCEL",
                    "CODAmount" => 100,
                    "CODReference" => "COD REFERENCE",
                    "Content" => "CONTENT",
                    "DeliveryAddress" => [
                        'City' => $this->deliverAddress->city,
                        'ContactEmail' => $this->deliverAddress->contactEmail,
                        'ContactName' => $this->deliverAddress->contactName,
                        'ContactPhone' => $this->deliverAddress->contactPhone,
                        'CountryIsoCode' => $this->deliverAddress->countryIsoCode,
                        'HouseNumber' => $this->deliverAddress->houseNumber,
                        'HouseNumberInfo' => $this->deliverAddress->houseNumberInfo,
                        'Street' => $this->deliverAddress->street,
                        'ZipCode' => $this->deliverAddress->zipCode,
                        'Name' => $this->deliverAddress->name,
                    ],
                    "PickupAddress" => [
                        'City' => $this->pickupAddress->city,
                        'ContactEmail' => $this->pickupAddress->contactEmail,
                        'ContactName' => $this->pickupAddress->contactName,
                        'ContactPhone' => $this->pickupAddress->contactPhone,
                        'CountryIsoCode' => $this->pickupAddress->countryIsoCode,
                        'HouseNumber' => $this->pickupAddress->houseNumber,
                        'HouseNumberInfo' => $this->pickupAddress->houseNumberInfo,
                        'Street' => $this->pickupAddress->street,
                        'ZipCode' => $this->pickupAddress->zipCode,
                        'Name' => $this->pickupAddress->name,
                    ],
                    "PickupDate" => $data['pickupDate'],
                    "ServiceList" => [
                        [
                            "Code" => "PSD",
                            "PSDParameter" => [
                                "StringValue" => "2351-CSOMAGPONT",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $url = env('MYGLS_HOST') . '/PrintLabels';

        $response = Http::post($url, $parcels, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return $response->json();
    }

    public function fetch($trackingNumber)
    {
        $requestData = [
            "Username" => $this->credentials['username'],
            "Password" => $this->credentials['password'],
            "ParcelNumber" => $trackingNumber,
            "LanguageIsoCode" => "si",
            "ReturnPOD" => false,
        ];

        $url = env('MYGLS_HOST') . '/GetParcelStatuses';

        $response = Http::post($url, $requestData, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return $response->json();
    }

    public function delete($trackingNumber, $data)
    {
        // Implementation for the delete method
    }

    public function save($trackingNumber, $data)
    {
        // Implementation for the save method
    }
}

