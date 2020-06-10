<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/9/20
 * Time: 2:46 PM
 */

namespace App\Services\Clients\Providers\Orange;

use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Models\Transaction;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Objects\Account;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OrangeWebPaymentClient extends OrangeClient
{
    /**
     * @param Account $account
     * @return bool
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buy(Account $account): bool
    {
        Log::debug("{$this->getClientName()}: Sending request to service provider to generate payToken");
        
        $httpClient = $this->getHttpClient();
        try {
            $response = $httpClient->request('POST', 'mp/init');
        } catch (\Exception $exception) {
            Log::emergency('Error sending init request to service provider: ' . $exception->getMessage());
            throw new BadRequestException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error sending init request to service provider: ' . $exception->getMessage());
        }
        
        $content = $response->getBody()->getContents();
        
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
        
        $body = json_decode($content);
        
        try {
            $payToken = $body->data->payToken;
    
            $transaction = Transaction::where('internal_id', $account->getIntId())->first();
            $transaction->merchant_id = $payToken;
            $transaction->save();
            
        } catch (\Exception $exception) {
            Log::emergency("{$this->getClientName()}: Error retrieving pay token from response");
            throw new BadRequestException(ErrorCodesConstants::GENERAL_CODE, $exception->getMessage());
        }
        
        $json = [
            'notifUrl'          => config('app.url') . '/callback/orange/wp',
            'channelUserMsisdn' => config('app.services.orange.webpayment_channel_msisdn'),
            'amount'            => "{$account->getAmount()}",
            'subscriberMsisdn'  => substr($account->getAccountNumber(), -9),
            'pin'               => config('app.services.orange.webpayment_channel_pin'),
            /*
             * Warning!!!! Sending only the first twenty characters of the transaction uuid. As Orange only supports 20 chars
             */
            'orderId'           => substr($account->getIntId(), 0, 20),
            'description'       => 'Corlang Account Top Up',
            'payToken'          => $payToken,
        ];
        
        Log::debug("{$this->getClientName()}: Sending request to service provider to initiate payment", [
            'json' => $json
        ]);
        
        try {
            $response = $httpClient->request('POST', 'mp/pay', [
                'json' => $json
            ]);
            
            // Push to customer if request was successful
            // not serious if transaction fails.
            // User can manually open transaction to confirm
            
            try {
                $httpClient->request('GET', "mp/push/$payToken");
                Log::info("{$this->getClientName()}: Transaction pushed to customer for confirmation successfully");
            } catch (\ Exception $exception) {
                Log::warning("{$this->getClientName()}: Error pushing transaction to customer");
            }
            
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response = $exception->getResponse();
        } catch (\Exception $exception) {
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to verify account: ' . $exception->getMessage());
        }
        
        $content = $response->getBody()->getContents();
        
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
        
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            $body = json_decode($content);
            $code = $body->data->inittxnstatus;
            switch ($code) {
                case '00671': // FAKE NUMBER NOT IN ORANGE
                    $error_code = ErrorCodesConstants::SUBSCRIBER_NOT_FOUND;
                    break;
                case '99051': // NUMBER IN ORANGE BUT NOT ORANGE MONEY
                    $error_code = ErrorCodesConstants::SUBSCRIBER_NOT_FOUND;
                    break;
                case '60019': // BALANCE CROSSED
                    $error_code = ErrorCodesConstants::INSUFFICIENT_FUNDS_IN_WALLET;
                    break;
                default:
                    $error_code = ErrorCodesConstants::GENERAL_CODE;
            }
            throw new BadRequestException($error_code, $body->message);
        }
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function status($transaction)
    {
        $status = $this->getStatus($transaction);
    
        Log::info("{$this->getClientName()}: Transaction exists in partner system with status $status");
        if (strtoupper($status) == 'PENDING') {
            return true;
        }
        throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, 'Transaction is not in a reliable state. We expect to have the status \'PENDING\'');
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function finalStatus($transaction)
    {
        $status = $this->getStatus($transaction);
        if ($status == 'SUCCESSFULL' || $status == 'SUCCESSFUL') {
            return true;
        } else if (in_array($status, [
            'FAILED',
            'EXPIRED',
            'CANCELLED',
            'CANCELED',
        ])) {
            return false;
        } else {
            throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, 'Transaction is not a final status');
        }
    }
    
    /**
     * @param $transaction
     * @return mixed
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getStatus($transaction)
    {
        Log::debug("{$this->getClientName()}: Sending request to service provider to get status");
        
        $httpClient = $this->getHttpClient();
        try {
            $response = $httpClient->request('GET', "mp/paymentstatus/$transaction->merchant_id");
        } catch (\Exception $exception) {
            Log::emergency('Error sending init request to service provider: ' . $exception->getMessage());
            throw new GeneralException(ErrorCodesConstants::GENERAL_CODE,
                'Service provider error: ' . $exception->getMessage());
        }
        
        $content = $response->getBody()->getContents();
        
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
        
        $body = json_decode($content);
        
        return $body->data->status;
    }
    
    /**
     * @return Client
     */
    public function getHttpClient()
    {
        $oauthToken = config('app.services.orange.webpayment_token');
        $xauthToken = base64_encode(config('app.services.orange.webpayment_api_username') . ':' . config('app.services.orange.webpayment_api_password'));
        
        return new Client([
            'base_uri'        => config('app.services.orange.webpayment_url'),
            'timeout'         => 120,
            'connect_timeout' => 120,
            'allow_redirects' => true,
            'verify'          => false,
            'headers'         => [
                'Authorization' => "Bearer $oauthToken",
                'X-AUTH-TOKEN'  => $xauthToken,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ]);
    }
    
    /**
     * @return string
     */
    public function getClientName(): string
    {
        return class_basename($this);
    }
}