<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/10/20
 * Time: 3:23 PM
 */

namespace App\Models\Attributes;


trait TransactionAttribute
{
    public function getIsAsynchronousAttribute()
    {
        return in_array($this->service_code, [
            config('app.services.mtn.cashout_code'),
            config('app.services.mtn.cashin_code'),
            config('app.services.orange.webpayment_code')
        ]);
    }
}