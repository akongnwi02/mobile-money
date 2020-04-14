<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/18/20
 * Time: 8:53 PM
 */

namespace App\Services\Clients;

use App\Services\Objects\PrepaidMeter;

class TestClient implements ClientInterface
{
    public function search($meterCode): PrepaidMeter
    {
        $meter = new PrepaidMeter();
        $meter->setServiceCode('Test Code')
            ->setMeterCode($meterCode)
            ->setName('Duke')
            ->setAddress('123 Deido Douala');
        return $meter;
    }
    
    public function buy(PrepaidMeter $meter) : string
    {
        return '1254145478745254';
    }
}