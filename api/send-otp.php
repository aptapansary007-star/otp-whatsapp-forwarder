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

// Get phone number from POST data
$input = json_decode(file_get_contents('php://input'), true);
$phone = isset($input['phone']) ? $input['phone'] : '';

// Validate phone number
if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number. Please enter 10 digits.']);
    exit;
}

// Generate 4-digit OTP
$otp = rand(1000, 9999);

// Store OTP in session with timestamp
$_SESSION['otp'] = $otp;
$_SESSION['phone'] = $phone;
$_SESSION['otp_time'] = time();

// Forward OTP to WhatsApp using Node.js service
$whatsapp_data = [
    'phone' => $phone,
    'otp' => $otp
];

// Call Node.js WhatsApp service
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/send-whatsapp');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($whatsapp_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$whatsapp_response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Check if WhatsApp forwarding was successful
if ($curl_error || !$whatsapp_response) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
    exit;
}

$whatsapp_result = json_decode($whatsapp_response, true);

if ($whatsapp_result['status'] !== 'success') {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP via WhatsApp.']);
    exit;
}

// Success response
echo json_encode([
    'status' => 'success',
    'message' => 'OTP sent successfully to your WhatsApp',
    'phone' => $phone
]);
?>
