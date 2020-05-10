<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/16/20
 * Time: 10:16 PM
 */

namespace App\Services\Clients\Providers\Orange;


use App\Services\Clients\ClientInterface;
use App\Services\Objects\Account;

class OrangeClient implements ClientInterface
{
    
    /**
     * @param $accountNumber
     * @return Account
     */
    public function search($accountNumber): Account
    {
        // TODO: Implement search() method.
    }
    
    /**
     * @param Account $account
     * @return bool
     */
    public function buy(Account $account): bool
    {
        // TODO: Implement buy() method.
    }
}