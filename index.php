<?php
require_once __DIR__ . '/backend/bootstrap.php';    

// Maintenance mode (local XAMPP stays viewable)
if (MAINTENANCE_MODE && APP_ENV !== 'local') {
    http_response_code(503);
    header('Retry-After: 3600');

    //require FRONTEND_PATH . '/pages/error/maintenance.php';
    exit;
}

$pages = [
    'home_main' => 'main/home_main.php',
    'course_offering' => 'main/course_offering.php',
    'faculty_evaluation' => 'main/faculty_evaluation.php',
    'clearance_main' => 'main/clearance_main.php',
    'cor' => 'main/cor.php',
    'profile' => 'main/profile.php',


    'admin_home' => 'admin/admin_home.php',
    'enrollment_period' => 'admin/enrollment_period.php',
    'student_enrollment' => 'admin/student_enrollment.php',
    'cor_submissions' => 'admin/cor_submissions.php',
    'course_offerings' => 'admin/course_offerings.php',
    'course_management' => 'admin/course_management.php',

    'login' => 'auth/login.php',
    'forget' => 'auth/forget.php',
    'verification' => 'auth/verification.php',
    'reset' => 'auth/reset.php',
    'set_password' => 'auth/set_password.php',
    
    'error404' => 'error/error404.php',
    'maintenance' => 'error/maintenance.php',
    'sample' => 'sample.php',

];

//                        change this 'register'. pick the page in the $pages
$page = $_GET['page'] ?? 'login';

if (
    !is_string($page) ||
    !isset($pages[$page]) ||
    !is_file(FRONTEND_PATH . '/pages/' . $pages[$page])
) {
    http_response_code(404);

    require FRONTEND_PATH . '/pages/error/error404.php';
    exit;
}

require FRONTEND_PATH . '/pages/' . $pages[$page];
