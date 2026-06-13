<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        print_r($error);
    }
});

$_GET['id'] = 'W8Z6SJYBGY'; // some dummy id
try {
    include 'book/index.php';
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
