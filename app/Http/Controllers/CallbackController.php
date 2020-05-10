<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/10/20
 * Time: 2:24 PM
 */

namespace App\Http\Controllers;


use App\Jobs\CallbackJob;
use App\Services\Constants\QueueConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function mtnCallback(Request $request)
    {
        Log::info('New callback request received from service provider', [
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
            config('business.transaction.status.failed'),
            config('business.transaction.status.success'),
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
}