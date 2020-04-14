<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/16/20
 * Time: 5:14 PM
 */

namespace App\Services\Objects;


/**
 * Class PrepaidMeter
 * @package App\Services\Objects
 */
class PrepaidMeter
{
    /**
     * @var
     */
    public $int_id;
    /**
     * @var
     */
    public $meter_code;
    /**
     * @var
     */
    public $service_code;
    /**
     * @var
     */
    public $address;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $phone;
    /**
     * @var
     */
    public $email;
    /**
     * @var
     */
    public $city;
    /**
     * @var
     */
    public $state;
    /**
     * @var
     */
    public $price;
    /**
     * @var
     */
    public $amount;
    /**
     * @var
     */
    public $energy;
    
    /**
     * @return mixed
     */
    public function getMeterCode()
    {
        return $this->meter_code;
    }
    
    /**
     * @param mixed $meter_code
     * @return PrepaidMeter
     */
    public function setMeterCode($meter_code)
    {
        $this->meter_code = $meter_code;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }
    
    /**
     * @param mixed $address
     * @return PrepaidMeter
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @param mixed $name
     * @return PrepaidMeter
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }
    
    /**
     * @param mixed $price
     * @return PrepaidMeter
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }
    
    /**
     * @param mixed $amount
     * @return PrepaidMeter
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getEnergy()
    {
        return $this->energy;
    }
    
    /**
     * @param mixed $energy
     * @return PrepaidMeter
     */
    public function setEnergy($energy)
    {
        $this->energy = $energy;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getIntId()
    {
        return $this->int_id;
    }
    
    /**
     * @param $int_id
     * @return PrepaidMeter
     */
    public function setIntId($int_id)
    {
        $this->int_id = $int_id;
        return $this;
        
    }
    
    /**
     * @param mixed $service_code
     * @return PrepaidMeter
     */
    public function setServiceCode($service_code)
    {
        $this->service_code = $service_code;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getServiceCode()
    {
        return $this->service_code;
    }
    
    /**
     * @param mixed $phone
     * @return PrepaidMeter
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }
}