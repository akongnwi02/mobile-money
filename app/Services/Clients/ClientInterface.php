<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/14/20
 * Time: 7:27 PM
 */

namespace App\Services\Clients;

use App\Services\Objects\Account;

interface ClientInterface
{
    /**
     * @param $accountNumber
     * @return Account
     */
    public function search($accountNumber): Account;
    
    /**
     * @param Account $account
     * @return bool
     */
    public function buy(Account $account) : bool;
}