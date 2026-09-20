-- =====================================================================
--  DABHI CHIKKI  —  E-COMMERCE DATABASE SCHEMA
-- =====================================================================
--  Engine   : InnoDB (foreign keys + transactions required throughout)
--  Charset  : utf8mb4 (full emoji / multi-language support - Gujarati/Hindi)
--  Currency : all money columns are DECIMAL(10,2), stored in INR
--  Weights  : always stored in GRAMS (int) internally so shipping/box-limit
--             math never has to deal with unit conversion bugs.
--
--  Import order matters because of foreign keys. Import this file as-is,
--  top to bottom, on a fresh database. Then run seed.sql.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- SECTION 1: USERS, ROLES & GRANULAR PERMISSIONS
-- =====================================================================
-- Design note: role = the coarse bucket (superadmin/admin/employee/buyer).
-- Fine-grained "can this specific admin see financials?" control lives in
-- user_permissions, which superadmin sets for admins, and admins set for
-- their employees. This is what gives you the toggle-per-feature behaviour
-- you described, without needing a separate table per feature.

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            CHAR(36) NOT NULL UNIQUE,               -- public-safe identifier (never expose auto-increment id in URLs)
    role            ENUM('superadmin','admin','employee','buyer') NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    phone           VARCHAR(15)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at   DATETIME NULL,
    phone_verified_at   DATETIME NULL,
    must_reset_password TINYINT(1) NOT NULL DEFAULT 0,       -- forced reset after admin/employee creation
    created_by      INT UNSIGNED NULL,                       -- which admin/superadmin created this account (staff only)
    last_login_at   DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone (phone),
    KEY idx_users_role (role),
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key  VARCHAR(80) NOT NULL UNIQUE,             -- e.g. 'view_financials', 'manage_products'
    label           VARCHAR(150) NOT NULL,                   -- human readable, shown as a checkbox label
    category        VARCHAR(50) NOT NULL,                    -- groups checkboxes in the UI: 'financial','inventory','orders','products','marketing','settings','users'
    applies_to_role ENUM('admin','employee','both') NOT NULL DEFAULT 'both',
    description     VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_permissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    permission_id   INT UNSIGNED NOT NULL,
    is_enabled      TINYINT(1) NOT NULL DEFAULT 0,
    granted_by      INT UNSIGNED NULL,                       -- who flipped this toggle
    granted_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_permission (user_id, permission_id),
    CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_up_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_up_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    token_hash      VARCHAR(255) NOT NULL,
    expires_at      DATETIME NOT NULL,
    used_at         DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pr_user (user_id),
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE otp_verifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier      VARCHAR(190) NOT NULL,                   -- email or phone
    purpose         ENUM('login','signup','checkout','password_reset') NOT NULL,
    otp_hash        VARCHAR(255) NOT NULL,
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at      DATETIME NOT NULL,
    verified_at     DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_otp_identifier (identifier, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE addresses (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    phone           VARCHAR(15) NOT NULL,
    address_line1   VARCHAR(255) NOT NULL,
    address_line2   VARCHAR(255) NULL,
    landmark        VARCHAR(150) NULL,
    city            VARCHAR(100) NOT NULL,
    state           VARCHAR(100) NOT NULL,
    pincode         VARCHAR(10) NOT NULL,
    country         VARCHAR(60) NOT NULL DEFAULT 'India',
    address_type    ENUM('home','office','other') NOT NULL DEFAULT 'home',
    is_default      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_addr_user (user_id),
    KEY idx_addr_pincode (pincode),
    CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NULL,                       -- who did it (null = system)
    action          VARCHAR(100) NOT NULL,                   -- e.g. 'price_updated', 'permission_toggled', 'stock_adjusted'
    entity_type     VARCHAR(60) NOT NULL,                    -- 'product','order','user','setting'
    entity_id       INT UNSIGNED NULL,
    old_value       JSON NULL,
    new_value       JSON NULL,
    ip_address      VARCHAR(45) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_user (user_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 2: CATALOG — CATEGORIES, PRODUCTS, VARIANTS, IMAGES
-- =====================================================================
-- parent_id supports one level of subcategory (Chikki -> Gud Chikki) but
-- is self-referencing so deeper nesting works if the client ever needs it.
-- is_active is the on/off toggle the client uses to hide a whole category.

CREATE TABLE categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NULL,
    name            VARCHAR(120) NOT NULL,
    slug            VARCHAR(140) NOT NULL UNIQUE,
    description     TEXT NULL,
    image_path      VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    sort_order      SMALLINT NOT NULL DEFAULT 0,
    meta_title      VARCHAR(160) NULL,
    meta_description VARCHAR(320) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cat_parent (parent_id),
    CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    name            VARCHAR(180) NOT NULL,
    slug            VARCHAR(200) NOT NULL UNIQUE,
    short_description VARCHAR(300) NULL,
    description     TEXT NULL,
    hsn_code        VARCHAR(10) NULL,                        -- required for GST invoices
    gst_rate_percent DECIMAL(4,2) NOT NULL DEFAULT 5.00,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    meta_title      VARCHAR(160) NULL,
    meta_description VARCHAR(320) NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_prod_category (category_id),
    FULLTEXT KEY ftx_prod_search (name, short_description, description),
    CONSTRAINT fk_prod_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT fk_prod_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A "variant" is a sellable weight pack (250g / 500g / 1kg) of a product.
-- Stock, price and SKU always live here, never on the product itself.
CREATE TABLE product_variants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    sku             VARCHAR(60) NOT NULL UNIQUE,
    weight_grams    INT UNSIGNED NOT NULL,                   -- always grams; 1kg = 1000
    mrp             DECIMAL(10,2) NOT NULL,
    selling_price   DECIMAL(10,2) NOT NULL,
    reorder_level   INT UNSIGNED NOT NULL DEFAULT 10,        -- triggers low-stock alert
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_variant_product (product_id),
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_images (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    image_path      VARCHAR(255) NOT NULL,
    alt_text        VARCHAR(150) NULL,                       -- SEO: image alt attribute
    sort_order      SMALLINT NOT NULL DEFAULT 0,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_pimg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    order_item_id   INT UNSIGNED NULL,                       -- set => "verified purchase"
    rating          TINYINT UNSIGNED NOT NULL,
    comment         TEXT NULL,
    image_path      VARCHAR(255) NULL,
    is_approved     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_review_product (product_id),
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 3: INVENTORY — BATCHES, EXPIRY, FIFO, STOCK MOVEMENTS
-- =====================================================================
-- Every unit of stock belongs to a batch. quantity_remaining is decremented
-- as sales/adjustments happen; the app always sells from the batch with the
-- EARLIEST expiry_date first (FIFO), which is a simple ORDER BY at query time.

CREATE TABLE inventory_batches (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id      INT UNSIGNED NOT NULL,
    batch_number    VARCHAR(60) NOT NULL,
    manufacture_date DATE NULL,
    expiry_date     DATE NOT NULL,
    quantity_received  INT UNSIGNED NOT NULL,
    quantity_remaining INT UNSIGNED NOT NULL,
    cost_price      DECIMAL(10,2) NULL,
    supplier_name   VARCHAR(150) NULL,
    received_by     INT UNSIGNED NULL,
    received_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes           VARCHAR(255) NULL,
    UNIQUE KEY uq_batch (variant_id, batch_number),
    KEY idx_batch_expiry (expiry_date),
    KEY idx_batch_variant_remaining (variant_id, quantity_remaining),
    CONSTRAINT fk_batch_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_batch_received_by FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_movements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id      INT UNSIGNED NOT NULL,
    batch_id        INT UNSIGNED NULL,
    movement_type   ENUM('purchase_in','sale_out','adjustment_in','adjustment_out','return_in','damage_out') NOT NULL,
    quantity        INT NOT NULL,                            -- positive number; direction implied by movement_type
    reference_type  VARCHAR(40) NULL,                        -- 'order','manual','return'
    reference_id    INT UNSIGNED NULL,
    performed_by    INT UNSIGNED NULL,
    notes           VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_stockmove_variant (variant_id),
    KEY idx_stockmove_batch (batch_id),
    CONSTRAINT fk_stockmove_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_stockmove_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(id) ON DELETE SET NULL,
    CONSTRAINT fk_stockmove_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 4: SHIPPING — WEIGHT SLABS + PINCODE SERVICEABILITY
-- =====================================================================

CREATE TABLE shipping_zones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,                   -- e.g. 'Local (Gujarat)', 'Rest of India', 'North-East / J&K'
    description     VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pincode_zones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pincode         VARCHAR(10) NOT NULL UNIQUE,
    zone_id         INT UNSIGNED NOT NULL,
    is_serviceable  TINYINT(1) NOT NULL DEFAULT 1,
    cod_available   TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_pz_zone (zone_id),
    CONSTRAINT fk_pz_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Weight-slab pricing per zone, e.g. zone 1, 0-250g = ₹40; 251-500g = ₹60 ...
CREATE TABLE shipping_rates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id         INT UNSIGNED NOT NULL,
    weight_from_grams INT UNSIGNED NOT NULL,
    weight_to_grams   INT UNSIGNED NOT NULL,
    rate            DECIMAL(8,2) NOT NULL,
    cod_extra_charge DECIMAL(8,2) NOT NULL DEFAULT 0,
    KEY idx_rate_zone_weight (zone_id, weight_from_grams, weight_to_grams),
    CONSTRAINT fk_rate_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 5: CART, BUILD-YOUR-BOX, COUPONS
-- =====================================================================

CREATE TABLE carts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NULL,                       -- null while guest
    session_token   VARCHAR(64) NULL,                        -- guest cart identifier (cookie-based)
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cart_user (user_id),
    KEY idx_cart_session (session_token),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- box_group_id lets several cart_items be tagged as belonging to the same
-- custom "Build Your Box" bundle so the UI can show them grouped together
-- and price/weight them as one unit if you ever add box-level pricing.
CREATE TABLE cart_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id         INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NOT NULL,
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    box_group_id    CHAR(36) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ci_cart (cart_id),
    KEY idx_ci_box_group (box_group_id),
    CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ci_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Saved "Build Your Box" templates a buyer can re-order (gifting/corporate).
CREATE TABLE build_boxes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    box_name        VARCHAR(120) NOT NULL,
    weight_limit_grams INT UNSIGNED NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE build_box_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    build_box_id    INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NOT NULL,
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_bbi_box FOREIGN KEY (build_box_id) REFERENCES build_boxes(id) ON DELETE CASCADE,
    CONSTRAINT fk_bbi_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coupons (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(40) NOT NULL UNIQUE,
    description     VARCHAR(255) NULL,
    discount_type   ENUM('flat','percent') NOT NULL,
    discount_value  DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount_amount DECIMAL(10,2) NULL,
    usage_limit_total   INT UNSIGNED NULL,
    usage_limit_per_user SMALLINT UNSIGNED NULL,
    valid_from      DATETIME NOT NULL,
    valid_to        DATETIME NOT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coupon_usage (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id       INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    used_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cu_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    CONSTRAINT fk_cu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 6: ORDERS, PAYMENTS, INVOICES, SHIPPING LABELS
-- =====================================================================

CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(30) NOT NULL UNIQUE,             -- e.g. DC-2026-000482, human-facing
    user_id         INT UNSIGNED NOT NULL,
    status          ENUM('placed','confirmed','packed','shipped','out_for_delivery','delivered','cancelled','return_requested','returned') NOT NULL DEFAULT 'placed',
    payment_method  ENUM('cod','online') NOT NULL,
    payment_status  ENUM('pending','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
    subtotal        DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_id       INT UNSIGNED NULL,
    shipping_charge DECIMAL(8,2) NOT NULL DEFAULT 0,
    cod_charge      DECIMAL(8,2) NOT NULL DEFAULT 0,
    cgst_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    sgst_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    igst_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    total_weight_grams INT UNSIGNED NOT NULL,
    shipping_address_id INT UNSIGNED NOT NULL,
    billing_address_id  INT UNSIGNED NOT NULL,
    customer_notes  VARCHAR(255) NULL,
    placed_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_order_user (user_id),
    KEY idx_order_status (status),
    KEY idx_order_placed_at (placed_at),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_ship_addr FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_bill_addr FOREIGN KEY (billing_address_id) REFERENCES addresses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- batch_id here is what makes FIFO + traceability real: at the moment of
-- order confirmation, the app allocates stock from the oldest-expiry batch
-- and records exactly which batch fulfilled this line.
CREATE TABLE order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    variant_id      INT UNSIGNED NOT NULL,
    batch_id        INT UNSIGNED NULL,
    product_name_snapshot   VARCHAR(180) NOT NULL,           -- frozen at time of order (product may change later)
    variant_label_snapshot  VARCHAR(60) NOT NULL,             -- e.g. "500 g"
    quantity        INT UNSIGNED NOT NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    gst_rate_percent DECIMAL(4,2) NOT NULL,
    gst_amount      DECIMAL(10,2) NOT NULL,
    line_total      DECIMAL(10,2) NOT NULL,
    box_group_id    CHAR(36) NULL,                           -- preserves Build-Your-Box grouping on the invoice
    KEY idx_oi_order (order_id),
    KEY idx_oi_variant (variant_id),
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT,
    CONSTRAINT fk_oi_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- This IS the buyer-facing tracking timeline: every status change, and who
-- (owner/employee) made it, exactly as you described.
CREATE TABLE order_status_history (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    status          VARCHAR(30) NOT NULL,
    remarks         VARCHAR(255) NULL,
    changed_by      INT UNSIGNED NULL,                       -- null = system/automated (e.g. payment webhook)
    changed_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_osh_order (order_id),
    CONSTRAINT fk_osh_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_osh_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Guarantees legally-required sequential invoice numbers per Indian
-- financial year (Apr–Mar), e.g. DC/2026-27/000123. Incremented inside a
-- transaction with a row lock — see includes/classes/Invoice.php.
CREATE TABLE invoice_counters (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    financial_year  VARCHAR(9) NOT NULL UNIQUE,              -- '2026-2027'
    last_number     INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL UNIQUE,
    invoice_number  VARCHAR(40) NOT NULL UNIQUE,
    financial_year  VARCHAR(9) NOT NULL,
    pdf_path        VARCHAR(255) NOT NULL,
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inv_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shipping_labels (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL UNIQUE,
    courier_name    VARCHAR(80) NULL,
    tracking_number VARCHAR(80) NULL,
    label_pdf_path  VARCHAR(255) NOT NULL,
    generated_by    INT UNSIGNED NULL,
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_label_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_label_user FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    gateway         ENUM('cashfree','cod') NOT NULL,
    gateway_order_id    VARCHAR(100) NULL,
    gateway_payment_id  VARCHAR(100) NULL,
    amount          DECIMAL(10,2) NOT NULL,
    currency        VARCHAR(5) NOT NULL DEFAULT 'INR',
    status          ENUM('created','pending','success','failed','refunded') NOT NULL DEFAULT 'created',
    raw_response    LONGTEXT NULL,                           -- full JSON from Cashfree, for support/debugging
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pay_order (order_id),
    CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NULL,
    user_id         INT UNSIGNED NULL,
    channel         ENUM('email','whatsapp','sms') NOT NULL,
    event_type      VARCHAR(60) NOT NULL,                    -- 'order_confirmed','order_shipped','order_delivered'...
    recipient       VARCHAR(190) NOT NULL,
    status          ENUM('sent','failed','skipped_disabled') NOT NULL,
    response_message TEXT NULL,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_order (order_id),
    CONSTRAINT fk_notif_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 7: SITE-WIDE SETTINGS & GST CONFIG
-- =====================================================================
-- Generic key/value store for every on/off toggle mentioned in the brief:
-- whatsapp_enabled, sms_enabled, cod_enabled, gst_enabled, free_shipping_threshold,
-- box_weight_limit_grams, etc. Keeps adding new toggles a data change, not a
-- schema change.

CREATE TABLE settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(80) NOT NULL UNIQUE,
    setting_value   TEXT NULL,
    updated_by      INT UNSIGNED NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gst_settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name   VARCHAR(180) NOT NULL,
    gstin           VARCHAR(20) NULL,
    business_state  VARCHAR(60) NOT NULL,                    -- used to decide CGST+SGST vs IGST on each order
    address         VARCHAR(255) NULL,
    is_gst_enabled  TINYINT(1) NOT NULL DEFAULT 1,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECTION 8: NAVIGATION MENUS & STOREFRONT SUBMENUS
-- =====================================================================

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `icon` VARCHAR(50) NULL DEFAULT NULL,
    `badge` VARCHAR(50) NULL DEFAULT NULL,
    `badge_color` VARCHAR(50) NULL DEFAULT NULL,
    `subtitle` VARCHAR(150) NULL DEFAULT NULL,
    `target` VARCHAR(20) NOT NULL DEFAULT '_self',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `location` VARCHAR(50) NOT NULL DEFAULT 'primary',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_parent_sort` (`parent_id`, `sort_order`),
    KEY `idx_location_active` (`location`, `is_active`),
    CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default permission
INSERT IGNORE INTO permissions (permission_key, label, category, applies_to_role, description)
VALUES ('manage_menus', 'Manage navigation menus & submenus', 'storefront', 'both', 'Create, edit, toggle visibility, and delete storefront menu items and dropdown submenus');

-- Seed default storefront navigation items
INSERT INTO `menu_items` (`id`, `parent_id`, `title`, `url`, `icon`, `badge`, `badge_color`, `subtitle`, `sort_order`, `is_active`, `location`) VALUES
(1, NULL, 'Home', 'index.php', NULL, NULL, NULL, NULL, 1, 1, 'primary'),
(2, NULL, 'Our Chikki', 'index.php#products', NULL, NULL, NULL, NULL, 2, 1, 'primary'),
(3, 2, 'Mandvi Peanut Chikki', 'product.php?slug=mandvi-chikki', '🥜', 'Bestseller', '#c7613d', 'Classic Saurashtra crunch', 1, 1, 'primary'),
(4, 2, 'TIL Sesame Chikki', 'product.php?slug=til-chikki', '⚪', 'Calcium', 'rgba(255,255,255,0.15)', 'Rich in natural calcium', 2, 1, 'primary'),
(5, 2, 'Daliya Gram Chikki', 'product.php?slug=daliya-chikki', '🌾', 'Crispy', 'rgba(255,255,255,0.15)', 'Crispy roasted chickpea', 3, 1, 'primary'),
(6, 2, '3 Mix Signature Box', 'product.php?slug=3-mix-chikki', '✨', 'Signature', '#f6dc94', 'Peanut, Til & Coconut', 4, 1, 'primary'),
(7, 2, 'Explore All 4 Flavors', 'index.php#products', '→', '', '', 'View full chikki collection', 5, 1, 'primary'),
(8, NULL, 'Our Craft', 'index.php#our-story', NULL, NULL, NULL, NULL, 3, 1, 'primary'),
(9, NULL, 'Reels', 'index.php#reels-section', '🔴', NULL, NULL, NULL, 4, 1, 'primary'),
(10, NULL, 'Reviews', 'index.php#reviews', NULL, NULL, NULL, NULL, 5, 1, 'primary'),
(11, NULL, 'Track Order', 'track.php', '📦', NULL, NULL, NULL, 6, 1, 'primary');

SET FOREIGN_KEY_CHECKS = 1;

