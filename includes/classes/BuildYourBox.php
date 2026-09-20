<?php
/**
 * BuildYourBox — manages the "Build Your Box" feature.
 * A buyer picks variants to fill a box up to a weight limit.
 * Saved boxes can be re-ordered.
 */
class BuildYourBox
{
    /** Default box weight limit from settings (grams). */
    public static function defaultWeightLimit(): int
    {
        return (int)getSetting('byob_default_weight_grams', '500');
    }

    /** Available box sizes from settings JSON. */
    public static function boxOptions(): array
    {
        $json = getSetting('byob_box_options', '[{"label":"500g Box","weight":500},{"label":"1kg Box","weight":1000},{"label":"2kg Box","weight":2000}]');
        return json_decode($json, true) ?? [
            ['label' => '500g Box', 'weight' => 500],
            ['label' => '1kg Box',  'weight' => 1000],
            ['label' => '2kg Box',  'weight' => 2000],
        ];
    }

    /** All products available for BYOB (active, with variants+stock). */
    public static function getAvailableProducts(): array
    {
        return Database::fetchAll(
            "SELECT p.id, p.name, p.slug, c.name AS category_name,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image,
                    v.id AS variant_id, v.sku, v.weight_grams, v.selling_price, v.mrp,
                    COALESCE(SUM(b.quantity_remaining), 0) AS stock
             FROM products p
             JOIN categories c ON c.id = p.category_id
             JOIN product_variants v ON v.product_id = p.id AND v.is_active = 1
             LEFT JOIN inventory_batches b ON b.variant_id = v.id
             WHERE p.is_active = 1 AND c.is_active = 1
             GROUP BY v.id
             HAVING stock > 0
             ORDER BY c.sort_order, p.name, v.weight_grams"
        );
    }

    // ── Saved Boxes (logged-in users) ─────────────────────────────────────

    public static function getUserBoxes(int $userId): array
    {
        $boxes = Database::fetchAll(
            'SELECT * FROM build_boxes WHERE user_id = ? ORDER BY created_at DESC', [$userId]
        );
        foreach ($boxes as &$box) {
            $box['items'] = self::getItems((int)$box['id']);
        }
        return $boxes;
    }

    public static function getBox(int $boxId, int $userId): ?array
    {
        $box = Database::fetchOne(
            'SELECT * FROM build_boxes WHERE id = ? AND user_id = ?', [$boxId, $userId]
        );
        if (!$box) return null;
        $box['items'] = self::getItems($boxId);
        $box['total_weight'] = array_sum(array_map(
            fn($i) => (int)$i['weight_grams'] * (int)$i['quantity'],
            $box['items']
        ));
        $box['total_price'] = array_sum(array_map(
            fn($i) => (float)$i['selling_price'] * (int)$i['quantity'],
            $box['items']
        ));
        return $box;
    }

    public static function getItems(int $boxId): array
    {
        return Database::fetchAll(
            "SELECT bbi.*, v.sku, v.weight_grams, v.selling_price,
                    p.name AS product_name, p.slug AS product_slug,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS product_image
             FROM build_box_items bbi
             JOIN product_variants v ON v.id = bbi.variant_id
             JOIN products p ON p.id = v.product_id
             WHERE bbi.build_box_id = ?
             ORDER BY p.name, v.weight_grams",
            [$boxId]
        );
    }

    /**
     * Create or update a saved box.
     * $items = [['variant_id'=>int, 'quantity'=>int], ...]
     */
    public static function saveBox(int $userId, string $name, int $weightLimitGrams, array $items): array
    {
        if (empty($name)) return ['ok' => false, 'error' => 'Box name is required.'];

        // Validate total weight
        $totalWeight = 0;
        foreach ($items as $item) {
            $v = Database::fetchOne('SELECT weight_grams FROM product_variants WHERE id = ?', [(int)$item['variant_id']]);
            if (!$v) continue;
            $totalWeight += (int)$v['weight_grams'] * (int)($item['quantity'] ?? 1);
        }
        if ($totalWeight > $weightLimitGrams) {
            return ['ok' => false, 'error' => "Box is too heavy ({$totalWeight}g). Limit is {$weightLimitGrams}g."];
        }

        Database::beginTransaction();
        try {
            Database::query(
                'INSERT INTO build_boxes (user_id, box_name, weight_limit_grams) VALUES (?, ?, ?)',
                [$userId, trim($name), $weightLimitGrams]
            );
            $boxId = Database::lastInsertId();

            foreach ($items as $item) {
                Database::query(
                    'INSERT INTO build_box_items (build_box_id, variant_id, quantity) VALUES (?, ?, ?)',
                    [$boxId, (int)$item['variant_id'], (int)($item['quantity'] ?? 1)]
                );
            }

            Database::commit();
            return ['ok' => true, 'box_id' => $boxId];
        } catch (Throwable $e) {
            Database::rollback();
            return ['ok' => false, 'error' => 'Failed to save box. Please try again.'];
        }
    }

    /** Add saved box items to cart (as a box_group_id group). */
    public static function addBoxToCart(int $boxId, int $userId): array
    {
        $box = self::getBox($boxId, $userId);
        if (!$box) return ['ok' => false, 'error' => 'Box not found.'];

        $groupId = bin2hex(random_bytes(18)); // UUID-ish

        foreach ($box['items'] as $item) {
            $result = Cart::addItem((int)$item['variant_id'], (int)$item['quantity'], $groupId);
            if (!$result['ok']) {
                return ['ok' => false, 'error' => "Could not add {$item['product_name']}: {$result['error']}"];
            }
        }

        return ['ok' => true, 'box_group_id' => $groupId];
    }

    public static function deleteBox(int $boxId, int $userId): bool
    {
        Database::query(
            'DELETE FROM build_boxes WHERE id = ? AND user_id = ?', [$boxId, $userId]
        );
        return true;
    }

    /** Validate a live session box (not yet saved) — used by JS AJAX. */
    public static function validateSessionBox(array $items, int $weightLimitGrams): array
    {
        $totalWeight = 0;
        $totalPrice  = 0.0;

        foreach ($items as &$item) {
            $v = Database::fetchOne(
                'SELECT v.weight_grams, v.selling_price, p.name
                 FROM product_variants v JOIN products p ON p.id = v.product_id
                 WHERE v.id = ? AND v.is_active = 1 AND p.is_active = 1',
                [(int)$item['variant_id']]
            );
            if (!$v) continue;
            $qty          = max(1, (int)($item['quantity'] ?? 1));
            $totalWeight += (int)$v['weight_grams'] * $qty;
            $totalPrice  += (float)$v['selling_price'] * $qty;
        }

        return [
            'total_weight' => $totalWeight,
            'weight_limit' => $weightLimitGrams,
            'remaining'    => max(0, $weightLimitGrams - $totalWeight),
            'over_limit'   => $totalWeight > $weightLimitGrams,
            'total_price'  => round($totalPrice, 2),
        ];
    }
}
