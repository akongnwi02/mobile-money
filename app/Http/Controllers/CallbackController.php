<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 6/9/20
 * Time: 7:54 PM
 */

namespace App\Http\Controllers;


class CallbackController extends Controller
{
    
    public function successResponse()
    {
        return response()->json([
            'status' => 'OK'
        ], 200);
    }
    
    
    public function getClassName()
    {
        return class_basename($this);
    }
}