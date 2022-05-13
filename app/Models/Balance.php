<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/28/20
 * Time: 8:53 PM
 */

namespace App\Models;


use App\Models\Notifications\NotificationTrait;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use NotificationTrait;

    protected $table = 'balance';

    protected $fillable = [
        'previous',
        'current',
        'time',
        'service_code',
    ];
    
    protected $casts = [
        'previous' => 'double',
        'current' => 'double',
    ];
}