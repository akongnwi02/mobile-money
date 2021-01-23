<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 1/23/21
 * Time: 4:11 PM
 */

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseError extends Notification
{
    use Queueable;
    
    public $transaction;
    
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'mail',
//            'sms',
        ];
    }
    
    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject(config('app.name').': '. $this->getName())
            ->greeting('Dear Administrator')
            ->line('The transaction below failed when trying to initiate payment with the service provider. There maybe an error with the service provider\'s API')
            ->line($this->transaction->error)
            ->line($this->transaction->toJson())
            ->line('Login to the corlapay app to view more details about the transaction')
            ->error();
        
    }
    
//    public function toSms($notifiable)
//    {
//        return (new SmsMessage())
//            ->content(__('strings.emails.companies.companies.sms.company_created', [
//                'first_name' => $notifiable->owner->first_name,
//                'account' => $notifiable->name,
//                'app_name' => app_name(),
//            ]))
//            ->content(__('strings.emails.companies.companies.sms.login'));
//    }
    
    public function getName()
    {
        return class_basename($this);
    }
}