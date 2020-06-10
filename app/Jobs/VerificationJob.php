<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 6/2/20
 * Time: 10:44 PM
 */

namespace App\Jobs;


use App\Models\Transaction;
use App\Services\Clients\ClientProvider;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Support\Facades\Log;

class VerificationJob extends Job
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
    public $timeout = 500;
    /**
     * Number of retries
     * @var int
     */
    public $tries = 50;
    
    /**
     * Time to sleep for a transaction
     * @var int
     */
    public $sleep = 8;
    
    /**
     * Create a new job instance.
     * @param $transaction
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }
    
    public function handle()
    {
        Log::info("{$this->getJobName()}: Checking if transaction status is final at service provider's side", [
            'transaction.id'          => $this->transaction->id,
            'transaction.destination' => $this->transaction->destination,
            'transaction.service'     => $this->transaction->service_code,
            'transaction.merchant_id' => $this->transaction->merchant_id,
            'transaction.external_id' => $this->transaction->external_id,
            'transaction.internal_id' => $this->transaction->internal_id,
            'transaction.stats'       => $this->transaction->status,
            'transaction.amount'      => $this->transaction->amount,
        ]);
        
        // verify if transaction status is final
        if (in_array($this->transaction->status, [
            TransactionConstants::FAILED,
            TransactionConstants::SUCCESS,
        ])) {
            Log::warning("{$this->getJobName()}: Transaction is already in final state locally. No further check is required at service provider's side", [
                'transaction.status'                => $this->transaction->status,
                'transaction.id'                    => $this->transaction->id,
                'transaction.destination'           => $this->transaction->destination,
                'transaction.amount'                => $this->transaction->amount,
                'transaction.message'               => $this->transaction->message,
                'transaction.error'                 => $this->transaction->error,
                'transaction.error_code'            => $this->transaction->error_code,
                'transaction.external_id'           => $this->transaction->external_id,
                'transaction.merchant_id'           => $this->transaction->merchant_id,
                'transaction.internal_id'           => $this->transaction->internal_id,
                'transaction.verification_attempts' => $this->transaction->verification_attempts,
                'transaction.service'               => $this->transaction->service_code,
            ]);
            $this->delete();
            return;
        }
        
        try {
            $success = $this->client($this->transaction->service_code)->finalStatus($this->transaction);
            if ($success) {
                $this->transaction->status = TransactionConstants::SUCCESS;
            } else {
                $this->transaction->status     = TransactionConstants::FAILED;
                $this->transaction->error_code = ErrorCodesConstants::GENERAL_CODE;
            }
            $this->transaction->message = 'Final status decided by the verification manager job';
            $this->transaction->save();
            
            Log::info("{$this->getJobName()}: Final status received by verification worker, inserting transaction to CALLBACK queue", [
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'service'        => $this->transaction->service_code,
                'merchant_id'    => $this->transaction->merchant_id,
                'external_id'    => $this->transaction->external_id,
                'internal_id'    => $this->transaction->internal_id,
            ]);
            
            /*
             * Transaction was found successful after status verification.
             * Insert to callback queue
             */
            dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
            
            $this->delete();
            
        } catch (\Exception $e) {
            Log::info("{$this->getJobName()}: Final status not yet received by verification worker. Status will be rechecked after $this->sleep seconds", [
                'error message'  => $e->getMessage(),
                'status'         => $this->transaction->status,
                'transaction.id' => $this->transaction->id,
                'destination'    => $this->transaction->destination,
                'callback_url'   => $this->transaction->callback_url,
                'service'        => $this->transaction->service_code,
                'merchant_id'    => $this->transaction->merchant_id,
                'external_id'    => $this->transaction->external_id,
                'internal_id'    => $this->transaction->internal_id,
                'attempts'       => $this->attempts(),
            ]);
            
            /*
             * Delay job before attempting the next status verification
             */
            $this->release($this->sleep);
        }
    }
    
    /**
     * @param \Exception|null $exception
     */
    public function failed(\Exception $exception = null)
    {
        $this->transaction->status         = TransactionConstants::FAILED;
        $this->transaction->message        = 'Transaction status not yet received. Manually set to failed';
        $this->transaction->to_be_verified = true;
        $this->transaction->error_code     = ErrorCodesConstants::GENERAL_CODE;
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
            'transaction.merchant_id'           => $this->transaction->merchant_id,
            'transaction.internal_id'           => $this->transaction->internal_id,
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