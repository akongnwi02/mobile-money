<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 10/13/20
 * Time: 6:19 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Authentication extends Model
{
    protected $fillable = [
        'expires_in',
        'access_token',
        'refresh_token',
        'service_code',
        'type'
    ];
    
    protected $casts = [
        'expires_in' => 'integer',
    ];
}