<?php
/**
 * Backend Handler: Search Products (JSON Response)
 *
 * Location: modules/products/search_handler.php
 * Description: Responds to AJAX/fetch requests from the sales interface.
 * Accepts a 'search_term' via GET request, uses the searchProducts()
 * function from this module, and returns matching products as a JSON array.
 *
 * Request Method: GET
 * Parameters:
 * - search_term (string): The term to search for.
 *
 * Response:
 * - Success: JSON array of product objects. Example: [{"id":"...", "name":"...", ...}]
 * - Error:   JSON object with an 'error' key and appropriate HTTP status code.
 */

// --- Environment Setup ---
// Disable direct error output for JSON endpoints, rely on logs.
// ini_set('display_errors', 0);
// error_reporting(0); // Uncomment for production

// --- Set HTTP Headers ---
// Indicate JSON response content type.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff'); // Security header

// --- Include Core Files ---
// Paths are relative to THIS script's location (modules/products/)
try {
    // Go up two levels to reach the root directory for config.php
    require_once __DIR__ . '/../../config.php';
    // Include the product functions file (it's in the same directory)
    require_once __DIR__ . '/product_functions.php';
} catch (Throwable $e) {
    // Catch potential errors during file inclusion
    error_log("FATAL ERROR in search_handler.php: Failed to include core files - " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    // Use json_encode directly here as helper functions might not be loaded
    echo json_encode(['error' => 'Server setup error. Please contact administrator.']);
    exit();
}


// --- Defensive Check: Ensure search function exists ---
if (!function_exists('searchProducts')) {
    error_log("FATAL ERROR in search_handler.php: searchProducts function not found after include.");
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Server configuration error: Search functionality unavailable.']);
    exit();
}

// --- Process the Request ---
// Get the search term from the GET parameters, trim whitespace.
$searchTerm = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$results = []; // Initialize results as an empty array

// Perform the search only if a non-empty search term is provided.
if (!empty($searchTerm)) {
    $results = searchProducts($searchTerm);
    // Ensure the function returned an array as expected
    if (!is_array($results)) {
         error_log("search_handlerI. .php: searchProducts() returned non-array for term '{$searchTerm}'. Check product_functions.php implementation.");
         // Return empty results to the client to avoid breaking the UI
         $results = [];
         // Optionally, log this as a server error or return 500 status if critical
    }
} else {
    // No search term provided, return empty results.
    $results = [];
}

// --- Output JSON Response ---
// Encode the results array (which might be empty) into JSON format.
try {
    // Use flags for readability and error handling (PHP >= 7.3 for JSON_THROW_ON_ERROR)
    echo json_encode(
        $results,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
} catch (JsonException $e) {
    // Handle potential errors during JSON encoding
    error_log("search_handler.php: JSON encoding error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Server error: Failed to format the product data response.']);
}

// --- End Script Execution ---
exit(); // Ensure no further output interferes with the JSON response.

?>