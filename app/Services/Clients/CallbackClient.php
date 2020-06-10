<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 10:48 PM
 */

namespace App\Services\Clients;

use App\Exceptions\GeneralException;
use App\Models\Transaction;
use App\Services\Constants\ErrorCodesConstants;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CallbackClient
{
    /**
     * @param Transaction $transaction
     * @throws GeneralException
     */
    public function send(Transaction $transaction)
    {
        $json = [
            'status'         => $transaction->status,
            'error_code'     => $transaction->error_code,
            'message'        => $transaction->message,
            'to_be_verified' => $transaction->to_be_verified,
            'asset'          => $transaction->merchant_id
        ];
    
        Log::debug("{$this->getClientName()}: Sending callback request", [
            'url'  => $transaction->callback_url,
            'json' => $json
    
        ]);
        $httpClient = $this->getHttpClient();
    
        try {
        
            $response = $httpClient->request('PATCH', $transaction->callback_url . '/' . $transaction->external_id, [
                'json' => $json
            ]);
        } catch (GuzzleException $exception) {
        
            throw new GeneralException(ErrorCodesConstants::CALLBACK_SEND_ERROR, 'Error sending callback request to callback url: ' . $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from callback client", [
            'transaction.status' => $transaction->status,
            'transaction.id'     => $transaction->id,
            'status code'        => $response->getStatusCode(),
            'content'            => $content,
        ]);
    }
    
        /**
         * @return Client
         */
        public function getHttpClient()
    {
        return new Client([
            'timeout'         => 120,
            'connect_timeout' => 120,
            'allow_redirects' => true,
            'headers'         => [
                'Authorization' => 'Bearer',
                'Accept'        => 'application/json'
            ],
        ]);
    }
    
        public function getClientName()
    {
        return class_basename($this);
    }
}