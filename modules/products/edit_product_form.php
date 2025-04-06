<?php
/**
 * Form: Edit Product
 *
 * Displays an HTML form pre-filled with an existing product's details
 * (Name, Category, Price) allowing for modifications. Stock is read-only.
 * Submits data via POST to edit_product_handler.php for processing.
 */

// Start session if not already started (needed for flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include Core Files ---
require_once __DIR__ . '/../../config.php'; // Global settings
require_once __DIR__ . '/product_functions.php'; // Contains getProductById()

// --- Define Redirect URL for Errors/Cancel ---
$product_list_url = '../../index.php?view=products'; // Back to the main product list

// --- Get Product ID and Fetch Data ---
// Product ID is expected as a GET parameter (e.g., edit_product_form.php?id=SKU123)
$product_id = isset($_GET['id']) ? trim($_GET['id']) : null;
$product = null; // Initialize product variable

if (empty($product_id)) {
    // If no ID is provided, redirect back to the product list with an error.
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'No Product ID specified for editing.'];
    header("Location: " . $product_list_url);
    exit();
}

// Attempt to fetch the product details using the provided ID.
$product = getProductById($product_id);

if ($product === null) {
    // If product is not found for the given ID, redirect back with an error.
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Cannot edit. Product with ID '" . htmlspecialchars($product_id, ENT_QUOTES) . "' was not found."];
    header("Location: " . $product_list_url);
    exit();
}

// --- Handle Flash Messages (If redirected back from the handler due to error) ---
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Clear after displaying
}

// --- Include Header ---
require_once __DIR__ . '/../../templates/header.php'; // Starts HTML, includes CSS/JS, nav

?>

<div class="container mx-auto px-4 py-6 max-w-2xl">

    <nav aria-label="breadcrumb" class="mb-4 text-sm text-gray-600">
      <ol class="list-none p-0 inline-flex space-x-2">
        <li><a href="<?php echo $product_list_url; ?>" class="text-blue-600 hover:underline">Product Management</a></li>
        <li><span class="text-gray-500 mx-2">/</span></li>
        <li class="text-gray-800 font-semibold" aria-current="page">Edit Product</li>
      </ol>
    </nav>

    <h1 class="text-3xl font-bold mb-5 text-gray-800 flex items-center">
        Edit Product:&#160; <span class="font-mono text-2xl text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200"><?php echo htmlspecialchars($product['id']); ?></span>
    </h1>


    <?php if ($flash_message): ?>
    <div class="mb-5 p-4 rounded-md shadow-sm <?php echo ($flash_message['type'] === 'success') ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>" role="alert">
        <span class="font-semibold"><?php echo ucfirst($flash_message['type']); ?>:</span>
        <?php echo htmlspecialchars($flash_message['text']); ?>
    </div>
    <?php endif; ?>

    <form action="edit_product_handler.php" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-5 border border-gray-200">

        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Product ID (SKU)</label>
            <input type="text" readonly disabled
                   value="<?php echo htmlspecialchars($product['id']); ?>"
                   class="form-input block w-full px-3 py-2 rounded-md border-gray-300 shadow-sm bg-gray-100 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-300"
                   aria-label="Product ID (read-only)">
            <p class="mt-1 text-xs text-gray-500">The Product ID cannot be changed.</p>
        </div>

        <div>
            <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500 font-bold">*</span></label>
            <input type="text" id="product_name" name="name" required maxlength="100"
                   value="<?php echo htmlspecialchars($product['name'] ?? '', ENT_QUOTES); // Use ENT_QUOTES for attributes ?>"
                   class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   placeholder="Descriptive name (e.g., Highland Fresh Milk 1L)">
        </div>

        <div>
            <label for="product_category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <input type="text" id="product_category" name="category" maxlength="50"
                   value="<?php echo htmlspecialchars($product['category'] ?? '', ENT_QUOTES); ?>"
                   class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   placeholder="e.g., Dairy, Beverages, Snacks (Optional)">
        </div>

        <div>
             <label for="product_price" class="block text-sm font-medium text-gray-700 mb-1">Price (LKR) <span class="text-red-500 font-bold">*</span></label>
             <div class="relative mt-1 rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <span class="text-gray-500 sm:text-sm">Rs.</span>
                </div>
                <input type="number" id="product_price" name="price" required step="0.01" min="0.00"
                       value="<?php echo htmlspecialchars(number_format((float)($product['price'] ?? 0.00), 2, '.', ''), ENT_QUOTES); // Format required for input type=number ?>"
                       class="form-input block w-full rounded-md border border-gray-300 pl-10 pr-4 py-2 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                       placeholder="0.00">
             </div>
         </div>

        <div>
             <label class="block text-sm font-medium text-gray-700 mb-1">Current Stock Quantity</label>
             <input type="number" readonly disabled
                    value="<?php echo htmlspecialchars($product['stock'] ?? '0', ENT_QUOTES); ?>"
                    class="form-input block w-full px-3 py-2 rounded-md border-gray-300 shadow-sm bg-gray-100 text-gray-500 cursor-not-allowed focus:ring-0 focus:border-gray-300"
                    aria-label="Current stock (read-only)">
             <p class="mt-1 text-xs text-gray-500">Stock level is managed automatically through sales and cannot be edited here.</p>
        </div>


        <div class="flex justify-end items-center space-x-4 pt-5 border-t border-gray-200 mt-6">
             <a href="<?php echo $product_list_url; ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-5 rounded-md shadow-sm border border-gray-300 transition duration-150 ease-in-out">
                Cancel
             </a>
             <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded-md shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150 ease-in-out inline-flex items-center">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                   <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                 </svg>
                Save Changes
             </button>
        </div>

    </form>

</div><?php
// --- Include Footer ---
require_once __DIR__ . '/../../templates/footer.php'; // Closes HTML document
?>