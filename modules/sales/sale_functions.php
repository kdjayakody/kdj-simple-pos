 
<?php
/**
 * Sales Module Functions
 *
 * Provides functions for sales-related operations such as generating unique Sale IDs,
 * recording sale data persistently, and retrieving sales data for reporting.
 * Relies on core data handling functions for file access.
 */

// --- Include Core Files ---
// Use __DIR__ for reliable path resolution relative to this script (modules/sales/)
try {
    require_once __DIR__ . '/../../config.php'; // Settings, SALES_FILE constant
    // Core functions for reading/writing JSON files with locking
    require_once __DIR__ . '/../core/data_handling.php';
} catch (Throwable $e) {
    error_log("FATAL ERROR in sale_functions.php: Failed to include core files - " . $e->getMessage());
    // If this happens, subsequent function calls will likely fail.
    // Consider adding checks within functions or letting PHP handle the fatal error.
    die("Server setup error: Cannot load core files for sales module."); // Stop execution
}


/**
 * Generates a unique Sale ID based on the current date and a daily sequential number.
 *
 * Format: YYYYMMDD-NNN (e.g., 20250407-001) where NNN is zero-padded.
 * The sequence resets each day based on the server's timezone (set in config.php).
 *
 * Note: This method reads the last sale ID to determine the next sequence number.
 * While file locking during write prevents data corruption, there's a very small
 * theoretical chance of a race condition if two sales read the sequence number
 * almost simultaneously before either can write the new record. For a simple,
 * low-concurrency system, this risk is generally acceptable.
 *
 * @return string|false The generated Sale ID string (e.g., "20250407-001"), or false on error reading sales data.
 */
function generateSaleId(): string|false
{
    // Get today's date string in YYYYMMDD format based on configured timezone
    $todayDateStr = date('Ymd');
    $salesData = readJsonFile(SALES_FILE); // Read with shared lock

    if ($salesData === false) {
        error_log("generateSaleId Error: Failed to read sales file (" . SALES_FILE . ") using readJsonFile.");
        return false; // Indicate failure to generate ID due to read error
    }

    $lastSaleSequence = 0;
    // If sales data exists, find the last sequence number used *today*
    if (!empty($salesData)) {
        // Iterate backwards through sales for efficiency
        for ($i = count($salesData) - 1; $i >= 0; $i--) {
            $sale = $salesData[$i];
            // Check if the sale ID exists and starts with today's date string
            if (isset($sale['sale_id']) && is_string($sale['sale_id']) && str_starts_with($sale['sale_id'], $todayDateStr . '-')) {
                // Extract the sequence number part after the hyphen
                $parts = explode('-', $sale['sale_id']);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = intval($parts[1]);
                    // Keep track of the highest sequence number found for today
                    if ($sequence > $lastSaleSequence) {
                        $lastSaleSequence = $sequence;
                    }
                    // Optimization: If IDs are strictly sequential, we could break here.
                    // But iterating through all of today's allows for potential out-of-order writes (less likely).
                }
            }
             // Optimization: If we encounter a sale ID from a previous day, we can stop searching backwards.
             elseif (isset($sale['sale_id']) && is_string($sale['sale_id']) && strlen($sale['sale_id']) > 8) {
                if (substr($sale['sale_id'], 0, 8) < $todayDateStr) {
                    break; // Found sales from a previous day, no need to look further back.
                }
            }
        }
    }

    // Calculate the next sequence number
    $nextSequence = $lastSaleSequence + 1;

    // Format the sequence number with leading zeros (e.g., 3 digits wide: 001, 010, 100)
    // Adjust padding length (3) if you expect more than 999 sales per day.
    $sequenceStr = str_pad((string)$nextSequence, 3, '0', STR_PAD_LEFT);

    // Combine date and sequence to form the new Sale ID
    $newSaleId = $todayDateStr . '-' . $sequenceStr;

    return $newSaleId;
}


/**
 * Records the details of a completed sale into the sales data file (sales.json).
 * Appends the new sale record to the existing array of sales.
 * Assumes input data structure is correct; primary validation happens before calling this.
 *
 * @param array $saleData Associative array containing the complete sale details.
 * Must include at least 'sale_id', 'timestamp', 'items' (array), 'total_amount'.
 * @return bool True if the sale was successfully recorded, false otherwise (e.g., file read/write error).
 */
function recordSale(array $saleData): bool
{
    // --- Basic Input Structure Validation ---
    if (empty($saleData['sale_id']) || empty($saleData['timestamp']) || !isset($saleData['items']) || !is_array($saleData['items']) || !isset($saleData['total_amount'])) {
        error_log("recordSale Error: Input sale data is missing required keys (sale_id, timestamp, items, total_amount).");
        return false; // Indicate failure due to invalid input structure
    }

    // --- Read Existing Sales ---
    $allSales = readJsonFile(SALES_FILE); // Uses shared lock for read initially
    if ($allSales === false) {
        error_log("recordSale Error: Failed to read existing sales data from " . SALES_FILE);
        return false; // Indicate failure due to read error
    }
    // Ensure $allSales is an array, even if the file was empty or new
    if (!is_array($allSales)) {
        $allSales = [];
    }

    // --- Append New Sale Data ---
    // Add the newly completed sale record to the end of the array
    $allSales[] = $saleData;

    // --- Write Updated Sales Data ---
    // Use writeJsonFile which handles exclusive locking for the write operation.
    if (!writeJsonFile(SALES_FILE, $allSales)) {
        error_log("recordSale Error: Failed to write updated sales data to " . SALES_FILE);
        return false; // Indicate failure due to write error
    }

    // If write was successful
    return true;
}


/**
 * Retrieves all sales transactions recorded on a specific date.
 * Useful for generating daily sales reports.
 *
 * @param string $dateString The date to filter sales by, expected in 'YYYY-MM-DD' format.
 * @return array|false An array of sale objects matching the specified date.
 * Returns an empty array [] if no sales are found for that date.
 * Returns false if there was an error reading the sales data file.
 */
function getSalesByDate(string $dateString): array|false
{
    // --- Validate Input Date Format (Basic Check) ---
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
         error_log("getSalesByDate Error: Invalid date format '{$dateString}'. Expected 'YYYY-MM-DD'.");
         return false; // Indicate failure due to invalid input format
    }

    // --- Read All Sales Data ---
    $allSales = readJsonFile(SALES_FILE);
    if ($allSales === false) {
        error_log("getSalesByDate Error: Failed to read sales data from " . SALES_FILE);
        return false; // Indicate failure due to read error
    }
    // Ensure it's an array if file was empty/new
     if (!is_array($allSales)) { $allSales = []; }


    // --- Filter Sales by Date ---
    $salesOnDate = [];
    foreach ($allSales as $sale) {
        // Check if timestamp exists and is a string
        if (isset($sale['timestamp']) && is_string($sale['timestamp'])) {
            // Timestamps are stored in ISO 8601 format (e.g., 2025-04-07T14:03:19+05:30)
            // Extract the date part (first 10 characters: YYYY-MM-DD) for comparison
            $saleDatePart = substr($sale['timestamp'], 0, 10);
            // Compare the extracted date part with the requested date string
            if ($saleDatePart === $dateString) {
                $salesOnDate[] = $sale; // Add matching sale to the results array
            }
        }
    }

    // Return the array of sales found for the specified date (could be empty)
    return $salesOnDate;
}


?>
