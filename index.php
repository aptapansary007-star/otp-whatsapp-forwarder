<?php
header('Content-Type: application/json');

// Simple health check for backend
echo json_encode([
    'status' => 'ok',
    'message' => 'Ludo24 OTP Backend Service',
    'version' => '1.0.0',
    'endpoints' => [
        'POST /api/send-otp.php' => 'Generate and send OTP',
        'POST /api/verify-otp.php' => 'Verify OTP',
        'GET /health' => 'WhatsApp service health check'
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
