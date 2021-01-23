<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 1/23/21
 * Time: 6:44 PM
 */

namespace App\Models\Notifications;


use Illuminate\Notifications\Notifiable;

trait NotificationTrait
{
    use Notifiable;
    
    
    public function routeNotificationForMail()
    {
        return config('mail.from.address');
    }
}