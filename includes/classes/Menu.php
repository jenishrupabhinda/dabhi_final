<?php
/**
 * Menu model — DB interactions for navigation_menus / menu_items table.
 */
class Menu
{
    private static bool $tableChecked = false;

    /**
     * Automatically ensure menu_items table and manage_menus permission exist.
     * Prevents 1146 "Table doesn't exist" exceptions across any environment.
     */
    public static function ensureTable(): void
    {
        if (self::$tableChecked) {
            return;
        }
        self::$tableChecked = true;

        try {
            Database::query('SELECT 1 FROM menu_items LIMIT 1');
        } catch (\Throwable $e) {
            try {
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
                        KEY `idx_location_active` (`location`, `is_active`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");

                // Seed defaults if empty
                try {
                    $countRow = Database::fetchOne('SELECT COUNT(*) AS c FROM menu_items');
                    if (!$countRow || (int)$countRow['c'] === 0) {
                        self::seedDefaults();
                    }
                } catch (\Throwable $ignore) {}

                // Ensure permission exists in permissions table
                try {
                    $perm = Database::fetchOne("SELECT id FROM permissions WHERE permission_key = 'manage_menus'");
                    if (!$perm) {
                        Database::query("
                            INSERT INTO permissions (permission_key, label, category, applies_to_role, description)
                            VALUES ('manage_menus', 'Manage navigation menus & submenus', 'storefront', 'both', 'Create, edit, toggle visibility, and delete storefront menu items and dropdown submenus')
                        ");
                    }
                } catch (\Throwable $ignore) {}
            } catch (\Throwable $createEx) {
                error_log('Menu::ensureTable creation failed: ' . $createEx->getMessage());
            }
        }
    }

    /**
     * All menu items as a flat list with parent title joined.
     */
    public static function getAll(string $location = 'primary'): array
    {
        self::ensureTable();
        try {
            return Database::fetchAll(
                'SELECT m.*, p.title AS parent_title
                 FROM menu_items m
                 LEFT JOIN menu_items p ON p.id = m.parent_id
                 WHERE m.location = ?
                 ORDER BY COALESCE(m.parent_id, m.id), m.parent_id IS NOT NULL, m.sort_order ASC, m.id ASC',
                [$location]
            );
        } catch (\Throwable $e) {
            error_log('Menu::getAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get two-level tree for storefront rendering.
     * If $onlyActive is true, returns only active parent items and active child items.
     * Returns: [ ['id'=>1, 'title'=>'...', 'children'=>[...]], ... ]
     */
    public static function getTree(string $location = 'primary', bool $onlyActive = true): array
    {
        self::ensureTable();
        try {
            $sql = 'SELECT * FROM menu_items WHERE location = ?';
            $params = [$location];
            if ($onlyActive) {
                $sql .= ' AND is_active = 1';
            }
            $sql .= ' ORDER BY sort_order ASC, id ASC';

            $rows = Database::fetchAll($sql, $params);
            $indexed = [];
            foreach ($rows as $r) {
                $r['children'] = [];
                $indexed[$r['id']] = $r;
            }

            $tree = [];
            foreach ($indexed as &$item) {
                if ($item['parent_id'] && isset($indexed[$item['parent_id']])) {
                    $indexed[$item['parent_id']]['children'][] = &$item;
                } elseif (!$item['parent_id']) {
                    $tree[] = &$item;
                }
            }
            return $tree;
        } catch (\Throwable $e) {
            error_log('Menu::getTree failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top-level parent menu items for dropdown selection.
     */
    public static function getParents(string $location = 'primary', ?int $excludeId = null): array
    {
        self::ensureTable();
        try {
            $sql = 'SELECT id, title FROM menu_items WHERE parent_id IS NULL AND location = ?';
            $params = [$location];
            if ($excludeId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $sql .= ' ORDER BY sort_order ASC, title ASC';
            return Database::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('Menu::getParents failed: ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array
    {
        self::ensureTable();
        try {
            return Database::fetchOne('SELECT * FROM menu_items WHERE id = ?', [$id]);
        } catch (\Throwable $e) {
            error_log('Menu::getById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert or update a menu item.
     */
    public static function save(array $data, ?int $id = null): array
    {
        self::ensureTable();
        $title      = trim((string)($data['title'] ?? ''));
        $url        = trim((string)($data['url'] ?? ''));
        $parentId   = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $icon       = trim((string)($data['icon'] ?? '')) ?: null;
        $badge      = trim((string)($data['badge'] ?? '')) ?: null;
        $badgeColor = trim((string)($data['badge_color'] ?? '')) ?: null;
        $subtitle   = trim((string)($data['subtitle'] ?? '')) ?: null;
        $target     = (isset($data['target']) && in_array($data['target'], ['_self', '_blank'], true)) ? $data['target'] : '_self';
        $sortOrder  = (int)($data['sort_order'] ?? 0);
        $isActive   = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
        $location   = trim((string)($data['location'] ?? 'primary')) ?: 'primary';

        if ($title === '') {
            return ['ok' => false, 'error' => 'Menu title is required.'];
        }
        if ($url === '') {
            return ['ok' => false, 'error' => 'URL / Link is required.'];
        }

        // Prevent setting self as parent
        if ($id !== null && $parentId === $id) {
            return ['ok' => false, 'error' => 'A menu item cannot be its own parent.'];
        }

        // Only allow 1 level of nesting (parent cannot be a child of another parent)
        if ($parentId) {
            $parent = self::getById($parentId);
            if ($parent && !empty($parent['parent_id'])) {
                return ['ok' => false, 'error' => 'Submenus cannot have further nested submenus.'];
            }
        }

        try {
            if ($id) {
                Database::query(
                    'UPDATE menu_items SET
                        parent_id   = ?,
                        title       = ?,
                        url         = ?,
                        icon        = ?,
                        badge       = ?,
                        badge_color = ?,
                        subtitle    = ?,
                        target      = ?,
                        sort_order  = ?,
                        is_active   = ?,
                        location    = ?
                     WHERE id = ?',
                    [
                        $parentId, $title, $url, $icon, $badge, $badgeColor,
                        $subtitle, $target, $sortOrder, $isActive, $location, $id
                    ]
                );
            } else {
                Database::query(
                    'INSERT INTO menu_items
                        (parent_id, title, url, icon, badge, badge_color, subtitle, target, sort_order, is_active, location)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $parentId, $title, $url, $icon, $badge, $badgeColor,
                        $subtitle, $target, $sortOrder, $isActive, $location
                    ]
                );
                $id = (int)Database::lastInsertId();
            }
            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle active / visible status.
     */
    public static function toggle(int $id): bool
    {
        $item = self::getById($id);
        if (!$item) {
            return false;
        }
        $newStatus = $item['is_active'] ? 0 : 1;
        Database::query('UPDATE menu_items SET is_active = ? WHERE id = ?', [$newStatus, $id]);
        return true;
    }

    /**
     * Delete a menu item.
     */
    public static function delete(int $id): array
    {
        $item = self::getById($id);
        if (!$item) {
            return ['ok' => false, 'error' => 'Menu item not found.'];
        }
        try {
            // Delete child items or let foreign key cascade delete them
            Database::query('DELETE FROM menu_items WHERE id = ?', [$id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Cannot delete menu item: ' . $e->getMessage()];
        }
    }

    /**
     * Seed or restore default storefront navigation menus.
     */
    public static function seedDefaults(): void
    {
        Database::query('DELETE FROM menu_items WHERE location = "primary"');

        // 1. Home
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Home', 'index.php', null, 1, 1, 'primary')");

        // 2. Our Chikki (Parent with 4 flavors + view all)
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Our Chikki', 'index.php#products', null, 2, 1, 'primary')");
        $chikkiParentId = (int)Database::lastInsertId();

        Database::query("INSERT INTO menu_items (parent_id, title, url, icon, badge, badge_color, subtitle, sort_order, is_active, location) VALUES
            (?, 'Mandvi Peanut Chikki', 'product.php?slug=mandvi-chikki', '🥜', 'Bestseller', '#c7613d', 'Classic Saurashtra crunch', 1, 1, 'primary'),
            (?, 'TIL Sesame Chikki', 'product.php?slug=til-chikki', '⚪', 'Calcium', 'rgba(255,255,255,0.15)', 'Rich in natural calcium', 2, 1, 'primary'),
            (?, 'Daliya Gram Chikki', 'product.php?slug=daliya-chikki', '🌾', 'Crispy', 'rgba(255,255,255,0.15)', 'Crispy roasted chickpea', 3, 1, 'primary'),
            (?, '3 Mix Signature Box', 'product.php?slug=3-mix-chikki', '✨', 'Signature', '#f6dc94', 'Peanut, Til & Coconut', 4, 1, 'primary'),
            (?, 'Explore All 4 Flavors', 'index.php#products', '→', '', '', 'View full chikki collection', 5, 1, 'primary')
        ", [$chikkiParentId, $chikkiParentId, $chikkiParentId, $chikkiParentId, $chikkiParentId]);

        // 3. Our Craft
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Our Craft', 'index.php#our-story', null, 3, 1, 'primary')");

        // 4. Reels
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Reels', 'index.php#reels-section', '🔴', 4, 1, 'primary')");

        // 5. Reviews
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Reviews', 'index.php#reviews', null, 5, 1, 'primary')");

        // 6. Track Order
        Database::query("INSERT INTO menu_items (title, url, icon, sort_order, is_active, location) VALUES ('Track Order', 'track.php', '📦', 6, 1, 'primary')");
    }
}
