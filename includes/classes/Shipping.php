<?php
/**
 * Shipping calculator — pincode lookup + weight-slab rate.
 */
class Shipping
{
    /**
     * Full shipping calculation given a pincode and cart weight (grams).
     * Optional $subtotal to automatically apply free shipping threshold.
     * Returns:
     *   ['ok'=>true, 'rate'=>float, 'cod_extra'=>float, 'total_charge'=>float, 'is_free'=>bool, 'cod_available'=>bool, 'zone_id'=>int, 'zone_name'=>str]
     *   ['ok'=>false, 'error'=>'Not serviceable']
     */
    public static function calculate(string $pincode, int $weightGrams = 500, bool $isCod = false, float $subtotal = 0.0): array
    {
        $pincode = preg_replace('/\D/', '', $pincode);
        if (strlen($pincode) !== 6) {
            return ['ok' => false, 'error' => 'Please enter a valid 6-digit PIN code.'];
        }

        // 1. Check explicit pincode_zones mapping
        $pz = Database::fetchOne(
            'SELECT pz.*, sz.name AS zone_name
             FROM pincode_zones pz
             JOIN shipping_zones sz ON sz.id = pz.zone_id
             WHERE pz.pincode = ?',
            [$pincode]
        );

        $zoneId       = 2;
        $zoneName     = 'Rest of India';
        $isServiceable= true;
        $codAvailable = true;

        if ($pz) {
            if (!$pz['is_serviceable']) {
                return ['ok' => false, 'error' => 'Delivery is currently not available to PIN code ' . $pincode . '.'];
            }
            $zoneId       = (int)$pz['zone_id'];
            $zoneName     = $pz['zone_name'] ?? 'Local Zone';
            $isServiceable= (bool)$pz['is_serviceable'];
            $codAvailable = (bool)$pz['cod_available'];
        } else {
            // Intelligent pan-India zone mapping:
            // 36xxxx - 39xxxx are Gujarat PIN codes (Zone 1 - Gujarat Local)
            $prefix2 = (int)substr($pincode, 0, 2);
            if ($prefix2 >= 36 && $prefix2 <= 39) {
                $zoneId   = 1;
                $zoneName = 'Gujarat (Local)';
            } else {
                $zoneId   = 2;
                $zoneName = 'Rest of India';
            }
        }

        // 2. COD Availability checks
        if ($isCod) {
            $globalCod = getSetting('cod_enabled', '1') === '1';
            if (!$globalCod) {
                return ['ok' => false, 'error' => 'Cash on Delivery (COD) is temporarily disabled. Please pay online.'];
            }
            if (!$codAvailable) {
                return ['ok' => false, 'error' => 'Cash on Delivery is not available for PIN code ' . $pincode . '. Please choose online payment.'];
            }
        }

        // 3. Weight-slab rate lookup
        $rate = Database::fetchOne(
            'SELECT * FROM shipping_rates
             WHERE zone_id = ? AND weight_from_grams <= ? AND weight_to_grams >= ?
             LIMIT 1',
            [$zoneId, $weightGrams, $weightGrams]
        );

        if (!$rate) {
            // Fallback: highest weight slab
            $rate = Database::fetchOne(
                'SELECT * FROM shipping_rates
                 WHERE zone_id = ? ORDER BY weight_to_grams DESC LIMIT 1',
                [$zoneId]
            );
        }

        $baseRate = $rate ? (float)$rate['rate'] : (float)getSetting('default_shipping_charge', '60');
        $codExtra = ($isCod && $rate) ? (float)$rate['cod_extra_charge'] : 0.0;

        // 4. Free shipping threshold check
        $freeThreshold = (float)getSetting('free_shipping_threshold', '999');
        if ($freeThreshold <= 0) {
            $freeThreshold = (float)getSetting('free_shipping_above', '999');
        }

        $isFree = false;
        $shippingCharge = $baseRate;

        if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
            $isFree = true;
            $shippingCharge = 0.0;
        }

        $totalCharge = round($shippingCharge + $codExtra, 2);

        return [
            'ok'            => true,
            'rate'          => round($shippingCharge, 2),
            'base_rate'     => round($baseRate, 2),
            'cod_extra'     => round($codExtra, 2),
            'total_charge'  => $totalCharge,
            'is_free'       => $isFree,
            'free_threshold'=> $freeThreshold,
            'cod_available' => $codAvailable,
            'zone_id'       => $zoneId,
            'zone_name'     => $zoneName,
        ];
    }

    /** Check if a pincode is serviceable (for storefront pincode widget). */
    public static function isServiceable(string $pincode): array
    {
        $pincode = preg_replace('/\D/', '', $pincode);
        if (strlen($pincode) !== 6) {
            return ['ok' => false, 'error' => 'Please enter a valid 6-digit PIN code.'];
        }

        $calc = self::calculate($pincode, 500, false, 0);
        if (!$calc['ok']) {
            return $calc;
        }

        $freeThreshold = (float)getSetting('free_shipping_threshold', '999');

        return [
            'ok'            => true,
            'pincode'       => $pincode,
            'zone_name'     => $calc['zone_name'],
            'charge'        => $calc['base_rate'],
            'free_above'    => $freeThreshold,
            'cod_available' => (bool)$calc['cod_available'],
            'message'       => 'Delivery available (' . $calc['zone_name'] . ').',
        ];
    }
}
