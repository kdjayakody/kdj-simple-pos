 
<?php
/**
 * Simple POS System Configuration
 *
 * Defines constants for file paths, timezone, and basic settings.
 */

// --- Error Reporting (Development) ---
// Turn on error reporting for debugging purposes during development.
// Should be turned off or logged to a file in a production environment.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Timezone ---
// Set the default timezone. Adjust 'Asia/Colombo' if needed.
// Important for consistent timestamps in sales records.
date_default_timezone_set('Asia/Colombo');

// --- File Paths ---
// Define the absolute path to the data directory.
// __DIR__ gets the directory of the current file (config.php).
define('DATA_PATH', __DIR__ . '/data/');

// Define the full paths to the JSON data files.
define('PRODUCTS_FILE', DATA_PATH . 'products.json');
define('SALES_FILE', DATA_PATH . 'sales.json');

// --- Basic Store Settings ---
// Define the name of the store, potentially used in headers or receipts.
define('STORE_NAME', 'My Simple Grocery');

// Ensure the data directory exists, try to create if not (basic check)
if (!is_dir(DATA_PATH)) {
    // Attempt to create the directory recursively
    if (!mkdir(DATA_PATH, 0775, true)) {
        // If creation fails, terminate with an error.
        // Permissions might be an issue on the server.
        die("Error: Data directory does not exist and could not be created. Please check permissions for: " . DATA_PATH);
    }
}

// --- Optional: Define a base URL if needed for links/redirects ---
// Useful if the app isn't running at the web root.
// Example: define('BASE_URL', '/simple-pos'); // Adjust if needed
// For simplicity now, we'll assume it runs at the root or handle paths relatively.

?>