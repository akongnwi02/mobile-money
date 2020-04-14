<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/18/20
 * Time: 11:13 PM
 */

namespace App\Exceptions;


class ForbiddenException extends \Exception
{
    public $error_code;
    
    public $status = 403;
    
    public function __construct($error_code, $message = "Request is forbidden")
    {
        $this->error_code = $error_code;
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