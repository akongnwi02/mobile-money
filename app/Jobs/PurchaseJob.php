<?php

namespace App\Jobs;

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
     * Execute the job.
     *
     * @param Account $account
     * @return void
     */
    public function handle(Account $account)
    {
        // readme
        // transaction is status created
        // when picked by process status is processing
        // when service provider rejects the transaction, it is set to failed
        // and put to callback queue
        Log::info('Processing new purchase job', [
            'status'         => $this->transaction->status,
            'transaction id' => $this->transaction->internal_id
        ]);
        $this->transaction->status = TransactionConstants::PROCESSING;
        $this->transaction->purchase_attempts = $this->attempts();
        $this->transaction->save();
        
        $account->setAmount($this->transaction->amount)
            ->setAccountNumber($this->transaction->destination)
            ->setServiceCode($this->transaction->service_code)
            ->setReference($this->transaction->reference);
        
        try {
            $status = $this->client($account->getServiceCode())->buy($account);
            $this->transaction->asset = $account->getServiceCode();
            $this->transaction->status = TransactionConstants::SUCCESS;
            $this->transaction->message = 'Transaction completed successfully';
            
        } catch (\Exception $exception) {
            // status verification not supported by vendors
            // hence will have to comment this part
//            if ($exception instanceof NotFoundException
//            || $exception instanceof ForbiddenException
//            || $exception instanceof BadRequestException) {
            
            // Delete the job for any of the reasons above
            $this->transaction->status = TransactionConstants::FAILED;
            $this->transaction->error = $exception->getMessage();
            $this->transaction->message = 'Transaction failed manually';
            
            Log::info('Processing new purchase completed', [
                'status'         => $this->transaction->status,
                'transaction id' => $this->transaction->internal_id
            ]);
            // no status check is available hence delete the job
            $this->delete();
//                $this->release($this->attempts()*5);

//            } else {
//                // requeue the job to be retried after this number of seconds
//                $this->release($this->attempts() * 5);
//            }
        }
        
        $this->transaction->save();
        
        Log::info('Processing new purchase completed', ['status' => $this->transaction->status, 'transaction id' => $this->transaction->internal_id]);
        
        dispatch(new CallbackJob($this->transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
    }
    
}

