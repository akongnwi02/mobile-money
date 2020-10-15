<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 4/16/20
 * Time: 10:16 PM
 */

namespace App\Services\Clients\Providers\Orange;


use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Models\Authentication;
use App\Models\Transaction;
use App\Services\Clients\ClientInterface;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Objects\Account;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class OrangeClient implements ClientInterface
{
    public $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * @param $accountNumber
     * @return Account
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws GeneralException
     */
    public function search($accountNumber): Account
    {
        $msisdn = substr($accountNumber, -9);
        $searchUrl = $this->config['url'] . "infos/subscriber/customer/$msisdn";
        Log::debug("{$this->getClientName()}: Sending request to service provider to search for user account", ['url' => $searchUrl]);
    
        $httpClient = $this->getHttpClient($searchUrl);
        
        $json = [
            'channelMsisdn' => $this->config['channel_msisdn'],
            'pin'           => $this->config['pin'],
        ];
        try {
            $response = $httpClient->request('POST', '', [
                'json' => $json
            ]);
        } catch (ClientException $exception) {
            Log::error('Error sending search request to service provider: ' . $exception->getMessage());
            $response = $exception->getResponse();
        } catch (\Exception $exception) {
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR, 'Error connecting to service provider: ' . $exception->getMessage());
        }
        
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
    
        $body = json_decode($content);
        
        if ($response->getStatusCode() == 200) {
            $account = new Account();
            $account->setName(join(' ', [@$body->data->firstName, @$body->data->lastName]));
            return $account;
        } else {
            throw new BadRequestException(ErrorCodesConstants::SUBSCRIBER_NOT_FOUND, "Error searching the subscriber");
        }
    }
    
    /**
     * @param Account $account
     * @return bool
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws GeneralException
     */
    public function buy(Account $account): bool
    {
        $payTokenUrl = $this->config['url'] . $this->config['subscription'] . '/init';
        
        Log::debug("{$this->getClientName()}: Sending request to service provider to generate payToken", ['url' => $payTokenUrl]);
    
        $payTokenClient = $this->getHttpClient($payTokenUrl);
        try {
            $payTokenResponse = $payTokenClient->request('POST');
        } catch (\Exception $exception) {
            Log::emergency('Error sending init request to service provider: ' . $exception->getMessage());
            throw new BadRequestException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error sending init request to service provider: ' . $exception->getMessage());
        }
    
        $content = $payTokenResponse->getBody()->getContents();
    
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
            'notifUrl'          => $this->config['callback_url'],
            'channelUserMsisdn' => $this->config['channel_msisdn'],
            'amount'            => "{$account->getAmount()}",
            'subscriberMsisdn'  => substr($account->getAccountNumber(), -9),
            'pin'               => $this->config['pin'],
            /*
             * Warning!!!! Sending only the first twenty characters of the transaction uuid. As Orange only supports 20 chars
             * Replace also (-) with (.) as the orange platform does not support the (-) character
             */
            'orderId'           => str_replace('-', 'a', substr($account->getIntId(), 0, 20)),
            'description'       => 'Transaction on CorlaPay platform',
            'payToken'          => $payToken,
        ];
    
        $payUrl = $this->config['url'] . $this->config['subscription'] . '/pay';
        
        Log::debug("{$this->getClientName()}: Sending request to service provider to initiate payment", [
            'json' => $json,
            'url' => $payUrl
        ]);
    
        $paymentClient = $this->getHttpClient($payUrl);
        try {
            $paymentResponse = $paymentClient->request('POST', '', [
                'json' => $json
            ]);
        
            // Push to customer if request was successful
            // not serious if request fails.
            // User can manually open transaction
            // by dialing code on his phone to confirm transaction
            // do this only if the service is a merchant payment service
            if ($this->config['subscription'] == 'mp') {
                try {
    
                    $pushUrl  = $this->config['url'] . $this->config['subscription'] . "/push/$payToken";

                    $pushClient = $this->getHttpClient($pushUrl);
                    $pushClient->request('GET');
                    Log::info("{$this->getClientName()}: Transaction pushed to customer for confirmation successfully");
                } catch (\ Exception $exception) {
                    Log::warning("{$this->getClientName()}: Error pushing transaction to customer", ['message' => $exception->getMessage()]);
                }
            }
            
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            Log::error("{$this->getClientName()}: An error occurred when sending payment request");
            $paymentResponse = $exception->getResponse();
        } catch (\Exception $exception) {
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Unexpected error initiating payment with service provider: ' . $exception->getMessage());
        }
    
        $content = $paymentResponse->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
    
        if ($paymentResponse->getStatusCode() == 200) {
            return true;
        } else {
            $body = json_decode($content);
            $code = $this->config['subscription'] == 'cashin' ? @$body->data->txnstatus : @$body->data->inittxnstatus;
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
     * @throws BadRequestException
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
     * @return mixed
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BadRequestException
     */
    public function getStatus($transaction)
    {
        $statusUrl = $this->config['url'] . $this->config['subscription'] . "/paymentstatus/$transaction->merchant_id";
        
        Log::debug("{$this->getClientName()}: Sending request to service provider to get status", ['url' => $statusUrl]);
        
        $httpClient = $this->getHttpClient($statusUrl);
        try {
            $response = $httpClient->request('GET');
        } catch (\Exception $exception) {
            Log::emergency('Error sending status request to service provider: ' . $exception->getMessage());
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
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BadRequestException
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
    
    public function getClientName()
    {
        return class_basename($this);
    }
    
    /**
     * @return mixed
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessToken()
    {
        $auth = Authentication::where('service_code', $this->config['service_code'])->latest()->get()->first();
    
        if ($auth && (Carbon::now()->diffInSeconds($auth->created_at) < $auth->expires_in)) {
            Log::debug("{$this->getClientName()}: Valid OAuth token found for service: {$this->config['service_code']}", [
                'expires_in' => $auth->expires_in,
                'created_at' => $auth->created_at->toDateTimeString(),
                'current_time' => Carbon::now()->toDateTimeString(),
            ]);
            return $auth->access_token;
        }
    
        Log::debug("{$this->getClientName()}: No valid access token found locally. Connecting to service provider to generate new one", [
            'service' => $this->config['service_code']
        ]);
    
        return $this->generateNewAccessToken();
    }
    
    /**
     * @return mixed
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function generateNewAccessToken()
    {
       $base64String = base64_encode($this->config['key'] . ':' . $this->config['secret']);
    
        $url = $this->config['auth_url'] . "token";
    
        Log::debug("{$this->getClientName()}: Generating new authorization token for {$this->config['service_code']}", [
            'url' => $url,
            'key' => $this->config['key']
        ]);
    
        $accessTokenClient = new Client();
    
        try {
            $response = $accessTokenClient->request('POST', $url, [
                'timeout'         => 120,
                'connect_timeout' => 120,
                'allow_redirects' => true,
                'verify'          => false,
                'headers' => [
                    'Authorization' => 'Basic ' . $base64String,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ],
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);
        } catch (\Exception $exception) {
            Log::error("{$this->getClientName()}: Could not authenticate with service provider", [
                'service_code' => $this->config['service_code'],
                'error' => $exception->getMessage()
            ]);
            throw new BadRequestException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to generate token: ' . $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response received from service provider (may be hidden for security)", [
//            'response' => $content
        ]);
    
        $body = json_decode($content);
    
        if (isset($body->access_token)) {
            
            // save and return access token
            Authentication::create([
                'expires_in' => $body->expires_in,
                'access_token' => $body->access_token,
                'refresh_token' => null,
                'service_code' => $this->config['service_code'],
                'type' => 'Bearer'
            ]);
            
            Log::info("{$this->getClientName()}: Token Retrieved successfully");
            
            return $body->access_token;
        }
        
        Log::emergency("{$this->getClientName()}: Cannot authenticate with service provider", ['service' => $this->config['service_code']]);
        
        throw new BadRequestException(ErrorCodesConstants::GENERAL_CODE, 'Cannot get token from response');
    }
    
    
    /**
     * @param $url
     * @return Client
     * @throws BadRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getHttpClient($url)
    {
        $oauthToken = $this->getAccessToken();
        $xauthToken = base64_encode($this->config['username'] . ':' . $this->config['password']);
        
        return new Client([
            'base_uri'        => $url,
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
}