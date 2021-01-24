<?php 
define('BASE_URL', '/v1');

class AMASS {
    const BASE_URL = '/api/v1';
    const ROUTES = [
        'POST' => array(
            BASE_URL . '/users' => 'User@create',
            BASE_URL . '/login' => 'User@login',
            BASE_URL . '/verify' => 'User@initiate',
        ),

        'GET' => array(
        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array( 
            BASE_URL . '/users' => ['email', 'phone_number', 'password', 'token'],
            BASE_URL . '/login' => ['password', 'username'],
            BASE_URL . '/verify' => ['email', 'phone_number', 'name'],
        ),
        'GET' => array(

        )
    ];
}
