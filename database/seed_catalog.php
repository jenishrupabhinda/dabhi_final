<?php
/**
 * seed_catalog.php — Populates categories with images and seeds starter products,
 * variants, inventory batches, and sample reviews.
 *
 * Run from CLI:
 *   C:\wamp64\bin\php\php8.3.14\php.exe database/seed_catalog.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

require_once __DIR__ . '/../includes/bootstrap.php';

echo "Updating category images...\n";

$catImages = [
    'chikki'  => '/assets/images/categories/chikki.jpg',
    'sweets'  => '/assets/images/categories/sweets.jpg',
    'namkeen' => '/assets/images/categories/namkeen.jpg',
    'combos'  => '/assets/images/categories/combos.jpg',
    'gifts'   => '/assets/images/categories/gifts.jpg',
];

foreach ($catImages as $slug => $imgPath) {
    Database::query("UPDATE categories SET image_path = ? WHERE slug = ?", [$imgPath, $slug]);
}
echo "Category images updated successfully.\n";

// Clear existing products if any (or check if already seeded)
$existingCount = (int)Database::fetchOne("SELECT COUNT(*) AS c FROM products")['c'];
if ($existingCount > 0) {
    echo "Found {$existingCount} existing products. Clearing old demo products and inventory...\n";
    Database::query("DELETE FROM reviews");
    Database::query("DELETE FROM inventory_batches");
    Database::query("DELETE FROM product_images");
    Database::query("DELETE FROM product_variants");
    Database::query("DELETE FROM products");
}

// Find category IDs
$chikkiCat  = Database::fetchOne("SELECT id FROM categories WHERE slug = 'chikki'")['id'] ?? 1;
$sweetsCat  = Database::fetchOne("SELECT id FROM categories WHERE slug = 'sweets'")['id'] ?? 2;
$namkeenCat = Database::fetchOne("SELECT id FROM categories WHERE slug = 'namkeen'")['id'] ?? 3;
$combosCat  = Database::fetchOne("SELECT id FROM categories WHERE slug = 'combos'")['id'] ?? 4;
$giftsCat   = Database::fetchOne("SELECT id FROM categories WHERE slug = 'gifts'")['id'] ?? 5;

// Find or create a user ID for created_by and reviews
$sampleUser = Database::fetchOne("SELECT id FROM users LIMIT 1");
if (!$sampleUser) {
    Database::query(
        "INSERT INTO users (full_name, email, phone, password_hash, role, is_active) 
         VALUES ('Store Admin', 'admin@dabhichikki.com', '9876543210', ?, 'superadmin', 1)",
        [password_hash('Admin@1234', PASSWORD_BCRYPT)]
    );
    $userId = (int)Database::lastInsertId();
} else {
    $userId = (int)$sampleUser['id'];
}

$productsData = [
    [
        'category_id' => $chikkiCat,
        'name' => 'Classic Peanut (Sing) Gud Chikki',
        'slug' => 'classic-peanut-sing-gud-chikki',
        'short_description' => 'Crispy golden brittle handcrafted with roasted Saurashtra peanuts and organic desi jaggery.',
        'description' => 'Our signature recipe passed down through generations. Made using slow-roasted premium Saurashtra groundnuts and purest organic cane jaggery. Absolutely no refined sugar, no glucose syrup, and zero preservatives. Crunchy, healthy, and packed with natural energy.',
        'hsn_code' => '17049090',
        'is_featured' => 1,
        'image' => '/assets/images/products/sing-chikki.jpg',
        'variants' => [
            ['sku' => 'CHK-SING-250',  'weight' => 250,  'mrp' => 120.00, 'price' => 99.00,  'stock' => 80],
            ['sku' => 'CHK-SING-500',  'weight' => 500,  'mrp' => 230.00, 'price' => 189.00, 'stock' => 60],
            ['sku' => 'CHK-SING-1000', 'weight' => 1000, 'mrp' => 440.00, 'price' => 360.00, 'stock' => 40],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'The best sing chikki I have had! Super crispy and authentic jaggery flavor.'],
            ['rating' => 5, 'comment' => 'Reminds me of home in Rajkot. Fresh crunch, no stale oil smell. 10/10.']
        ]
    ],
    [
        'category_id' => $chikkiCat,
        'name' => 'Special White Til Ladoo & Chikki',
        'slug' => 'special-white-til-ladoo-chikki',
        'short_description' => 'Traditional roasted white sesame seeds infused with cardamom and natural desi jaggery.',
        'description' => 'A winter and Makar Sankranti delicacy. Premium aromatic white til seeds are gentle-roasted to perfection and bound together with golden molten jaggery and freshly ground green cardamom. Rich in calcium and iron.',
        'hsn_code' => '17049090',
        'is_featured' => 1,
        'image' => '/assets/images/products/til-ladoo.jpg',
        'variants' => [
            ['sku' => 'CHK-TIL-250', 'weight' => 250, 'mrp' => 140.00, 'price' => 119.00, 'stock' => 70],
            ['sku' => 'CHK-TIL-500', 'weight' => 500, 'mrp' => 270.00, 'price' => 229.00, 'stock' => 50],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Super soft yet firm texture. Cardamom aroma is intoxicating.'],
            ['rating' => 4, 'comment' => 'Very fresh and pure ingredients. My grandparents loved it!']
        ]
    ],
    [
        'category_id' => $chikkiCat,
        'name' => 'Royal Dryfruit Kaju Badam Chikki',
        'slug' => 'royal-dryfruit-kaju-badam-chikki',
        'short_description' => 'Gourmet brittle packed with whole cashews, Californian almonds, and pistachios in caramel glaze.',
        'description' => 'Pure luxury in every crunch. Whole Goan cashews, premium almonds, and Iranian pistachios glazed in a whisper-thin golden caramel matrix. Perfect healthy gift for festive occasions and celebrations.',
        'hsn_code' => '17049090',
        'is_featured' => 1,
        'image' => '/assets/images/products/dryfruit-chikki.jpg',
        'variants' => [
            ['sku' => 'CHK-DF-250', 'weight' => 250, 'mrp' => 350.00, 'price' => 299.00, 'stock' => 45],
            ['sku' => 'CHK-DF-500', 'weight' => 500, 'mrp' => 680.00, 'price' => 579.00, 'stock' => 30],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Loaded with premium dry fruits, not just peanuts disguised as dry fruits. Premium quality.']
        ]
    ],
    [
        'category_id' => $sweetsCat,
        'name' => 'Authentic Rajkot Peda',
        'slug' => 'authentic-rajkot-peda',
        'short_description' => 'Famous Saurashtra soft roasted mawa peda garnished with saffron strands and sliced pistachios.',
        'description' => 'Prepared strictly using pure full-cream cow milk mawa, slow-cooked in traditional iron kadhais until golden brown, then perfumed with Kashmiri saffron and cardamom. Soft, velvety, and richly satisfying.',
        'hsn_code' => '21069099',
        'is_featured' => 1,
        'image' => '/assets/images/products/rajkot-peda.jpg',
        'variants' => [
            ['sku' => 'SWT-PEDA-250', 'weight' => 250, 'mrp' => 200.00, 'price' => 175.00, 'stock' => 50],
            ['sku' => 'SWT-PEDA-500', 'weight' => 500, 'mrp' => 390.00, 'price' => 340.00, 'stock' => 35],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Genuine taste of Rajkot peda! Melts in your mouth without being overly sweet.']
        ]
    ],
    [
        'category_id' => $sweetsCat,
        'name' => 'Kaju Katli (Pure Cashew Fudge)',
        'slug' => 'kaju-katli-pure-cashew-fudge',
        'short_description' => 'Diamond-cut melt-in-mouth cashew sweet adorned with genuine silver vark.',
        'description' => 'Handmade with 100% top-grade cashew nuts and sugar syrup. No artificial essence or flour filler. Smooth, delicate texture that melts instantly on the palate.',
        'hsn_code' => '21069099',
        'is_featured' => 1,
        'image' => '/assets/images/products/kaju-katli.jpg',
        'variants' => [
            ['sku' => 'SWT-KK-250', 'weight' => 250, 'mrp' => 300.00, 'price' => 260.00, 'stock' => 40],
            ['sku' => 'SWT-KK-500', 'weight' => 500, 'mrp' => 580.00, 'price' => 499.00, 'stock' => 30],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Exceptional quality. Soft, rich cashew flavor. Packaging was very secure.']
        ]
    ],
    [
        'category_id' => $namkeenCat,
        'name' => 'Crunchy Bhavnagari Gathiya',
        'slug' => 'crunchy-bhavnagari-gathiya',
        'short_description' => 'Light, airy chickpea flour snack seasoned with carom seeds (ajwain) and black pepper.',
        'description' => 'The crown jewel of Gujarati morning breakfast and afternoon tea. Made from pure gram flour, cold-pressed groundnut oil, freshly cracked black pepper, and hing. Crisp on the outside, feather-soft inside.',
        'hsn_code' => '21069099',
        'is_featured' => 1,
        'image' => '/assets/images/products/bhavnagari-gathiya.jpg',
        'variants' => [
            ['sku' => 'NMK-GATH-250', 'weight' => 250, 'mrp' => 110.00, 'price' => 89.00,  'stock' => 90],
            ['sku' => 'NMK-GATH-500', 'weight' => 500, 'mrp' => 210.00, 'price' => 169.00, 'stock' => 60],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Crisp and not oily at all. Pair it with fried green chillies for the ultimate snack.']
        ]
    ],
    [
        'category_id' => $namkeenCat,
        'name' => 'Traditional Ratlami Sev',
        'slug' => 'traditional-ratlami-sev',
        'short_description' => 'Spicy, zesty gram-flour sev seasoned with aromatic cloves, pepper, and secret spices.',
        'description' => 'For those who crave bold flavours! Crispy golden sev infused with spicy clove warmth, black pepper, and carom. Makes a phenomenal topping for poha, chaat, and daily snacking.',
        'hsn_code' => '21069099',
        'is_featured' => 1,
        'image' => '/assets/images/products/ratlami-sev.jpg',
        'variants' => [
            ['sku' => 'NMK-SEV-250', 'weight' => 250, 'mrp' => 110.00, 'price' => 89.00,  'stock' => 85],
            ['sku' => 'NMK-SEV-500', 'weight' => 500, 'mrp' => 210.00, 'price' => 169.00, 'stock' => 55],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'The clove kick is authentic and fantastic. Super crunchy!']
        ]
    ],
    [
        'category_id' => $giftsCat,
        'name' => 'Royal Heritage Sweet & Chikki Hamper',
        'slug' => 'royal-heritage-sweet-chikki-hamper',
        'short_description' => 'Grand festive assortment box featuring Peanut Chikki, Til Ladoo, Dryfruit Chikki, and Rajkot Peda.',
        'description' => 'An opulent gift for weddings, corporate celebrations, and festive gatherings. Packed in a lavish gold-embossed keepsake box with individual sealed freshness trays.',
        'hsn_code' => '21069099',
        'is_featured' => 1,
        'image' => '/assets/images/products/royal-gift-box.jpg',
        'variants' => [
            ['sku' => 'GIFT-ROYAL-1000', 'weight' => 1000, 'mrp' => 899.00, 'price' => 749.00, 'stock' => 35],
        ],
        'reviews' => [
            ['rating' => 5, 'comment' => 'Sent this as a Diwali corporate gift to clients. Everyone was impressed by the packaging and quality!']
        ]
    ]
];

$now = date('Y-m-d H:i:s');
$mfgDate = date('Y-m-d', strtotime('-3 days'));
$expDate = date('Y-m-d', strtotime('+120 days'));

foreach ($productsData as $p) {
    Database::query(
        "INSERT INTO products (category_id, name, slug, short_description, description, hsn_code, gst_rate_percent, is_active, is_featured, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 5.00, 1, ?, ?, ?, ?)",
        [
            $p['category_id'],
            $p['name'],
            $p['slug'],
            $p['short_description'],
            $p['description'],
            $p['hsn_code'],
            $p['is_featured'],
            $userId,
            $now,
            $now
        ]
    );
    $prodId = (int)Database::lastInsertId();

    // Primary image
    Database::query(
        "INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary)
         VALUES (?, ?, ?, 1, 1)",
        [$prodId, $p['image'], $p['name']]
    );

    // Variants & Inventory Batches
    foreach ($p['variants'] as $idx => $v) {
        Database::query(
            "INSERT INTO product_variants (product_id, sku, weight_grams, mrp, selling_price, reorder_level, is_active)
             VALUES (?, ?, ?, ?, ?, 10, 1)",
            [$prodId, $v['sku'], $v['weight'], $v['mrp'], $v['price']]
        );
        $varId = (int)Database::lastInsertId();

        $batchNo = 'BAT-' . strtoupper(substr(md5($v['sku']), 0, 6));
        Database::query(
            "INSERT INTO inventory_batches (variant_id, batch_number, manufacture_date, expiry_date, quantity_received, quantity_remaining, cost_price, supplier_name, received_by, received_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'In-house Kitchen', ?, ?)",
            [
                $varId,
                $batchNo,
                $mfgDate,
                $expDate,
                $v['stock'] + 20,
                $v['stock'],
                $v['price'] * 0.65,
                $userId,
                $now
            ]
        );
    }

    // Reviews
    foreach ($p['reviews'] as $r) {
        Database::query(
            "INSERT INTO reviews (product_id, user_id, rating, comment, is_approved, created_at)
             VALUES (?, ?, ?, ?, 1, ?)",
            [$prodId, $userId, $r['rating'], $r['comment'], $now]
        );
    }

    echo "Seeded product: {$p['name']} with variants & inventory.\n";
}

echo "\nCatalog seeding successfully completed!\n";
