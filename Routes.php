<?php 
define('BASE_URL', '/v1');

class AMASS {
    const BASE_URL = '/api/v1';
    const ROUTES = [
        'POST' => array(
            BASE_URL . '/users' => 'User@create',
            BASE_URL . '/login' => 'User@login',
            BASE_URL . '/verify' => 'User@initiate',
            BASE_URL . '/products' => 'Product@create',
            BASE_URL . '/comments' => 'Product@addComment',
            BASE_URL . '/appointments' => 'User@bookAppointment',
            BASE_URL . '/all/products' => 'Product@get',
            BASE_URL . '/s/products' => 'Product@getProduct',
            BASE_URL . '/s/users' => 'User@getUser',
            BASE_URL . '/products/comments' => 'Product@getComments',
        ),

        'GET' => array(
        )
    ];

    const ALLOWED_PARAM = [
        'POST' => array( 
            BASE_URL . '/users' => ['email', 'phone_number', 'password', 'token'],
            BASE_URL . '/login' => ['password', 'username'],
            BASE_URL . '/verify' => ['email', 'phone_number', 'name'],
            BASE_URL . '/products' => ['token', 'name', 'description', 'category_id', 'price', 'file'],
            BASE_URL . '/comments' => ['product_id', 'comment', 'rate', 'hash'],
            BASE_URL . '/appointments' => ['product_id', 'hash'],
            BASE_URL . '/all/products' => ['page_num'],
            BASE_URL . '/s/products' => ['product_id'],
            BASE_URL . '/s/users' => ['user_id'],
            BASE_URL . '/products/comments' => ['product_id'],
        ),
        'GET' => array(
            
        )
    ];
}
