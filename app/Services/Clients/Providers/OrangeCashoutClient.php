<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/17/20
 * Time: 11:34 PM
 */

namespace App\Services\Clients\Providers;


use App\Services\Objects\Account;

class OrangeCashoutClient extends OrangeClient
{
    public function buy(Account $account): bool
    {
        parent::buy($account); // TODO: Change the autogenerated stub
    }
}