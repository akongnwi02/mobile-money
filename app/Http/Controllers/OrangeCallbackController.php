<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/10/20
 * Time: 2:24 PM
 */

namespace App\Http\Controllers;


use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Exceptions\NotFoundException;
use App\Jobs\CallbackJob;
use App\Models\Transaction;
use App\Notifications\StatusMismatchError;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrangeCallbackController extends CallbackController
{
    
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws BadRequestException
     * @throws GeneralException
     * @throws NotFoundException
     */
    public function orangeWpCallback(Request $request)
    {
        Log::info("{$this->getClassName()}: New callback request received from Orange web payment service provider", [
            'ip'      => $request->ip(),
            'payload' => $request->input()
        ]);
    
        $transaction = Transaction::where('merchant_id', $request->input('payToken'))->first();
        
        if (! $transaction) {
            Log::emergency("{$this->getClassName()}: Received status update from Orange web payment for a transaction which does not exist in the local system", [
                'payload' => $request->input()
            ]);
    
            throw new NotFoundException(ErrorCodesConstants::TRANSACTION_NOT_FOUND, "The transaction {$request->input('payToken')} does not exist in our system");
        }
    
        $transactionInLocalDb = [
            'transaction.status'                => $transaction->status,
            'transaction.internal_id'           => $transaction->internal_id,
            'transaction.id'                    => $transaction->id,
            'transaction.service_code'          => $transaction->service_code,
            'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
            'transaction.destination'           => $transaction->destination,
        ];
        Log::info("{$this->getClassName()}: Transaction exists in the local system with the following value", $transactionInLocalDb);
    
    
        if (in_array($transaction->status, [
            TransactionConstants::SUCCESS,
            TransactionConstants::FAILED,
        ])) {
            Log::warning("{$this->getClassName()}: Transaction in final status $transaction->status received a status update of {$request->input('status')}",[
                'transaction.status'                => $transaction->status,
                'transaction.internal_id'           => $transaction->internal_id,
                'transaction.id'                    => $transaction->id,
                'transaction.service_code'          => $transaction->service_code,
                'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
                'transaction.destination'           => $transaction->destination,
            ]);
    
            if ($transaction->status == TransactionConstants::SUCCESS) {
                if ($request->input('status') == 'SUCCESSFULL' || $request->input('status') == 'SUCCESSFUL') {
                    return $this->successResponse();
                }
            } else if ($transaction->status == TransactionConstants::FAILED) {
                if (in_array($request->input('status'), [
                    // Guessing possible status as documentation is not proper
                    'FAILED',
                    'EXPIRED',
                    'CANCELLED',
                    'CANCELED',
                    'ERROR',
                    'ERRORED',
                    // GUESS WORK
                    'ABORTED',
                    'DELETED',
                    'TERMINATED',
                ])) {
                    return $this->successResponse();
                }
            }
            Log::emergency("{$this->getClassName()}: Transaction in final status $transaction->status received a status mismatch of {$request->input('status')}",[
                'transaction.status'                => $transaction->status,
                'transaction.internal_id'           => $transaction->internal_id,
                'transaction.id'                    => $transaction->id,
                'transaction.service_code'          => $transaction->service_code,
                'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
                'transaction.destination'           => $transaction->destination,
            ]);
            
            /*
             * Notify the channel of the failed status check
             */
            if (config('app.enable_notifications')) {
                $transaction->notify(new StatusMismatchError($transaction));
            }
            throw new GeneralException(ErrorCodesConstants::TRANSACTION_IN_FINAL_STATUS, "Transaction $transaction->merchant_id is already in final status $transaction->status");
        };
    
        $transaction->message = $request->input('message');
        
        if ($request->input('status') == 'SUCCESSFULL' || $request->input('status') == 'SUCCESSFUL') {
            $transaction->status = TransactionConstants::SUCCESS;
        }
        
        /**
         * Make sure the status is not a premature status before failing the transaction if status is not successful
         */
        else if (in_array($request->input('status'), ['PENDING', 'INITIATED'])) {
            Log::warning("{$this->getClassName()}: Premature status received from orange webpayment provider", [
                'status received' => $request->input('status'),
                'transaction in local system' => $transactionInLocalDb
            ]);
    
            throw new BadRequestException(ErrorCodesConstants::GENERAL_CODE, "We cannot update the transaction {$request->input('payToken')} with status {$request->input('status')}");
        }
        else {
            $transaction->status = TransactionConstants::FAILED;
            $transaction->error_code = ErrorCodesConstants::GENERAL_CODE;
            $transaction->error = 'Unexpected Error';
        }
    
        $transaction->save();
    
        Log::info("{$this->getClassName()}: Inserting transaction to CALLBACK queue");
    
        dispatch(new CallbackJob($transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
    
        return $this->successResponse();
    }

}