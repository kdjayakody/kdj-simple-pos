<?php
/**
 * Template: Product List View
 *
 * Displays products in a table with search, add, edit, delete functionality.
 * This template is included by index.php when ?view=products.
 * It assumes 'modules/products/product_functions.php' has been included previously by index.php.
 */

// --- Defensive Check: Ensure functions are available ---
if (!function_exists('getAllProducts') || !function_exists('searchProducts')) {
    // This should not happen if index.php is working correctly.
    die("FATAL ERROR: Required product functions are not available in product_list.php. Check index.php includes.");
}

// --- Handle Search Term ---
// Check if a search term was submitted via GET request
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$products = []; // Initialize products array
$error_message = ''; // For displaying errors if functions fail unexpectedly

// --- Retrieve Products ---
// Use searchProducts if a term is provided, otherwise getAllProducts
if (!empty($search_term)) {
    $products = searchProducts($search_term);
} else {
    $products = getAllProducts();
}

// getAllProducts/searchProducts should always return an array (empty on error/no results)
// based on our updated functions. No need to check for 'false' return.

// --- Handle Flash Messages (display feedback from actions) ---
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    // Remove the message from session so it doesn't show again on refresh
    unset($_SESSION['flash_message']);
}

?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold mb-6 text-gray-800 border-b pb-2">Product Management</h1>

    <?php if ($flash_message): ?>
    <div class="mb-4 p-4 rounded-md shadow <?php echo ($flash_message['type'] === 'success') ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>" role="alert">
        <span class="font-semibold"><?php echo ucfirst($flash_message['type']); ?>:</span>
        <?php echo htmlspecialchars($flash_message['text']); ?>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="mb-4 p-4 rounded-md shadow bg-red-100 border border-red-300 text-red-800" role="alert">
        <span class="font-semibold">Error:</span>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row justify-between items-center mb-5 gap-4">
        <div>
            <a href="modules/products/add_product_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-5 rounded-md shadow-sm hover:shadow-md transition duration-150 ease-in-out inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Product
            </a>
        </div>

        <form action="index.php" method="GET" class="flex items-center w-full md:w-auto">
            <input type="hidden" name="view" value="products"> <label for="search_term" class="sr-only">Search Products</label> <input
                type="text"
                id="search_term"
                name="search_term"
                placeholder="Search ID or Name..."
                value="<?php echo htmlspecialchars($search_term); ?>"
                class="form-input border-gray-300 rounded-l-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out w-full md:w-72"
            >
            <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-r-md shadow-sm hover:shadow-md transition duration-150 ease-in-out inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?view=products" class="ml-3 text-sm text-blue-600 hover:text-blue-800 hover:underline self-center" title="Clear search results">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="overflow-x-auto"> <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="w-1/12 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                        <th scope="col" class="w-4/12 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                        <th scope="col" class="w-2/12 px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Category</th>
                        <th scope="col" class="w-1/12 px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Price</th>
                        <th scope="col" class="w-1/12 px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Stock</th>
                        <th scope="col" class="w-2/12 px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-mono"><?php echo htmlspecialchars($product['id'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($product['category'] ?? ''); // Empty if not set ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                <?php echo isset($product['price']) ? 'Rs. ' . number_format((float)$product['price'], 2) : 'N/A'; // Added currency indication based on location ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-mono <?php echo (isset($product['stock']) && intval($product['stock']) <= 0) ? 'text-red-600 font-bold' : 'text-gray-900'; ?>">
                                <?php echo isset($product['stock']) ? number_format((int)$product['stock']) : 'N/A'; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium space-x-3">
                                <a href="modules/products/edit_product_form.php?id=<?php echo urlencode($product['id'] ?? ''); ?>" class="text-indigo-600 hover:text-indigo-900 transition duration-150 ease-in-out inline-block p-1 rounded hover:bg-indigo-100" title="Edit Product">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                                    <span class="sr-only">Edit</span>
                                </a>
                                <a href="modules/products/delete_product_handler.php?id=<?php echo urlencode($product['id'] ?? ''); ?>" class="text-red-600 hover:text-red-900 transition duration-150 ease-in-out inline-block p-1 rounded hover:bg-red-100" title="Delete Product"
                                   onclick="return confirm('WARNING: Are you sure you want to permanently delete product \'<?php echo htmlspecialchars(addslashes($product['name'] ?? ''), ENT_QUOTES); ?>\' (ID: <?php echo htmlspecialchars(addslashes($product['id'] ?? ''), ENT_QUOTES); ?>)? This action cannot be undone.');">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    <span class="sr-only">Delete</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center px-4 py-10 text-gray-500">
                                <?php if (!empty($search_term)): ?>
                                    No products found matching your search: "<strong><?php echo htmlspecialchars($search_term); ?></strong>".
                                <?php else: ?>
                                    No products have been added yet.
                                    <a href="modules/products/add_product_form.php" class="text-blue-600 hover:text-blue-800 font-semibold hover:underline">Add the first product?</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> </div> </div> 