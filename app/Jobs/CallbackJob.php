<?php

namespace App\Jobs;

use App\Exceptions\GeneralException;
use App\Models\Transaction;
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
        Log::info('Processing new callback job', [
            'transaction status' => $this->transaction->status,
            'transaction id' => $this->transaction->internal_id,
            'callback_url' => $this->transaction->callback_url,
        ]);
        
        $this->transaction->callback_attempts = $this->attempts();
        $this->transaction->save();
        try {
            $callbackClient->send($this->transaction);
            $this->transaction->is_callback_sent = true;
            $this->transaction->save();
            Log::info('Callback request success', [
                'transaction status' => $this->transaction->status,
                'transaction id' => $this->transaction->internal_id,
                'callback_url' => $this->transaction->callback_url,
            ]);
        } catch (GeneralException $e) {
            Log::info('Callback request failed', [
                'error message' => $e->getMessage(),
                'transaction status' => $this->transaction->status,
                'transaction id' => $this->transaction->internal_id,
                'callback_url' => $this->transaction->callback_url,
            ]);
            $this->release($this->attempts()*2);
        }
    }
}
