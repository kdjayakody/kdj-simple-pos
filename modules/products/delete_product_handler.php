<?php
/**
 * Handler: Delete Product
 *
 * Processes the request to delete a product identified by its ID, typically passed via GET.
 * Retrieves the product ID, calls the deleteProduct function, sets a session flash
 * message for feedback, and redirects the user back to the product list view.
 *
 * Note: While using GET for a delete operation is simpler for basic link triggering
 * (especially with a JS confirmation), for more robust applications, using POST/DELETE
 * methods via forms or JavaScript fetch is generally recommended for actions
 * that change server state.
 */

// Start session if not already started (required for flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include Core Files ---
require_once __DIR__ . '/../../config.php'; // Global settings
require_once __DIR__ . '/product_functions.php'; // Contains deleteProduct() and getProductById()

// --- Define Redirect URL ---
$product_list_url = '../../index.php?view=products'; // Redirect back here after action

// --- Get Product ID from GET Parameter ---
$product_id = isset($_GET['id']) ? trim($_GET['id']) : null;

// --- Validate Product ID ---
if (empty($product_id)) {
    // If no ID is provided, set error message and redirect.
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Deletion failed: No Product ID was specified.'];
    header("Location: " . $product_list_url);
    exit(); // Stop execution
}

// --- Get Product Details (for user feedback) BEFORE Deleting ---
// Fetch the product to get its name for a more informative message.
$product_to_delete = getProductById($product_id);
$product_identifier_for_message = htmlspecialchars($product_id, ENT_QUOTES); // Default identifier

if ($product_to_delete !== null) {
    // If product found, use its name and ID for the message
    $product_name = isset($product_to_delete['name']) ? $product_to_delete['name'] : '';
    $product_identifier_for_message = "'" . htmlspecialchars($product_name, ENT_QUOTES) . "' (ID: " . htmlspecialchars($product_id, ENT_QUOTES) . ")";
} else {
    // If the product doesn't exist even before trying to delete it
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => "Deletion failed: Product with ID '" . htmlspecialchars($product_id, ENT_QUOTES) . "' could not be found."
    ];
    header("Location: " . $product_list_url);
    exit(); // Stop execution
}

// --- Attempt to Delete the Product ---
// Call the function that handles removal from the data store.
$result = deleteProduct($product_id); // Returns true on success, false on failure

// --- Set Feedback Message based on Result ---
if ($result) {
    // Success!
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => "Product " . $product_identifier_for_message . " was successfully deleted."
    ];
} else {
    // Failure. deleteProduct logs details server-side.
    // The 'product not found' case was handled above, so this usually indicates a file write error.
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => "Failed to delete product " . $product_identifier_for_message . ". An unexpected error occurred during file update. Please check server logs."
    ];
}

// --- Redirect User Back to Product List ---
header("Location: " . $product_list_url);
exit(); // Ensure script stops after redirect header

?>