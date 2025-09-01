<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get OTP from POST data
$input = json_decode(file_get_contents('php://input'), true);
$entered_otp = isset($input['otp']) ? $input['otp'] : '';

// Validate OTP format
if (empty($entered_otp) || !preg_match('/^[0-9]{4}$/', $entered_otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP format. Please enter 4 digits.']);
    exit;
}

// Check if OTP exists in session
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
    echo json_encode(['status' => 'error', 'message' => 'No OTP found. Please request a new OTP.']);
    exit;
}

// Check OTP expiry (5 minutes = 300 seconds)
$current_time = time();
$otp_time = $_SESSION['otp_time'];
$time_diff = $current_time - $otp_time;

if ($time_diff > 300) {
    // Clear expired OTP
    unset($_SESSION['otp']);
    unset($_SESSION['otp_time']);
    echo json_encode(['status' => 'error', 'message' => 'OTP expired. Please request a new OTP.']);
    exit;
}

// Verify OTP
$session_otp = $_SESSION['otp'];

if ($entered_otp != $session_otp) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please try again.']);
    exit;
}

// OTP verified successfully
$_SESSION['authenticated'] = true;
$_SESSION['user_phone'] = $_SESSION['phone'];

// Clear OTP data
unset($_SESSION['otp']);
unset($_SESSION['otp_time']);

// Success response
echo json_encode([
    'status' => 'success',
    'message' => 'OTP verified successfully',
    'phone' => $_SESSION['user_phone'],
    'redirect' => 'dashboard.php'
]);
?>
