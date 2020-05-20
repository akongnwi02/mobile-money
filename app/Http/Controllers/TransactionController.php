<?php

namespace App\Http\Controllers;

use App\Exceptions\GeneralException;
use App\Exceptions\NotFoundException;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TransactionResource;
use App\Jobs\PurchaseJob;
use App\Models\Transaction;
use App\Services\Clients\ClientProvider;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Constants\QueueConstants;
use App\Services\Constants\TransactionConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Webpatser\Uuid\Uuid;

class TransactionController extends Controller
{
    use ClientProvider;
    
    /**
     * @param Request $request
     * @return AccountResource
     * @throws GeneralException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function search(Request $request)
    {
        Log::info('Incoming search request', ['input' => $request->input()]);
        $this->validate($request, [
            'destination'  => ['required', 'string', 'min:7'],
            'service_code' => ['required', 'string', 'min:3',],
        ]);
        
        $account = $this->client($request['service_code'])->search($request['destination']);
        
        return new AccountResource($account);
        
    }
    
    /**
     * @param Request $request
     * @param Transaction $transaction
     * @return TransactionResource
     * @throws GeneralException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(Request $request, Transaction $transaction)
    {
        Log::info('Incoming execute request', ['input' => $request->input()]);
        $this->validate($request, [
            'phone'        => ['sometimes', 'numeric', 'min:9'],
            'destination'  => ['required', 'string', 'min:7'],
            'service_code' => ['required', 'string', 'min:3',],
            'external_id'  => ['required', 'string', Rule::unique('transactions', 'external_id')],
            'amount'       => ['required', 'numeric', 'regex:/^(?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d+)?$/'],
            'callback_url' => ['required', 'url'],
            'auth_payload' => ['sometimes', 'nullable', 'min:4'],
        ]);
        
        $transaction->internal_id  = Uuid::generate(4)->string;
        $transaction->external_id  = $request['external_id'];
        $transaction->phone        = $request['phone'];
        $transaction->destination  = $request['destination'];
        $transaction->service_code = $request['service_code'];
        $transaction->amount       = $request['amount'];
        $transaction->callback_url = $request['callback_url'];
        $transaction->reference    = $request['auth_payload'];
        $transaction->status       = TransactionConstants::CREATED;
        
        if ($transaction->save()) {
            Log::info('New transaction created', [
                'status'       => $transaction->status,
                'id'           => $transaction->id,
                'external_id'  => $transaction->external_id,
                'service_code' => $transaction->service_code,
                'destination'  => $transaction->destination
            ]);
            
            dispatch(new PurchaseJob($transaction))->onQueue(QueueConstants::PURCHASE_QUEUE);
            
            return new TransactionResource($transaction);
        }
        throw new GeneralException(ErrorCodesConstants::TRANSACTION_CREATION_ERROR, 'Error creating transaction in local database');
    }
    
    /**
     * @param $external_id
     * @return TransactionResource
     * @throws NotFoundException
     */
    public function status($external_id)
    {
        $transaction = Transaction::where('external_id', $external_id)->first();
        if ($transaction) {
            return new TransactionResource($transaction);
        }
        throw new NotFoundException(ErrorCodesConstants::TRANSACTION_NOT_FOUND, 'Transaction not found in local database');
    }
}
