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

class MtnCashinClient extends MtnClient
{
    /**
     * @param $accountNumber
     * @return Account
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search($accountNumber): Account
    {
        return parent::verifyNumber($accountNumber);
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
        return parent::performTransaction($account);
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BadRequestException
     */
    public function status($transaction)
    {
        return parent::verifyTransaction($transaction);
    }
}