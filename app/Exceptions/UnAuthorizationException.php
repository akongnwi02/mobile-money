<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 9/3/19
 * Time: 12:43 AM
 */

namespace App\Exceptions;


class UnAuthorizationException extends \Exception
{
    public $error_code;
    
    public $status = 401;
    
    public function __construct($message = "User is not authenticated")
    {
        parent::__construct($message);
    }
    
    public function status()
    {
        return $this->status;
    }
    
    public function error_code()
    {
        return $this->error_code;
    }
}