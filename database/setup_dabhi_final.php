<?php
/**
 * setup_dabhi_final.php
 * Automated database installer and seeder for Dabhi_final.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbName = 'dabhi_final';

$mysqli = @new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    // Try empty password
    $mysqli = @new mysqli($host, $user, '');
    if ($mysqli->connect_error) {
        die("Connection error: " . $mysqli->connect_error . "\n");
    }
}
echo "Connected to MySQL successfully.\n";

// 1. Create database
$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($dbName);
echo "Database `{$dbName}` selected.\n";

// 2. Read schema.sql from Dabhi_final
$schemaPath = __DIR__ . '/schema.sql';
if (!file_exists($schemaPath)) {
    die("schema.sql not found at $schemaPath\n");
}

echo "Executing schema.sql...\n";
$schemaSql = file_get_contents($schemaPath);
// Disable foreign key checks for schema creation
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
$mysqli->multi_query($schemaSql);
while ($mysqli->next_result()) { /* flush results */ }
echo "schema.sql executed.\n";

// 3. Read seed.sql from Dabhi_final
$seedPath = __DIR__ . '/seed.sql';
if (file_exists($seedPath)) {
    echo "Executing seed.sql...\n";
    $seedSql = file_get_contents($seedPath);
    $mysqli->multi_query($seedSql);
    while ($mysqli->next_result()) { /* flush results */ }
    echo "seed.sql executed.\n";
}

// 4. Run compatibility alterations for storefront support
echo "Applying compatibility enhancements...\n";

// Check if extra columns exist in orders, if not add them
$orderColsRes = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'ship_name'");
if ($orderColsRes->num_rows === 0) {
    $mysqli->query("ALTER TABLE `orders`
        ADD COLUMN `guest_email` VARCHAR(190) NULL AFTER `user_id`,
        ADD COLUMN `ship_name` VARCHAR(150) NULL,
        ADD COLUMN `ship_phone` VARCHAR(20) NULL,
        ADD COLUMN `ship_line1` VARCHAR(255) NULL,
        ADD COLUMN `ship_line2` VARCHAR(255) NULL,
        ADD COLUMN `ship_city` VARCHAR(100) NULL,
        ADD COLUMN `ship_state` VARCHAR(100) NULL,
        ADD COLUMN `ship_pincode` VARCHAR(10) NULL,
        ADD COLUMN `total` DECIMAL(10,2) NULL,
        ADD COLUMN `gst_amount` DECIMAL(10,2) NULL,
        ADD COLUMN `notes` TEXT NULL,
        MODIFY COLUMN `shipping_address_id` INT UNSIGNED NULL,
        MODIFY COLUMN `billing_address_id` INT UNSIGNED NULL
    ");
}

// Add compatibility columns to order_items
$itemColsRes = $mysqli->query("SHOW COLUMNS FROM order_items LIKE 'product_name'");
if ($itemColsRes->num_rows === 0) {
    $mysqli->query("ALTER TABLE `order_items`
        ADD COLUMN `product_id` INT UNSIGNED NULL AFTER `order_id`,
        ADD COLUMN `product_name` VARCHAR(180) NULL,
        ADD COLUMN `variant_label` VARCHAR(60) NULL,
        ADD COLUMN `weight_grams` INT UNSIGNED NULL,
        ADD COLUMN `sku` VARCHAR(50) NULL,
        ADD COLUMN `mrp` DECIMAL(10,2) NULL,
        ADD COLUMN `selling_price` DECIMAL(10,2) NULL,
        ADD COLUMN `gst_rate` DECIMAL(4,2) NULL
    ");
}

// Create user_addresses compatibility table
$mysqli->query("CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(50) DEFAULT 'Home',
  `full_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `line1` VARCHAR(255) NOT NULL,
  `line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `pincode` VARCHAR(10) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_ua_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create coupon_uses compatibility table
$mysqli->query("CREATE TABLE IF NOT EXISTS `coupon_uses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `coupon_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `fk_cu_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 5. Seed Superadmin Accounts
$adminPassHash = password_hash('admin123', PASSWORD_BCRYPT);
$jenishPassHash = password_hash('root', PASSWORD_BCRYPT); // or password123

$mysqli->query("INSERT IGNORE INTO `users` (`id`, `uuid`, `role`, `full_name`, `email`, `phone`, `password_hash`, `is_active`)
VALUES 
  (1, '11111111-1111-1111-1111-111111111111', 'superadmin', 'Admin', 'admin@dabhichikki.com', '9999999999', '{$adminPassHash}', 1),
  (2, '22222222-2222-2222-2222-222222222222', 'superadmin', 'Jenish', 'jenish@gmail.com', '9876543210', '{$adminPassHash}', 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;");

// 6. Seed Default Category: Chikki
$mysqli->query("INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`)
VALUES (1, 'Chikki', 'chikki', 'Traditional Indian chikki made with pure jaggery and dry fruits.', 1, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);");

// 7. Seed the 4 Chikki Products from DabhiNew
echo "Seeding 4 storefront Chikki products and variants...\n";

$products = [
    [
        'id' => 1,
        'name' => 'Mandvi Chikki',
        'slug' => 'mandvi-chikki',
        'short_desc' => 'Classic groundnut chikki made with pure jaggery — crunchy, sweet, and irresistible.',
        'desc' => "Our Mandvi Chikki is crafted from the finest groundnuts and pure sugarcane jaggery. No added sugar, no preservatives. Every bite is a crisp, golden celebration of tradition. Perfect as an everyday snack or gifting option.",
        'variants' => [
            ['sku' => 'MAN-250', 'weight' => 250, 'mrp' => 150.00, 'price' => 120.00],
            ['sku' => 'MAN-500', 'weight' => 500, 'mrp' => 250.00, 'price' => 200.00],
            ['sku' => 'MAN-1KG', 'weight' => 1000, 'mrp' => 460.00, 'price' => 380.00],
        ],
        'images' => [
            ['path' => 'assets/images/products/mandvi-chikki-1.jpg', 'primary' => 1],
            ['path' => 'assets/images/products/mandvi-chikki-2.jpg', 'primary' => 0],
        ]
    ],
    [
        'id' => 2,
        'name' => 'TIL Chikki',
        'slug' => 'til-chikki',
        'short_desc' => 'Sesame seed chikki with pure jaggery — nutty, fragrant, and wholesome.',
        'desc' => "Our TIL Chikki combines toasted white sesame seeds with rich jaggery syrup, pressed into perfectly crisp bars. Rich in calcium and iron, this traditional snack is as nutritious as it is delicious.",
        'variants' => [
            ['sku' => 'TIL-250', 'weight' => 250, 'mrp' => 150.00, 'price' => 120.00],
            ['sku' => 'TIL-500', 'weight' => 500, 'mrp' => 250.00, 'price' => 200.00],
            ['sku' => 'TIL-1KG', 'weight' => 1000, 'mrp' => 460.00, 'price' => 380.00],
        ],
        'images' => [
            ['path' => 'assets/images/products/til-chikki-1.jpg', 'primary' => 1],
            ['path' => 'assets/images/products/til-chikki-2.jpg', 'primary' => 0],
        ]
    ],
    [
        'id' => 3,
        'name' => 'Daliya Chikki',
        'slug' => 'daliya-chikki',
        'short_desc' => 'Broken wheat chikki with jaggery — hearty, wholesome, and uniquely satisfying.',
        'desc' => "Daliya Chikki is a unique take on the classic chikki, made with roasted broken wheat (daliya) and pure jaggery. High in fibre and energy, it is the perfect guilt-free snack to power through your day.",
        'variants' => [
            ['sku' => 'DAL-250', 'weight' => 250, 'mrp' => 150.00, 'price' => 120.00],
            ['sku' => 'DAL-500', 'weight' => 500, 'mrp' => 250.00, 'price' => 200.00],
            ['sku' => 'DAL-1KG', 'weight' => 1000, 'mrp' => 460.00, 'price' => 380.00],
        ],
        'images' => [
            ['path' => 'assets/images/products/daliya-chikki-1.jpg', 'primary' => 1],
            ['path' => 'assets/images/products/daliya-chikki-2.jpg', 'primary' => 0],
        ]
    ],
    [
        'id' => 4,
        'name' => '3 Mix Chikki',
        'slug' => '3-mix-chikki',
        'short_desc' => 'A delightful blend of Mandvi, Til, and Coconut Crush — three flavours in every bar.',
        'desc' => "Our signature 3 Mix Chikki brings together the goodness of groundnuts, sesame seeds, and coconut crush in one perfectly balanced bar. A crowd favourite and the ideal introduction to Dabhi Chikki. Great for gifting!",
        'variants' => [
            ['sku' => 'MIX-250', 'weight' => 250, 'mrp' => 180.00, 'price' => 150.00],
            ['sku' => 'MIX-500', 'weight' => 500, 'mrp' => 300.00, 'price' => 250.00],
            ['sku' => 'MIX-1KG', 'weight' => 1000, 'mrp' => 540.00, 'price' => 450.00],
        ],
        'images' => [
            ['path' => 'assets/images/products/3-mix-chikki-1.jpg', 'primary' => 1],
            ['path' => 'assets/images/products/3-mix-chikki-2.jpg', 'primary' => 0],
        ]
    ]
];

foreach ($products as $p) {
    // Insert product
    $stmt = $mysqli->prepare("INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `hsn_code`, `gst_rate_percent`, `is_active`, `is_featured`)
        VALUES (?, 1, ?, ?, ?, ?, '1704', 5.00, 1, 1)
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `slug`=VALUES(`slug`), `short_description`=VALUES(`short_description`), `description`=VALUES(`description`), `is_active`=1");
    $stmt->bind_param('issss', $p['id'], $p['name'], $p['slug'], $p['short_desc'], $p['desc']);
    $stmt->execute();

    // Insert variants
    foreach ($p['variants'] as $v) {
        $stmtV = $mysqli->prepare("INSERT INTO `product_variants` (`product_id`, `sku`, `weight_grams`, `mrp`, `selling_price`, `reorder_level`, `is_active`)
            VALUES (?, ?, ?, ?, ?, 10, 1)
            ON DUPLICATE KEY UPDATE `mrp`=VALUES(`mrp`), `selling_price`=VALUES(`selling_price`), `is_active`=1");
        $stmtV->bind_param('isidd', $p['id'], $v['sku'], $v['weight'], $v['mrp'], $v['price']);
        $stmtV->execute();

        // Get variant ID
        $varRes = $mysqli->query("SELECT id FROM product_variants WHERE sku = '{$v['sku']}' LIMIT 1");
        $varRow = $varRes->fetch_assoc();
        $varId = $varRow['id'] ?? null;

        if ($varId) {
            // Seed inventory batch if none exists
            $batchNum = 'BATCH-' . $v['sku'];
            $mysqli->query("INSERT IGNORE INTO `inventory_batches` (`variant_id`, `batch_number`, `quantity_received`, `quantity_remaining`, `received_at`, `notes`)
                VALUES ({$varId}, '{$batchNum}', 100, 100, NOW(), 'Initial inventory')");
        }
    }

    // Insert images
    $order = 1;
    foreach ($p['images'] as $img) {
        $mysqli->query("INSERT INTO `product_images` (`product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`)
            VALUES ({$p['id']}, '{$img['path']}', '{$p['name']}', {$img['primary']}, {$order})
            ON DUPLICATE KEY UPDATE `image_path`=VALUES(`image_path`), `is_primary`=VALUES(`is_primary`)");
        $order++;
    }
}

// 8. Seed Default Settings
$settings = [
    'app_name'                => 'Dabhi Chikki',
    'app_tagline'             => 'Pure Taste Since 2009',
    'currency'                => 'INR',
    'currency_symbol'         => '₹',
    'free_shipping_above'     => '500',
    'default_shipping_charge' => '60',
    'cod_enabled'             => '1',
    'cashfree_mode'           => 'test',
    'contact_phone'           => '+91 98765 43210',
    'contact_email'           => 'orders@dabhichikki.com',
];

foreach ($settings as $k => $v) {
    $mysqli->query("INSERT INTO `settings` (`setting_key`, `setting_value`)
        VALUES ('{$k}', '{$v}')
        ON DUPLICATE KEY UPDATE `setting_value` = '{$v}'");
}

// 9. Shipping zone
$mysqli->query("INSERT IGNORE INTO `shipping_zones` (`id`, `name`, `description`) VALUES (1, 'All India', 'Standard pan-India delivery');");

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n============================================\n";
echo " Dabhi_final Database Setup Completed!\n";
echo " Database: {$dbName}\n";
echo " Admin: admin@dabhichikki.com (pass: admin123)\n";
echo " Superadmin: jenish@gmail.com (pass: admin123)\n";
echo " Products seeded: 4 (with 250g, 500g, 1kg variants)\n";
echo "============================================\n";
