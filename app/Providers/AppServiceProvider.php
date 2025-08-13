<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force PHP upload settings for large files (2GB)
        if (function_exists('ini_set')) {
            ini_set('upload_max_filesize', '2048M');
            ini_set('post_max_size', '2048M');
            ini_set('max_execution_time', '3600');
            ini_set('max_input_time', '3600');
            ini_set('memory_limit', '1024M');
        }

        // Suppress broken pipe errors in development server
        if (app()->environment('local')) {
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                // Ignore broken pipe and file_put_contents errors
                if (strpos($errstr, 'Broken pipe') !== false || 
                    strpos($errstr, 'file_put_contents') !== false ||
                    strpos($errfile, 'server.php') !== false) {
                    return true; // Suppress these errors
                }
                return false; // Let other errors through
            }, E_WARNING | E_NOTICE);
        }
    }
}
