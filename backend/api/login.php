<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/student.php';

ini_set('display_errors', '0');
header('Content-Type: application/json');


function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}


if (
    !empty($_SESSION['login_locked_until']) &&
    time() < $_SESSION['login_locked_until']
) {
    respond([
        'success' => false,
        'message' => 'Too many attempts. Please try again in a few minutes.'
    ], 429);
}


$account_number = trim($_POST['student_number'] ?? '');
$password = $_POST['password'] ?? '';


$errors = [];


if ($account_number === '') {
    $errors['student_number'] = 'Enter your account number.';
}


if ($password === '') {
    $errors['password'] = 'Enter your password.';
}


if ($errors) {
    respond([
        'success' => false,
        'errors' => $errors
    ], 422);
}


try {

    $user = user_find_by_account_number(
        $conn,
        $account_number
    );


    if ($user) {

        $valid = password_verify(
            $password,
            $user['password_hash']
        );

    } else {

        password_hash($password, PASSWORD_DEFAULT);
        $valid = false;
    }


    if (!$valid) {

        $_SESSION['login_attempts'] =
            ($_SESSION['login_attempts'] ?? 0) + 1;


        if ($_SESSION['login_attempts'] >= 5) {

            $_SESSION['login_locked_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }


        respond([
            'success' => false,
            'message' => 'Invalid account number or password.'
        ], 401);
    }


    session_regenerate_id(true);


    unset(
        $_SESSION['login_attempts'],
        $_SESSION['login_locked_until']
    );


    $_SESSION['user_id'] =
        (int) $user['user_id'];


    $_SESSION['account_number'] =
        $user['account_number'];


    $_SESSION['role'] =
        $user['role'];


    if ($user['role'] === 'STUDENT') {

        $student = student_find_by_number(
            $conn,
            $account_number
        );


        if (!$student) {

            respond([
                'success' => false,
                'message' => 'Student account information could not be found.'
            ], 500);
        }


        $_SESSION['student_id'] =
            (int) $student['student_id'];


        $_SESSION['student_number'] =
            $student['student_number'];


        $_SESSION['student_name'] =
            trim(
                $student['first_name'] . ' ' .
                ($student['middle_name']
                    ? $student['middle_name'] . ' '
                    : '') .
                $student['last_name']
            );


        respond([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => BASE_URL . '?page=home_main'
        ]);
    }


    if ($user['role'] === 'ADMIN') {

        $_SESSION['username'] = 'Admin';

        respond([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => BASE_URL . '?page=admin_home'
        ]);
    }


    if ($user['role'] === 'INSTRUCTOR') {

        respond([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => BASE_URL . '?page=instructor_home'
        ]);
    }


    respond([
        'success' => false,
        'message' => 'Invalid account role.'
    ], 403);


} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    respond([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ], 500);
}