<?php
/**
 * Form: Add New Product
 *
 * Location: modules/products/add_product_form.php
 * Description: Displays the HTML form for entering details of a new product.
 * Submits data via POST to add_product_handler.php for processing.
 */

// Start session if not already started (needed for flash messages if redirected back)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include Core Files ---
// Use __DIR__ to ensure paths are correct relative to this script's location
try {
    require_once __DIR__ . '/../../config.php'; // Provides STORE_NAME, path constants etc.
} catch (Throwable $e) {
     error_log("FATAL ERROR in add_product_form.php: Failed to include config.php - " . $e->getMessage());
     die("Server configuration error. Please contact administrator.");
}


// --- Handle Flash Messages ---
// Check if the handler redirected back with a message (e.g., validation error)
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Clear the message after displaying it once
}

// --- Include Header ---
// This starts the HTML document, includes CSS/JS, and the site navigation.
// It should ideally define BASE_URL or paths should be handled carefully.
try {
    require_once __DIR__ . '/../../templates/header.php';
} catch (Throwable $e) {
     error_log("FATAL ERROR in add_product_form.php: Failed to include header.php - " . $e->getMessage());
     die("Server error: Could not load page structure. Please contact administrator.");
}


// --- Determine Cancel URL ---
// Construct URL back to the product list page
$cancel_url = '../../index.php?view=products'; // Relative path from modules/products/

?>

<div class="container mx-auto px-4 py-6 max-w-2xl"> <nav aria-label="breadcrumb" class="mb-4 text-sm text-gray-600">
      <ol class="list-none p-0 inline-flex space-x-2">
        <li><a href="<?php echo htmlspecialchars($cancel_url); ?>" class="text-blue-600 hover:underline">Product Management</a></li>
        <li><span class="text-gray-500 mx-2">/</span></li>
        <li class="text-gray-800 font-semibold" aria-current="page">Add New Product</li>
      </ol>
    </nav>

    <h1 class="text-3xl font-bold mb-5 text-gray-800">Add New Product</h1>

    <?php if ($flash_message): ?>
    <div class="mb-5 p-4 rounded-md shadow-sm <?php echo ($flash_message['type'] === 'success') ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>" role="alert">
        <span class="font-semibold"><?php echo ucfirst($flash_message['type']); ?>:</span>
        <?php echo htmlspecialchars($flash_message['text']); ?>
    </div>
    <?php endif; ?>

    <form action="add_product_handler.php" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-5 border border-gray-200" novalidate>
        <div>
            <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Product ID (SKU) <span class="text-red-500 font-bold">*</span></label>
            <input type="text" id="product_id" name="id" required maxlength="50"
                   class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   placeholder="Unique code (e.g., SKU12345)"
                   aria-describedby="id_help"
                   aria-required="true">
             <p id="id_help" class="mt-1 text-xs text-gray-500">A unique code for identifying the product (max 50 characters).</p>
        </div>

        <div>
            <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500 font-bold">*</span></label>
            <input type="text" id="product_name" name="name" required maxlength="100"
                   class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   placeholder="Descriptive name (e.g., Highland Fresh Milk 1L)"
                   aria-required="true">
        </div>

        <div>
            <label for="product_category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <input type="text" id="product_category" name="category" maxlength="50"
                   class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   placeholder="e.g., Dairy, Beverages, Snacks (Optional)">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
             <div>
                 <label for="product_price" class="block text-sm font-medium text-gray-700 mb-1">Price (LKR) <span class="text-red-500 font-bold">*</span></label>
                 <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                      <span class="text-gray-500 sm:text-sm">Rs.</span> </div>
                    <input type="number" id="product_price" name="price" required step="0.01" min="0.00"
                           class="form-input block w-full rounded-md border border-gray-300 pl-10 pr-4 py-2 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out font-mono"
                           placeholder="0.00"
                           aria-required="true">
                 </div>
             </div>

             <div>
                 <label for="product_stock" class="block text-sm font-medium text-gray-700 mb-1">Initial Stock Quantity <span class="text-red-500 font-bold">*</span></label>
                 <input type="number" id="product_stock" name="stock" required step="1" min="0"
                        class="form-input block w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out font-mono"
                        placeholder="e.g., 50"
                        aria-required="true">
             </div>
        </div>

        <div class="flex justify-end items-center space-x-4 pt-5 border-t border-gray-200 mt-6">
             <a href="<?php echo htmlspecialchars($cancel_url); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-5 rounded-md shadow-sm border border-gray-300 transition duration-150 ease-in-out">
                Cancel
             </a>
             <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1 -mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Add Product
             </button>
        </div>

    </form>

</div><?php
// --- Include Footer ---
// This closes the HTML document and includes global JS if any.
try {
    require_once __DIR__ . '/../../templates/footer.php';
} catch (Throwable $e) {
     error_log("FATAL ERROR in add_product_form.php: Failed to include footer.php - " . $e->getMessage());
     // Page might render incompletely if this fails.
}
?>Hey, Cortana. Number. Make it. 19 inch. Fail to check. Upload. Fail to screenshot the on the paved. Debugging sticker debug can I fix? Says handler Ki first name. Ah, remember. 