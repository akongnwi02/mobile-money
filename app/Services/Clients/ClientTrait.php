<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 12:02 AM
 */

namespace App\Services\Clients;

use App\Exceptions\GeneralException;
use App\Services\Clients\Providers\ExpressUnionClient;
use App\Services\Clients\Providers\OrangeClient;

trait ClientTrait
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
            case config('app.services.orange.code'):
                return new OrangeClient();
                break;
            case config('app.services.express_union.code'):
                return new ExpressUnionClient();
                break;
            default:
                throw new GeneralException('Unknown Micro Service');
        }
    }
}