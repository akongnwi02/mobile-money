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
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function mtnCallback(Request $request)
    {
        Log::info('New callback request received from MTN service provider', [
            'ip'      => $request->ip(),
            'payload' => $request->input()
        ]);
        $transaction = Transaction::find();
        
        $transactionInLocalDb = [
            'transaction.status'                => $transaction->status,
            'transaction.internal_id'           => $transaction->internal_id,
            'transaction.id'                    => $transaction->id,
            'transaction.service_code'          => $transaction->service_code,
            'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
            'transaction.destination'           => $transaction->destination,
        ];
    
        Log::debug('Transaction exists in local database', $transactionInLocalDb);
    
        if (in_array($transaction->status, [
            TransactionConstants::SUCCESS,
            TransactionConstants::FAILED,
        ])) {
            Log::emergency('Transaction in final status received a status update',[
                'status received'                   => $request->input('status'),
                'transaction.status'                => $transaction->status,
                'transaction.uuid'                  => $transaction->uuid,
                'transaction.code'                  => $transaction->code,
                'transaction.service_code'          => $transaction->service_code,
                'transaction.movement_code'         => $transaction->movement_code,
                'transaction.paymentaccount'        => $transaction->paymentaccount,
                'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
                'transaction.destination'           => $transaction->destination,
                'transaction.total_customer_amount' => $transaction->total_customer_amount,
            ]);
            throw new ServerErrorException(BusinessErrorCodes::TRANSACTION_IN_FINAL_STATUS, "Transaction $transaction->code in final state received a status update");
        };
    
        $transaction->status         = $request->input('status');
        $transaction->asset          = $request->input('asset');
        $transaction->message        = $request->input('message');
        $transaction->error_code     = $request->input('error_code');
        $transaction->to_be_verified = $request->input('to_be_verified');
    
        $transaction->save();
    
        Log::info('Inserting transaction to COMPLETE queue');
    
        dispatch(new CallbackJob($transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
    
        return response()->json([
            'status' => 'OK'
        ], 200);
    }
    
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
    
            throw new NotFoundException(ErrorCodesConstants::TRANSACTION_NOT_FOUND, 'Sending status updated for a transaction not existing in local system');
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
            Log::emergency("{$this->getClassName()}: Transaction in final status received a status update",[
                'transaction.status'                => $transaction->status,
                'transaction.internal_id'           => $transaction->internal_id,
                'transaction.id'                    => $transaction->id,
                'transaction.service_code'          => $transaction->service_code,
                'transaction.created_at'            => $transaction->created_at->toDatetimeString(),
                'transaction.destination'           => $transaction->destination,
            ]);
            throw new GeneralException(ErrorCodesConstants::TRANSACTION_IN_FINAL_STATUS, "Transaction $transaction->id in final state received a status update");
        };
    
        $transaction->message = $request->input('message');
        
        if ($request->input('status' == 'SUCCESSFULL')) {
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
        }
    
        $transaction->save();
    
        Log::info("{$this->getClassName()}: Inserting transaction to CALLBACK queue");
    
        dispatch(new CallbackJob($transaction))->onQueue(QueueConstants::CALLBACK_QUEUE);
    
        return response()->json([
            'status' => 'OK'
        ], 200);
    }
    
    public function getClassName()
    {
        return class_basename($this);
    }
}