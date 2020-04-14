<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/19/20
 * Time: 8:33 PM
 */

namespace App\Services\Constants;


class ErrorCodesConstants
{
    const GENERAL_CODE                      = '00000';
    const SERVICE_NOT_FOUND                 = '10000';
    const PAYMENT_METHOD_FOUND              = '10001';
    const INVALID_INPUTS                    = '10002';
    const AUTHENTICATION_ERROR              = '10003';
    const AUTHORIZATION_ERROR               = '10004';
    const METHOD_NOT_ALLOWED                = '10005';
    const PATH_NOT_FOUND                    = '10006';
    const TOO_MANY_ATTEMPTS                 = '10007';
    const TRANSACTION_SAVE_TO_CACHE_ERROR   = '10008';
    const TRANSACTION_CREATION_ERROR        = '10009';
    const TRANSACTION_NOT_IN_CACHE          = '10010';
    const MICRO_SERVICE_CONNECTION_ERROR    = '10011';
    const NO_PRICE_RANGE_ERROR              = '10012';
    const UNKNOWN_SERVICE_CATEGORY          = '10013';
    const INVALID_API_KEY                   = '10014';
    const INVALID_LANG_KEY                  = '10015';
    const INVALID_ACCEPT_HEADER_ERROR       = '10016';
    const IP_WHITELIST_ERROR                = '10017';
    const TRANSACTION_NOT_FOUND             = '10018';
    const SERVICE_PROVIDER_CONNECTION_ERROR = '10019';
    const METER_CODE_NOT_FOUND              = '10020';
    const CALLBACK_SEND_ERROR               = '10021';
}