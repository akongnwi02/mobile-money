<?php


namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalanceError extends Notification
{
    use Queueable;

    public $error;

    public function __construct($error)
    {
        $this->error = $error;
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
            // 'sms',
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
            ->line('There was a problem getting the account balance from the service provider.')
            ->line($this->error)
            ->line('Check the logs of the mobile money microservice')
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