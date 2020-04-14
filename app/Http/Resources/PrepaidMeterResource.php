<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/16/20
 * Time: 5:12 PM
 */

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrepaidMeterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'meter_code'   => $this->meter_code,
            'service_code' => $this->service_code,
            'address'      => $this->address,
            'name'         => $this->name,
        ];
    }
}