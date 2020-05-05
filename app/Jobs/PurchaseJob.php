<?php

namespace App\Jobs;

use App\Exceptions\BadRequestException;
use App\Models\Transaction;
use App\Services\Clients\ClientProvider;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use App\Services\Objects\Account;
use Illuminate\Support\Facades\Log;

class PurchaseJob extends Job
{
    use ClientProvider;
    
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
    public $tries = 1;
    
    /**
     * Timeout
     * @var int
     */
    public $timeout = 300;
    
    /**
     * Create a new job instance.
     * @param $transaction
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }
    
    /**
     * Execute the job.
     *
     * @param Account $account
     * @return void
     * @throws \App\Exceptions\GeneralException
     */
    public function handle(Account $account)
    {
        Log::info("{$this->getJobName()}: Processing new purchase job", [
            'status'         => $this->transaction->status,
            'transaction.id' => $this->transaction->id,
            'destination'    => $this->transaction->destination
        ]);
        $this->transaction->status            = TransactionConstants::PROCESSING;
        $this->transaction->purchase_attempts = $this->attempts();
        $this->transaction->save();
        
        $meter->setAmount($this->transaction->amount)
            ->setMeterCode($this->transaction->destination)
            ->setServiceCode($this->transaction->service_code)
            ->setPhone($this->transaction->phone)
            ->setIntId($this->transaction->internal_id);
        
        try {
            $token = $this->client($meter->getServiceCode())->buy($meter);
            
            $this->transaction->asset   = $token;
            $this->transaction->status  = TransactionConstants::SUCCESS;
            $this->transaction->message = 'Transaction completed successfully';
            $this->transaction->save();
            
            Log::info("{$this->getJobName()}: Transaction effectuated successfully. Inserted into CALLBACK queue", [
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination
            ]);
            
            /*
             * Transaction successful, dispatch to callback queue
             */
            dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
            
        } catch (BadRequestException $exception) {
            $this->transaction->status     = TransactionConstants::FAILED;
            $this->transaction->error      = $exception->getMessage();
            $this->transaction->message    = 'Transaction failed due to a client error';
            $this->transaction->error_code = $exception->error_code();
            $this->transaction->save();
            
            Log::info("{$this->getJobName()}: Transaction failed due to client error. Inserted into CALLBACK queue", [
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'exception'      => $exception,
            ]);
            
            /*
             * Transaction failed due to a client error, dispatch to callback queue
             */
            dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
        }
        
    }
    
    public function getJobName()
    {
        return class_basename($this);
    }
}