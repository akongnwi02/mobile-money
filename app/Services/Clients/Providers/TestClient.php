<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/18/20
 * Time: 8:53 PM
 */

namespace App\Services\Clients;

use App\Services\Objects\Account;

class TestClient implements ClientInterface
{
    /**
     * @param $accountNumber
     * @return Account
     */
    public function search($accountNumber): Account
    {
        $account = new Account();
        $account->setAccountNumber($accountNumber)
            ->setName('John Doe')
            ->setReference('70487')
            ->setActive(true);
        return $account;
    }
    
    /**
     * @param Account $account
     * @return bool
     */
    public function buy(Account $account): bool
    {
        return true;
    }
}