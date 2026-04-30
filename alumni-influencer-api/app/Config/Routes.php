<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });

    $routes->post('auth/register', 'Auth::register');
    $routes->get('auth/verify-email', 'Auth::verifyEmail');
    $routes->post('auth/login', 'Auth::login');
    $routes->post('auth/forgot-password', 'Auth::forgotPassword');
    $routes->post('auth/reset-password', 'Auth::resetPassword');

    $routes->get('featured-alumnus', 'PublicController::featuredAlumnus', ['filter' => 'jwtAuth:api_key_only,ability_read_alumni_of_day']);
    $routes->get('docs', 'DocsController::index');

    $routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
        $routes->post('auth/logout', 'Auth::logout');
        $routes->get('auth/usage-stats', 'Auth::usageStats');
    });

    $routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
        $routes->get('api-keys', 'ApiKeyController::index');
        $routes->post('api-keys', 'ApiKeyController::generate');
        $routes->get('api-keys/(:num)/stats', 'ApiKeyController::stats/$1');
        $routes->delete('api-keys/(:num)', 'ApiKeyController::revoke/$1');
    });

    $routes->group('', ['filter' => 'jwtAuth'], function ($routes) {
        $routes->get('profile', 'AlumniProfileController::show');
        $routes->post('profile', 'AlumniProfileController::create');
        $routes->put('profile/(:num)', 'AlumniProfileController::update/$1');
        $routes->post('profile/upload-image', 'AlumniProfileController::uploadImage');
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

    $routes->group('', ['filter' => 'jwtAuth:api_key_only,ability_read_alumni'], function ($routes) {
        $routes->get('profile/full', 'AlumniProfileController::fullProfile');
        $routes->get('profile/all', 'AlumniProfileController::allProfiles');
        $routes->get('profile/filter-options', 'AlumniProfileController::filterOptions');
    });

    $routes->group('analytics', ['filter' => 'jwtAuth:api_key_only,ability_read_analytics'], function ($routes) {
        $routes->get('kpi', 'AnalyticsController::kpi');
        $routes->get('alumni-by-programme', 'AnalyticsController::programme');
        $routes->get('certifications-over-time', 'AnalyticsController::certificationsTrend');
        $routes->get('curriculum-skills-gap-by-programme', 'AnalyticsController::curriculumSkillsGapByProgramme');
        $routes->get('top-employers', 'AnalyticsController::employment');
        $routes->get('job-titles', 'AnalyticsController::jobTitles');
        $routes->get('graduation-trends', 'AnalyticsController::graduation');
    });
});
