<?php
/**
 * RBAC — fine-grained permission checks on top of Auth's coarse role check.
 *
 * How the toggle system works end-to-end:
 *   1. `permissions` table lists every checkbox that can ever exist
 *      (seeded in database/seed.sql).
 *   2. Superadmin's permissions page writes rows into `user_permissions`
 *      for a given admin (is_enabled 1/0 per permission).
 *   3. That admin's own permissions page does the same for their employees
 *      — but RBAC::grant() below refuses to let an admin grant a permission
 *      to an employee that the admin does not itself hold, so access can
 *      only ever narrow as it cascades down, never widen.
 *   4. Every gated page/action calls RBAC::can($userId, 'permission_key')
 *      before doing anything sensitive.
 *
 * Superadmin implicitly passes every check (see can(), first line) —
 * it never needs rows in user_permissions.
 */

class RBAC
{
    public static function can(int|string $userIdOrPerm, ?string $permissionKey = null): bool
    {
        if ($permissionKey === null) {
            $permissionKey = (string)$userIdOrPerm;
            $userId = (int)(Auth::id() ?? 0);
        } else {
            $userId = (int)$userIdOrPerm;
        }

        if ($userId <= 0) {
            return false;
        }

        $user = Database::fetchOne('SELECT role FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return false;
        }
        if ($user['role'] === 'superadmin') {
            return true; // superadmin always has full access
        }
        if ($user['role'] === 'buyer') {
            return false; // buyers never hold admin permissions
        }

        $row = Database::fetchOne(
            'SELECT up.is_enabled
             FROM user_permissions up
             JOIN permissions p ON p.id = up.permission_id
             WHERE up.user_id = ? AND p.permission_key = ?',
            [$userId, $permissionKey]
        );

        return $row !== null && (int) $row['is_enabled'] === 1;
    }

    /** Guard for entry-point files: RBAC::requireCan('manage_products') or RBAC::requireCan($userId, 'manage_products'); */
    public static function requireCan(int|string $userIdOrPerm, ?string $permissionKey = null): void
    {
        if (!self::can($userIdOrPerm, $permissionKey)) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../../public_html/partials/403.php')) {
                include __DIR__ . '/../../public_html/partials/403.php';
            } else {
                echo 'You do not have permission to access this page.';
            }
            exit;
        }
    }

    /**
     * Grant/revoke a permission to a target user, enforced so a granter can
     * never hand out a permission they don't themselves hold (except
     * superadmin, who holds everything implicitly).
     */
    public static function grant(int $granterId, int $targetUserId, string $permissionKey, bool $enabled): bool
    {
        $granterRole = Database::fetchOne('SELECT role FROM users WHERE id = ?', [$granterId])['role'] ?? null;

        if ($granterRole !== 'superadmin' && !self::can($granterId, $permissionKey)) {
            return false; // cannot grant what you don't have
        }

        $permission = Database::fetchOne('SELECT id FROM permissions WHERE permission_key = ?', [$permissionKey]);
        if (!$permission) {
            return false;
        }

        Database::query(
            'INSERT INTO user_permissions (user_id, permission_id, is_enabled, granted_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), granted_by = VALUES(granted_by), granted_at = NOW()',
            [$targetUserId, $permission['id'], $enabled ? 1 : 0, $granterId]
        );

        // TODO (next phase): write an audit_logs row here.
        return true;
    }

    /** All permission checkboxes + current state for a given user (drives the toggle-grid UI). */
    public static function allForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT p.permission_key, p.label, p.category, p.description,
                    COALESCE(up.is_enabled, 0) AS is_enabled
             FROM permissions p
             LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = ?
             ORDER BY p.category, p.label',
            [$userId]
        );
    }
}
