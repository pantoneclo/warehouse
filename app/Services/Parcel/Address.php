<?php
namespace App\Services\Parcel;

class Address
{
    public 
    $city,
    $contactEmail,
    $contactPhone,
    $contactName,
    $countryIsoCode,
    $houseNumber,
    $street,
    $zipCode,
    $name,
    $houseNumberInfo;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
