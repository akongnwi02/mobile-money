<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/14/20
 * Time: 7:28 PM
 */

namespace App\Services\Constants;

class TransactionConstants
{
    // TransactionConstants Status
    const CREATED = 'created';
    const PROCESSING = 'processing';
    const SUCCESS = 'success';
    const FAILED = 'failed';
    const ERRORED = 'errored';
    const VERIFICATION = 'verification';
}