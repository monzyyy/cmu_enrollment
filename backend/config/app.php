<?php

define('APP_NAME', 'cmuenrollment');

$host = $_SERVER['HTTP_HOST'] ?? '';
$host = preg_replace('/:\d+$/', '', strtolower($host));
$isLocalHost = $host === 'localhost'
	|| $host === '127.0.0.1'
	|| preg_match('/^(10|192\.168|172\.(1[6-9]|2\d|3[0-1]))\./', $host);

define('APP_ENV', $isLocalHost ? 'local' : 'production');

/*
 * Detect the project's URL folder.
 * This allows the project to work on localhost
 * and on the production server without hardcoding
 * the project folder.
 */
$scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
$rootPath   = str_replace('\\', '/', realpath(ROOT_PATH));
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

$relative = substr($scriptFile, strlen($rootPath));                     // e.g. /backend/api/register.php
$base     = substr($scriptName, 0, strlen($scriptName) - strlen($relative)); // e.g. /cool_freeze

define('BASE_URL', rtrim($base, '/') . '/');

define('MAINTENANCE_MODE', false);

date_default_timezone_set('Asia/Manila');