 
<?php
// Ensure config is loaded - needed for STORE_NAME and potentially BASE_URL
// This check is defensive; header.php should always be included *after* config.php by the main controller (index.php)
if (!defined('STORE_NAME')) {
    // Construct the path relative to the templates directory
    $configPath = __DIR__ . '/../config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Fallback attempt if the script is included from an unexpected context
        if (file_exists('config.php')) {
             require_once 'config.php';
        } else {
             // If config cannot be found, stop execution as it's essential.
             die("FATAL ERROR: Configuration file 'config.php' not found or loaded before header template.");
        }
    }
}

// Define base URL (you might want to move this to config.php if needed more widely)
// Handles running in subdirectories correctly for links/forms. Auto-detects http/https.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Calculates the path from the document root to the directory containing index.php
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
// Ensure it ends with a slash if it's not the root
$basePath = rtrim($scriptPath, '/\\') . '/';
define('BASE_URL', $protocol . $host . $basePath);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(STORE_NAME); ?> - Simple POS</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* You can add minimal global styles or overrides here if needed */
        /* Example: Ensure buttons have a consistent minimum width */
        /* .btn { min-width: 80px; } */
        /* Fix table layout for product/sales lists */
        .table-fixed-layout {
            table-layout: fixed;
            width: 100%;
        }
        /* Style for loading indicators or specific components */
        [x-cloak] { display: none !important; } /* Hide Alpine elements until initialized */
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">

    <header class="bg-blue-600 text-white shadow-lg sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="text-2xl font-bold">
                <a href="<?php echo BASE_URL; ?>index.php?view=sale"><?php echo htmlspecialchars(STORE_NAME); ?></a>
            </div>
            <ul class="flex space-x-3 md:space-x-4">
                <li><a href="<?php echo BASE_URL; ?>index.php?view=sale" class="text-sm md:text-base px-3 py-2 rounded hover:bg-blue-500 transition duration-150 ease-in-out">New Sale</a></li>
                <li><a href="<?php echo BASE_URL; ?>index.php?view=products" class="text-sm md:text-base px-3 py-2 rounded hover:bg-blue-500 transition duration-150 ease-in-out">Products</a></li>
                <li><a href="<?php echo BASE_URL; ?>index.php?view=reports" class="text-sm md:text-base px-3 py-2 rounded hover:bg-blue-500 transition duration-150 ease-in-out">Reports</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-6">