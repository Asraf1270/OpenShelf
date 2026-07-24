<?php
/**
 * OpenShelf Custom Autoloader
 * 
 * This autoloader handles:
 * - PHPMailer classes (PHPMailer\PHPMailer namespace)
 * - Your custom classes in the lib/ directory
 * - Any other third-party libraries you add later
 */

// Prevent multiple inclusions
if (defined('OPENSHELF_AUTOLOAD_LOADED')) {
    return;
}
define('OPENSHELF_AUTOLOAD_LOADED', true);

/**
 * Register the autoloader
 */
spl_autoload_register(function ($class) {
    // Debug mode - set to false in production
    $debug = false;
    
    // PHPMailer classes (namespace: PHPMailer\PHPMailer)
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $class_name = substr($class, strlen('PHPMailer\\PHPMailer\\'));
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . $class_name . '.php';
        
        if (file_exists($file)) {
            if ($debug) error_log("Loading PHPMailer class: $class from $file");
            require_once $file;
            return;
        }
    }
    
    // Your custom classes in lib directory
    if (strpos($class, 'OpenShelf\\') === 0) {
        $class_name = substr($class, strlen('OpenShelf\\'));
        $file = __DIR__ . '/../lib/' . str_replace('\\', '/', $class_name) . '.php';
        
        if (file_exists($file)) {
            if ($debug) error_log("Loading OpenShelf class: $class from $file");
            require_once $file;
            return;
        }
    }
    
    // Generic classes in lib directory (no namespace)
    $file = __DIR__ . '/../lib/' . $class . '.php';
    if (file_exists($file)) {
        if ($debug) error_log("Loading generic class: $class from $file");
        require_once $file;
        return;
    }
    
    // Debug log for missing classes
    if ($debug && !empty($class)) {
        error_log("Autoloader could not find class: $class");
    }
});

/**
 * Composer-style class map for better performance
 * Add frequently used classes here
 */
$class_map = [
    // PHPMailer classes
    'PHPMailer\\PHPMailer\\PHPMailer' => __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php',
    'PHPMailer\\PHPMailer\\SMTP' => __DIR__ . '/phpmailer/phpmailer/src/SMTP.php',
    'PHPMailer\\PHPMailer\\Exception' => __DIR__ . '/phpmailer/phpmailer/src/Exception.php',
    'PHPMailer\\PHPMailer\\POP3' => __DIR__ . '/phpmailer/phpmailer/src/POP3.php',
    'PHPMailer\\PHPMailer\\OAuth' => __DIR__ . '/phpmailer/phpmailer/src/OAuth.php',
    
    // Your custom classes (add as needed)
    'Mailer' => __DIR__ . '/../lib/Mailer.php',
    'Database' => __DIR__ . '/../lib/Database.php',
    'Auth' => __DIR__ . '/../lib/Auth.php',
    'Validator' => __DIR__ . '/../lib/Validator.php',
];

// Register class map loader (runs before the spl_autoload_register)
spl_autoload_register(function ($class) use ($class_map) {
    if (isset($class_map[$class])) {
        require_once $class_map[$class];
        return true;
    }
    return false;
}, true, true); // Priority: true (highest)

/**
 * Helper function to verify autoloader is working
 */
function dump_autoloader_status() {
    echo "<h3>Autoloader Status</h3>";
    echo "<pre>";
    echo "Autoloader registered: " . (defined('OPENSHELF_AUTOLOAD_LOADED') ? '✅' : '❌') . "\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Include Path: " . get_include_path() . "\n";
    
    // Check critical files
    $critical_files = [
        'PHPMailer' => __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php',
        'Mailer wrapper' => __DIR__ . '/../lib/Mailer.php',
    ];
    
    echo "\nCritical files:\n";
    foreach ($critical_files as $name => $path) {
        echo "  $name: " . (file_exists($path) ? '✅' : '❌') . " ($path)\n";
    }
    
    echo "</pre>";
}

/**
 * Version information
 */
define('OPENSHELF_VERSION', '1.0.0');
define('OPENSHELF_AUTOLOADER_VERSION', '1.0.0');

// Optional: Load environment variables if you have a .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = parse_ini_file(__DIR__ . '/../.env');
    foreach ($dotenv as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Optional: Set default timezone if not set in php.ini
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Dhaka'); // Change to your timezone
}

// Optional: Error reporting based on environment
if (getenv('OPENSHELF_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}