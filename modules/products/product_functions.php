 
<?php
/**
 * Product Module Functions
 *
 * Contains functions for managing product data (CRUD operations + stock updates)
 * interacting with the products.json file via core data handling functions.
 */

// Ensure core files are loaded using absolute paths relative to this file's location
require_once __DIR__ . '/../../config.php'; // Defines PRODUCTS_FILE
require_once __DIR__ . '/../core/data_handling.php'; // Provides readJsonFile() and writeJsonFile()

/**
 * Retrieves all products from the JSON data file.
 *
 * @return array Returns an array of all products. If the file doesn't exist, is empty,
 * or there's a read/decode error, it returns an empty array []
 * after logging the error (if any).
 */
function getAllProducts(): array
{
    $products = readJsonFile(PRODUCTS_FILE);

    if ($products === false) {
        // readJsonFile logs the specific error (lock, read, decode)
        error_log("getAllProducts: readJsonFile failed for " . PRODUCTS_FILE . ". Returning empty array.");
        return []; // Return empty array on failure to allow graceful handling downstream
    }

    // Ensure the final result is always an array
    return is_array($products) ? $products : [];
}

/**
 * Finds a single product by its unique ID. Case-sensitive comparison.
 *
 * @param string $productId The ID of the product to find.
 * @return array|null The product data as an associative array if found, null otherwise or on read error.
 */
function getProductById(string $productId): ?array
{
    // Trim Product ID just in case
    $productId = trim($productId);
    if (empty($productId)) {
        return null;
    }

    $products = getAllProducts();
    // getAllProducts now returns [] on error/empty, so no need to check for false here

    foreach ($products as $product) {
        // Ensure 'id' exists and compare as strings
        if (isset($product['id']) && (string) $product['id'] === $productId) {
            return $product; // Return the found product array
        }
    }

    return null; // Product not found
}

/**
 * Checks if a product ID already exists in the data store.
 *
 * @param string $productId The product ID to check.
 * @return bool True if the ID *does not* exist (is unique), false if it *does* exist or on error reading products.
 */
function isProductIdUnique(string $productId): bool
{
    // An ID is unique if getProductById doesn't find it.
    return getProductById($productId) === null;
}

/**
 * Adds a new product to the data store (products.json).
 * Validates required fields and uniqueness of the ID.
 *
 * @param array $newProductData Associative array containing new product data.
 * Expected keys: 'id', 'name', 'price', 'stock'. 'category' is optional.
 * @return bool True on successful addition, false on validation error, duplicate ID, or file write failure.
 */
function addProduct(array $newProductData): bool
{
    // 1. Validation & Sanitization
    if (empty($newProductData['id']) || !isset($newProductData['name']) || !isset($newProductData['price']) || !isset($newProductData['stock'])) {
        error_log("addProduct Error: Missing required fields (id, name, price, stock). Data: " . print_r($newProductData, true));
        // Consider setting a user-facing error message via session flash data
        // $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Missing required product fields.'];
        return false;
    }

    $id = trim((string) $newProductData['id']);
    $name = trim((string) $newProductData['name']);
    $price = $newProductData['price']; // Validate numeric below
    $stock = $newProductData['stock']; // Validate numeric below
    $category = isset($newProductData['category']) ? trim((string) $newProductData['category']) : ''; // Optional field

    if (empty($id) || empty($name)) {
         error_log("addProduct Error: ID or Name is empty after trimming.");
         return false;
    }
    if (!is_numeric($price) || floatval($price) < 0 || !is_numeric($stock) || intval($stock) < 0) {
         error_log("addProduct Error: Invalid numeric value for price ('{$price}') or stock ('{$stock}'). Must be non-negative numbers.");
         return false;
    }

    // Cast to appropriate types
    $price = floatval($price);
    $stock = intval($stock);

    // 2. Check Uniqueness of ID
    if (!isProductIdUnique($id)) {
        error_log("addProduct Error: Product ID '{$id}' already exists.");
        // $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Product ID '{$id}' already exists."];
        return false;
    }

    // 3. Read current products
    $products = getAllProducts();
    // Since getAllProducts handles read errors internally now by returning [],
    // we just proceed. An error during read would result in an empty $products array.

    // 4. Prepare and Add new product data
    $productToAdd = [
        'id'       => $id,
        'name'     => $name,
        'price'    => $price,
        'category' => $category,
        'stock'    => $stock
    ];
    $products[] = $productToAdd; // Append to the array

    // 5. Write updated products back to file
    if (!writeJsonFile(PRODUCTS_FILE, $products)) {
        error_log("addProduct Error: Failed to write updated products to " . PRODUCTS_FILE);
        // writeJsonFile logs details, but we log context here.
        return false;
    }

    // $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Product '{$name}' added successfully."];
    return true; // Success!
}

/**
 * Updates an existing product's details (name, price, category).
 * IMPORTANT: This function does NOT update stock levels. Use updateProductStock() for that.
 *
 * @param string $productId The ID of the product to update.
 * @param array $updatedData Associative array containing the data fields to update
 * (typically 'name', 'price', 'category').
 * @return bool True on success, false if product not found, validation fails, or write fails.
 */
function updateProduct(string $productId, array $updatedData): bool
{
    // 1. Validation & Sanitization
     $productId = trim($productId);
     if (empty($productId)) {
         error_log("updateProduct Error: Product ID cannot be empty.");
         return false;
     }
     // Check for required fields in the update data array
     if (!isset($updatedData['name']) || !isset($updatedData['price']) || !isset($updatedData['category'])) {
        error_log("updateProduct Error: Missing required fields (name, price, category) in update data for ID '{$productId}'.");
        return false;
     }

    $newName = trim((string) $updatedData['name']);
    $newPrice = $updatedData['price']; // Validate numeric below
    $newCategory = trim((string) $updatedData['category']); // Optional, allow empty

    if (empty($newName)) {
         error_log("updateProduct Error: Product Name cannot be empty for ID '{$productId}'.");
         return false;
    }
    if (!is_numeric($newPrice) || floatval($newPrice) < 0) {
         error_log("updateProduct Error: Invalid numeric value for price ('{$newPrice}') for ID '{$productId}'. Must be non-negative.");
         return false;
    }
    $newPrice = floatval($newPrice);


    // 2. Read current products
    $products = getAllProducts();

    // 3. Find the product to update
    $productIndex = -1;
    foreach ($products as $index => $product) {
        if (isset($product['id']) && (string) $product['id'] === $productId) {
            $productIndex = $index;
            break;
        }
    }

    if ($productIndex === -1) {
        error_log("updateProduct Error: Product with ID '{$productId}' not found.");
        return false; // Product not found
    }

    // 4. Update data in the array
    $products[$productIndex]['name'] = $newName;
    $products[$productIndex]['price'] = $newPrice;
    $products[$productIndex]['category'] = $newCategory;
    // NOTE: Stock is intentionally NOT updated here.

    // 5. Write updated products back to file
    if (!writeJsonFile(PRODUCTS_FILE, $products)) {
        error_log("updateProduct Error: Failed to write updated products to " . PRODUCTS_FILE . " for ID '{$productId}'.");
        return false;
    }

    // $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Product '{$newName}' (ID: {$productId}) updated successfully."];
    return true; // Success!
}

/**
 * Updates the stock level for a specific product.
 * Used by sales processing (negative quantityChange) or manual adjustments.
 *
 * @param string $productId The ID of the product whose stock needs updating.
 * @param int $quantityChange The amount to change the stock by (e.g., -1 for selling one item).
 * @param bool $allowNegativeStock If true, allows stock count to become negative. Defaults to false.
 * @return bool True on success, false if product not found, stock validation fails (if applicable), or write fails.
 */
 function updateProductStock(string $productId, int $quantityChange, bool $allowNegativeStock = false): bool
 {
     $productId = trim($productId);
     if (empty($productId)) {
         error_log("updateProductStock Error: Product ID cannot be empty.");
         return false;
     }

     $products = getAllProducts();

     $productIndex = -1;
     foreach ($products as $index => $product) {
         if (isset($product['id']) && (string) $product['id'] === $productId) {
             $productIndex = $index;
             break;
         }
     }

     if ($productIndex === -1) {
         error_log("updateProductStock Error: Product with ID '{$productId}' not found.");
         return false; // Product not found
     }

     // Get current stock, default to 0 if not set or invalid
     $currentStock = (isset($products[$productIndex]['stock']) && is_numeric($products[$productIndex]['stock']))
                     ? intval($products[$productIndex]['stock'])
                     : 0;

     $newStock = $currentStock + $quantityChange;

     // Validate stock level if negative stock is not allowed
     if (!$allowNegativeStock && $newStock < 0) {
         error_log("updateProductStock Error: Insufficient stock for Product ID '{$productId}'. Current: {$currentStock}, Requested Change: {$quantityChange}, Resulting: {$newStock}");
         // Set a user-facing message if needed
         // $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Insufficient stock for '{$products[$productIndex]['name']}'. Available: {$currentStock}."];
         return false; // Not enough stock
     }

     // Update stock in the array
     $products[$productIndex]['stock'] = $newStock;

     // Write updated products back to file
     if (!writeJsonFile(PRODUCTS_FILE, $products)) {
         error_log("updateProductStock Error: Failed to write updated stock to " . PRODUCTS_FILE . " for ID '{$productId}'.");
         return false;
     }

     // Log stock change for auditing? (Optional)
     // error_log("Stock Update Success: ID '{$productId}', Change {$quantityChange}, New Stock {$newStock}");

     return true; // Success!
 }


/**
 * Deletes a product from the data store by its ID.
 *
 * @param string $productId The ID of the product to delete.
 * @return bool True on success, false if product not found or write failure.
 */
function deleteProduct(string $productId): bool
{
    $productId = trim($productId);
    if (empty($productId)) {
        error_log("deleteProduct Error: Product ID cannot be empty.");
        return false;
    }

    $products = getAllProducts();
    $initialCount = count($products);

    // Create a new array excluding the product to be deleted
    $updatedProducts = [];
    $found = false;
    foreach ($products as $product) {
        if (isset($product['id']) && (string) $product['id'] === $productId) {
            $found = true; // Mark as found, but don't add to the new array
        } else {
            $updatedProducts[] = $product; // Keep this product
        }
    }

    if (!$found) {
        error_log("deleteProduct Error: Product with ID '{$productId}' not found.");
        return false; // Product not found
    }

    // Write the filtered array back to the file
    // No need to re-index as we built a new sequential array.
    if (!writeJsonFile(PRODUCTS_FILE, $updatedProducts)) {
        error_log("deleteProduct Error: Failed to write updated products to " . PRODUCTS_FILE . " after attempting to delete ID '{$productId}'.");
        return false;
    }

    // $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Product ID '{$productId}' deleted successfully."];
    return true; // Success!
}

/**
 * Searches products by ID or Name (case-insensitive).
 * Returns all products if the search term is empty.
 *
 * @param string $searchTerm The string to search for within product ID or Name.
 * @return array An array of matching products. Returns an empty array if no matches or on read error.
 */
function searchProducts(string $searchTerm): array
{
    $products = getAllProducts();
    $searchTerm = trim($searchTerm);

    if (empty($searchTerm)) {
        return $products; // Return all if search term is empty
    }

    $searchTermLower = strtolower($searchTerm);
    $results = [];

    foreach ($products as $product) {
        $idMatch = isset($product['id']) && str_contains(strtolower((string)$product['id']), $searchTermLower);
        $nameMatch = isset($product['name']) && str_contains(strtolower((string)$product['name']), $searchTermLower);
        // Add category search?
        // $categoryMatch = isset($product['category']) && str_contains(strtolower((string)$product['category']), $searchTermLower);

        if ($idMatch || $nameMatch /*|| $categoryMatch*/) {
            $results[] = $product;
        }
    }

    return $results; // Return the array of matching products (might be empty)
}

?>
