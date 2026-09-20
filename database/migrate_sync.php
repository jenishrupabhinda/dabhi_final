<?php
/**
 * migrate_sync.php
 * Synchronizes database configurations, tables, batches, products, and reviews in dabhi_final
 */
$pdo = new PDO('mysql:host=localhost;dbname=dabhi_final', 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "1. Activating all products...\n";
$pdo->query("UPDATE products SET is_active = 1");

echo "2. Fixing inventory_batches expiry dates...\n";
$pdo->query("UPDATE inventory_batches SET expiry_date = '2027-01-04' WHERE expiry_date = '0000-00-00' OR expiry_date IS NULL");

echo "3. Updating gst_settings...\n";
$gstCount = $pdo->query("SELECT COUNT(*) FROM gst_settings")->fetchColumn();
if ($gstCount == 0) {
    $pdo->query("INSERT INTO gst_settings (id, business_name, gstin, business_state, address, is_gst_enabled)
                 VALUES (1, 'Dabhi Chikki', '24AAJDUDHEJ5555', 'Gujarat', 'Rajkot, Gujarat, India', 1)");
} else {
    $pdo->query("UPDATE gst_settings SET business_name = 'Dabhi Chikki', gstin = '24AAJDUDHEJ5555', business_state = 'Gujarat', address = 'Rajkot, Gujarat, India', is_gst_enabled = 1 WHERE id = 1");
}

echo "4. Updating settings...\n";
$settingsToSync = [
    'gst_enabled'             => '1',
    'free_shipping_threshold' => '999',
    'free_shipping_above'     => '999',
    'default_shipping_charge' => '60',
    'cod_enabled'             => '1',
    'online_payment_enabled'  => '1',
    'low_stock_default_threshold' => '10',
    'expiry_alert_days'       => '30',
];
foreach ($settingsToSync as $k => $v) {
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$k, $v]);
}

echo "5. Seeding pincode_zones if empty...\n";
$pCount = $pdo->query("SELECT COUNT(*) FROM pincode_zones")->fetchColumn();
if ($pCount == 0) {
    $pdo->query("INSERT INTO pincode_zones (pincode, zone_id, is_serviceable, cod_available) VALUES
        ('360001', 1, 1, 1),
        ('360002', 1, 1, 1),
        ('360003', 1, 1, 1),
        ('360004', 1, 1, 1),
        ('360005', 1, 1, 1),
        ('360575', 1, 1, 1),
        ('380001', 1, 1, 1),
        ('380015', 1, 1, 1),
        ('390001', 1, 1, 1),
        ('395001', 1, 1, 1)");
}

echo "6. Checking users for reviews...\n";
$users = $pdo->query("SELECT id, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo "   Found " . count($users) . " users: \n";
foreach ($users as $u) {
    echo "   User #{$u['id']}: {$u['email']}\n";
}

$firstUserId = $users[0]['id'] ?? 1;

// Ensure we have at least 2 customer / sample user accounts
if (count($users) < 2) {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $pdo->query("INSERT IGNORE INTO users (uuid, role, full_name, email, phone, password_hash, is_active)
                 VALUES ('$uuid', 'buyer', 'Pooja Patel', 'pooja@example.com', '9898989898', '" . password_hash('Pass@123', PASSWORD_BCRYPT) . "', 1)");
    $users = $pdo->query("SELECT id, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
}
$uid1 = $users[0]['id'];
$uid2 = $users[1]['id'] ?? $uid1;

echo "7. Seeding authentic reviews if empty...\n";
$rCount = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
if ($rCount == 0) {
    $reviews = [
        // Mandvi Chikki (Product 1)
        [1, $uid1, 5, 'Best peanut chikki in Gujarat! The jaggery flavor is pure and authentic, not overly sweet. Very crunchy.'],
        [1, $uid2, 5, 'Ordered a 1kg box. Crisp snap, freshly roasted groundnuts. Reminds me of traditional winter taste!'],
        [1, $uid1, 4, 'Quality is top notch. Delivery was fast to Ahmedabad.'],
        
        // TIL Chikki (Product 2)
        [2, $uid2, 5, 'White sesame chikki is superb. Soft bite yet crunchy, pure desi jaggery aroma. High quality sesame.'],
        [2, $uid1, 5, 'Must-have for winters! Rich in calcium and so delicious.'],
        
        // Daliya Chikki (Product 3)
        [3, $uid1, 5, 'Very light, roasted gram crunch is delightful. Perfect healthy tea-time snack.'],
        [3, $uid2, 4, 'Super crispy and fresh. Kids loved it.'],
        
        // 3 Mix Chikki (Product 4)
        [4, $uid1, 5, 'The 3 Mix chikki is unbeatable! Groundnut, sesame, and coconut blend is pure royalty.'],
        [4, $uid2, 5, 'Phenomenal taste. The coconut crunch elevates the entire flavor profile. Best signature item!'],
        [4, $uid2, 5, 'Sent as gifts to relatives in Mumbai. Everyone loved the fresh aroma and packaging.'],
    ];

    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, is_approved, created_at)
                           VALUES (?, ?, ?, ?, 1, NOW() - INTERVAL FLOOR(RAND() * 15) DAY)");
    foreach ($reviews as $r) {
        $stmt->execute($r);
    }
}

echo "8. Verifying tables and data...\n";
$prodCheck = $pdo->query("SELECT id, name, is_active FROM products")->fetchAll(PDO::FETCH_ASSOC);
foreach ($prodCheck as $p) {
    echo "  Product {$p['id']} - {$p['name']}: Active = {$p['is_active']}\n";
}

$batchCheck = $pdo->query("SELECT COUNT(*) as c FROM inventory_batches WHERE expiry_date > '2026-01-01'")->fetch(PDO::FETCH_ASSOC);
echo "  Batches with valid expiry: {$batchCheck['c']}\n";

$revCheck = $pdo->query("SELECT COUNT(*) as c FROM reviews WHERE is_approved = 1")->fetch(PDO::FETCH_ASSOC);
echo "  Approved reviews: {$revCheck['c']}\n";

$pzCheck = $pdo->query("SELECT COUNT(*) as c FROM pincode_zones")->fetch(PDO::FETCH_ASSOC);
echo "  Pincodes configured: {$pzCheck['c']}\n";

echo "Database sync completed successfully!\n";
