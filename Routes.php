<?php 
define('BASE_URL', '/v1');

class AMASS {
    const BASE_URL = '/api/v1';
    const ROUTES = [
        'POST' => array(
            BASE_URL . '/users' => 'User@create',
            BASE_URL . '/login' => 'User@login',
        ),

        'GET' => array(
        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array( 
            BASE_URL . '/users' => ['name', 'email', 'phone_number', 'password', 'role'],
            BASE_URL . '/login' => ['password', 'username'],
        ),
        'GET' => array(

        )
    ];
}
