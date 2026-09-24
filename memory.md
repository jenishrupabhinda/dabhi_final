# DABHI CHIKKI — Comprehensive Project Memory (`memory.md`)

> **Note for AI Assistant**: Read this file first when working on the `Dabhi_final` repository. It contains the complete architectural blueprint, file index, database schema, design rules, authentication details, and common pitfalls so you do NOT need to scan the entire filesystem on each turn.

---

## 1. Project Identity & Context

- **Brand**: Dabhi Chikki (Authentic Handmade Chikki with Pure Jaggery Since 2009, Rajkot, Gujarat).
- **Core Value Proposition**: 100% pure jaggery, natural ingredients, no liquid glucose, no refined sugar, handcrafted crunch.
- **Repository / Directory**: `c:\wamp64\www\Dabhi_final`
- **Local Dev URL**: `http://localhost/dabhi_final/public_html/`
- **Git Branch**: `Menu`
- **Git Remote**: `https://github.com/jenishrupabhinda/dabhi_final.git`
- **Git Executable (Windows)**: `C:\Users\Manan\AppData\Local\Atlassian\SourceTree\git_local\cmd\git.exe`
- **Git Auth**: Token embedded in `.git/config` remote URL with local `[credential] helper = ""` to prevent interactive credential manager GUI popups from hanging background commands.

---

## 2. Technology Stack & Framework

- **Backend**: Native PHP 8.3 (Strict types `declare(strict_types=1);`, OOP domain models in `includes/classes/`, singleton PDO database layer, session management, CSRF protection).
- **Database**: MySQL 8.0 running on WAMP (`DB_HOST=localhost`, `DB_NAME=dabhi_final`, `DB_USER=root`, `DB_PASS=root` or empty, `DB_CHARSET=utf8mb4`).
- **Frontend Architecture**: Server-rendered PHP templates, Vanilla JavaScript (cart drawer, modals, checkout calculations), compiled production Tailwind CSS from the Yogurt Alley design system.
- **Fonts**:
  - Headings / Serif: Recoleta / Poppins Bold
  - Body / UI: Poppins (`var(--font-sans)`)
  - Badges / Codes / Currency: Cascadia Code (`var(--font-mono)`)

---

## 3. Design System & Visual Palette

> **CRITICAL RULE ON STYLING**:
> `public_html/assets/css/yogurtalley.css` is a **pre-compiled production CSS file**. Tailwind JIT is **not** actively compiling on save.
> **DO NOT USE ARBITRARY TAILWIND CLASSES** (e.g. `bg-[#260f10]`, `backdrop-blur-xl`, `border-[#f6dc94]/30`) unless they already exist in `yogurtalley.css` or you will get invisible/transparent backgrounds!
> **ALWAYS** define custom colors and components in dedicated CSS classes (or `<style>` tags / inline styles).

### Official Brand Palette:
| Role | Hex Code | Purpose |
|------|----------|---------|
| **Header Burgundy** | `#541f21` | Sticky header, main brand accents, primary buttons |
| **Dropdown Solid Surface** | `#2b0e0d` | Desktop dropdown menus (`.nav-dropdown-card`) & mobile drawer body — **100% OPAQUE & SOLID** |
| **Dark Drawer Header** | `#210e0d` | Mobile drawer header background |
| **Amber / Jaggery Gold** | `#f6dc94` | Accent borders, badges, hover links, price highlights |
| **Terracotta Rust** | `#c7613d` | Bestseller badges, CTA buttons, active state highlights |
| **Warm Page Background** | `#faf7f2` / `#fdfaf6` | Storefront body background |
| **Dark Text** | `#2b1311` | High-contrast body typography |
| **Card Borders** | `rgba(246, 220, 148, 0.25)` | Subtle golden separators on dark cards |

---

## 4. Directory Structure & Key Files

```
c:\wamp64\www\Dabhi_final\
├── database/
│   ├── create_menu_table.php     # Migration & seeder for menu_items table & manage_menus permission
│   ├── migrate_sync.php          # Database synchronization (products, inventory, GST, shipping, reviews)
│   ├── schema.sql                # Complete SQL schema definition
│   ├── dabhi_final.sql           # Database SQL dump
│   └── seed_catalog.php          # Catalog seeding helper
├── docs/                         # Architecture, GST, inventory, and payment documentation
├── includes/
│   ├── bootstrap.php             # Core bootstrap: config, error reporting, autoloader, session, timezone
│   ├── config.php                # Environment variables, DB credentials, SMTP, SMS, WhatsApp, Cashfree
│   ├── db/
│   │   └── Database.php          # Singleton PDO wrapper (fetchOne, fetchAll, query, lastInsertId, transactions)
│   ├── classes/
│   │   ├── Auth.php              # Session-based authentication, password verification, user state
│   │   ├── RBAC.php              # Role-based access control & permission checks
│   │   ├── Menu.php              # Storefront navigation menu model (getTree, getAll, save, toggle, delete)
│   │   ├── Product.php           # Product catalog, variants, images, active flags
│   │   ├── Category.php          # Product categories & category tree
│   │   ├── Cart.php              # Guest & user shopping cart, calculations, item counts
│   │   ├── Order.php             # Order placement, status state machine, order items
│   │   ├── Invoice.php           # GST compliant tax invoice generation & sequencing
│   │   ├── GST.php               # GST rate resolution (CGST, SGST, IGST) based on billing vs shipping state
│   │   ├── Shipping.php          # Shipping zones, pincode eligibility, flat rates, free shipping logic
│   │   ├── Coupon.php            # Coupon discount validation & application
│   │   ├── Inventory.php         # Stock batches, expiry tracking, movements
│   │   ├── Review.php            # Customer reviews & moderation
│   │   ├── AuditLog.php          # Admin activity logging
│   │   ├── payment/              # Payment gateways (CashfreeGateway.php)
│   │   └── notifications/        # Notification dispatchers (Email, SMS, WhatsApp)
│   └── helpers/
│       ├── functions.php         # Global helpers (e, url, asset, flashSet, flashRender, csrfToken, csrfVerify, isPost, get, post, redirect, jsonResponse, getSetting, settingEnabled)
│       └── upload.php            # Secure file upload handler
├── public_html/
│   ├── index.php                 # Storefront homepage (Hero, 4 Signature Chikkis, Box Builder, Story, Reels, Reviews)
│   ├── product.php               # Product detail page (250g, 500g, 1kg pack sizes, reviews, Add to Cart)
│   ├── cart.php                  # Dedicated shopping cart page
│   ├── checkout.php              # Multi-step checkout with real-time pincode validation, GST & shipping
│   ├── order-confirmation.php    # Order success page with tracking link
│   ├── track.php                 # Public order tracking by Order ID / Mobile
│   ├── account.php               # Customer account profile, past orders, saved addresses
│   ├── auth.php                  # Customer sign in / register / OTP
│   ├── logout.php                # Logout handler
│   ├── invoice.php               # View/download customer invoice
│   ├── partials/
│   │   ├── _header.php           # Sticky brand header, dynamic desktop dropdown nav, dynamic mobile drawer
│   │   ├── _footer.php           # Storefront footer with links, payment badges, brand statement
│   │   └── _cart_drawer.php      # Flyout slide-over cart drawer
│   ├── admin/
│   │   ├── index.php             # Admin Dashboard with KPI stats, recent orders, charts
│   │   ├── menus.php             # ★ Storefront Navigation Menu & Submenu Manager (Add, Edit, Delete, Show/Hide)
│   │   ├── products.php          # Product catalog table
│   │   ├── product-edit.php      # Product create/edit form
│   │   ├── categories.php        # Category manager
│   │   ├── orders.php            # Order listing with status filter
│   │   ├── order-detail.php      # Detailed order inspection, fulfillment, tracking, invoice
│   │   ├── inventory.php         # Stock overview & batch summary
│   │   ├── inventory-batch.php   # Batch manager with manufacture & expiry dates
│   │   ├── reviews.php           # Customer review approval/rejection moderation
│   │   ├── coupons.php           # Coupon creation & discount limits
│   │   ├── modules.php           # CMS content modules (announcement bar, hero, story, badges)
│   │   ├── instagram-reels.php   # CMS video reels manager
│   │   ├── gst-settings.php      # GSTIN, business state, tax enable/disable
│   │   ├── shipping-settings.php # Pincodes, zones, COD availability, shipping charges
│   │   ├── payment-settings.php  # COD toggle, Cashfree credentials & test mode
│   │   ├── notification-settings.php # SMTP, MSG91 SMS, Meta WhatsApp settings
│   │   ├── users.php             # User management
│   │   ├── user-edit.php         # User role & permission assignment
│   │   ├── permissions.php       # RBAC permissions table
│   │   ├── audit-log.php         # System event log
│   │   ├── reports.php           # Sales & revenue reports
│   │   ├── analytics.php         # Product performance analytics
│   │   └── partials/
│   │       ├── sidebar.php       # Admin sidebar navigation (includes link to menus.php)
│   │       ├── topbar.php        # Admin topbar with user dropdown
│   │       └── page-start.php    # Admin layout header wrap
│   ├── api/                      # AJAX endpoints
│   │   ├── cart-add.php
│   │   ├── cart-update.php
│   │   ├── cart-remove.php
│   │   ├── pincode-check.php
│   │   ├── coupon-apply.php
│   │   └── review-submit.php
│   └── assets/
│       ├── css/
│       │   ├── yogurtalley.css   # Main storefront compiled CSS with design tokens
│       │   ├── main.css          # Storefront specific overrides
│       │   └── style.css         # Admin panel styles
│       ├── js/
│       └── images/               # Logos, chikki product box renders, badges
└── memory.md                     # THIS FILE
```

---

## 5. Navigation Menu System (`menu_items`)

### Database Table: `menu_items`
```sql
CREATE TABLE `menu_items` (
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
```

### Model Methods (`includes/classes/Menu.php`):
- `Menu::getTree(string $location = 'primary', bool $onlyActive = true)`: Returns hierarchical array with nested `children` key.
- `Menu::getAll(string $location = 'primary')`: Returns flat list with joined parent title for admin table.
- `Menu::getParents(string $location = 'primary', ?int $excludeId = null)`: Returns top-level items for parent select dropdown.
- `Menu::getById(int $id)`: Fetches single menu record.
- `Menu::save(array $data, ?int $id = null)`: Validates and inserts/updates menu item.
- `Menu::toggle(int $id)`: Toggles `is_active` (1 = Visible, 0 = Hidden) with instant feedback.
- `Menu::delete(int $id)`: Deletes menu item (and cascade-deletes its submenus).
- `Menu::seedDefaults()`: Restores the 4 signature chikkis and default storefront links.

### Storefront Rendering (`public_html/partials/_header.php`):
- Loads active items via `Menu::getTree('primary', true)`.
- **Desktop Navigation**:
  - Items with `children` render the rich `.nav-dropdown-card` on hover.
  - The dropdown card has a **solid 100% opaque `#2b0e0d` background** with high-contrast text, icons, and badges to prevent page content bleed-through.
  - Items without children render direct `.header-nav-link` anchors.
- **Mobile Navigation Drawer (GimiGimi Style)**:
  - **Custom Wavy Noodle Hamburger Icon**: Extracted SVG path featuring 3 rhythmic wavy strokes representing noodles/chikki strips.
  - **Identical 1-to-1 Sync with Desktop**: Driven by the exact same `$navTree` structure from `Menu::getTree('primary', true)`. Any menu or submenu added, edited, deleted, or hidden in admin immediately updates both desktop and mobile in lockstep.
  - **Horizontal Multi-Level Sliding Panels**:
    - `#mobile-panel-main`: Root level view showing all primary items with emoji icons and bold uppercase titles (`HOME`, `OUR CHIKKI`, `OUR CRAFT`, `REELS`, `REVIEWS`, `TRACK ORDER`). Items with submenus have a rust-red chevron `>`.
    - `#submenu-panel-{id}`: Level 1 sliding panel with `< {PARENT_TITLE}` back button and `View All →` link.
    - Card items inside submenus display icon badge, bold title, subtitle description, badge pill (`BESTSELLER`, `SIGNATURE`, etc.), and circular arrow button `→`.
    - Smooth horizontal cubic-bezier slide transitions (`transform: translateX(0)` vs `translateX(-35%)` / `translateX(100%)`).
  - **Drawer Header & Footer**:
    - Header: Deep roasted jaggery burgundy `#541f21` with brand logo and white `✕` close button.
    - Footer: Warm card background `#f7f1e6` featuring `LOGIN / SIGN UP` (or `MY ACCOUNT`) button, WhatsApp chat button (`#25D366`), and 5-star trust badge (`100% Pure Jaggery • Handcrafted Since 2009`).

---

## 6. Admin Panel Details & Credentials

- **Admin Login URL**: `http://localhost/dabhi_final/public_html/admin/login.php`
- **Storefront & Unified Login**:
  - The main page header profile icon features a right-aligned dropdown (`.profile-dropdown-wrap`):
    - **Guest**: Offers quick access to Customer Login (`auth.php`) and Admin & Staff Login (`admin/login.php`).
    - **Admin / Staff**: Displays a golden star indicator (`★`), links directly to `admin/index.php`, and provides quick dropdown links to Admin Dashboard, Storefront View, and Sign Out.
    - **Buyer**: Links to `account.php` with dropdown shortcuts to My Account, My Orders, and Sign Out.
  - `api/auth.php` and `assets/js/auth.js` automatically detect if credentials belong to staff (`superadmin`, `admin`, `employee`) or `buyer`, and dynamically route to `admin/index.php` or `account.php` with appropriate feedback.
- **Default Superadmin Account**:
  - Email: `jenish@gmail.com`
  - Role: `superadmin`
- **Roles in System**:
  - `superadmin`: Unrestricted access to all modules, financial settings, and user management.
  - `admin`: Storefront, catalog, orders, and CMS management.
  - `employee`: Inventory, batch packing, and shipping label printing.
  - `buyer` / `customer`: Storefront ordering and account portal.
- **CSRF Protection**: All state-changing POST forms and query actions must call `csrfVerify()` or `csrfVerifyToken(get('_token'))`.

---

## 7. Database Core Tables Summary

| Table | Description |
|-------|-------------|
| `menu_items` | Storefront navigation hierarchy (parent links, dropdown items, icons, badges, visibility) |
| `products` | 4 core chikkis (Mandvi, TIL, Daliya, 3 Mix) + future items, active flag, slug, SEO |
| `product_variants` | Pack sizes (250g, 500g, 1kg) with SKU, MRP, sell price, weight in grams |
| `product_images` | Gallery images per product with sort order |
| `categories` | Category taxonomy with slug, description, image, parent |
| `inventory_batches`| Production batches with batch number, quantity, manufacture & expiry dates |
| `stock_movements` | Audit log of all stock deductions (sales), additions (production), and adjustments |
| `orders` | Customer orders, total amount, subtotal, GST total, shipping, payment status, order status |
| `order_items` | Products and variants purchased within each order |
| `order_status_history` | Timestamped audit log of order status transitions |
| `invoices` | Official GST invoices with invoice number sequencing |
| `gst_settings` | Business GSTIN, state of origin (Gujarat), legal business name |
| `shipping_zones` | Regional shipping zones (Local Gujarat vs Rest of India) |
| `shipping_rates` | Weight-based and flat-rate shipping tiers |
| `pincode_zones` | Pincode mapping for delivery serviceability & COD availability |
| `coupons` & `coupon_usage` | Promo codes, percentage/flat discounts, minimum order thresholds |
| `reviews` | Customer ratings, reviews, photos, verified buyer flag, approval status |
| `settings` | Key-value store for global toggles (announcement text, free shipping threshold, COD toggle) |
| `instagram_reels` | CMS video reels embedded on the storefront |
| `users` | User accounts (superadmin, admin, employee, buyer) with bcrypt hashes |
| `permissions` & `user_permissions` | Granular permission keys (`manage_menus`, `manage_products`, `manage_orders`, etc.) |

---

## 8. Common Pitfalls & Developer Guidelines

1. **Do Not Scan Files Repeatedly**: Refer to this `memory.md` for schema, paths, classes, and rules.
2. **Never commit hardcoded credentials**: Keep `config.php` credentials in local configuration.
3. **Always use Database singleton**: Use `Database::fetchAll()`, `Database::fetchOne()`, `Database::query()`. Do not instantiate ad-hoc PDO connections.
4. **Tailwind CSS Safety**: `yogurtalley.css` is pre-compiled. Avoid arbitrary utility classes like `bg-[#xxx]` in HTML. Use explicit CSS styles or classes already in the bundle.
5. **Dropdown Transparency Gotcha**: Dropdowns must have `background: #2b0e0d !important; background-color: #2b0e0d !important;` to prevent underlying page cards from bleeding through.
6. **Timezone**: The application runs on `Asia/Kolkata` (`IST`). Always use `date_default_timezone_set('Asia/Kolkata')`.
7. **Git Push Protocol**: Branch is `Menu`. Before pushing, ensure credentials in `.git/config` are properly configured with empty helper to avoid background hanging.
