<?php
/**
 * GST — CGST/SGST (intra-state) vs IGST (inter-state) split.
 * Indian GST rules: if buyer and seller are in the same state → CGST + SGST (each = rate/2).
 *                   Otherwise → IGST (full rate).
 */
class GST
{
    private static ?array $settings = null;

    public static function settings(): array
    {
        if (self::$settings === null) {
            self::$settings = Database::fetchOne('SELECT * FROM gst_settings LIMIT 1') ?? [];
        }
        return self::$settings;
    }

    public static function getSettings(): array
    {
        return self::settings();
    }

    public static function isEnabled(): bool
    {
        return (int)(self::settings()['is_gst_enabled'] ?? 1) === 1;
    }

    public static function businessState(): string
    {
        return self::settings()['business_state'] ?? 'Gujarat';
    }

    public static function gstin(): ?string
    {
        return self::settings()['gstin'] ?? null;
    }

    public static function businessName(): string
    {
        return self::settings()['business_name'] ?? 'Dabhi Chikki';
    }

    /**
     * Calculate GST amounts for a set of cart items.
     *
     * @param array  $items       Cart items (each with selling_price, quantity, gst_rate_percent)
     * @param string $buyerState  Buyer's state name (from address)
     * @return array {cgst, sgst, igst, is_intra_state, breakdown[]}
     */
    public static function calculate(array $items, string $buyerState): array
    {
        if (!self::isEnabled()) {
            return ['cgst' => 0, 'sgst' => 0, 'igst' => 0, 'is_intra_state' => true, 'breakdown' => []];
        }

        $sellerState   = strtolower(trim(self::businessState()));
        $isIntraState  = strtolower(trim($buyerState)) === $sellerState;

        $cgst = $sgst = $igst = 0.0;
        $breakdown = [];

        foreach ($items as $item) {
            $lineTotal = (float)($item['selling_price'] ?? 0) * (int)($item['quantity'] ?? 1);
            $rate      = (float)($item['gst_rate_percent'] ?? 5.0);

            if ($isIntraState) {
                $half = round($lineTotal * ($rate / 2) / 100, 2);
                $cgst += $half;
                $sgst += $half;
                $breakdown[] = ['name' => $item['product_name'], 'cgst' => $half, 'sgst' => $half, 'igst' => 0];
            } else {
                $full = round($lineTotal * $rate / 100, 2);
                $igst += $full;
                $breakdown[] = ['name' => $item['product_name'], 'cgst' => 0, 'sgst' => 0, 'igst' => $full];
            }
        }

        return [
            'cgst'           => round($cgst, 2),
            'sgst'           => round($sgst, 2),
            'igst'           => round($igst, 2),
            'is_intra_state' => $isIntraState,
            'breakdown'      => $breakdown,
        ];
    }

    /** Return a human-readable GST label for display e.g. "CGST 2.5% + SGST 2.5%" */
    public static function label(float $rate, bool $isIntra): string
    {
        $half = $rate / 2;
        return $isIntra
            ? "CGST {$half}% + SGST {$half}%"
            : "IGST {$rate}%";
    }
}
