<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Set PHP upload limits for large files (2GB)
ini_set('upload_max_filesize', '2048M');
ini_set('post_max_size', '2048M');
ini_set('max_execution_time', '0'); // 무제한으로 설정
ini_set('max_input_time', '3600');
ini_set('memory_limit', '2048M');

// 추가적인 시간 제한 설정
set_time_limit(0); // 스크립트 실행 시간 제한 완전 제거

// Suppress broken pipe errors (harmless connection drops) - Enhanced
error_reporting(E_ERROR | E_PARSE);
ini_set('log_errors_max_len', 0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Additional error suppression for broken pipe issues
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suppress broken pipe and file_put_contents warnings
    if (strpos($errstr, 'Broken pipe') !== false || 
        strpos($errstr, 'file_put_contents') !== false ||
        $errno === E_WARNING || $errno === E_NOTICE) {
        return true; // Suppress these errors
    }
    return false; // Let other errors through
}, E_ALL);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
