<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/9/20
 * Time: 2:46 PM
 */

namespace App\Services\Clients\Providers\Orange;

use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Services\Objects\Account;

class OrangeWebPaymentClient extends OrangeClient
{
    /**
     * @param Account $account
     * @return bool
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws GeneralException
     */
    public function buy(Account $account): bool
    {
        return parent::buy($account);
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function status($transaction)
    {
        return parent::status($transaction);
    }
    
    /**
     * @return string
     */
    public function getClientName(): string
    {
        return class_basename($this);
    }
}