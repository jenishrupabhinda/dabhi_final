<?php
/**
 * Category model — wraps all DB interactions for the categories table.
 */
class Category
{
    // ── Queries ──────────────────────────────────────────────────────────

    /** All categories as a flat list (optionally with parent info joined). */
    public static function getAll(): array
    {
        return Database::fetchAll(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             ORDER BY c.sort_order, c.name'
        );
    }

    /** Only top-level, active categories. Used by storefront nav & footer. */
    public static function getActive(): array
    {
        return Database::fetchAll(
            'SELECT * FROM categories
             WHERE is_active = 1 AND parent_id IS NULL
             ORDER BY sort_order, name'
        );
    }

    /**
     * Full two-level tree.
     * Returns: [ ['id'=>1, 'name'=>'Chikki', ..., 'children'=>[ [...], ... ]], ... ]
     */
    public static function getTree(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM categories ORDER BY sort_order, name'
        );
        $indexed = [];
        foreach ($rows as $r) {
            $r['children'] = [];
            $indexed[$r['id']] = $r;
        }
        $tree = [];
        foreach ($indexed as &$cat) {
            if ($cat['parent_id'] && isset($indexed[$cat['parent_id']])) {
                $indexed[$cat['parent_id']]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }
        return $tree;
    }

    public static function getById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public static function getBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    // ── Mutations ────────────────────────────────────────────────────────

    /**
     * Insert or update a category.
     * $data keys: name, parent_id, description, image_path, sort_order,
     *             is_active, meta_title, meta_description
     * Returns ['ok'=>true, 'id'=>int] or ['ok'=>false, 'error'=>string]
     */
    public static function save(array $data, ?int $id = null): array
    {
        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            return ['ok' => false, 'error' => 'Category name is required.'];
        }

        // Auto-generate slug if not supplied
        $slug = trim($data['slug'] ?? '') ?: self::makeSlug($data['name']);

        // Ensure slug uniqueness (exclude current record on edit)
        $existing = Database::fetchOne(
            'SELECT id FROM categories WHERE slug = ? AND id != ?',
            [$slug, $id ?? 0]
        );
        if ($existing) {
            $slug = $slug . '-' . time();
        }

        $parentId = isset($data['parent_id']) && $data['parent_id'] ? (int)$data['parent_id'] : null;

        if ($id) {
            Database::query(
                'UPDATE categories SET
                    parent_id = ?, name = ?, slug = ?, description = ?,
                    image_path = ?, is_active = ?, sort_order = ?,
                    meta_title = ?, meta_description = ?
                 WHERE id = ?',
                [
                    $parentId, $data['name'], $slug, $data['description'] ?? null,
                    $data['image_path'] ?? null, (int)($data['is_active'] ?? 1),
                    (int)($data['sort_order'] ?? 0),
                    $data['meta_title'] ?? null, $data['meta_description'] ?? null,
                    $id,
                ]
            );
            AuditLog::record('category_updated', 'category', $id, null, ['name' => $data['name']]);
            return ['ok' => true, 'id' => $id];
        } else {
            Database::query(
                'INSERT INTO categories
                    (parent_id, name, slug, description, image_path, is_active, sort_order, meta_title, meta_description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $parentId, $data['name'], $slug, $data['description'] ?? null,
                    $data['image_path'] ?? null, (int)($data['is_active'] ?? 1),
                    (int)($data['sort_order'] ?? 0),
                    $data['meta_title'] ?? null, $data['meta_description'] ?? null,
                ]
            );
            $newId = Database::lastInsertId();
            AuditLog::record('category_created', 'category', $newId, null, ['name' => $data['name']]);
            return ['ok' => true, 'id' => $newId];
        }
    }

    /** Flip is_active. Returns new state. */
    public static function toggle(int $id): bool
    {
        Database::query(
            'UPDATE categories SET is_active = NOT is_active WHERE id = ?',
            [$id]
        );
        $cat = self::getById($id);
        AuditLog::record('category_toggled', 'category', $id, null, ['is_active' => $cat['is_active'] ?? null]);
        return (bool)($cat['is_active'] ?? false);
    }

    /** Delete — only succeeds if no products belong to this category. */
    public static function delete(int $id): array
    {
        $prod = Database::fetchOne(
            'SELECT COUNT(*) as c FROM products WHERE category_id = ?', [$id]
        );
        if ((int)($prod['c'] ?? 0) > 0) {
            return ['ok' => false, 'error' => 'Cannot delete: products exist in this category.'];
        }
        Database::query('DELETE FROM categories WHERE id = ?', [$id]);
        AuditLog::record('category_deleted', 'category', $id);
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
}
