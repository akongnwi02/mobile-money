<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 3/14/20
 * Time: 6:42 PM
 */

namespace App\Exceptions;

class BadRequestException extends \Exception
{
    public $error_code;
    
    public $status = 400;
    
    public function __construct($error_code, $message = 'Invalid input')
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