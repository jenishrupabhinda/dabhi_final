# Dabhi Chikki — E-Commerce Platform & Full Admin Management

Modern, high-performance handcrafted jaggery chikki e-commerce platform with a rich Yogurt Alley-inspired design, complete admin operations center, GST engine, FIFO batch inventory management, shipping calculator, review moderation, shipment tracking, and automated tax invoicing.

---

## 🌟 Key Features

### 1. Storefront Experience
- **Pixel-Perfect Responsive UI**: Warm earthy palette, Recoleta & Outfit typography, sticky mobile action bars, and slide-out cart drawer.
- **Product Details**: Multi-variant packaging (500g, 1kg), high-resolution gallery with touch swipe, live batch stock indicators ("Fresh Batch In Stock" vs "Only X Left"), customer reviews with verified badges, and delivery PIN code serviceability checker.
- **Cart & Checkout**: Dynamic free-shipping meter (Free shipping over ₹999), instant COD surcharge calculation, live CGST/SGST vs IGST split preview.
- **Order Tracking (`track.php`)**: Visual 6-stage delivery timeline (`Placed` → `Confirmed` → `Packed` → `Shipped` → `Out for Delivery` → `Delivered`), courier name, live tracking number, and printable tax invoice access.

### 2. Admin Operations Center
- **Dashboard (`admin/index.php`)**: Live sales revenue, daily orders counter, near-expiry batch warnings, and low-stock alerts.
- **GST Compliance (`admin/gst-settings.php`)**: Business GSTIN (`24AAJDUDHEJ5555`), Gujarat origin state (`24`), automatic intra-state (CGST 2.5% + SGST 2.5%) and inter-state (IGST 5.0%) calculations.
- **Shipping Management (`admin/shipping-settings.php`)**: Zone-based shipping (Gujarat Local vs Rest of India), weight slabs, configurable free shipping threshold, and COD extra fee.
- **FIFO Batch Inventory (`admin/inventory.php`)**: First-In, First-Expired, First-Out automatic batch deduction with audit trail in `stock_movements`.
- **Review Moderation (`admin/reviews.php`)**: Customer review submission queue, one-click approve/reject.
- **Shipping Labels & Invoices**: Thermal printable labels with vector barcodes (`admin/label.php`) and sequential GST Tax Invoices (`DC/26-27/XXXXXX`).

---

## 🔑 Default Admin Credentials

- **Admin Login URL**: `/admin/login.php`
- **Username**: `jenish@gmail.com`
- **Password**: `Jenish@123`
- **Role**: `superadmin`

---

## 🗄️ Database Setup

The complete working database dump with all tables, catalog products, batches, reviews, settings, and pincode zones is included:

- **Full Database Dump**: `database/dabhi_final.sql` (or `database/dabhi_final_dump.sql`)

### To Import:
```bash
mysql -u root -p dabhi_final < database/dabhi_final.sql
```
Or use phpMyAdmin:
1. Create a database named `dabhi_final`.
2. Go to **Import** tab.
3. Choose `database/dabhi_final.sql` and click **Import**.

---

## 📂 Project Architecture

```
dabhi_final/
├── public_html/              <- Web root (point Apache/cPanel here)
│   ├── index.php             <- Homepage with categories & featured carousel
│   ├── product.php           <- Product detail with reviews, stock & pincode check
│   ├── cart.php              <- Cart page
│   ├── checkout.php          <- Checkout with live GST & shipping
│   ├── track.php             <- Real-time order & shipment tracking
│   ├── invoice.php           <- Customer tax invoice view / print
│   ├── account.php           <- Buyer profile & order history
│   ├── admin/                <- Admin panel (Dashboard, Orders, Inventory, GST, Shipping, Reviews)
│   ├── api/                  <- AJAX endpoints (cart, pincode-check, review-submit, order)
│   └── assets/               <- Stylesheets, scripts, images & fonts
│
├── includes/                 <- Core business logic (outside web root)
│   ├── config.php            <- Database & environment configuration
│   ├── config.sample.php     <- Template for configuration
│   ├── bootstrap.php         <- App initialization & autoloader
│   ├── db/Database.php       <- PDO singleton
│   ├── helpers/functions.php <- Utility helpers
│   └── classes/              <- Service classes (Product, Order, Shipping, GST, Inventory, ShippingLabel, Invoice, Auth, RBAC)
│
├── database/                 <- Database dumps & migrations
│   ├── dabhi_final.sql       <- Complete ready-to-run database dump
│   ├── schema.sql            <- Schema definition
│   └── seed.sql              <- Initial permissions & settings
│
└── README.md
```
