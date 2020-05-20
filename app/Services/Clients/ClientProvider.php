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
                return new OrangeWebPaymentClient();
                break;
            case config('app.services.orange.cashin_code'):
                return new OrangeCashinClient();
                break;
            case config('app.services.orange.cashout_code'):
                return new OrangeCashoutClient();
                break;
            case config('app.services.express_union.cashin_code'):
                return new ExpressUnionCashinClient();
                break;
            case config('app.services.express_union.cashout_code'):
                return new ExpressUnionCashoutClient();
                break;
            case config('app.services.mtn.cashout_code'):
                return new MtnCashoutClient();
            case  config('app.services.mtn.cashin_code'):
                return new MtnCashinClient();
            default:
                throw new GeneralException('Unknown Micro Service');
        }
    }
}