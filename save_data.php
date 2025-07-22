<?php

// Log the raw input for debugging (optional, remove in production)
error_log("Raw PHP input: " . file_get_contents('php://input'));

// Decode the JSON data sent from the JavaScript frontend
// 'true' makes it an associative array instead of an object
$data_array = json_decode(file_get_contents('php://input'), true);

// Check if decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    // Log the JSON decoding error for debugging
    error_log("JSON decoding error: " . json_last_error_msg());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON received']);
    exit; // Stop execution if JSON is invalid
}

// --- Debugging Outputs (for testing, remove in production) ---

// Get the keys of the top-level array
$keys = array_keys($data_array);
// Use var_export or print_r without echo for better readability in logs/output
// echo print_r($keys); // This echoes the return value of print_r (which is 1), not the array content.
error_log("Keys from data_array: " . var_export($keys, true)); // Logs to server error log
echo "<pre>"; // Helps format print_r output in browser if you're viewing directly
print_r($keys);
echo "</pre>";

// Access the subid
if (isset($data_array["subid"])) {
    $subid = $data_array["subid"];
    error_log("Subid: " . $subid); // Logs to server error log
    echo "Subid: " . $subid . "<br>"; // Echoes to browser
} else {
    error_log("Subid not found in data_array.");
    echo "Error: Subid not found.<br>";
}

// The payload is already the $data_array
$payload = $data_array;
// echo $payload; // This will output "Array" because you can't echo an array directly as a string.
error_log("Full payload: " . json_encode($payload)); // Encode to JSON for logging
echo "<pre>Full Payload (JSON Encoded): "; // Display to browser
echo json_encode($payload, JSON_PRETTY_PRINT); // Prettier output for browser
echo "</pre>";

// --- Saving Data ---

if ($data_array != null) { // This check is generally fine
    // Define the base directory for data storage
    $base_data_dir = "data/"; // Ensure this directory exists and is writable
    $cloud_data_dir = "/home/cloud/screening_data/"; // Ensure this directory exists and is writable

    // Create the directories if they don't exist (important!)
    if (!is_dir($base_data_dir)) {
        mkdir($base_data_dir, 0755, true); // Recursive create
    }
    if (!is_dir($cloud_data_dir)) {
        mkdir($cloud_data_dir, 0755, true); // Recursive create
    }


    // Validate subid for file naming
    // IMPORTANT: Sanitize $subid to prevent directory traversal or invalid filenames
    // This is a basic example; for production, use a more robust sanitization
    $sanitized_subid = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subid); // Only allow alphanumeric, underscore, hyphen

    if (empty($sanitized_subid)) {
        error_log("Invalid subid for file naming: " . $subid);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid participant ID provided.']);
        exit;
    }

    $filename = $sanitized_subid . ".json";
    $json_to_save = json_encode($payload, JSON_PRETTY_PRINT); // Pretty print for human readability in files

    // Save to the first location
    $file_path_1 = $base_data_dir . $filename;
    if (file_put_contents($file_path_1, $json_to_save) === false) {
        error_log("Error saving data to " . $file_path_1);
        // You might want to send an error response back to the client here
    } else {
        error_log("Data successfully saved to " . $file_path_1);
    }

    // Save to the second location (e.g., cloud directory)
    $file_path_2 = $cloud_data_dir . $filename;
    if (file_put_contents($file_path_2, $json_to_save) === false) {
        error_log("Error saving data to " . $file_path_2);
        // You might want to send an error response back to the client here
    } else {
        error_log("Data successfully saved to " . $file_path_2);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data saved successfully.']);

} else {
    // Handle case where $data_array is null (e.g., empty POST or invalid JSON)
    error_log("Data array is null. No data received or invalid JSON.");
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No data received or invalid JSON.']);
}

?>