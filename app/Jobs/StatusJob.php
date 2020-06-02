<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/19/20
 * Time: 1:30 AM
 */

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\Clients\ClientProvider;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Support\Facades\Log;

class StatusJob extends Job
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
     * Timeout
     * @var int
     */
    public $timeout = 150;
    /**
     * Number of retries
     * @var int
     */
    public $tries = 5;
    
    /**
     * Create a new job instance.
     * @param $transaction
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }
    
    /**
     *
     */
    public function handle()
    {
        Log::info("{$this->getJobName()}: Processing new status verification job", [
            'status'         => $this->transaction->status,
            'transaction.id' => $this->transaction->id,
            'destination'    => $this->transaction->destination,
            'service'        => $this->transaction->service_code,
        ]);
        
        if (in_array($this->transaction->status, [
            TransactionConstants::FAILED,
            TransactionConstants::SUCCESS,
        ])) {
            Log::warning("{$this->getJobName()}: Transaction is already in final state. No further verification is required", [
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
        
        $this->transaction->verification_attempts = $this->attempts();
        $this->transaction->status                = TransactionConstants::VERIFICATION;
        $this->transaction->save();
        
        try {
            $this->client($this->transaction->service_code)->status($this->transaction);
            if ($this->transaction->is_asynchronous) {
                $this->transaction->status  = TransactionConstants::PROCESSING;
                $this->transaction->message = 'Transaction exists in provider system. Waiting callback from service provider';
                $this->transaction->save();
    
                Log::info("{$this->getJobName()}: Transaction saved by verification worker. Waiting for callback from provider", [
                    'status'         => $this->transaction->status,
                    'transaction.id' => $this->transaction->id,
                    'destination'    => $this->transaction->destination,
                    'service'        => $this->transaction->service_code,
                ]);
                $this->delete();
    
                // dispatch verification job to verify transaction status at regular interval
                dispatch(new VerificationJob($this->transaction))->onQueue(QueueConstants::VERIFICATION_QUEUE);
    
                return;
            }
            $this->transaction->status  = TransactionConstants::SUCCESS;
            $this->transaction->message = 'Transaction updated to success by verification worker';
            $this->transaction->save();
            
            Log::info("{$this->getJobName()}: Status updated to success, inserting transaction to callback queue", [
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'service'        => $this->transaction->service_code,
            ]);
            
            /*
             * Transaction was found successful after status verification.
             * Insert to callback queue
             */
            dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
            
            $this->delete();
            
        } catch (\Exception $e) {
            Log::info("{$this->getJobName()}: Status verification attempt failed", [
                'error message'  => $e->getMessage(),
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'callback_url'   => $this->transaction->callback_url,
                'service'        => $this->transaction->service_code,
                'attempts'       => $this->attempts(),
            ]);
            
            /*
             * Delay job before attempting the next status verification
             */
            $this->release($this->attempts() * 5);
        }
    }
    
    public function failed(\Exception $exception = null)
    {
        $this->transaction->status         = TransactionConstants::FAILED;
        $this->transaction->message        = 'Transaction failed unexpectedly while verifying status';
        $this->transaction->to_be_verified = true;
        $this->transaction->save();
        Log::emergency("{$this->getJobName()}: Transaction failed unexpectedly during status check. Inserted into CALLBACK queue", [
            'transaction.status'                => $this->transaction->status,
            'transaction.id'                    => $this->transaction->id,
            'transaction.destination'           => $this->transaction->destination,
            'transaction.amount'                => $this->transaction->amount,
            'transaction.message'               => $this->transaction->message,
            'transaction.error'                 => $this->transaction->error,
            'transaction.error_code'            => $this->transaction->error_code,
            'transaction.external_id'           => $this->transaction->external_id,
            'transaction.verification_attempts' => $this->transaction->verification_attempts,
            'service'                           => $this->transaction->service_code,
            'exception'                         => $exception,
        ]);
        
        /*
         * Transaction Status cannot be determined after several retries. Send to callback queue
         */
        dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
    }
    
    public function getJobName()
    {
        return class_basename($this);
    }
}