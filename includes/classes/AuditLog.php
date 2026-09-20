<?php
/**
 * AuditLog — thin wrapper to write entries to audit_logs.
 * Called from Category, Product, Order etc. instead of direct DB calls.
 */
class AuditLog
{
    public static function record(
        string $action,
        string $entityType,
        ?int   $entityId  = null,
        mixed  $oldValue  = null,
        mixed  $newValue  = null
    ): void {
        try {
            Database::query(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    Auth::id(),
                    $action,
                    $entityType,
                    $entityId,
                    $oldValue !== null ? json_encode($oldValue) : null,
                    $newValue !== null ? json_encode($newValue) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (Throwable $e) {
            // Never let audit logging crash the app
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('AuditLog error: ' . $e->getMessage());
            }
        }
    }
}
