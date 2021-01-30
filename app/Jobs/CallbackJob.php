<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Notifications\CallbackError;
use App\Services\Clients\CallbackClient;
use Illuminate\Support\Facades\Log;

class CallbackJob extends Job
{
    /**
     * @var Transaction
     */
    public $transaction;
    
    /**
     * Avoid processing deleted jobs
     */
    public $deleteWhenMissingModels = true;
    
    /**
     * Number of retries
     * @var int
     */
    public $tries = 10;
    
    /**
     * Timeout
     * @var int
     */
    public $timeout = 120;
    
    /**
     * Create a new job instance.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
    
    /**
     * Execute the job.
     *
     * @param CallbackClient $callbackClient
     * @return void
     */
    public function handle(CallbackClient $callbackClient)
    {
        Log::info("{$this->getJobName()}: Processing new callback job", [
            'status'         => $this->transaction->status,
            'transaction.id' => $this->transaction->id,
            'destination'    => $this->transaction->destination,
            'callback_url'   => $this->transaction->callback_url,
            'service'        => $this->transaction->service_code,
        ]);
    
        /*
         * Make sure we don't send callback for a transaction which was previously sent
         */
        if ($this->transaction->is_callback_sent) {
            Log::warning("{$this->getJobName()}: Trying to re-send callback for a transaction which was previously sent", [
                'transaction.status'                => $this->transaction->status,
                'transaction.id'                    => $this->transaction->id,
                'transaction.destination'           => $this->transaction->destination,
                'transaction.amount'                => $this->transaction->amount,
                'transaction.message'               => $this->transaction->message,
                'transaction.error'                 => $this->transaction->error,
                'transaction.error_code'            => $this->transaction->error_code,
                'transaction.external_id'           => $this->transaction->external_id,
                'transaction.verification_attempts' => $this->transaction->verification_attempts,
                'transaction.service'               => $this->transaction->service_code,
        
            ]);
            $this->delete();
            return;
        }
        $this->transaction->callback_attempts = $this->attempts();
        $this->transaction->save();
        try {
            $callbackClient->send($this->transaction);
            
            $this->transaction->is_callback_sent = true;
            $this->transaction->save();
            Log::info("{$this->getJobName()}: Callback request sent successful", [
                'transaction.status' => $this->transaction->status,
                'transaction.id'     => $this->transaction->id,
                'destination'        => $this->transaction->destination,
                'callback_url'       => $this->transaction->callback_url,
                'service'            => $this->transaction->service_code,
            ]);
        } catch (\Exception $e) {
            Log::info("{$this->getJobName()}: Callback request failed", [
                'error message'      => $e->getMessage(),
                'transaction.status' => $this->transaction->status,
                'transaction.id'     => $this->transaction->id,
                'destination'        => $this->transaction->destination,
                'callback_url'       => $this->transaction->callback_url,
                'service'            => $this->transaction->service_code,
            ]);
            $this->release($this->attempts() * 2);
        }
    }
    
    public function failed(\Exception $exception = null)
    {
        Log::emergency("{$this->getJobName()}: Callback request could not be sent to callback url", [
            'error message'                 => $exception->getMessage(),
            'transaction.status'            => $this->transaction->status,
            'transaction.id'                => $this->transaction->id,
            'transaction.destination'       => $this->transaction->destination,
            'transaction.callback_url'      => $this->transaction->callback_url,
            'transaction.external_id'       => $this->transaction->external_id,
            'transaction.amount'            => $this->transaction->amount,
            'transaction.callback_attempts' => $this->transaction->callback_attempts,
            'transaction.service'           => $this->transaction->service_code,

        ]);
        
    
        if (config('app.enable_notifications')) {
            try {
                Log::info("{$this->getJobName()}: Notifying administrator of the failure");
                $this->transaction->notify(new CallbackError($this->transaction));
            } catch (\Exception $exception) {
                Log::error("{$this->getJobName()}: Error sending notification");
            }
        }
    
    }
    
    public function getJobName()
    {
        return class_basename($this);
    }
}
