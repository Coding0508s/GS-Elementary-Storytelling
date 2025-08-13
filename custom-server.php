<?php

/**
 * Laravel Development Server (Custom)
 * Fixes broken pipe errors in file uploads
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Set proper working directory
$publicPath = __DIR__.'/public';

// Serve static files directly (images, css, js, etc.)
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    // Get the file extension and set appropriate content type
    $extension = pathinfo($publicPath.$uri, PATHINFO_EXTENSION);
    $mimeTypes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'ico' => 'image/x-icon',
        'woff' => 'application/font-woff',
        'woff2' => 'application/font-woff2',
        'ttf' => 'application/font-ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    
    // Set cache headers for static files
    header('Cache-Control: public, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    
    readfile($publicPath.$uri);
    return;
}

// Enhanced error handling to prevent broken pipe
set_error_handler(function($severity, $message, $file, $line) {
    // Suppress broken pipe errors specifically
    if (strpos($message, 'Broken pipe') !== false) {
        return true; // Suppress the error
    }
    
    // Log other errors normally
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});

// Set output buffering to prevent broken pipe
if (!ob_get_level()) {
    ob_start();
}

// Ignore user aborts to prevent broken pipe on large uploads
ignore_user_abort(true);

// Try to write log entry, but suppress broken pipe errors
try {
    $formattedDateTime = date('D M j H:i:s Y');
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $remoteAddress = ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . ':' . ($_SERVER['REMOTE_PORT'] ?? '0');
    
    // Only log if stdout is writable
    if (is_writable('php://stdout')) {
        @file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");
    }
} catch (Exception $e) {
    // Silently ignore logging errors
}

// Include the main application
require_once $publicPath.'/index.php';
