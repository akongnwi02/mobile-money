<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/search', ['uses' => 'TransactionController@search']);

$router->post('/execute', ['uses' => 'TransactionController@execute']);

$router->get('/status/{external_id}', ['uses' => 'TransactionController@status']);