<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 12:02 AM
 */

namespace App\Services\Clients;

use App\Exceptions\GeneralException;
use App\Services\Clients\Providers\ExpressUnionCashinClient;
use App\Services\Clients\Providers\ExpressUnionCashoutClient;
use App\Services\Clients\Providers\ExpressUnionClient;
use App\Services\Clients\Providers\OrangeCashinClient;
use App\Services\Clients\Providers\OrangeCashoutClient;
use App\Services\Clients\Providers\OrangeClient;

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
            default:
                throw new GeneralException('Unknown Micro Service');
        }
    }
}