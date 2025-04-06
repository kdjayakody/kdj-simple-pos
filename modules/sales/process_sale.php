 
<?php
/**
 * Handler: Process Sale
 *
 * Location: modules/sales/process_sale.php
 * Description: Handles the finalization of a sale submitted from the sales interface.
 * - Expects JSON payload via POST.
 * - Performs server-side validation (product existence, stock levels).
 * - Generates a unique Sale ID.
 * - Records the sale details to sales.json.
 * - Updates product stock levels.
 * - Returns a JSON response indicating success (with receipt data) or failure (with error message).
 */

// --- Environment Setup ---
// Disable direct error output for API endpoints. Rely on server logs.
// ini_set('display_errors', 0);
// error_reporting(0); // Uncomment for production

// --- Set HTTP Headers ---
// Indicate JSON response and prevent sniffing.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// --- Include Core Files ---
// Use __DIR__ for reliable path resolution.
try {
    require_once __DIR__ . '/../../config.php'; // Global settings, constants
    require_once __DIR__ . '/sale_functions.php'; // generateSaleId(), recordSale()
    require_once __DIR__ . '/../products/product_functions.php'; // getProductById(), updateProductStock()
} catch (Throwable $e) {
    error_log("FATAL ERROR in process_sale.php: Failed to include core files - " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Server setup error. Cannot process sale.']);
    exit();
}

// --- Check Request Method ---
// Ensure the request is coming via POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is accepted for processing sales.']);
    exit();
}

// --- Get Input Data (JSON Payload from Request Body) ---
$inputJSON = file_get_contents('php://input');
$inputData = null;
try {
    // Decode the JSON payload into a PHP associative array.
    $inputData = json_decode($inputJSON, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    // Handle cases where the received data is not valid JSON.
    http_response_code(400); // Bad Request
    error_log("process_sale.php: Invalid JSON received: " . $e->getMessage() . " - Input: " . substr($inputJSON, 0, 500)); // Log part of input
    echo json_encode(['success' => false, 'message' => 'Invalid sale data format received. Expected valid JSON.']);
    exit();
}

// --- Validate Input Structure & Basic Values ---
// Check if essential keys exist in the decoded data.
if (
    !$inputData || !isset($inputData['items']) || !is_array($inputData['items']) ||
    !isset($inputData['total_amount']) || !isset($inputData['amount_received']) || !isset($inputData['change_given'])
) {
    http_response_code(400); // Bad Request
    error_log("process_sale.php: Incomplete sale data structure received: " . substr($inputJSON, 0, 500));
    echo json_encode(['success' => false, 'message' => 'Incomplete sale data received from client.']);
    exit();
}

// Extract data into variables
$itemsInput = $inputData['items']; // Raw items array from input
$totalAmount = $inputData['total_amount'];
$amountReceived = $inputData['amount_received'];
$changeGiven = $inputData['change_given'];
$paymentType = $inputData['payment_type'] ?? 'Cash'; // Default payment type if not provided

// Validate numeric amounts and ensure enough payment was received (server-side check)
if (
    !is_numeric($totalAmount) || !is_numeric($amountReceived) || !is_numeric($changeGiven) ||
    floatval($amountReceived) < floatval($totalAmount)
) {
     http_response_code(400); // Bad Request
     error_log("process_sale.php: Invalid or insufficient payment amounts received. Total: {$totalAmount}, Received: {$amountReceived}. Input: " . substr($inputJSON, 0, 500));
     echo json_encode(['success' => false, 'message' => 'Invalid payment amounts received or amount received is less than total.']);
     exit();
}
// Cast amounts to float for consistency
$totalAmount = floatval($totalAmount);
$amountReceived = floatval($amountReceived);
$changeGiven = floatval($changeGiven); // Re-calculate server side? Safer. Let's recalculate.
$calculatedChange = $amountReceived - $totalAmount;
// Compare calculated change with client-sent change (allow for small float inaccuracies)
if (abs($calculatedChange - floatval($inputData['change_given'])) > 0.001) {
     error_log("process_sale.php: Change discrepancy. Client: {$inputData['change_given']}, Server Calc: {$calculatedChange}. Input: " . substr($inputJSON, 0, 500));
     // Decide whether to proceed or reject. Let's proceed but use server calculated change.
     $changeGiven = $calculatedChange;
}


// --- Server-Side Validation: Product Existence & Stock Check ---
$validationErrors = [];         // To collect error messages
$validatedItemsForReceipt = []; // To store details needed for the receipt response (incl. names)
$itemsToSave = [];              // To store minimal item details for saving in sales.json

if (empty($itemsInput)) {
     $validationErrors[] = "The sale cart is empty.";
} else {
    foreach ($itemsInput as $index => $item) {
        // Validate the structure of each item received
        if (empty($item['product_id']) || !isset($item['quantity']) || !is_numeric($item['quantity']) || intval($item['quantity']) <= 0 || !isset($item['price_at_sale']) || !is_numeric($item['price_at_sale'])) {
            $validationErrors[] = "Item #".($index+1)." has invalid data (ID, quantity, price).";
            continue; // Skip processing this malformed item
        }

        $productId = trim($item['product_id']);
        $quantity = intval($item['quantity']);
        $priceAtSale = floatval($item['price_at_sale']);

        // Fetch current product details from the data store using product functions
        $product = getProductById($productId);

        // Check 1: Product Existence
        if ($product === null) {
            $validationErrors[] = "Product ID '{$productId}' not found in inventory.";
            continue; // Cannot proceed with a non-existent product
        }

        // Check 2: Stock Availability
        // Ensure stock key exists and is numeric before comparison
        if (!isset($product['stock']) || !is_numeric($product['stock'])) {
            $validationErrors[] = "Inventory data error for Product '" . ($product['name'] ?? $productId) . "'. Stock level unavailable.";
            continue;
        }
        if ($product['stock'] < $quantity) {
            $validationErrors[] = "Insufficient stock for Product '" . ($product['name'] ?? $productId) . "'. Requested: {$quantity}, Available: {$product['stock']}.";
            continue;
        }

        // If all checks pass for this item:
        // 1. Prepare minimal data for saving to sales.json
        $itemsToSave[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'price_at_sale' => $priceAtSale // Store the price at the time of sale
        ];
        // 2. Prepare data needed for generating the receipt response (including name)
        $validatedItemsForReceipt[] = [
            'product_id' => $productId,
            'name' => $product['name'] ?? 'Unknown Product', // Get product name
            'quantity' => $quantity,
            'price_at_sale' => $priceAtSale
        ];

        // Optional Check 3: Price Verification (compare price_at_sale with current product['price'])
        // If prices change frequently, you might want to add a check here.
        // Example: if (abs($priceAtSale - floatval($product['price'])) > 0.01) { ... flag warning ... }
    }
}

// --- Handle Any Validation Errors Found ---
if (!empty($validationErrors)) {
    http_response_code(400); // Bad Request (client error, e.g., insufficient stock)
    $errorMessage = "Sale cannot be completed: " . implode(" ", $validationErrors);
    error_log("process_sale.php: Sale validation failed - " . $errorMessage . " - Input: " . substr($inputJSON, 0, 500));
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit();
}

// ========== Sale Validation Passed - Proceed with Processing ==========

// --- 1. Generate Unique Sale ID ---
$saleId = generateSaleId();
if ($saleId === false) {
    // Handle error if ID generation fails (e.g., cannot read sales file)
    http_response_code(500); // Internal Server Error
    error_log("process_sale.php: Critical - Failed to generate Sale ID.");
    echo json_encode(['success' => false, 'message' => 'Failed to generate unique Sale ID. Cannot process sale.']);
    exit();
}

// --- 2. Prepare Final Sale Data Object for Saving ---
$saleDataToSave = [
    'sale_id'         => $saleId,
    'timestamp'       => date('c'), // Use server time in ISO 8601 format (e.g., 2025-04-07T02:43:28+05:30)
    'items'           => $itemsToSave, // Save minimal item data (ID, Qty, Price)
    'total_amount'    => $totalAmount,
    'amount_received' => $amountReceived,
    'change_given'    => $changeGiven, // Use server-calculated or validated change
    'payment_type'    => $paymentType
];

// --- 3. Record the Sale Transaction ---
// Attempt to save the sale data to sales.json using the sale function.
if (!recordSale($saleDataToSave)) {
    // Handle error if saving the sale fails (e.g., cannot write to sales file)
    http_response_code(500); // Internal Server Error
    error_log("process_sale.php: Critical - Failed to record sale (ID: {$saleId}) using recordSale function.");
    echo json_encode(['success' => false, 'message' => 'Failed to save sale record after validation. Cannot complete sale.']);
    exit();
}

// --- 4. Update Product Stock Levels ---
// Iterate through the validated items and decrease stock levels.
$stockUpdateErrors = []; // Keep track of any failures during stock update
foreach ($itemsToSave as $item) {
    $productId = $item['product_id'];
    $quantityChange = -$item['quantity']; // Use negative value to decrease stock

    // Call the product function to update stock. Disallow going below zero.
    if (!updateProductStock($productId, $quantityChange, false)) {
        // If stock update fails for an item (should be rare if validation passed), log it.
        // The sale is already recorded; this indicates potential data inconsistency.
        $stockUpdateErrors[] = $productId;
        error_log("process_sale.php: WARNING - Stock update failed for Product ID '{$productId}' (Qty change: {$quantityChange}) for Sale ID '{$saleId}'. Manual stock verification needed.");
    }
}

// --- 5. Prepare Success Response (including Receipt Data) ---
$successMessage = "Sale (ID: {$saleId}) completed successfully.";
// Append warning if any stock updates failed
if (!empty($stockUpdateErrors)) {
     $successMessage .= " NOTE: Issue updating stock for product IDs: " . implode(', ', $stockUpdateErrors) . ". Please verify levels manually.";
}

// Structure the receipt data to be sent back to the frontend interface.
$receiptData = [
    'store_name' => defined('STORE_NAME') ? STORE_NAME : 'Your Store', // Get store name from config
    'sale_id' => $saleDataToSave['sale_id'],
    'timestamp' => $saleDataToSave['timestamp'], // Use the exact timestamp saved
    'items' => [], // Format items with names for display
    'total_amount' => $saleDataToSave['total_amount'],
    'amount_received' => $saleDataToSave['amount_received'],
    'change_given' => $saleDataToSave['change_given'],
    'payment_type' => $saleDataToSave['payment_type']
];
// Populate receipt items using the names collected during validation
foreach($validatedItemsForReceipt as $item) {
    $receiptData['items'][] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['price_at_sale'], // Price per unit at time of sale
        'item_total' => round($item['price_at_sale'] * $item['quantity'], 2) // Calculate line total
    ];
}

// --- Send JSON Success Response ---
echo json_encode([
    'success' => true,
    'sale_id' => $saleId,
    'message' => $successMessage,
    'receipt' => $receiptData // Embed the prepared receipt data
]);

// --- End Script Execution ---
exit();

?>
