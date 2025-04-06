 
<?php
/**
 * Reports Module Functions
 *
 * Contains functions responsible for fetching and processing data required
 * for generating sales reports (e.g., daily summary).
 * Relies on functions from the Sales module to access sale records.
 */

// --- Include Core Files ---
// Use __DIR__ for reliable path resolution relative to this script (modules/reports/)
try {
    require_once __DIR__ . '/../../config.php'; // Global settings
    // Need access to sales data retrieval functions
    require_once __DIR__ . '/../sales/sale_functions.php'; // Provides getSalesByDate()
} catch (Throwable $e) {
    error_log("FATAL ERROR in report_functions.php: Failed to include core files - " . $e->getMessage());
    // If core files are missing, reporting functions cannot operate.
    die("Server setup error: Cannot load core files required for the reports module.");
}


/**
 * Generates summary data for a daily sales report.
 *
 * Calculates the total sales amount and the total number of transactions
 * that occurred on the specified date.
 *
 * @param string $dateString The date for which to generate the report, expected
 * in 'YYYY-MM-DD' format (e.g., '2025-04-07').
 *
 * @return array|false An associative array containing the report summary:
 * ['date' => string, 'total_sales' => float, 'transaction_count' => int]
 * Returns array even if there are no sales (values will be 0).
 * Returns false if the date format is invalid or if there was an
 * error reading the underlying sales data file.
 */
function generateDailySalesReportData(string $dateString): array|false
{
    // --- Validate Input Date Format ---
    // Ensure the provided date string matches the expected 'YYYY-MM-DD' format.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
         error_log("generateDailySalesReportData Error: Invalid date format provided ('{$dateString}'). Expected 'YYYY-MM-DD'.");
         return false; // Indicate failure due to invalid input format
    }

    // --- Retrieve Sales Data for the Specified Date ---
    // Call the function from the sales module to get relevant records.
    $salesOnDate = getSalesByDate($dateString);

    // Check if getSalesByDate encountered an error (e.g., couldn't read sales.json)
    if ($salesOnDate === false) {
        error_log("generateDailySalesReportData Error: Failed to retrieve sales data for date '{$dateString}' using getSalesByDate().");
        // Propagate the failure indicator
        return false;
    }

    // --- Calculate Summary Statistics ---
    $totalSalesAmount = 0.0;
    $numberOfTransactions = 0;

    // Ensure we have an array (getSalesByDate should return [] if no sales found)
    if (is_array($salesOnDate)) {
        // The number of transactions is simply the count of sales records for that day.
        $numberOfTransactions = count($salesOnDate);

        // Iterate through each sale record found for the date
        foreach ($salesOnDate as $sale) {
            // Sum up the 'total_amount' from each sale record.
            // Include checks to handle potentially missing or non-numeric data gracefully.
            if (isset($sale['total_amount']) && is_numeric($sale['total_amount'])) {
                $totalSalesAmount += (float)$sale['total_amount'];
            } else {
                // Log a warning if a sale record seems corrupt or incomplete.
                $saleId = isset($sale['sale_id']) ? $sale['sale_id'] : 'N/A';
                error_log("generateDailySalesReportData Warning: Sale record (ID: {$saleId}) processed for date '{$dateString}' has missing or invalid 'total_amount'. Skipping amount for this record.");
            }
        }
    } else {
         // This block should ideally not be reached if getSalesByDate is robust.
         error_log("generateDailySalesReportData Error: Expected an array from getSalesByDate() for date '{$dateString}', but received non-array type. Treating as zero sales.");
         $numberOfTransactions = 0;
         $totalSalesAmount = 0.0;
         // Optionally return false here if this is considered a critical error.
         // return false;
    }

    // --- Prepare and Return Result Array ---
    // Structure the calculated summary data into an associative array.
    $reportData = [
        'date'              => $dateString,          // The date the report is for
        'total_sales'       => $totalSalesAmount,    // Total monetary value of sales
        'transaction_count' => $numberOfTransactions // Total number of sales transactions
    ];

    return $reportData; // Return the summary data
}

// --- Future Enhancements ---
// You could add more functions here as reporting needs grow:
// - generateDateRangeSalesReportData(string $startDate, string $endDate)
// - generateProductSalesReportData(string $productId, string $startDate, string $endDate)
// - generateCategorySalesReportData(string $category, string $startDate, string $endDate)
// ... etc.

?>
