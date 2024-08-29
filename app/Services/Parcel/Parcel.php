<?php
namespace App\Services\Parcel;
use App\Services\Parcel\Address;

abstract class Parcel
{
    protected $credentials;
    protected $pickupAddress;
    protected $deliverAddress;
    protected $reference;
    protected $amount ;


    public function __construct($credentials,?Address $pickupAddress = null,?Address $deliverAddress=null, $reference)
    {
        $this->credentials = $credentials;
        $this->pickupAddress = $pickupAddress;
        $this->deliverAddress = $deliverAddress;
        $this->reference = $reference;
    }
    
    
    abstract public function create($data);
    abstract public function fetch($trackingNumber , $url);
    abstract public function delete ($trackingNumber, $data);
    abstract public function save ($trackingNumber, $data);

   
}