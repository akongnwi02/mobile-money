<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/17/20
 * Time: 11:33 PM
 */

namespace App\Services\Clients\Providers\Orange;


use App\Services\Objects\Account;

class OrangeCashinClient extends OrangeClient
{
    /**
     * @param Account $account
     * @return bool
     * @throws \App\Exceptions\BadRequestException
     * @throws \App\Exceptions\GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buy(Account $account): bool
    {
        return parent::buy($account);
    }
    
    /**
     * @param $accountNumber
     * @return Account
     * @throws \App\Exceptions\BadRequestException
     * @throws \App\Exceptions\GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search($accountNumber): Account
    {
        return parent::search($accountNumber);
    }
}