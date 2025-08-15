<?php
// Error display configuration for development and production

// Check if we're in development or production environment
$is_development = (
    strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || 
    strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false ||
    strpos($_SERVER['SERVER_NAME'], 'mamp') !== false ||
    $_SERVER['SERVER_NAME'] === 'localhost'
);

if ($is_development) {
    // Development environment - show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
} else {
    // Production environment - for debugging, temporarily show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Temporarily enabled for debugging
    ini_set('display_startup_errors', 1); // Temporarily enabled for debugging
    ini_set('log_errors', 1);
    
    // Set custom error log file if possible
    if (is_writable(dirname(__DIR__))) {
        ini_set('error_log', dirname(__DIR__) . '/error.log');
    }
}

// Set timezone (adjust as needed)
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Kolkata');
}

// Custom error handler for better error logging in production
function customErrorHandler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error_types = array(
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    );
    
    $error_type = isset($error_types[$severity]) ? $error_types[$severity] : 'Unknown Error';
    $error_message = "[$error_type] $message in $file on line $line";
    
    // Log the error
    error_log($error_message);
    
    // In development, also display the error
    global $is_development;
    if ($is_development) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin: 5px; border-radius: 4px;'>";
        echo "<strong>$error_type:</strong> $message<br>";
        echo "<small>File: $file | Line: $line</small>";
        echo "</div>";
    }
    
    return true;
}

// Set the custom error handler
set_error_handler('customErrorHandler');

// Custom exception handler
function customExceptionHandler($exception) {
    $error_message = "Uncaught Exception: " . $exception->getMessage() . 
                    " in " . $exception->getFile() . 
                    " on line " . $exception->getLine();
    
    error_log($error_message);
    
    global $is_development;
    if ($is_development) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px; border-radius: 4px;'>";
        echo "<h3>Uncaught Exception</h3>";
        echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><br>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px; border-radius: 4px;'>";
        echo "<h3>An error occurred</h3>";
        echo "<p>We're sorry, but something went wrong. Please try again later.</p>";
        echo "</div>";
    }
}

// Set the custom exception handler
set_exception_handler('customExceptionHandler');

// Ensure session configuration is appropriate
if (!headers_sent() && session_status() == PHP_SESSION_NONE) {
    // Set secure session parameters for production ONLY if no session is active
    if (!$is_development) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    }
}
?>