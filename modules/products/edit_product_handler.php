<?php
/**
 * Handler: Edit Product
 *
 * Processes the POST data submitted from edit_product_form.php.
 * Validates the input (relies on updateProduct function), updates the specified
 * product's details (name, price, category) in the data store, sets a session
 * flash message, and redirects the user. Redirects back to the edit form on
 * failure, or the product list on success.
 */

// Start session if not already started (essential for flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include Core Files ---
require_once __DIR__ . '/../../config.php'; // Global settings
require_once __DIR__ . '/product_functions.php'; // Contains updateProduct()

// --- Define Redirect URLs ---
$product_list_url = '../../index.php?view=products'; // Back to the main product list
$edit_form_url_base = 'edit_product_form.php?id='; // Base URL for redirecting back to edit form

// --- Security Check: Allow POST Method Only ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If accessed directly or via GET, redirect away with an error message.
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Invalid request method for updating a product.'
    ];
    header("Location: " . $product_list_url);
    exit(); // Stop script execution
}

// --- Retrieve Submitted Data ---
// Get the product ID from the hidden field and other editable fields.
$product_id = isset($_POST['id']) ? trim($_POST['id']) : null;
$name       = isset($_POST['name']) ? trim($_POST['name']) : '';
$category   = isset($_POST['category']) ? trim($_POST['category']) : ''; // Optional, allow empty
$price      = isset($_POST['price']) ? $_POST['price'] : ''; // Keep raw for validation

// --- Basic Input Validation ---
// Crucial: Check if the ID was actually submitted.
if (empty($product_id)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Failed to update product: Missing Product ID. Please try editing again.'
    ];
    // Can't redirect back to edit form without ID, so go to list.
    header("Location: " . $product_list_url);
    exit();
}
// Also check other essential fields received from the form
if ($name === '' || $price === '') {
     $_SESSION['flash_message'] = [
         'type' => 'error',
         'text' => 'Failed to update product: Product Name and Price cannot be empty.'
     ];
     // Redirect back to the edit form with the ID
     header("Location: " . $edit_form_url_base . urlencode($product_id));
     exit();
}


// --- Prepare Data Array for Update Function ---
// This structure should contain only the fields that `updateProduct` modifies.
$updatedData = [
    'name'     => $name,
    'price'    => $price,    // updateProduct will validate/cast this
    'category' => $category,
];

// --- Attempt to Update Product ---
// Call the function responsible for finding the product, validation, and file writing.
// updateProduct handles checks like valid price format.
$result = updateProduct($product_id, $updatedData); // Returns true on success, false on failure

// --- Set Feedback Message (Flash Message) ---
if ($result) {
    // Success! Set a positive message.
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => "Product '" . htmlspecialchars($name, ENT_QUOTES) . "' (ID: " . htmlspecialchars($product_id, ENT_QUOTES) . ") updated successfully!"
    ];
    // Redirect to the product list on success
    header("Location: " . $product_list_url);
    exit();

} else {
    // Failure. Try to provide a more specific error message.
    // updateProduct logs detailed errors server-side.
    $error_text = 'Failed to update product (ID: ' . htmlspecialchars($product_id, ENT_QUOTES) . '). ';

    // Check common reasons for failure (based on updateProduct's validation)
    if (empty($name)) { // Should have been caught above, but check again
        $error_text .= 'Product Name cannot be empty.';
    } elseif (!is_numeric($price) || floatval($price) < 0) {
        $error_text .= 'Price must be a valid non-negative number.';
    } else {
        // Could be product not found (less likely if edit form loaded) or a file write error.
        $error_text .= 'An unexpected error occurred. Please check data or server logs for details.';
    }

    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => $error_text
    ];

    // --- Redirect back to the Edit Form on error ---
    // This allows the user to see the error and correct the form.
    header("Location: " . $edit_form_url_base . urlencode($product_id));
    exit();
}

// --- Fallback (Should not be reached) ---
// If the script somehow continues past exit(), redirect as a safety measure.
header("Location: " . $product_list_url);
exit();

?>