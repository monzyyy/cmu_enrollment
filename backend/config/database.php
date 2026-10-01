<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions

$db_server = "localhost";
$db_user   = "root";
$db_pass   = "";
$db_name   = "cmuenrollment_db";

try {
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    exit(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}
// Don't close here. PHP closes it automatically when the request ends.