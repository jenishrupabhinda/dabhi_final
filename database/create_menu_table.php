<?php
/**
 * create_menu_table.php
 * Creates the menu_items table, seeds default storefront navigation items,
 * and adds the manage_menus permission if needed.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

echo "1. Creating menu_items table...\n";
Database::query("
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `icon` VARCHAR(50) NULL DEFAULT NULL,
    `badge` VARCHAR(50) NULL DEFAULT NULL,
    `badge_color` VARCHAR(50) NULL DEFAULT NULL,
    `subtitle` VARCHAR(150) NULL DEFAULT NULL,
    `target` VARCHAR(20) NOT NULL DEFAULT '_self',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `location` VARCHAR(50) NOT NULL DEFAULT 'primary',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_parent_sort` (`parent_id`, `sort_order`),
    KEY `idx_location_active` (`location`, `is_active`),
    CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "2. Checking permissions table for manage_menus...\n";
$permExists = Database::fetchOne("SELECT id FROM permissions WHERE permission_key = 'manage_menus'");
if (!$permExists) {
    Database::query("
        INSERT INTO permissions (permission_key, label, category, applies_to_role, description)
        VALUES ('manage_menus', 'Manage navigation menus & submenus', 'storefront', 'both', 'Create, edit, toggle visibility, and delete storefront menu items and dropdown submenus')
    ");
    echo "   Permission 'manage_menus' added.\n";
} else {
    echo "   Permission 'manage_menus' already exists.\n";
}

echo "3. Seeding default storefront menu items if table empty...\n";
$count = (int)Database::fetchOne("SELECT COUNT(*) as c FROM menu_items")['c'];
if ($count === 0) {
    // 1. Home
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Home', 'index.php', null, 1, 1, 'primary'
    ]);

    // 2. Our Chikki (Parent)
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Our Chikki', 'index.php#products', null, 2, 1, 'primary'
    ]);
    $chikkiParentId = (int)Database::lastInsertId();

    // Submenus for Our Chikki
    Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $chikkiParentId, 'Mandvi Peanut Chikki', 'product.php?slug=mandvi-chikki', '🥜', 'Bestseller', '#c7613d', 'Classic Saurashtra crunch', 1, 1, 'primary'
    ]);
    Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $chikkiParentId, 'TIL Sesame Chikki', 'product.php?slug=til-chikki', '⚪', 'Calcium', 'rgba(255,255,255,0.15)', 'Rich in natural calcium', 2, 1, 'primary'
    ]);
    Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $chikkiParentId, 'Daliya Gram Chikki', 'product.php?slug=daliya-chikki', '🌾', 'Crispy', 'rgba(255,255,255,0.15)', 'Crispy roasted chickpea', 3, 1, 'primary'
    ]);
    Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $chikkiParentId, '3 Mix Signature Box', 'product.php?slug=3-mix-chikki', '✨', 'Signature', '#f6dc94', 'Peanut, Til & Coconut', 4, 1, 'primary'
    ]);
    Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $chikkiParentId, 'Explore All 4 Flavors', 'index.php#products', '→', '', '', 'View full chikki collection', 5, 1, 'primary'
    ]);

    // 3. Our Craft
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Our Craft', 'index.php#our-story', null, 3, 1, 'primary'
    ]);

    // 4. Reels
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Reels', 'index.php#reels-section', '🔴', 4, 1, 'primary'
    ]);

    // 5. Reviews
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Reviews', 'index.php#reviews', null, 5, 1, 'primary'
    ]);

    // 6. Track Order
    Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES (?, ?, ?, ?, ?, ?)", [
        'Track Order', 'track.php', '📦', 6, 1, 'primary'
    ]);

    echo "   Seeded " . (6 + 5) . " default menu items successfully.\n";
} else {
    echo "   menu_items table already contains $count items.\n";
}

echo "Done!\n";
