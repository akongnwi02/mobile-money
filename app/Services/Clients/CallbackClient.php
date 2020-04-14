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
        $httpClient = $this->getHttpClient();
        try {
            $response = $httpClient->request('PATCH', $transaction->callback_url.'/'.$transaction->external_id, [
                'json' => [
                    'status' => $transaction->status,
                    'error' => $transaction->error,
                    'message' => $transaction->message,
                    'asset' => $transaction->asset,
                ]
            ]);
        } catch (GuzzleException $exception) {
            
            throw new GeneralException(ErrorCodesConstants::CALLBACK_SEND_ERROR,'Error sending callback to CORE: ' . $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
        
        Log::debug('response from service provider', [
            'provider' => config('app.services.iat.code'),
            'response' => $content
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
        ]);
    }
}