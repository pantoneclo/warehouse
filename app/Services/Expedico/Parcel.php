<?php
namespace App\Services\Expedico;
abstract class Parcel
{
    protected $credentials;

   
    public function __construct($credentials)
    {
        $this->credentials = $credentials; 
      
    }
    abstract public function fetch($trackingNumber );
 
}
