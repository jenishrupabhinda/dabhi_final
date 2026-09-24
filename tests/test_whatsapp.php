<?php
require_once __DIR__ . '/../includes/bootstrap.php';

echo "=== 1. Testing WhatsAppSender::getConfig() ===\n";
$cfg = WhatsAppSender::getConfig();
echo "Mode: " . $cfg['mode'] . "\n";
echo "Enabled: " . ($cfg['enabled'] ? 'Yes' : 'No') . "\n";
echo "Force Recipient: " . ($cfg['test_force_recipient'] ? 'Yes' : 'No') . "\n";

echo "\n=== 2. Testing WhatsAppSender::sanitizePhone() ===\n";
$tests = [
    '9876543210' => '919876543210',
    '+91 98765 43210' => '919876543210',
    '09876543210' => '919876543210',
    '+1 (555) 123-4567' => '15551234567',
    '919876543210' => '919876543210',
];
foreach ($tests as $input => $expected) {
    $out = WhatsAppSender::sanitizePhone($input);
    if ($out !== $expected) {
        echo "FAILED for $input: got $out, expected $expected\n";
        exit(1);
    }
}
echo "All phone sanitizer tests passed!\n";

echo "\n=== 3. Testing WhatsAppSender::buildEventMessage() ===\n";
$sampleOrder = [
    'id' => 999,
    'order_number' => 'ORD-TEST-999',
    'full_name' => 'Rajesh Sharma',
    'total_amount' => 850.50,
];
$msg = WhatsAppSender::buildEventMessage('order_placed', $sampleOrder);
echo "Sample Message Output:\n" . $msg . "\n";

echo "\n=== 4. Testing Diagnostics on Missing Token ===\n";
$res = WhatsAppSender::testConnection('919876543210', 'hello_world', null, [
    'phone_number_id' => '1029384756',
    'api_token' => '',
]);
echo "Missing token test result: ok=" . ($res['ok'] ? 'true' : 'false') . ", error=" . $res['error'] . "\n";
echo "Help: " . ($res['help'] ?? 'None') . "\n";

echo "\n=== 5. Testing Mock API Call & Error Troubleshooting ===\n";
$resInvalid = WhatsAppSender::testConnection('919876543210', 'hello_world', null, [
    'phone_number_id' => '1029384756',
    'api_token' => 'INVALID_DUMMY_TOKEN',
]);
echo "Invalid token test result: ok=" . ($resInvalid['ok'] ? 'true' : 'false') . "\n";
echo "HTTP Code: " . ($resInvalid['http_code'] ?? 0) . "\n";
echo "Meta Error: " . ($resInvalid['error'] ?? '') . "\n";
echo "Help Tip: " . ($resInvalid['help'] ?? 'None') . "\n";

echo "\n=== 6. Checking notifications_log ===\n";
$recent = Database::fetchOne('SELECT * FROM notifications_log WHERE channel = "whatsapp" ORDER BY id DESC LIMIT 1');
if ($recent) {
    echo "Last Log ID: " . $recent['id'] . "\n";
    echo "Event: " . $recent['event_type'] . "\n";
    echo "Recipient: " . $recent['recipient'] . "\n";
    echo "Status: " . $recent['status'] . "\n";
    echo "Response: " . $recent['response_message'] . "\n";
} else {
    echo "No log found.\n";
}

echo "\nALL TESTS COMPLETED SUCCESSFULLY!\n";
