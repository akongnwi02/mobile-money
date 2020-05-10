<?php

/**
 * Created by PhpStorm.
 * User: devert
 * Date: 8/31/19
 * Time: 12:06 AM
 */

namespace App\Models;

use App\Models\Attributes\TransactionAttribute;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use TransactionAttribute;
    
    protected $fillable = [];

    protected $casts = [
        'amount' => 'double',
        'is_callback_sent' => 'boolean'
    ];
}