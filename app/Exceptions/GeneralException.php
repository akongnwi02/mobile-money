<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 8/29/19
 * Time: 8:49 AM
 */

namespace App\Exceptions;

class GeneralException extends \Exception
{
    public $error_code;
    
    public $status = 500;
    
    public function __construct($error_code, $message = 'Server Error')
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