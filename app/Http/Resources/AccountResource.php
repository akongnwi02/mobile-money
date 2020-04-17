<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/16/20
 * Time: 10:53 PM
 */

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'account_number' => $this->accountNumber,
            'account_name' => $this->name,
        ];
    }
}