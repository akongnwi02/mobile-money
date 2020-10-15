<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/9/20
 * Time: 2:44 PM
 */

namespace App\Services\Clients\Providers\Mtn;


use App\Exceptions\BadRequestException;
use App\Exceptions\GeneralException;
use App\Models\Authentication;
use App\Services\Clients\ClientInterface;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Objects\Account;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MtnClient implements ClientInterface
{
    public $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * @param Account $account
     * @return bool
     */
    public function buy(Account $account): bool
    {
        // IMPLEMENTED IN CHILDREN
    }
    public function search($accountNumber): Account
    {
        // IMPLEMENTED IN CHILDREN
    }
    
    
    /**
     * @param $accountNumber
     * @return Account
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyNumber($accountNumber): Account
    {
        // TODO To be tested and re-executed
        $bearerToken = $this->getAccessToken();
        $verifyUrl = $this->config['url'] . "/{$this->config['subscription']}/v1_0/accountholder/msisdn/$accountNumber/active/";
        
        Log::debug("{$this->getClientName()}: Verifying client account", [
            'account number' => $accountNumber,
            'url' => $verifyUrl,
        ]);
    
        $httpClient = $this->getHttpClient($verifyUrl);
        try {
            $response = $httpClient->request('GET', "", [
                'headers' => ['Authorization' => "Bearer $bearerToken"]
            ]);
        } catch (\GuzzleHttp\Exception\ServerException $exception) {
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to verify account: ' . $exception->getMessage());
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response = $exception->getResponse();
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
    
        if ($response->getStatusCode() == 200) {
            if ($content == 'true') {
                $account = new Account();
                $account->setServiceCode($this->config['service_code'])
                    ->setAccountNumber($accountNumber)
                    ->setActive(true);
                return $account;
            }
        }
        throw new BadRequestException(ErrorCodesConstants::SUBSCRIBER_CANNOT_PERFORM,
            'The subscriber is not allowed to perform this transaction.');
    }
    
    
    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BadRequestException
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
        $url = $this->config['url'] . "/{$this->config['subscription']}/token/";
    
        $basicAuth = base64_encode($this->config['user'] . ':' . $this->config['password']);
    
        Log::debug("{$this->getClientName()}: Generating new authorization token for {$this->config['subscription']} api", [
            'url' => $url
        ]);
    
        $httpClient = $this->getHttpClient($url);
        try {
            $response = $httpClient->request('POST', '', [
                'headers' => ['Authorization' => "Basic $basicAuth"]
            ]);
        } catch (\Exception $exception) {
            throw new BadRequestException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to generate token: ' . $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
//            'response' => $content
        ]);
    
        $body = json_decode($content);
    
        if (isset($body->access_token)) {
    
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
    
        throw new BadRequestException(ErrorCodesConstants::GENERAL_CODE, 'Cannot get token from response');
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
        $body = $this->getStatus($transaction);
        $status = $body->status;
        if ($status == 'SUCCESSFUL') {
            $transaction->merchant_id = @$body->financialTransactionId;
            $transaction->save();
            return true;
        } else if (in_array($status, [
            // Guessing possible status as documentation is not proper
            'FAILED',
            'EXPIRED',
            'CANCELLED',
            'CANCELED',
            // GUESS WORK
            'ABORTED',
            'DELETED',
            'TERMINATED',
        ])) {
            $transaction->merchant_id = @$body->financialTransactionId;
            $transaction->save();
            return false;
        } else {
            throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, 'Transaction is not a final status');
        }
    }
    
    /**
     * @param $transaction
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BadRequestException
     */
    public function verifyTransaction($transaction)
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
     * @throws BadRequestException
     */
    public function getStatus($transaction)
    {
        $bearerToken = $this->getAccessToken();
        $statusUrl = $this->config['url'] . "{$this->config['perform_uri']}/$transaction->internal_id";
    
        Log::debug("{$this->getClientName()}: Sending verification request to service provider", [
            'URL' => $statusUrl,
            'transaction.id' => $transaction->id,
            'status' => $transaction->status,
            'destination' => $transaction->destination,
            'service_code' => $transaction->service_code,
            'internal_id' => $transaction->internal_id,
        ]);
    
        $httpClient = $this->getHttpClient($statusUrl);
        try {
            $response = $httpClient->request('GET', "", [
                'headers' => ['Authorization' => "Bearer $bearerToken"]
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response = $exception->getResponse();
        } catch (\Exception $exception) {
            throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
    
        if ($response->getStatusCode() == 200) {
    
            $body = json_decode($content);
            // the transaction exists in service provider system irrespective of status.
            // waiting callback request from the provider with final status
            return $body;
        }
        throw new GeneralException(ErrorCodesConstants::GENERAL_CODE,
            'Status verification returned non 2xx code');
    }
    
    /**
     * @param Account $account
     * @return bool
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function performTransaction(Account $account)
    {
        $bearerToken = $this->getAccessToken();
        $performUrl = $this->config['url'] . "{$this->config['perform_uri']}";
        
        $json = [
            'amount'       => $account->getAmount(),
            'currency'     => 'XAF',
            'externalId'   => $account->getIntId(),
            $this->config['subscription'] == 'collection' ? 'payer':'payee'  => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $account->getAccountNumber(),
            ],
            'payerMessage' => 'CorlangLimited',
            'payeeNote'    => 'Thanks',
        ];
    
        Log::debug("{$this->getClientName()}: Sending purchase request to service provider", [
            'json' => $json,
            'url' => $performUrl,
        ]);
    
        $httpClient = $this->getHttpClient($performUrl);
        try {
            $response = $httpClient->request('POST', '', [
                'headers' => [
                    'X-Reference-Id' => $account->getIntId(),
                    'Authorization' => "Bearer $bearerToken"
                ],
                'json' => $json
            ]);
        }
        catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response = $exception->getResponse();
        }
        catch (\Exception $exception) {
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to execute service: ' . $exception->getMessage());
        }
    
        $content = $response->getBody()->getContents();
    
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
        $body = json_decode($content);

        if ($response->getStatusCode() == 202) {
            return true;
        } else if (isset($body->code)){
            switch ($body->code) {
                case "PAYEE_NOT_FOUND":
                    $error_code = ErrorCodesConstants::SUBSCRIBER_NOT_FOUND;
                    break;
                case "NOT_ENOUGH_FUNDS":
                    $error_code = ErrorCodesConstants::INSUFFICIENT_FUNDS_IN_WALLET;
                    break;
                case "PAYEE_NOT_ALLOWED_TO_RECEIVE":
                    $error_code = ErrorCodesConstants::SUBSCRIBER_CANNOT_PERFORM;
                    break;
                case "PAYER_LIMIT_REACHED":
                    $error_code = ErrorCodesConstants::CUSTOMER_ACCOUNT_LIMIT;
                    break;
                default:
                    $error_code = ErrorCodesConstants::GENERAL_CODE;
            }
            throw new BadRequestException($error_code, $body->message);
        }
        throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, 'An unexpected error occured during purchase');
    }
    
    /**
     * @param $url
     * @return Client
     */
    public function getHttpClient($url)
    {
        return new Client([
            'base_uri'        => $url,
            'timeout'         => 120,
            'connect_timeout' => 120,
            'allow_redirects' => true,
            'headers'         => [
                'X-Target-Environment' => $this->config['environment'],
                'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
                'Content-Type' => 'application/json',
                'X-Callback-Url' => $this->config['callback_url'],
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