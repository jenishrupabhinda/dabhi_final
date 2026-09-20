-- =====================================================================
--  DABHI CHIKKI — SEED DATA
--  Run this AFTER schema.sql, once, on a fresh database.
--  NOTE: This does NOT create a superadmin login. Run
--  database/create_superadmin.php from the command line for that —
--  keeping the first password out of version control/SQL files is safer.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Permission checkboxes superadmin can toggle for an admin, and an
-- admin can toggle for an employee. Add rows here any time you need a
-- new toggle — no code change required for the toggle to exist, only
-- for what it actually gates.
-- ---------------------------------------------------------------------
INSERT INTO permissions (permission_key, label, category, applies_to_role, description) VALUES
('view_financials',        'View financial reports & revenue',      'financial',  'admin',  'Sales totals, revenue, profit margins'),
('manage_gst',             'Manage GST settings & filings',         'financial',  'admin',  'GST rate config and GST reports'),
('view_product_analytics', 'View product performance analytics',    'analytics',  'both',   'Best sellers, slow movers'),
('manage_products',        'Add / edit products',                   'products',   'both',   ''),
('manage_prices',          'Edit product pricing',                  'products',   'admin',  'Separate from general product editing'),
('manage_categories',      'Add / edit / toggle categories',        'products',   'admin',  ''),
('manage_inventory',       'Receive stock & manage batches',        'inventory',  'both',   ''),
('view_expiry_alerts',     'View near-expiry stock alerts',         'inventory',  'both',   ''),
('manage_orders',          'View & update order status',            'orders',     'both',   ''),
('print_shipping_labels',  'Generate / print shipping labels',      'orders',     'both',   ''),
('manage_shipping_rules',  'Manage shipping zones & rates',         'settings',   'admin',  ''),
('manage_notifications',   'Toggle email/WhatsApp/SMS settings',    'settings',   'admin',  ''),
('manage_payment_settings','Toggle COD / configure Cashfree',       'settings',   'admin',  ''),
('manage_coupons',         'Create & manage discount coupons',      'marketing',  'both',   ''),
('manage_reviews',         'Approve / reject product reviews',      'marketing',  'both',   ''),
('generate_reports',       'Generate custom/weekly/monthly reports','financial',  'admin',  ''),
('manage_employees',       'Add employees & reset their passwords', 'users',      'admin',  ''),
('manage_admins',          'Add admins & reset their passwords',    'users',      'admin',  'Superadmin always has this; admin needs it explicitly granted'),
('view_audit_log',         'View the admin/employee activity log', 'users',      'admin',  '');

-- ---------------------------------------------------------------------
-- Site-wide feature toggles (all the on/off switches from the brief)
-- ---------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_theme',                 'light'),
('cod_enabled',                '1'),
('online_payment_enabled',     '1'),
('cashfree_mode',              'test'),          -- 'test' or 'live' — dev-safe default
('whatsapp_notifications_enabled', '0'),
('sms_notifications_enabled',      '0'),
('email_notifications_enabled',    '1'),
('free_shipping_threshold',    '999'),           -- INR; 0 disables free shipping
('build_box_weight_limit_grams','2000'),
('low_stock_default_threshold','10'),
('expiry_alert_days',          '30'),            -- flag batches expiring within N days
('site_maintenance_mode',      '0');

-- ---------------------------------------------------------------------
-- Starter category tree — client can rename/toggle/add via admin panel
-- ---------------------------------------------------------------------
INSERT INTO categories (id, parent_id, name, slug, is_active, sort_order) VALUES
(1, NULL, 'Chikki',    'chikki',    1, 1),
(2, NULL, 'Sweets',    'sweets',    1, 2),
(3, NULL, 'Namkeen',   'namkeen',   1, 3),
(4, NULL, 'Combos',    'combos',    1, 4),
(5, NULL, 'Gifts',     'gifts',     1, 5);

INSERT INTO categories (parent_id, name, slug, is_active, sort_order) VALUES
(1, 'Gud Chikki',      'gud-chikki',      1, 1),
(1, 'Sugar Chikki',    'sugar-chikki',    1, 2),
(1, 'Til Chikki',      'til-chikki',      1, 3),
(1, 'Dryfruit Chikki', 'dryfruit-chikki', 1, 4);

-- ---------------------------------------------------------------------
-- Example shipping zone + weight-slab rates (client edits via admin panel)
-- ---------------------------------------------------------------------
INSERT INTO shipping_zones (id, name, description) VALUES
(1, 'Gujarat (Local)', 'Same-state delivery'),
(2, 'Rest of India',   'All other serviceable pincodes');

INSERT INTO shipping_rates (zone_id, weight_from_grams, weight_to_grams, rate, cod_extra_charge) VALUES
(1, 0,   250,  30.00, 20.00),
(1, 251, 500,  45.00, 20.00),
(1, 501, 1000, 65.00, 20.00),
(1, 1001, 2000, 90.00, 20.00),
(2, 0,   250,  50.00, 25.00),
(2, 251, 500,  70.00, 25.00),
(2, 501, 1000, 100.00, 25.00),
(2, 1001, 2000, 140.00, 25.00);

-- ---------------------------------------------------------------------
-- GST business profile — client fills real GSTIN in admin panel later
-- ---------------------------------------------------------------------
INSERT INTO gst_settings (business_name, gstin, business_state, is_gst_enabled) VALUES
('Dabhi Chikki', NULL, 'Gujarat', 1);

-- ---------------------------------------------------------------------
-- Invoice counter for the current Indian financial year (Apr–Mar)
-- ---------------------------------------------------------------------
INSERT INTO invoice_counters (financial_year, last_number) VALUES ('2026-2027', 0);
