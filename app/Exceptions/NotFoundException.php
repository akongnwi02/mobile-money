<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 8/29/19
 * Time: 8:49 AM
 */

namespace App\Exceptions;

class NotFoundException extends \Exception
{
    public $error_code;
    
    public $status = 404;
    
    public function __construct($error_code, $message = 'Resource or service not found')
    {
        $this->error_code = $error_code;
        
        parent::__construct($message);
    }
    
    public function error_code()
    {
        return $this->error_code;
    }
    
    public function status()
    {
        return $this->status;
    }
}