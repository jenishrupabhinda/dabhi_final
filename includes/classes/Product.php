<?php
/**
 * Product model — catalog queries, variant/image helpers, search.
 */
class Product
{
    // ── Listing queries ──────────────────────────────────────────────────

    /**
     * Paginated product list for admin.
     * Supports search, category filter, active filter.
     */
    public static function adminList(
        int    $page       = 1,
        int    $perPage    = 20,
        string $search     = '',
        int    $categoryId = 0,
        string $status     = ''
    ): array {
        $conditions = ['1=1'];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(p.name LIKE ? OR p.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($categoryId > 0) {
            $conditions[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($status === 'active') {
            $conditions[] = 'p.is_active = 1';
        } elseif ($status === 'inactive') {
            $conditions[] = 'p.is_active = 0';
        } elseif ($status === 'featured') {
            $conditions[] = 'p.is_featured = 1';
        }

        $where  = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c FROM products p WHERE {$where}", $params
        )['c'] ?? 0);

        $rows = Database::fetchAll(
            "SELECT p.*, c.name AS category_name,
                    (SELECT pi.image_path FROM product_images pi
                     WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image,
                    (SELECT COALESCE(SUM(b.quantity_remaining),0)
                     FROM product_variants v JOIN inventory_batches b ON b.variant_id = v.id
                     WHERE v.product_id = p.id) AS total_stock
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE {$where}
             ORDER BY p.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Storefront: active products by category slug.
     * Includes cheapest selling_price and primary image.
     */
    public static function getByCategory(
        string $categorySlug,
        int    $page    = 1,
        int    $perPage = 16,
        string $sort    = 'popular'
    ): array {
        $cat = null;
        $params = [];
        $where = 'WHERE p.is_active = 1';

        if ($categorySlug !== '') {
            $cat = Database::fetchOne(
                'SELECT id, name, slug, description, meta_title, meta_description FROM categories WHERE slug = ? AND is_active = 1', [$categorySlug]
            );
            if (!$cat) return ['rows' => [], 'total' => 0, 'last_page' => 1, 'category' => null];
            $where .= ' AND p.category_id = ?';
            $params[] = $cat['id'];
        }

        $orderBy = match ($sort) {
            'price_asc'  => 'min_price ASC',
            'price_desc' => 'min_price DESC',
            'newest'     => 'p.created_at DESC',
            default      => 'p.is_featured DESC, p.updated_at DESC',
        };

        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as c FROM products p {$where}";
        $total = (int)(Database::fetchOne($countSql, $params)['c'] ?? 0);

        $sql = "SELECT p.*,
                       (SELECT MIN(v.selling_price) FROM product_variants v
                        WHERE v.product_id = p.id AND v.is_active = 1) AS min_price,
                       (SELECT v.mrp FROM product_variants v
                        WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS max_mrp,
                       (SELECT v.id FROM product_variants v
                        WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS min_variant_id,
                       (SELECT pi.image_path FROM product_images pi
                        WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image,
                       ROUND(AVG(r.rating),1) AS avg_rating,
                       COUNT(DISTINCT r.id) AS review_count
                FROM products p
                LEFT JOIN reviews r ON r.product_id = p.id AND r.is_approved = 1
                {$where}
                GROUP BY p.id
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $rows = Database::fetchAll($sql, $params);

        return [
            'rows'      => $rows,
            'total'     => $total,
            'last_page' => max(1, (int)ceil($total / $perPage)),
            'category'  => $cat,
        ];
    }

    /** Featured products for homepage. */
    public static function getFeatured(int $limit = 8): array
    {
        return Database::fetchAll(
            "SELECT p.*,
                    (SELECT MIN(v.selling_price) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS min_price,
                    (SELECT v.mrp FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS max_mrp,
                    (SELECT v.id FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 ORDER BY v.selling_price ASC LIMIT 1) AS min_variant_id,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image,
                    ROUND(AVG(r.rating),1) AS avg_rating,
                    COUNT(DISTINCT r.id) AS review_count
             FROM products p
             LEFT JOIN reviews r ON r.product_id = p.id AND r.is_approved = 1
             WHERE p.is_active = 1 AND p.is_featured = 1
             GROUP BY p.id
             ORDER BY p.updated_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /** Full-text search for storefront search bar. */
    public static function search(string $query, int $limit = 20): array
    {
        $q = '%' . $query . '%';
        return Database::fetchAll(
            "SELECT p.*,
                    (SELECT MIN(v.selling_price) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS min_price,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) AS primary_image
             FROM products p
             WHERE p.is_active = 1 AND (p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ?)
             LIMIT ?",
            [$q, $q, $q, $limit]
        );
    }

    /** Single product by slug for product detail page. */
    public static function getBySlug(string $slug): ?array
    {
        $prod = Database::fetchOne(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1",
            [$slug]
        );
        if (!$prod) return null;

        $id = $prod['id'];
        $prod['variants'] = self::getVariants($id);
        $prod['images']   = self::getImages($id);
        $prod['reviews']  = Database::fetchAll(
            'SELECT r.*, COALESCE(u.full_name, "Verified Customer") AS full_name
             FROM reviews r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 20',
            [$id]
        );
        $prod['avg_rating']   = (float)(Database::fetchOne(
            'SELECT ROUND(AVG(rating),1) as avg FROM reviews WHERE product_id = ? AND is_approved = 1', [$id]
        )['avg'] ?? 0);
        $prod['review_count'] = (int)(Database::fetchOne(
            'SELECT COUNT(*) as c FROM reviews WHERE product_id = ? AND is_approved = 1', [$id]
        )['c'] ?? 0);

        return $prod;
    }

    public static function getById(int $id): ?array
    {
        $prod = Database::fetchOne('SELECT * FROM products WHERE id = ?', [$id]);
        if (!$prod) return null;
        $prod['variants'] = self::getVariants($id);
        $prod['images']   = self::getImages($id);
        return $prod;
    }

    public static function getVariants(int $productId): array
    {
        return Database::fetchAll(
            'SELECT v.*,
                    COALESCE(SUM(b.quantity_remaining), 0) AS stock
             FROM product_variants v
             LEFT JOIN inventory_batches b ON b.variant_id = v.id
             WHERE v.product_id = ? AND v.is_active = 1
             GROUP BY v.id
             ORDER BY v.weight_grams ASC',
            [$productId]
        );
    }

    public static function getImages(int $productId): array
    {
        return Database::fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC',
            [$productId]
        );
    }

    // ── Mutations ────────────────────────────────────────────────────────

    /**
     * Save a product (insert or update).
     * $data keys: category_id, name, slug?, short_description, description,
     *             hsn_code, gst_rate_percent, is_active, is_featured,
     *             meta_title, meta_description
     * $variants: array of ['sku','weight_grams','mrp','selling_price','reorder_level','is_active','id'?]
     */
    public static function save(array $data, array $variants = [], ?int $id = null): array
    {
        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') return ['ok' => false, 'error' => 'Product name is required.'];

        $slug = trim($data['slug'] ?? '') ?: self::makeSlug($data['name']);
        $existing = Database::fetchOne(
            'SELECT id FROM products WHERE slug = ? AND id != ?',
            [$slug, $id ?? 0]
        );
        if ($existing) $slug = $slug . '-' . time();

        if ($id) {
            $old = self::getById($id);
            Database::query(
                'UPDATE products SET
                    category_id=?, name=?, slug=?, short_description=?, description=?,
                    hsn_code=?, gst_rate_percent=?, is_active=?, is_featured=?,
                    meta_title=?, meta_description=?
                 WHERE id=?',
                [
                    (int)$data['category_id'], $data['name'], $slug,
                    $data['short_description'] ?? null, $data['description'] ?? null,
                    $data['hsn_code'] ?? null, (float)($data['gst_rate_percent'] ?? 5),
                    (int)($data['is_active'] ?? 1), (int)($data['is_featured'] ?? 0),
                    $data['meta_title'] ?? null, $data['meta_description'] ?? null,
                    $id,
                ]
            );
            AuditLog::record('product_updated', 'product', $id, $old, $data);
        } else {
            Database::query(
                'INSERT INTO products
                    (category_id, name, slug, short_description, description, hsn_code,
                     gst_rate_percent, is_active, is_featured, meta_title, meta_description, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (int)$data['category_id'], $data['name'], $slug,
                    $data['short_description'] ?? null, $data['description'] ?? null,
                    $data['hsn_code'] ?? null, (float)($data['gst_rate_percent'] ?? 5),
                    (int)($data['is_active'] ?? 1), (int)($data['is_featured'] ?? 0),
                    $data['meta_title'] ?? null, $data['meta_description'] ?? null,
                    Auth::id(),
                ]
            );
            $id = Database::lastInsertId();
            AuditLog::record('product_created', 'product', $id, null, $data);
        }

        // Upsert variants
        foreach ($variants as $v) {
            if (!empty($v['id'])) {
                Database::query(
                    'UPDATE product_variants SET sku=?, weight_grams=?, mrp=?, selling_price=?, reorder_level=?, is_active=? WHERE id=?',
                    [
                        $v['sku'], (int)$v['weight_grams'], (float)$v['mrp'],
                        (float)$v['selling_price'], (int)($v['reorder_level'] ?? 10),
                        (int)($v['is_active'] ?? 1), (int)$v['id'],
                    ]
                );
            } else {
                Database::query(
                    'INSERT INTO product_variants (product_id,sku,weight_grams,mrp,selling_price,reorder_level,is_active) VALUES (?,?,?,?,?,?,?)',
                    [
                        $id, $v['sku'], (int)$v['weight_grams'], (float)$v['mrp'],
                        (float)$v['selling_price'], (int)($v['reorder_level'] ?? 10), (int)($v['is_active'] ?? 1),
                    ]
                );
            }
        }

        return ['ok' => true, 'id' => $id];
    }

    public static function toggle(int $id): void
    {
        Database::query('UPDATE products SET is_active = NOT is_active WHERE id = ?', [$id]);
        AuditLog::record('product_toggled', 'product', $id);
    }

    public static function deleteVariant(int $variantId): array
    {
        // Block if in any active order
        $inCart = Database::fetchOne('SELECT COUNT(*) as c FROM cart_items WHERE variant_id = ?', [$variantId]);
        if ((int)($inCart['c'] ?? 0) > 0) {
            return ['ok' => false, 'error' => 'Variant is in active carts. Remove from carts first.'];
        }
        Database::query('DELETE FROM product_variants WHERE id = ?', [$variantId]);
        return ['ok' => true];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public static function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    /** Percentage discount between MRP and selling price. */
    public static function discountPct(float $mrp, float $selling): int
    {
        if ($mrp <= 0) return 0;
        return (int)round((($mrp - $selling) / $mrp) * 100);
    }
}
