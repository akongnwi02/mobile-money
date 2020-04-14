<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/14/20
 * Time: 7:27 PM
 */

namespace App\Services\Clients;

use App\Services\Objects\PrepaidMeter;

interface ClientInterface
{
    /**
     * @param $meterCode
     * @return PrepaidMeter
     */
    public function search($meterCode): PrepaidMeter;
    
    /**
     * @param PrepaidMeter $meter
     * @return string
     */
    public function buy(PrepaidMeter $meter) : string;
}