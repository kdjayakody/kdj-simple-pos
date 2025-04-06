<?php
/**
 * Handler: Add New Product
 *
 * Processes the POST data submitted from the add_product_form.php.
 * It validates the input (primarily by calling the addProduct function which has
 * internal validation), attempts to add the product to the data store,
 * sets a session flash message indicating success or failure, and redirects
 * the user back to the product list page.
 */

// Start session if not already started (essential for flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include Core Files ---
// Use __DIR__ for reliable path resolution relative to this script's location.
require_once __DIR__ . '/../../config.php'; // Global settings, constants
require_once __DIR__ . '/product_functions.php'; // Contains addProduct(), isProductIdUnique() etc.

// --- Define Redirect URL ---
// Usually, we redirect back to the list view after adding.
// BASE_URL is defined in header.php, which isn't included here. Use relative path.
$redirect_url = '../../index.php?view=products';
$form_url = 'add_product_form.php'; // URL to redirect back to form on specific errors

// --- Security Check: Allow POST Method Only ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If accessed directly or via GET, redirect away with an error message.
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => 'Invalid request method for adding a product.'
    ];
    header("Location: " . $redirect_url);
    exit(); // Stop script execution
}

// --- Retrieve Submitted Data ---
// Use null coalescing operator (??) for cleaner defaults if data isn't set.
$id       = trim($_POST['id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$price    = $_POST['price'] ?? ''; // Keep raw for validation within addProduct
$stock    = $_POST['stock'] ?? ''; // Keep raw for validation within addProduct

// --- Prepare Data Array ---
// This structure matches the input expected by the addProduct function.
$newProductData = [
    'id'       => $id,
    'name'     => $name,
    'price'    => $price,    // addProduct will validate/cast
    'category' => $category,
    'stock'    => $stock     // addProduct will validate/cast
];

// --- Attempt to Add Product ---
// Call the function responsible for validation, uniqueness checks, and file writing.
$result = addProduct($newProductData); // Returns true on success, false on failure

// --- Set Feedback Message (Flash Message) ---
if ($result) {
    // Success! Set a positive message.
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => "Product '" . htmlspecialchars($name, ENT_QUOTES) . "' (ID: " . htmlspecialchars($id, ENT_QUOTES) . ") added successfully!"
    ];
    // Redirect to the product list on success
    header("Location: " . $redirect_url);
    exit();

} else {
    // Failure. Try to provide a more specific error message for the user.
    // The addProduct function logs detailed errors server-side.
    $error_text = 'Failed to add product. ';

    // Check for common, specific errors to give better user feedback
    if (empty($id) || empty($name) || $price === '' || $stock === '') {
        $error_text .= 'Please ensure all required fields (*) are filled correctly.';
    } elseif (!isProductIdUnique($id)) {
        // Check uniqueness again just for the user message (addProduct already checked)
        $error_text .= "The Product ID '" . htmlspecialchars($id, ENT_QUOTES) . "' already exists.";
    } elseif (!is_numeric($price) || floatval($price) < 0 || !is_numeric($stock) || intval($stock) < 0) {
        $error_text .= "Price and Stock must be valid non-negative numbers.";
    }
    else {
        // Generic error if the cause isn't one of the above common issues
        $error_text .= 'An unexpected error occurred. Please check the data or contact support.';
        // Consulting server error logs would be necessary here.
    }

    $_SESSION['flash_message'] = [
        'type' => 'error',
        'text' => $error_text
    ];

    // --- OPTIONAL: Redirect back to the form on error ---
    // To help the user correct the input. You might also pass back the submitted
    // values via session to re-populate the form, but that adds complexity.
    // For simplicity now, we redirect back to the product list even on error,
    // but display the error message there. If you want to redirect back to the form:
    // header("Location: " . $form_url);
    // exit();
    // --- END OPTIONAL ---

    // Default: Redirect back to the product list even on error
    header("Location: " . $redirect_url);
    exit();
}

// --- Fallback (Should not be reached) ---
// If the script somehow continues past exit(), redirect as a safety measure.
header("Location: " . $redirect_url);
exit();

?>
