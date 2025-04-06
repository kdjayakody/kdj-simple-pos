 
<?php
/**
 * Core Data Handling Functions
 *
 * Provides functions for safely reading and writing JSON data files
 * using file locking (flock) to prevent data corruption/race conditions.
 */

// Ensure config constants like DATA_PATH are available.
// This prevents errors if this file were ever included stand-alone,
// but typically it will be included after config.php.
if (!defined('DATA_PATH')) {
    // Adjust path if needed based on include context, assumes included from root or similar level
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Fallback if the structure is different than expected during include
        // This might indicate an issue elsewhere.
         if (file_exists('config.php')) {
             require_once 'config.php';
         } else {
            die("Error: Configuration file not found in data_handling.php.");
         }
    }
}


/**
 * Reads data from a JSON file with shared locking.
 * Allows multiple scripts to read the file simultaneously but blocks writing.
 *
 * @param string $filePath The full path to the JSON file.
 * @return array|false Returns the decoded PHP array on success,
 * an empty array [] if the file doesn't exist or is empty,
 * or false on critical errors (e.g., lock failure, read failure, JSON decode error).
 */
function readJsonFile(string $filePath): array|false
{
    // Check if the file exists first
    if (!file_exists($filePath)) {
        // Treat non-existent file as empty data source
        return [];
    }

    // Check if file is readable
    if (!is_readable($filePath)) {
        error_log("Error: File is not readable: " . $filePath);
        return false;
    }

    $fileHandle = @fopen($filePath, 'r'); // Use 'r' for reading
    if (!$fileHandle) {
        error_log("Error: Failed to open file for reading: " . $filePath);
        return false;
    }

    // Acquire a shared lock (LOCK_SH). Waits if an exclusive lock is held.
    if (!flock($fileHandle, LOCK_SH)) {
        error_log("Error: Could not acquire shared lock for reading file: " . $filePath);
        fclose($fileHandle);
        return false;
    }

    // Read the file content
    $fileSize = filesize($filePath);
    $jsonData = ($fileSize > 0) ? fread($fileHandle, $fileSize) : '';

    // Release the lock NOW, before potentially slow decoding
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);

    // Check if read was successful
    if ($jsonData === false) {
        error_log("Error: Failed to read file content: " . $filePath);
        return false;
    }

    // If the file was empty, return an empty array
    if (empty(trim($jsonData))) {
        return [];
    }

    // Decode the JSON data into a PHP associative array
    $data = json_decode($jsonData, true);

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error: JSON decode error '" . json_last_error_msg() . "' in file: " . $filePath);
        return false;
    }

    // Ensure we have an array, even if JSON was valid but not an object/array (e.g. "null")
     if (!is_array($data)) {
        error_log("Error: Decoded JSON is not an array in file: " . $filePath);
        return []; // Return empty array for consistency if JSON root isn't an array/object
     }


    return $data;
}

/**
 * Writes data to a JSON file with exclusive locking.
 * Blocks other readers and writers until the operation is complete.
 * Creates the file if it doesn't exist, truncates and overwrites if it does.
 *
 * @param string $filePath The full path to the JSON file.
 * @param array $data The PHP array data to encode and write.
 * @return bool True on success, false on any failure (open, lock, encode, write).
 */
function writeJsonFile(string $filePath, array $data): bool
{
    // Use 'w' for writing: creates file if not exists, truncates to zero length if exists.
    $fileHandle = @fopen($filePath, 'w');
    if (!$fileHandle) {
        error_log("Error: Failed to open file for writing: " . $filePath . " - Check permissions.");
        return false;
    }

    // Acquire an exclusive lock (LOCK_EX). Waits if any lock (shared or exclusive) is held.
    if (!flock($fileHandle, LOCK_EX)) {
        error_log("Error: Could not acquire exclusive lock for writing file: " . $filePath);
        fclose($fileHandle);
        return false;
    }

    // Encode the PHP array into a JSON string
    // JSON_PRETTY_PRINT makes the file human-readable (good for debugging)
    // JSON_UNESCAPED_SLASHES prevents escaping '/' characters
    // JSON_UNESCAPED_UNICODE ensures multi-byte characters (like names) are stored correctly
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Check for JSON encoding errors
    if ($jsonData === false) {
        error_log("Error: JSON encode error '" . json_last_error_msg() . "' for file: " . $filePath);
        // Release lock before returning
        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);
        return false;
    }

    // Write the JSON string to the file
    if (fwrite($fileHandle, $jsonData) === false) {
        error_log("Error: Failed to write data to file: " . $filePath);
        // Release lock before returning
        flock($fileHandle, LOCK_UN);
        fclose($fileHandle);
        return false;
    }

    // IMPORTANT: Ensure data is physically written to disk before releasing the lock
    fflush($fileHandle);

    // Release the exclusive lock
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);

    return true;
}

?>
