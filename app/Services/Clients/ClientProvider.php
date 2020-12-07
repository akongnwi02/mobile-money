<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 12:02 AM
 */

namespace App\Services\Clients;

use App\Exceptions\GeneralException;
use App\Services\Clients\Providers\ExpressUnion\ExpressUnionCashinClient;
use App\Services\Clients\Providers\ExpressUnion\ExpressUnionCashoutClient;
use App\Services\Clients\Providers\Mtn\MtnCashinClient;
use App\Services\Clients\Providers\Mtn\MtnCashoutClient;
use App\Services\Clients\Providers\Orange\OrangeCashinClient;
use App\Services\Clients\Providers\Orange\OrangeCashoutClient;
use App\Services\Clients\Providers\Orange\OrangeWebPaymentClient;
use App\Services\Constants\ErrorCodesConstants;

trait ClientProvider
{
    /**
     * @param $serviceCode
     * @return ClientInterface
     * @throws GeneralException
     */
    public function client($serviceCode)
    {
        if (strtolower(config('app.env') == 'testing')) {
            return new TestClient();
        }
        switch ($serviceCode) {
            case config('app.services.orange.webpayment_code'):
                $config['subscription']   = 'mp';
                $config['url']            = config('app.services.orange.webpayment_url') . 'omcoreapis/1.0.2/';
                $config['auth_url']       = config('app.services.orange.webpayment_url');
                $config['service_code']   = config('app.services.orange.webpayment_code');
                $config['callback_url']   = config('app.url') . '/callback/orange/wp';
                $config['channel_msisdn'] = config('app.services.orange.webpayment_channel_msisdn');
                $config['pin']            = config('app.services.orange.webpayment_channel_pin');
                $config['key']            = config('app.services.orange.webpayment_key');
                $config['secret']         = config('app.services.orange.webpayment_secret');
                $config['token']          = config('app.services.orange.webpayment_token');
                $config['username']       = config('app.services.orange.webpayment_api_username');
                $config['password']       = config('app.services.orange.webpayment_api_password');
                return new OrangeWebPaymentClient($config);
                break;
            
            case config('app.services.orange.cashin_code'):
                $config['subscription']   = 'cashin';
                $config['url']            = config('app.services.orange.webpayment_url') . 'omcoreapis/1.0.2/';
                $config['auth_url']       = config('app.services.orange.webpayment_url');
                $config['service_code']   = config('app.services.orange.cashin_code');
                $config['callback_url']   = config('app.url') . '/callback/orange/wp';
                $config['channel_msisdn'] = config('app.services.orange.webpayment_channel_msisdn');
                $config['pin']            = config('app.services.orange.webpayment_channel_pin');
                $config['token']          = config('app.services.orange.webpayment_token');
                $config['key']            = config('app.services.orange.webpayment_key');
                $config['secret']         = config('app.services.orange.webpayment_secret');
                $config['username']       = config('app.services.orange.webpayment_api_username');
                $config['password']       = config('app.services.orange.webpayment_api_password');
                return new OrangeCashinClient($config);
                break;
            
            case config('app.services.orange.cashout_code'):
                $config['subscription'] = 'cashout';
                return new OrangeCashoutClient($config);
                break;
            case config('app.services.express_union.cashin_code'):
                return new ExpressUnionCashinClient();
                break;
            case config('app.services.express_union.cashout_code'):
                return new ExpressUnionCashoutClient();
                break;
            
            case config('app.services.mtn.cashout_code'):
                $config['url']              = config('app.services.mtn.url');
                $config['environment']      = config('app.services.mtn.environment');
                $config['callback_url']     = config('app.url') . '/callback/mtn';
                $config['subscription']     = 'collection';
                $config['subscription_key'] = config('app.services.mtn.cashout_key');
                $config['user']             = config('app.services.mtn.cashout_user');
                $config['password']         = config('app.services.mtn.cashout_password');
                $config['perform_uri']      = '/collection/v1_0/requesttopay';
                $config['service_code']     = config('app.services.mtn.cashout_code');
                return new MtnCashoutClient($config);
            
            case  config('app.services.mtn.cashin_code'):
    
                $config['proxy']            = config('app.fixie_url');
                $config['url']              = config('app.services.mtn.url');
                $config['environment']      = config('app.services.mtn.environment');
                $config['callback_url']     = config('app.url') . '/callback/mtn';
                $config['subscription']     = 'disbursement';
                $config['subscription_key'] = config('app.services.mtn.cashin_key');
                $config['user']             = config('app.services.mtn.cashin_user');
                $config['password']         = config('app.services.mtn.cashin_password');
                $config['perform_uri']      = '/disbursement/v1_0/transfer';
                $config['status_uri']       = '';
                $config['service_code']     = config('app.services.mtn.cashin_code');
                $config['use_proxy']        = config('app.services.mtn.cashin_use_proxy');
            
                return new MtnCashinClient($config);
            
            default:
                throw new GeneralException(ErrorCodesConstants::SERVICE_NOT_FOUND, 'Unknown Micro Service');
        }
    }
}