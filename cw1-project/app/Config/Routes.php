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
    $routes->post('auth/forgot-password', 'Auth::forgotPassword');
    $routes->post('auth/reset-password', 'Auth::resetPassword');

    $routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
        $routes->post('auth/logout', 'Auth::logout');

        $routes->get('profile', 'AlumniProfileController::show');
        $routes->post('profile', 'AlumniProfileController::create');
        $routes->put('profile/(:num)', 'AlumniProfileController::update/$1');
        $routes->post('profile/upload-image', 'AlumniProfileController::uploadImage');
        $routes->get('profile/full', 'AlumniProfileController::fullProfile');

        $routes->get('degrees', 'DegreeController::index');
        $routes->get('degrees/(:num)', 'DegreeController::show/$1');
        $routes->post('degrees', 'DegreeController::create');
        $routes->put('degrees/(:num)', 'DegreeController::update/$1');
        $routes->delete('degrees/(:num)', 'DegreeController::delete/$1');

        $routes->get('certifications', 'CertificationController::index');
        $routes->get('certifications/(:num)', 'CertificationController::show/$1');
        $routes->post('certifications', 'CertificationController::create');
        $routes->put('certifications/(:num)', 'CertificationController::update/$1');
        $routes->delete('certifications/(:num)', 'CertificationController::delete/$1');

        $routes->get('licences', 'LicenceController::index');
        $routes->get('licences/(:num)', 'LicenceController::show/$1');
        $routes->post('licences', 'LicenceController::create');
        $routes->put('licences/(:num)', 'LicenceController::update/$1');
        $routes->delete('licences/(:num)', 'LicenceController::delete/$1');

        $routes->get('courses', 'CourseController::index');
        $routes->get('courses/(:num)', 'CourseController::show/$1');
        $routes->post('courses', 'CourseController::create');
        $routes->put('courses/(:num)', 'CourseController::update/$1');
        $routes->delete('courses/(:num)', 'CourseController::delete/$1');

        $routes->get('employment-history', 'EmploymentHistoryController::index');
        $routes->get('employment-history/(:num)', 'EmploymentHistoryController::show/$1');
        $routes->post('employment-history', 'EmploymentHistoryController::create');
        $routes->put('employment-history/(:num)', 'EmploymentHistoryController::update/$1');
        $routes->delete('employment-history/(:num)', 'EmploymentHistoryController::delete/$1');

        $routes->get('event-participations', 'EventParticipationController::index');
        $routes->get('event-participations/(:num)', 'EventParticipationController::show/$1');
        $routes->post('event-participations', 'EventParticipationController::create');
        $routes->put('event-participations/(:num)', 'EventParticipationController::update/$1');
        $routes->delete('event-participations/(:num)', 'EventParticipationController::delete/$1');

        $routes->post('bids', 'BidController::placeBid');
        $routes->put('bids/(:num)', 'BidController::updateBid/$1');
        $routes->post('bids/(:num)/cancel', 'BidController::cancelBid/$1');
        $routes->get('bids/history', 'BidController::bidHistory');
        $routes->get('bids/monthly-limit', 'BidController::monthlyLimitStatus');
    });
});
