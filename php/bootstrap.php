<?php
/**
 * Bootstrap file - Autoloader and common setup
 */

// Simple PSR-4 style autoloader for App namespace
spl_autoload_register(function ($class) {
    // Base namespace prefix
    $prefix = 'App\\';
    
    // Base directory for namespace prefix
    $baseDir = __DIR__ . '/classes/';
    
    // Check if class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Require file if it exists
    if (file_exists($file)) {
        require $file;
    }
});
