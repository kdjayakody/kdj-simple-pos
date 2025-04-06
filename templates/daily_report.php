 
<?php
/**
 * Template: Daily Sales Report
 *
 * Displays a summary of sales (total amount and transaction count) for a selected date.
 * Included by index.php when view=reports.
 * Assumes report_functions.php has already been included.
 */

// Ensure functions are available (defensive check)
if (!function_exists('generateDailySalesReportData')) {
    die("Error: Report functions not loaded.");
}

// --- Determine Date for Report ---
// Default to today's date based on server timezone (set in config.php)
$default_date = date('Y-m-d'); // Current date in YYYY-MM-DD format

// Check if a specific date was requested via GET parameter
$report_date = isset($_GET['report_date']) ? trim($_GET['report_date']) : $default_date;

// Validate the date format before using it (basic check)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)) {
     // If format is invalid, fall back to default date and maybe show a warning
     error_log("daily_report.php: Invalid report_date format received '{$report_date}'. Falling back to default.");
     $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Invalid date format requested. Showing report for {$default_date}."];
     $report_date = $default_date;
     // Redirect to clear the invalid parameter? Optional.
     // header("Location: index.php?view=reports"); exit();
}


// --- Generate Report Data ---
$reportData = generateDailySalesReportData($report_date); // Returns array or false

// --- Handle Flash Messages (e.g., for invalid date fallback) ---
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Clear the message after retrieving it
}

?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold mb-6 text-gray-800 border-b pb-2">Daily Sales Report</h1>

    <!-- Display Flash Message -->
    <?php if ($flash_message): ?>
    <div class="mb-4 p-4 rounded-md shadow <?php echo ($flash_message['type'] === 'success') ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>" role="alert">
        <span class="font-semibold"><?php echo ucfirst($flash_message['type']); ?>:</span>
        <?php echo htmlspecialchars($flash_message['text']); ?>
    </div>
    <?php endif; ?>


    <!-- Date Selection Form -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow border border-gray-200">
        <form action="index.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="view" value="reports"> <!-- Keep the view parameter -->
            <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-0">Select Report Date:</label>
            <input type="date" id="report_date" name="report_date"
                   value="<?php echo htmlspecialchars($report_date); ?>"
                   max="<?php echo date('Y-m-d'); ?>"
                   class="form-input border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 transition duration-150 ease-in-out"
                   onchange="this.form.submit()"> <!-- Auto-submit form when date changes -->
             <!-- Optional Manual Submit Button -->
             <!-- <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded shadow transition duration-150 ease-in-out ml-2">View Report</button> -->
        </form>
    </div>


    <!-- Report Display Area -->
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <?php if ($reportData === false): ?>
            <div class="p-4 rounded-md bg-red-100 border border-red-300 text-red-800" role="alert">
                <h2 class="text-xl font-semibold mb-2">Error Generating Report</h2>
                <p>Could not generate the report for <?php echo htmlspecialchars($report_date); ?>. There might have been an issue reading the sales data. Please check server logs or try again later.</p>
            </div>
        <?php elseif (is_array($reportData)): ?>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                Sales Summary for: <span class="text-blue-700"><?php echo date("F j, Y (l)", strtotime($reportData['date'])); // Format date nicely ?></span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg shadow-sm text-center">
                    <p class="text-sm font-medium text-blue-800 uppercase tracking-wider mb-1">Total Sales Amount</p>
                    <p class="text-3xl font-bold text-blue-900 font-mono">
                        Rs. <?php echo number_format($reportData['total_sales'], 2); ?>
                    </p>
                </div>
                <div class="bg-green-50 border border-green-200 p-4 rounded-lg shadow-sm text-center">
                     <p class="text-sm font-medium text-green-800 uppercase tracking-wider mb-1">Number of Transactions</p>
                     <p class="text-3xl font-bold text-green-900 font-mono">
                        <?php echo number_format($reportData['transaction_count']); ?>
                    </p>
                </div>
            </div>

            <?php if ($reportData['transaction_count'] === 0): ?>
                <p class="text-center text-gray-500 mt-6 italic">No sales transactions were recorded on this date.</p>
            <?php endif; ?>

             <!-- Optional: Add link/button to view detailed sales for this date? -->

        <?php else: ?>
             <!-- Should not happen if generate function is correct, but as fallback -->
             <div class="p-4 rounded-md bg-yellow-100 border border-yellow-300 text-yellow-800" role="alert">
                 <p>An unexpected issue occurred while preparing the report data.</p>
             </div>
        <?php endif; ?>
    </div><!-- /Report Display Area -->

</div> <!-- /.container -->

<?php
// No specific footer needed by this template, but index.php includes the main site footer.
?>