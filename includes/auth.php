<?php
/**
 * Authentication functions for Archery Score Recording System
 */

require_once 'settings.php';

/**
 * Verify recorder login credentials
 */
function verifyRecorderLogin($username, $password) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Get recorder with provided username
    $stmt = $conn->prepare("SELECT RecorderID, Username, Password, FirstName, LastName 
                          FROM RecorderTable 
                          WHERE Username = ? AND IsActive = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return false;
    }
    
    $recorder = $result->fetch_assoc();
    
    // Verify password using MD5 hash comparison
    if (md5($password) === $recorder['Password']) {
        // Set session variables
        $_SESSION['recorder_id'] = $recorder['RecorderID'];
        $_SESSION['recorder_name'] = $recorder['FirstName'] . ' ' . $recorder['LastName'];
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    return false;
}

/**
 * Logout recorder
 */
function logoutRecorder() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ' . BASE_URL . 'recorder/login.php');
    exit;
}

/**
 * Check session timeout
 */
function checkSessionTimeout($timeout = 1800) { // Default timeout: 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session has expired
        logoutRecorder();
    } else {
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Create new recorder account
 */
function createRecorder($username, $password, $firstName, $lastName, $email) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT RecorderID FROM RecorderTable WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return 'Username already exists';
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT RecorderID FROM RecorderTable WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return 'Email already exists';
    }
    
    // Hash password with MD5
    $hashedPassword = md5($password);
    
    // Insert new recorder
    $stmt = $conn->prepare("INSERT INTO RecorderTable (Username, Password, FirstName, LastName, Email, IsActive) 
                          VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssss", $username, $hashedPassword, $firstName, $lastName, $email);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return 'Database error: ' . $conn->error;
    }
}

/**
 * Update recorder password
 */
function updateRecorderPassword($recorderId, $currentPassword, $newPassword) {
    $conn = getDbConnection();
    if (!$conn) {
        return 'Database connection error';
    }
    
    // Get recorder data
    $stmt = $conn->prepare("SELECT Password FROM RecorderTable WHERE RecorderID = ?");
    $stmt->bind_param("i", $recorderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return 'Recorder not found';
    }
    
    $recorder = $result->fetch_assoc();
    
    // Verify current password with MD5
    if (md5($currentPassword) !== $recorder['Password']) {
        return 'Current password is incorrect';
    }
    
    // Hash new password with MD5
    $hashedPassword = md5($newPassword);
    
    // Update password
    $stmt = $conn->prepare("UPDATE RecorderTable SET Password = ? WHERE RecorderID = ?");
    $stmt->bind_param("si", $hashedPassword, $recorderId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return 'Database error: ' . $conn->error;
    }
}