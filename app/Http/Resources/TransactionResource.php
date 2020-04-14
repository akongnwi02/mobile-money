<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 10:30 PM
 */

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'destination'  => $this->destination,
            'internal_id'  => $this->internal_id,
            'external_id'  => $this->external_id,
            'status'       => $this->status,
            'service_code' => $this->service_code,
            'asset'        => $this->asset,
            'error'        => $this->error,
            'message'      => $this->message,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'address'      => $this->address,
            'name'         => $this->name,
        ];
    }
}
