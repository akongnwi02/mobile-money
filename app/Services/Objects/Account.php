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
}