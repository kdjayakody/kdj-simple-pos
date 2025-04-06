 
<?php
/**
 * Simple POS - Main Entry Point & Router
 *
 * Initializes the application, handles basic routing based on URL parameters,
 * and includes the necessary header, view template, and footer.
 */

// Start session management. Useful for flash messages or potentially managing sale state.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Core Initialization ---
// Load configuration settings and core functions required globally.
// Use require_once to ensure they are loaded only once and halt if missing.
require_once 'config.php'; // Defines constants like file paths, STORE_NAME
require_once 'modules/core/data_handling.php'; // Provides readJsonFile() and writeJsonFile()
// If you create a helpers file later, include it here:
// require_once 'modules/core/helpers.php';

// --- 2. Determine View ---
// Decide which section/page to display based on the 'view' GET parameter.
// Define a list of valid views to prevent arbitrary file inclusion.
$allowed_views = ['sale', 'products', 'reports'];
// Set the default view if none is requested or the requested view is invalid.
$current_view = 'sale'; // Default to the main sales interface

if (isset($_GET['view'])) {
    $requested_view = strtolower(trim($_GET['view']));
    if (in_array($requested_view, $allowed_views, true)) {
        $current_view = $requested_view;
    }
    // Optional: Redirect or show a 404 message if the view is invalid?
    // For simplicity, we currently just fall back to the default 'sale' view.
}

// --- 3. Load Header Template ---
// Includes the HTML head, navigation bar, and opening body/main tags.
// BASE_URL constant is defined within header.php now.
require_once 'templates/header.php';

// --- 4. Load Page-Specific Logic & View Template ---
// Based on $current_view, load necessary functions from modules
// and then include the corresponding template file from the /templates/ directory.

// Optional: Add a variable for dynamic page titles if needed
// $page_title = ucfirst($current_view) . " - " . STORE_NAME; // Example

echo "\n";

switch ($current_view) {
    case 'products':
        // Product Management Page
        require_once 'modules/products/product_functions.php'; // Functions to get/add/edit products
        // The product_list.php template will use functions defined in product_functions.php
        include 'templates/product_list.php';
        break;

    case 'reports':
        // Sales Reports Page
        require_once 'modules/reports/report_functions.php'; // Functions to generate report data
        // The daily_report.php template uses functions from report_functions.php
        include 'templates/daily_report.php';
        break;

    case 'sale':
    default: // Default view is the sales interface
        // Sales Interface Page
        // Needs functions for both sales operations and product lookup
        require_once 'modules/products/product_functions.php';
        require_once 'modules/sales/sale_functions.php'; // Functions for processing sales, generating IDs etc.
        // The sales_interface.php template uses functions from both included files
        include 'templates/sales_interface.php';
        break;
}

echo "\n";

// --- 5. Load Footer Template ---
// Includes closing main/body/html tags and JavaScript files.
require_once 'templates/footer.php';

// --- End of Script ---
// Optional: Add any final cleanup code here if needed.
?>
