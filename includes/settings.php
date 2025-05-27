<?php
/**
 * Database Configuration
 */
$host = "feenix-mariadb.swin.edu.au";
$user = "s104700948";
$pwd = "210705";
$sql_db = "s104700948_db";

/**
 * Application Settings
 */
define('APP_NAME', 'Archery Score Recording System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/cos20031/s104700948/archery_projectdemo/');

/**
 * Error Reporting
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Session Configuration
 */
session_start();
session_regenerate_id(true);

/**
 * Database Connection
 */
function getDbConnection() {
    global $host, $user, $pwd, $sql_db;
    
    try {
        $conn = new mysqli($host, $user, $pwd, $sql_db);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

/**
 * Security Functions
 */

/**
 * Sanitize input to prevent XSS
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verify that the user is logged in as a recorder
 */
function isRecorderLoggedIn() {
    return isset($_SESSION['recorder_id']) && !empty($_SESSION['recorder_id']);
}

/**
 * Redirect to login page if not logged in as recorder
 */
function requireRecorderLogin() {
    if (!isRecorderLoggedIn()) {
        header('Location: ' . BASE_URL . 'recorder/login.php');
        exit;
    }
}

/**
 * Get current recorder data
 */
function getCurrentRecorder() {
    if (!isRecorderLoggedIn()) {
        return null;
    }
    
    $conn = getDbConnection();
    if (!$conn) {
        return null;
    }
    
    $recorder_id = $_SESSION['recorder_id'];
    $stmt = $conn->prepare("SELECT RecorderID, Username, FirstName, LastName, Email FROM RecorderTable WHERE RecorderID = ? AND IsActive = 1");
    $stmt->bind_param("i", $recorder_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        // Invalid recorder ID in session, logout
        session_unset();
        session_destroy();
        return null;
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd/m/Y') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}