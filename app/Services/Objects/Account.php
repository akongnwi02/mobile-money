<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/16/20
 * Time: 10:09 PM
 */

namespace App\Services\Objects;


class Account
{
    public $accountNumber;
    public $amount;
    public $name;
    public $reference;
    public $serviceCode;
    public $int_id;
    public $active;
    
    /**
     * @return mixed
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }
    
    /**
     * @param mixed $accountNumber
     * @return Account
     */
    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = $accountNumber;
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
     * @return Account
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }
    
    /**
     * @param mixed $reference
     * @return Account
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
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
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getServiceCode()
    {
        return $this->serviceCode;
    }
    
    /**
     * @param mixed $serviceCode
     * @return Account
     */
    public function setServiceCode($serviceCode)
    {
        $this->serviceCode = $serviceCode;
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
     * @param mixed $internal_id
     * @return Account
     */
    public function setIntId($internal_id)
    {
        $this->int_id = $internal_id;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }
    
    /**
     * @param mixed $active
     * @return Account
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }
}