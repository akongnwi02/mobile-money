<?php

namespace App\Jobs;

use App\Exceptions\BadRequestException;
use App\Models\Transaction;
use App\Notifications\PurchaseError;
use App\Services\Clients\ClientProvider;
use App\Services\Constants\ErrorCodesConstants;
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
            'destination'    => $this->transaction->destination,
            'service_code'   => $this->transaction->service_code,
        ]);
        $this->transaction->status            = TransactionConstants::PROCESSING;
        $this->transaction->purchase_attempts = $this->attempts();
        $this->transaction->save();
        
        $account->setAmount($this->transaction->amount)
            ->setServiceCode($this->transaction->service_code)
            ->setAccountNumber($this->transaction->destination)
            ->setIntId($this->transaction->internal_id);
        
        try {
            $this->client($account->getServiceCode())->buy($account);
            
            // if the provider will send a callback request, terminate early
            if ($this->transaction->is_asynchronous) {
                Log::info("{$this->getJobName()}: Transaction initiated successfully. Insert job to status checking queue while waiting for callback from provider", [
                    'status'         => $this->transaction->status,
                    'transaction.id' => $this->transaction->id,
                    'destination'    => $this->transaction->destination,
                    'service_code'   => $this->transaction->service_code,
                ]);
                
                // dispatch verification job to verify transaction status at regular interval
                dispatch(new VerificationJob($this->transaction))->onQueue(QueueConstants::VERIFICATION_QUEUE);
                
                $this->delete();
    
                return;
            }
            $this->transaction->status  = TransactionConstants::SUCCESS;
            $this->transaction->message = 'Transaction completed successfully';
            $this->transaction->save();
            
            Log::info("{$this->getJobName()}: Transaction effectuated successfully. Inserted into CALLBACK queue", [
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'service'        => $this->transaction->service_code,
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
                'service'        => $this->transaction->service_code,
                'exception'      => $exception,
            ]);
            
            /*
             * Transaction failed due to a client error, dispatch to callback queue
             */
            dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
        }
    }
    
    /**
     * @param \Exception|null $exception
     */
    public function failed(\Exception $exception = null)
    {
        $this->transaction->status     = TransactionConstants::ERRORED;
        $this->transaction->message    = 'Transaction failed unexpectedly';
        $this->transaction->error      = $exception->getMessage();
        $this->transaction->error_code = ErrorCodesConstants::GENERAL_CODE;
        $this->transaction->save();
        Log::emergency("{$this->getJobName()}: Transaction failed unexpectedly during purchase. Inserted into VERIFICATION queue", [
            'transaction.status'      => $this->transaction->status,
            'transaction.id'          => $this->transaction->id,
            'transaction.destination' => $this->transaction->destination,
            'transaction.amount'      => $this->transaction->amount,
            'transaction.message'     => $this->transaction->message,
            'transaction.error'       => $this->transaction->error,
            'transaction.error_code'  => $this->transaction->error_code,
            'transaction.external_id' => $this->transaction->external_id,
            'transaction.service'     => $this->transaction->service_code,
            'exception'               => $exception,
        ]);
        
        if (config('app.enable_notifications')) {
            try {
                Log::info("{$this->getJobName()}: Notifying administrator of the failure");
                $this->transaction->notify(new PurchaseError($this->transaction));
            } catch (\Exception $exception) {
                Log::error("{$this->getJobName()}: Error sending notification");
            }
        }
        
        /*
         * Transaction failed due to a unexpected error, dispatch to verification queue
         */
        dispatch(new StatusJob($this->transaction))->onQueue(QueueConstants::STATUS_QUEUE);
    }
    
    public function getJobName()
    {
        return class_basename($this);
    }
}