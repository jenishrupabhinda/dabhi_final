<?php
/**
 * ShippingLabel — generates a printable HTML shipping label for an order.
 * In production, swap renderHTML to a PDF library (DOMPDF / mPDF).
 */
class ShippingLabel
{
    public static function getOrCreate(int $orderId, ?int $generatedBy = null): ?array
    {
        $label = Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$orderId]);
        if (!$label) {
            $pdfPath = '/uploads/labels/label_' . $orderId . '.pdf';
            Database::query(
                'INSERT INTO shipping_labels (order_id, label_pdf_path, generated_by)
                 VALUES (?,?,?)',
                [$orderId, $pdfPath, $generatedBy]
            );
            $label = Database::fetchOne('SELECT * FROM shipping_labels WHERE order_id = ?', [$orderId]);
        }
        return $label;
    }

    public static function updateTracking(int $orderId, string $courier, string $trackingNumber): bool
    {
        Database::query(
            'UPDATE shipping_labels SET courier_name=?, tracking_number=? WHERE order_id=?',
            [$courier, $trackingNumber, $orderId]
        );
        return true;
    }

    public static function barcodeSVG(string $code, int $height = 44): string
    {
        $code = strtoupper(trim($code));
        if (!$code) return '';
        // Clean barcode bar representation
        $bars = [2, 1, 3, 1, 1, 2, 1, 3, 2, 1];
        for ($i = 0; $i < strlen($code); $i++) {
            $val = ord($code[$i]);
            $bars[] = ($val % 3) + 1;
            $bars[] = (($val >> 1) % 3) + 1;
            $bars[] = (($val >> 3) % 2) + 1;
        }
        $bars[] = 3; $bars[] = 1; $bars[] = 2; $bars[] = 2;

        $x = 0;
        $rects = '';
        foreach ($bars as $idx => $w) {
            if ($idx % 2 === 0) {
                $rects .= "<rect x='{$x}' y='0' width='{$w}' height='{$height}' fill='#000'/>";
            }
            $x += $w;
        }
        return "<svg viewBox='0 0 {$x} {$height}' preserveAspectRatio='none' style='width:100%;max-width:320px;height:{$height}px;display:block;margin:0 auto;'>{$rects}</svg>";
    }

    public static function renderHTML(array $order, ?array $label = null): string
    {
        $addr          = $order['shipping_address'] ?? [];
        $recipientName = $addr['full_name'] ?? $order['ship_name'] ?? $order['full_name'] ?? 'Valued Customer';
        $phone         = $addr['phone'] ?? $order['ship_phone'] ?? $order['phone'] ?? '';
        $line1         = $addr['address_line1'] ?? $order['ship_line1'] ?? '';
        $line2         = $addr['address_line2'] ?? $order['ship_line2'] ?? '';
        $landmark      = $addr['landmark'] ?? '';
        $city          = $addr['city'] ?? $order['ship_city'] ?? '';
        $state         = $addr['state'] ?? $order['ship_state'] ?? '';
        $pincode       = $addr['pincode'] ?? $order['ship_pincode'] ?? '';

        $courier       = $label['courier_name'] ?? 'Delhivery / BlueDart Express';
        $tracking      = $label['tracking_number'] ?? '';
        $barcodeVal    = $tracking ?: $order['order_number'];
        $isCod         = ($order['payment_method'] ?? '') === 'cod';
        $totalAmt      = (float)($order['total_amount'] ?? $order['total'] ?? 0);
        $weightGrams   = (int)($order['total_weight_grams'] ?? 500);
        $weightText    = $weightGrams >= 1000 ? ($weightGrams / 1000) . ' kg' : $weightGrams . ' g';
        $gstin         = GST::gstin() ?? '24AAJDUDHEJ5555';
        $bizAddress    = getSetting('business_address', 'Dabhi Chikki Works, Station Road, Gujarat, India - 360001');

        $itemsSummary  = [];
        if (!empty($order['items'])) {
            foreach ($order['items'] as $it) {
                $name = $it['product_name_snapshot'] ?? $it['product_name'] ?? 'Chikki';
                $w = $it['variant_label_snapshot'] ?? $it['variant_label'] ?? '';
                $itemsSummary[] = htmlspecialchars($name . ($w ? " ($w)" : '')) . ' × ' . (int)$it['quantity'];
            }
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Shipping Label — <?= htmlspecialchars($order['order_number']) ?></title>
          <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
              font-size: 12px;
              color: #000;
              background-color: #f3f4f6;
              padding: 24px;
              display: flex;
              flex-direction: column;
              align-items: center;
            }
            .toolbar {
              display: flex;
              gap: 12px;
              margin-bottom: 20px;
              width: 100%;
              max-width: 420px;
              justify-content: space-between;
              align-items: center;
            }
            .btn {
              padding: 8px 18px;
              font-size: 13px;
              font-weight: 600;
              border-radius: 8px;
              border: 1px solid #d1d5db;
              background: #fff;
              color: #111;
              cursor: pointer;
              box-shadow: 0 1px 2px rgba(0,0,0,0.05);
              transition: all 0.15s ease;
            }
            .btn-primary {
              background: #c7613d;
              color: #fff;
              border-color: #b0502e;
            }
            .btn:hover { opacity: 0.92; }

            /* ── Label Container (Strict 4x6 / A6 Dimensions) ── */
            .label-sheet {
              width: 100%;
              max-width: 420px;
              background: #fff;
              border: 2px solid #000;
              padding: 16px;
              box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }

            .row-split {
              display: flex;
              justify-content: space-between;
              align-items: center;
              border-bottom: 2px solid #000;
              padding-bottom: 10px;
              margin-bottom: 10px;
            }
            .brand-name {
              font-size: 20px;
              font-weight: 900;
              letter-spacing: 0.05em;
              text-transform: uppercase;
            }
            .courier-badge {
              font-size: 13px;
              font-weight: 800;
              text-transform: uppercase;
              border: 1.5px solid #000;
              padding: 4px 10px;
              border-radius: 4px;
            }

            .barcode-section {
              text-align: center;
              border-bottom: 2px solid #000;
              padding-bottom: 10px;
              margin-bottom: 10px;
            }
            .tracking-text {
              font-family: monospace;
              font-size: 13px;
              font-weight: 700;
              letter-spacing: 0.15em;
              margin-top: 4px;
            }

            /* Routing grid */
            .grid-routing {
              display: grid;
              grid-template-columns: 1fr 1fr;
              border-bottom: 2px solid #000;
              margin-bottom: 10px;
            }
            .routing-cell {
              padding: 6px 8px;
            }
            .routing-cell:first-child {
              border-right: 2px solid #000;
            }
            .routing-label {
              font-size: 9px;
              font-weight: 700;
              text-transform: uppercase;
              color: #444;
            }
            .routing-val {
              font-size: 13px;
              font-weight: 800;
              margin-top: 2px;
            }

            /* Ship To Box */
            .shipto-section {
              border-bottom: 2px solid #000;
              padding-bottom: 12px;
              margin-bottom: 10px;
            }
            .shipto-tag {
              display: inline-block;
              font-size: 10px;
              font-weight: 900;
              background: #000;
              color: #fff;
              padding: 2px 6px;
              border-radius: 2px;
              margin-bottom: 6px;
            }
            .recipient-name {
              font-size: 16px;
              font-weight: 800;
              line-height: 1.2;
              margin-bottom: 4px;
            }
            .recipient-address {
              font-size: 12px;
              line-height: 1.4;
              color: #111;
            }
            .pincode-highlight {
              display: flex;
              align-items: center;
              justify-content: space-between;
              margin-top: 8px;
              padding-top: 6px;
              border-top: 1px dashed #666;
            }
            .pincode-number {
              font-size: 26px;
              font-weight: 900;
              letter-spacing: 0.12em;
              line-height: 1;
            }
            .recipient-phone {
              font-size: 13px;
              font-weight: 700;
            }

            /* Payment & Order details */
            .payment-banner {
              text-align: center;
              border: 2px solid #000;
              padding: 8px 10px;
              margin-bottom: 10px;
              background: #fff;
            }
            .payment-banner.is-cod {
              background: #000;
              color: #fff;
            }
            .payment-title {
              font-size: 15px;
              font-weight: 900;
              letter-spacing: 0.04em;
            }
            .payment-sub {
              font-size: 11px;
              margin-top: 2px;
              font-weight: 600;
            }

            /* Return address & Items */
            .footer-info {
              display: flex;
              justify-content: space-between;
              font-size: 10px;
              line-height: 1.35;
              color: #222;
            }
            .return-address {
              max-width: 60%;
            }
            .order-meta {
              text-align: right;
              max-width: 38%;
            }

            @media print {
              body {
                background: #fff;
                padding: 0;
                margin: 0;
              }
              .toolbar { display: none !important; }
              .label-sheet {
                box-shadow: none;
                border: 2px solid #000;
                width: 100%;
                max-width: 100%;
                margin: 0;
              }
              @page {
                size: auto;
                margin: 4mm;
              }
            }
          </style>
        </head>
        <body>

          <!-- Top Toolbar (Screen only) -->
          <div class="toolbar">
            <span style="font-size:12px;color:#555;">Order <strong>#<?= htmlspecialchars($order['order_number']) ?></strong></span>
            <div style="display:flex;gap:8px;">
              <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print Label</button>
              <button type="button" class="btn" onclick="window.close()">✕ Close</button>
            </div>
          </div>

          <!-- Printable Shipping Label -->
          <div class="label-sheet">

            <!-- Row 1: Brand & Courier -->
            <div class="row-split">
              <div>
                <div class="brand-name">Dabhi Chikki</div>
                <div style="font-size:9px;color:#444;font-weight:600;margin-top:2px;">GSTIN: <?= htmlspecialchars($gstin) ?></div>
              </div>
              <div class="courier-badge">
                <?= htmlspecialchars($courier) ?>
              </div>
            </div>

            <!-- Row 2: Barcode & Tracking Number -->
            <div class="barcode-section">
              <?= self::barcodeSVG($barcodeVal, 46) ?>
              <div class="tracking-text">
                <?= htmlspecialchars($tracking ?: $order['order_number']) ?>
              </div>
            </div>

            <!-- Row 3: Routing Grid (City & Weight) -->
            <div class="grid-routing">
              <div class="routing-cell">
                <div class="routing-label">Destination City / Hub</div>
                <div class="routing-val"><?= htmlspecialchars(strtoupper($city ?: 'GUJARAT')) ?></div>
              </div>
              <div class="routing-cell">
                <div class="routing-label">Total Weight</div>
                <div class="routing-val"><?= htmlspecialchars($weightText) ?></div>
              </div>
            </div>

            <!-- Row 4: Ship To -->
            <div class="shipto-section">
              <span class="shipto-tag">SHIP TO</span>
              <div class="recipient-name"><?= htmlspecialchars($recipientName) ?></div>
              <div class="recipient-address">
                <?= htmlspecialchars($line1) ?>
                <?php if (!empty($line2)): ?>, <?= htmlspecialchars($line2) ?><?php endif; ?>
                <?php if (!empty($landmark)): ?><br>Near: <?= htmlspecialchars($landmark) ?><?php endif; ?>
                <br><?= htmlspecialchars($city) ?><?php if (!empty($state)): ?>, <?= htmlspecialchars($state) ?><?php endif; ?>
              </div>
              <div class="pincode-highlight">
                <div>
                  <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#555;">PINCODE</div>
                  <div class="pincode-number"><?= htmlspecialchars($pincode) ?></div>
                </div>
                <div style="text-align:right;">
                  <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#555;">CONTACT NUMBER</div>
                  <div class="recipient-phone">📞 <?= htmlspecialchars($phone ?: '—') ?></div>
                </div>
              </div>
            </div>

            <!-- Row 5: Payment Badge -->
            <?php if ($isCod): ?>
              <div class="payment-banner is-cod">
                <div class="payment-title">CASH ON DELIVERY (COD): ₹<?= number_format($totalAmt, 2) ?></div>
                <div class="payment-sub">PLEASE COLLECT EXACT CASH FROM CUSTOMER</div>
              </div>
            <?php else: ?>
              <div class="payment-banner">
                <div class="payment-title">PREPAID ORDER</div>
                <div class="payment-sub">DO NOT COLLECT ANY CASH FROM RECIPIENT</div>
              </div>
            <?php endif; ?>

            <!-- Row 6: Return Address & Order Items -->
            <div class="footer-info">
              <div class="return-address">
                <strong>RETURN IF UNDELIVERED TO:</strong><br>
                <?= nl2br(htmlspecialchars($bizAddress)) ?>
              </div>
              <div class="order-meta">
                <strong>ORDER #<?= htmlspecialchars($order['order_number']) ?></strong><br>
                Date: <?= !empty($order['placed_at']) ? date('d-m-Y', strtotime($order['placed_at'])) : date('d-m-Y') ?><br>
                <?php if (!empty($itemsSummary)): ?>
                  Items: <?= implode(', ', array_slice($itemsSummary, 0, 2)) ?>
                <?php endif; ?>
              </div>
            </div>

          </div>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
