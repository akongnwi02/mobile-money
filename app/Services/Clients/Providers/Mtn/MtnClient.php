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
use App\Services\Clients\ClientInterface;
use App\Services\Constants\ErrorCodesConstants;
use App\Services\Objects\Account;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MtnClient implements ClientInterface
{
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
     * @param $key
     * @param $uri
     * @return mixed
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authenticate($key, $uri)
    {
        Log::debug("{$this->getClientName()}: Generating new authorization token", [
            'url' => config('app.services.mtn.url') . $uri
        ]);
        $httpClient = $this->getHttpClient($key);
        try {
            $response = $httpClient->request('POST', $uri);
        } catch (\Exception $exception) {
            
            throw new GeneralException(ErrorCodesConstants::SERVICE_PROVIDER_CONNECTION_ERROR,
                'Error connecting to service provider to generate token: ' . $exception->getMessage());
        }
        
        $content = $response->getBody()->getContents();
        
        Log::debug("{$this->getClientName()}: Response from service provider", [
            'response' => $content
        ]);
        
        $body = json_decode($content);
        
        if ($body->access_token) {
            return $body->access_token;
        }
        
        throw new GeneralException(ErrorCodesConstants::GENERAL_CODE, 'Cannot get token from response');
    }
    
    /**
     * @param $accountNumber
     * @param $key
     * @param $subscription
     * @param $serviceCode
     * @return Account
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyNumber($accountNumber, $subscription, $key, $serviceCode): Account
    {
        $token = $this->authenticate($key, "/$subscription/token/");
        
        Log::debug("{$this->getClientName()}: Verifying client account", ['account number' => $accountNumber]);
        
        $httpClient = $this->getHttpClient($key, $token);
        try {
            $response = $httpClient->request('GET', "/$subscription/v1_0/accountholder/msisdn/$accountNumber/active");
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
                $account->setServiceCode($serviceCode)
                    ->setAccountNumber($accountNumber)
                    ->setActive(true);
                return $account;
            }
        }
        throw new BadRequestException(ErrorCodesConstants::SUBSCRIBER_CANNOT_PERFORM,
            'The subscriber is not allowed to perform this transaction.');
    }
    
    /**
     * @param $account
     * @param $subscription
     * @param $performUrl
     * @param $key
     * @param $serviceCode
     * @return bool
     * @throws BadRequestException
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function performTransaction(Account $account, $subscription, $performUrl, $key, $serviceCode)
    {
        $token = $this->authenticate($key, "/$subscription/token/");
        
        $json = [
            'amount'       => $account->getAmount(),
            'currency'     => 'XAF',
            'externalId'   => $account->getIntId(),
            $subscription == 'collection' ? 'payer':'payee' => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $account->getAccountNumber(),
            ],
            'payerMessage' => 'Corlang Limited',
            'payeeNote'    => 'CorPay',
        ];
        
        Log::debug("{$this->getClientName()}: Sending purchase request to service provider", [
            'json' => $json
        ]);
    
        $httpClient = $this->getHttpClient($key, $token);
        try {
            $response = $httpClient->request('POST', $performUrl, [
                'headers' => [
                    'X-Callback-Url' => config('app.url') . '/mtn/callback',
                    'X-Reference-Id' => $account->getIntId()
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
        
        if ($response->getStatusCode() == 202) {
            return true;
        } else {
            $body = json_decode($content);
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
                default:
                    $error_code = ErrorCodesConstants::GENERAL_CODE;
            }
        }
        throw new BadRequestException($error_code, $body->message);
    }
    
    /**
     * @param $transaction
     * @param $subscription
     * @param $performUrl
     * @param $key
     * @param $serviceCode
     * @return bool
     * @throws GeneralException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyTransaction($transaction, $subscription, $performUrl, $key, $serviceCode)
    {
        $token = $this->authenticate($key, "/$subscription/token/");
    
        Log::debug("{$this->getClientName()}: Sending verification request to service provider", [
            'transaction.id' => $transaction->id,
            'status' => $transaction->status,
            'destination' => $transaction->destination,
            'service_code' => $transaction->service_code,
            'internal_id' => $transaction->internal_id,
        ]);
    
        $httpClient = $this->getHttpClient($key, $token);
        try {
            $response = $httpClient->request('GET', "$performUrl/$transaction->internal_id");
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
            // the transaction exists in service provider system irrespective of status.
            // waiting callback request from the provider with final status
            return true;
        }
        throw new GeneralException(ErrorCodesConstants::GENERAL_CODE,
            'Status verification returned non 2xx code');
        
    }
    
    /**
     * @param $key
     * @param string $token
     * @return Client
     */
    public function getHttpClient($key, $token = '')
    {
        return new Client([
            'base_uri'        => config('app.services.mtn.url'),
            'timeout'         => 120,
            'connect_timeout' => 120,
            'allow_redirects' => true,
            'headers'         => [
                'Authorization' => $token,
                'X-Target-Environment' => config('app.services.mtn.environment'),
                'Ocp-Apim-Subscription-Key' => $key,
                'Content-Type' => 'application/json'
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