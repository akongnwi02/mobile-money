<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/14/20
 * Time: 7:27 PM
 */

namespace App\Services\Clients;

use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Services\Objects\Account;
use App\Services\Objects\BalanceObject;

interface ClientInterface
{
    /**
     * @param $accountNumber
     * @return Account
     */
    public function search($accountNumber): Account;
    
    /**
     * @param Account $account
     * @throws GeneralException
     * @throws BadRequestException
     * @return bool
     */
    public function buy(Account $account) : bool;

    /**
     * @return BalanceObject
     */
    public function balance(): BalanceObject;
}