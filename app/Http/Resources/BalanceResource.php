<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'current_balance' => $this->current,
            'previous_balance' => $this->previous,
            'time_requested' => $this->time,
            'service_code' => $this->service_code,
        ];
    }
}