<?php
/**
 * Invoice — sequential invoice number generation (locked row for concurrency safety)
 * and plain-HTML invoice rendering (PDF generation via browser print / future DOMPDF).
 */
class Invoice
{
    /** Return e.g. "DC/2026-27/000123" — increments inside a row-level lock. */
    public static function generate(int $orderId): array
    {
        $fy    = currentFinancialYear(); // e.g. "2026-2027"
        $ownTx = !Database::inTransaction();

        try {
            if ($ownTx) {
                Database::beginTransaction();
            }

            // Upsert counter row with row lock
            Database::query(
                "INSERT INTO invoice_counters (financial_year, last_number) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE last_number = last_number + 1",
                [$fy]
            );
            $row     = Database::fetchOne(
                'SELECT last_number FROM invoice_counters WHERE financial_year = ? FOR UPDATE',
                [$fy]
            );
            $num     = (int)$row['last_number'];
            $short   = substr($fy, 2, 2) . '-' . substr($fy, 7, 2); // "26-27"
            $invNum  = "DC/{$short}/" . str_pad($num, 6, '0', STR_PAD_LEFT);

            // PDF path (stub — in prod generate real PDF)
            $pdfPath = '/uploads/invoices/' . str_replace('/', '_', $invNum) . '.pdf';

            Database::query(
                'INSERT INTO invoices (order_id, invoice_number, financial_year, pdf_path) VALUES (?,?,?,?)',
                [$orderId, $invNum, $fy, $pdfPath]
            );

            if ($ownTx) {
                Database::commit();
            }
            return ['ok' => true, 'invoice_number' => $invNum, 'pdf_path' => $pdfPath];

        } catch (\Throwable $e) {
            if ($ownTx) {
                Database::rollback();
            }
            error_log('Invoice::generate error: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Invoice generation failed.'];
        }
    }

    /** Return invoice data for an order (creates one if missing). */
    public static function getOrCreate(int $orderId): ?array
    {
        $inv = Database::fetchOne('SELECT * FROM invoices WHERE order_id = ?', [$orderId]);
        if (!$inv) {
            $result = self::generate($orderId);
            if (!$result['ok']) return null;
            $inv = Database::fetchOne('SELECT * FROM invoices WHERE order_id = ?', [$orderId]);
        }
        return $inv;
    }

    /** Render an invoice as HTML (for browser print → PDF). */
    public static function renderHTML(array $order, array $invoice): string
    {
        $gstSettings = Database::fetchOne('SELECT * FROM gst_settings LIMIT 1') ?? [];
        $addr  = $order['shipping_address'];
        $isIntra = strtolower($addr['state'] ?? '') === strtolower($gstSettings['business_state'] ?? '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
          <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 20px; }
            .inv-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
            .inv-title { font-size: 22px; font-weight: bold; color: #C44D32; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            th, td { border: 1px solid #ddd; padding: 7px 10px; text-align: left; }
            th { background: #f5f1e8; font-weight: bold; }
            .total-row td { font-weight: bold; background: #f5f1e8; }
            .text-right { text-align: right; }
            .section-label { font-size: 10px; color: #888; font-weight: bold; text-transform: uppercase; margin-top: 14px; margin-bottom: 4px; }
            @media print { body { margin: 0; } button { display: none; } }
          </style>
        </head>
        <body>
          <div class="inv-header">
            <div>
              <div class="inv-title">DABHI CHIKKI</div>
              <div><?= htmlspecialchars($gstSettings['business_name'] ?? 'Dabhi Chikki') ?></div>
              <?php if (!empty($gstSettings['gstin'])): ?>
              <div>GSTIN: <?= htmlspecialchars($gstSettings['gstin']) ?></div>
              <?php endif; ?>
              <div><?= htmlspecialchars($gstSettings['address'] ?? '') ?></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:16px;font-weight:bold;">TAX INVOICE</div>
              <div><strong>Invoice No:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
              <div><strong>Order No:</strong> <?= htmlspecialchars($order['order_number']) ?></div>
              <div><strong>Date:</strong> <?= date('d M Y', strtotime($order['placed_at'])) ?></div>
            </div>
          </div>

          <div class="section-label">Bill / Ship To</div>
          <div>
            <strong><?= htmlspecialchars($addr['full_name'] ?? '') ?></strong><br>
            <?= htmlspecialchars($addr['address_line1'] ?? '') ?>
            <?= $addr['address_line2'] ? ', ' . htmlspecialchars($addr['address_line2']) : '' ?><br>
            <?= htmlspecialchars($addr['city'] ?? '') ?>, <?= htmlspecialchars($addr['state'] ?? '') ?> – <?= htmlspecialchars($addr['pincode'] ?? '') ?><br>
            Ph: <?= htmlspecialchars($addr['phone'] ?? $order['phone']) ?>
          </div>

          <div class="section-label" style="margin-top:20px;">Items</div>
          <table>
            <tr>
              <th>#</th><th>Item</th><th>Weight</th><th>HSN</th><th>Qty</th>
              <th class="text-right">Rate</th><th class="text-right">GST</th><th class="text-right">Amount</th>
            </tr>
            <?php foreach ($order['items'] as $i => $item): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($item['product_name_snapshot']) ?></td>
              <td><?= htmlspecialchars($item['variant_label_snapshot']) ?></td>
              <td>—</td>
              <td><?= $item['quantity'] ?></td>
              <td class="text-right">₹<?= number_format($item['unit_price'], 2) ?></td>
              <td class="text-right">₹<?= number_format($item['gst_amount'], 2) ?></td>
              <td class="text-right">₹<?= number_format($item['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
              <td colspan="7" class="text-right">Subtotal</td>
              <td class="text-right">₹<?= number_format($order['subtotal'], 2) ?></td>
            </tr>
            <?php if ((float)$order['discount_amount'] > 0): ?>
            <tr>
              <td colspan="7" class="text-right">Discount</td>
              <td class="text-right">−₹<?= number_format($order['discount_amount'], 2) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
              <td colspan="7" class="text-right">Shipping</td>
              <td class="text-right">₹<?= number_format($order['shipping_charge'], 2) ?></td>
            </tr>
            <?php if ((float)$order['igst_amount'] > 0): ?>
            <tr><td colspan="7" class="text-right">IGST</td><td class="text-right">₹<?= number_format($order['igst_amount'], 2) ?></td></tr>
            <?php else: ?>
            <tr><td colspan="7" class="text-right">CGST</td><td class="text-right">₹<?= number_format($order['cgst_amount'], 2) ?></td></tr>
            <tr><td colspan="7" class="text-right">SGST</td><td class="text-right">₹<?= number_format($order['sgst_amount'], 2) ?></td></tr>
            <?php endif; ?>
            <tr class="total-row">
              <td colspan="7" class="text-right">GRAND TOTAL</td>
              <td class="text-right">₹<?= number_format($order['total_amount'], 2) ?></td>
            </tr>
          </table>

          <div style="margin-top:20px;font-size:10px;color:#888;">
            <?= $isIntra ? 'Intra-state supply (CGST + SGST applicable)' : 'Inter-state supply (IGST applicable)' ?> ·
            Subject to jurisdiction of <?= htmlspecialchars($gstSettings['business_state'] ?? '') ?> courts.
          </div>
          <div style="margin-top:8px;"><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
