<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING);

// Allow all origins (for testing; restrict in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, apiKey, userOsBrowser, userIpAddress");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // stop further execution
}

// --- rest of your code ---
$appName = "Afootech Global Training Program";
$appVersion = "1.0.0";
$_HOST_NAME = "23.106.46.178";
$_DB_USERNAME = "afootechglobal_admin";
$_DB_PASSWORD = "18GK$2gHt@?w";

$conn = mysqli_connect($_HOST_NAME, $_DB_USERNAME, $_DB_PASSWORD) or die("Unable to connect to MySQL");
mysqli_select_db($conn, "afootechglobal_training_db") or die("Could not select database");
mysqli_set_charset($conn, "utf8mb4");

/////////////////////////////////////////////////////////////////
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once 'crud.php';
require_once 'errorHandlers.php';
require_once 'helper.php';
require_once 'functions.php';
require_once 'constants.php';