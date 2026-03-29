<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->post('auth/register', 'Auth::register');
    $routes->get('auth/verify-email', 'Auth::verifyEmail');
    $routes->post('auth/login', 'Auth::login');

    $routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
        $routes->post('auth/logout', 'Auth::logout');         
    });
});
