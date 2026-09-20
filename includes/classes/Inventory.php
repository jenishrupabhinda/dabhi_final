<?php
/**
 * Inventory — batch receiving, FIFO stock allocation, expiry alerts,
 * and the stock_movements audit trail.
 */
class Inventory
{
    /**
     * Add / receive a new inventory batch.
     */
    public static function receiveBatch(
        int $variantId,
        string $batchNumber,
        string $expiryDate,
        int $qty,
        ?float $costPrice = null,
        ?string $mfgDate = null,
        ?string $supplierName = null,
        ?int $receivedBy = null,
        string $notes = ''
    ): int {
        Database::query(
            "INSERT INTO inventory_batches
             (variant_id, batch_number, manufacture_date, expiry_date, quantity_received,
              quantity_remaining, cost_price, supplier_name, received_by, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $variantId,
                $batchNumber,
                $mfgDate ?: null,
                $expiryDate,
                $qty,
                $qty,
                $costPrice,
                $supplierName,
                $receivedBy,
                $notes,
            ]
        );
        $batchId = (int)Database::lastInsertId();

        Database::query(
            "INSERT INTO stock_movements
             (variant_id, batch_id, movement_type, quantity, reference_type, reference_id, notes, performed_by)
             VALUES (?, ?, 'purchase_in', ?, 'batch', ?, ?, ?)",
            [$variantId, $batchId, $qty, $batchId, 'Batch received: ' . $batchNumber, $receivedBy]
        );

        return $batchId;
    }

    /**
     * Get batches expiring within $days days with remaining stock.
     */
    public static function getNearExpiry(int $days = 30): array
    {
        return Database::fetchAll(
            "SELECT ib.*, pv.sku, pv.weight_grams, p.name AS product_name
             FROM inventory_batches ib
             JOIN product_variants pv ON pv.id = ib.variant_id
             JOIN products p ON p.id = pv.product_id
             WHERE ib.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND ib.quantity_remaining > 0
             ORDER BY ib.expiry_date ASC",
            [$days]
        );
    }

    public static function expiringWithin(int $days = 30): array
    {
        return self::getNearExpiry($days);
    }

    /**
     * Get variants at or below their reorder level.
     */
    public static function getLowStock(): array
    {
        return Database::fetchAll(
            "SELECT pv.id AS variant_id, pv.sku, pv.weight_grams, pv.reorder_level,
                    p.id AS product_id, p.name AS product_name,
                    COALESCE(SUM(ib.quantity_remaining), 0) AS current_stock
             FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             LEFT JOIN inventory_batches ib ON ib.variant_id = pv.id
             WHERE pv.is_active = 1
             GROUP BY pv.id, pv.sku, pv.weight_grams, pv.reorder_level, p.id, p.name
             HAVING current_stock <= pv.reorder_level
             ORDER BY current_stock ASC"
        );
    }

    public static function lowStock(): array
    {
        return self::getLowStock();
    }

    /**
     * Get current available stock for a variant across all active batches.
     */
    public static function currentStock(int $variantId): int
    {
        $row = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity_remaining), 0) AS stock
             FROM inventory_batches
             WHERE variant_id = ? AND quantity_remaining > 0",
            [$variantId]
        );
        return (int)($row['stock'] ?? 0);
    }

    /**
     * Deduct stock using strict FIFO (First In, First Expired / First Out) logic.
     * Decrements inventory_batches in order and records stock_movements.
     */
    public static function deductStock(
        int $variantId,
        int $qty,
        string $notes = '',
        ?int $orderId = null,
        ?int $performedBy = null
    ): array {
        $batches = Database::fetchAll(
            "SELECT id, quantity_remaining
             FROM inventory_batches
             WHERE variant_id = ? AND quantity_remaining > 0
             ORDER BY expiry_date ASC, id ASC",
            [$variantId]
        );

        $allocated = [];
        $remainingToDeduct = $qty;

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;
            $batchId = (int)$batch['id'];
            $rem = (int)$batch['quantity_remaining'];
            $take = min($remainingToDeduct, $rem);

            Database::query(
                "UPDATE inventory_batches SET quantity_remaining = quantity_remaining - ? WHERE id = ?",
                [$take, $batchId]
            );

            Database::query(
                "INSERT INTO stock_movements
                 (variant_id, batch_id, movement_type, quantity, reference_type, reference_id, performed_by, notes)
                 VALUES (?, ?, 'sale_out', ?, 'order', ?, ?, ?)",
                [$variantId, $batchId, $take, $orderId, $performedBy, $notes ?: 'Order deduction']
            );

            $allocated[] = ['batch_id' => $batchId, 'quantity' => $take];
            $remainingToDeduct -= $take;
        }

        return $allocated;
    }

    /**
     * Manual stock adjustment (e.g., damaged goods, discrepancy audits).
     */
    public static function adjustStock(
        int $variantId,
        int $batchId,
        string $type,
        int $qty,
        string $notes = '',
        ?int $performedBy = null
    ): void {
        $direction = ($type === 'adjustment_out' || $type === 'damage_out') ? -1 : 1;
        Database::query(
            "UPDATE inventory_batches SET quantity_remaining = quantity_remaining + ? WHERE id = ?",
            [$direction * $qty, $batchId]
        );
        Database::query(
            "INSERT INTO stock_movements
             (variant_id, batch_id, movement_type, quantity, reference_type, notes, performed_by)
             VALUES (?, ?, ?, ?, 'manual', ?, ?)",
            [$variantId, $batchId, $type, $qty, $notes, $performedBy]
        );
    }
}
