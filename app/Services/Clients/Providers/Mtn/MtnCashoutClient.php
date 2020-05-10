<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/9/20
 * Time: 2:44 PM
 */

namespace App\Services\Clients\Providers\Mtn;


use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Services\Objects\Account;

class MtnCashoutClient extends MtnClient
{
    public $subscription = 'collection';
    public $performUrl = '/collection/v1_0/requesttopay';
    /**
     * @param $accountNumber
     * @return Account
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search($accountNumber): Account
    {
        return parent::verifyNumber(
            $accountNumber,
            $this->subscription,
            config('app.services.mtn.cashout_key'),
            config('app.services.mtn.cashout_code'));
    }
    
    /**
     * @param Account $account
     * @return bool
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buy(Account $account): bool
    {
        return parent::performTransaction(
            $account,
            $this->subscription,
            $this->performUrl,
            config('app.services.mtn.cashout_key'),
            config('app.services.mtn.cashout_code'));
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function status($transaction)
    {
        return parent::verifyTransaction(
            $transaction,
            $this->subscription,
            $this->performUrl,
            config('app.services.mtn.cashout_key'),
            config('app.services.mtn.cashout_code'));
    }
    
    public function getClientName(): string
    {
        return class_basename($this);
    }
}