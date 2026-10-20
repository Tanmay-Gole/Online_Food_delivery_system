# Online Food Ordering & Delivery System (Version 1.0)
**Compliant with IEEE 830 Software Requirements Specification (SRS)**  
**Developer / Student:** Tanmay Gole (Reg. No: 2430030098)  
**Institution:** Manipal University Jaipur, BTech Information Technology  
**Date:** October 2026  

---

## 📖 Project Overview
The **Online Food Ordering & Delivery System** is a responsive, multi-tier web application connecting four key user roles:
1. **Customers:** Browse restaurants, filter by cuisine/rating/price, customize cart, calculate GST/delivery fees, checkout with simulated payments (Card, UPI, Wallet, COD), track live order status with animated stepper, submit ratings/reviews, and generate printable tax invoices.
2. **Restaurant Owners:** Manage menus (add/edit/delete dishes, toggle In-Stock/Sold-Out availability), accept/reject incoming order tickets, and update kitchen preparation stages (`Preparing` -> `Ready for Pickup`).
3. **Delivery Agents:** Mobile-friendly delivery portal displaying assigned pickups, restaurant address with click-to-call, customer address, cash collection indicators for COD, and status updates (`Picked Up & On the Way` -> `Delivered`).
4. **Platform Administrator:** Onboard & approve newly registered restaurants (REQ-4.7.2), view/activate/deactivate user accounts across all roles (REQ-4.7.1), and analyze platform financial analytics and summary sales reports (REQ-4.7.3).

---

## 🏛️ System Architecture
The application adheres strictly to the three-tier architectural model mandated in Section 2.1 of the SRS:
- **Presentation Layer:** Responsive HTML5, CSS3 design system, JavaScript (ES6+), real-time status polling stepper, print stylesheets for invoices.
- **Application Layer:** Modular PHP (PHP 7.4 / 8.x) scripts implementing session authentication, role-based access control (RBAC), password hashing via BCRYPT, and sanitized database interactions.
- **Data Layer:** MySQL 8.0 (managed via phpMyAdmin in XAMPP) with seamless local SQLite fallback for instant zero-configuration local evaluation.

---

## 🔑 Pre-Seeded Evaluation Accounts
All accounts are initialized with password: `Password123`

| Role | Email | Password | Primary Functions |
|---|---|---|---|
| **Administrator** | `admin@foodie.com` | `Password123` | Platform oversight, restaurant approvals, user management, financial reports |
| **Restaurant Owner** | `owner.punjab@foodie.com` | `Password123` | Kitchen dashboard for "The Royal Punjab", order processing, menu management |
| **Restaurant Owner** | `owner.bella@foodie.com` | `Password123` | Kitchen dashboard for "Bella Italia Ristorante" |
| **Delivery Agent** | `delivery.vikram@foodie.com` | `Password123` | Delivery portal for Vikram Singh, status updates (`Picked Up` -> `Delivered`) |
| **Customer** | `tanmay@example.com` | `Password123` | Tanmay Gole, ordering, live tracking, order history, ratings |

> *Tip:* You can also use the **1-Click Demo Login** buttons available directly on the login screen (`auth/login.php`) for fast evaluation!

---

## 🚀 How to Run the Project

### Option A: Running with Standalone PHP (Fastest & Zero Setup)
1. Open PowerShell or Terminal in this project directory:
   ```powershell
   cd C:\Users\golet\.gemini\antigravity\scratch\online-food-ordering-system
   ```
2. Start the built-in development server:
   ```powershell
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```
   *(The system automatically detects SQLite / MySQL and runs immediately with all seed data ready!)*

---

### Option B: Running with XAMPP (Apache + MySQL / phpMyAdmin)
1. Copy or move the `online-food-ordering-system` folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\online-food-ordering-system
   ```
2. Open **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.
3. Open **phpMyAdmin** in your browser (`http://localhost/phpmyadmin`).
4. Click **Import** and select the file:
   ```
   C:\xampp\htdocs\online-food-ordering-system\database\database.sql
   ```
   Click **Go**. This creates the `food_ordering_db` database and seeds all sample restaurants, users, menu items, and orders.
5. In your browser, open:
   ```
   http://localhost/online-food-ordering-system
   ```
6. Alternatively, visit `http://localhost/online-food-ordering-system/install.php` to run diagnostics or reset data with a single click.

---

## 📋 Requirements Traceability Matrix (SRS Mapping)

| Requirement ID | Specification | Implementation File |
|---|---|---|
| **REQ-4.1.1** | User registration with role selection | `auth/register.php` |
| **REQ-4.1.2** | Unique email validation & formatting | `includes/auth.php` |
| **REQ-4.1.3** | Password encryption via secure hashing | `includes/auth.php` (`password_hash`) |
| **REQ-4.1.4** | Login authentication with error handling & role redirection | `auth/login.php` |
| **REQ-4.1.5** | Password recovery mechanism via registered email | `auth/forgot_password.php`, `auth/reset_password.php` |
| **REQ-4.2.1** | Restaurant listing with cuisine, rating, delivery time | `customer/restaurants.php`, `index.php` |
| **REQ-4.2.2** | Search restaurants and menu items by keyword | `customer/restaurants.php` |
| **REQ-4.2.3** | Filtering by cuisine type, price range, and rating | `customer/restaurants.php` |
| **REQ-4.2.4** | Menu items grouped by category with price & availability | `customer/restaurant.php`, `restaurant/menu.php` |
| **REQ-4.3.1** | Cart add, update quantity, remove, restaurant switch check | `customer/cart.php`, `assets/js/cart.js`, `api/cart_action.php` |
| **REQ-4.3.2** | Auto-calculation of subtotal, 5% GST, delivery fee, total | `includes/helpers.php` (`getCartTotals`) |
| **REQ-4.3.3** | Delivery address & phone verification | `customer/checkout.php` |
| **REQ-4.3.4** | Unique order ID generation (`ORD-YYYYMMDD-XXXX`) | `includes/helpers.php` (`generateOrderNumber`), `customer/place_order.php` |
| **REQ-4.3.5** | Immediate notification to restaurant upon new order | `customer/place_order.php`, `includes/helpers.php` (`addNotification`) |
| **REQ-4.4.1** | Multiple payment methods (Card, UPI, Wallet, COD) | `customer/checkout.php`, `customer/place_order.php` |
| **REQ-4.4.2** | Payment status recorded against order (`pending`/`success`) | `customer/place_order.php`, `database/schema.sql` |
| **REQ-4.4.3** | Digital tax invoice / printable receipt | `customer/invoice.php`, `assets/css/print.css` |
| **REQ-4.4.4** | Masked card reference storage only (`**** **** **** 1234`) | `customer/place_order.php` |
| **REQ-4.5.1** | Real-time order tracking stepper (`Placed` -> `Delivered`) | `customer/track_order.php`, `assets/js/tracker.js` |
| **REQ-4.5.2** | Restaurant updates order status (`Accepted`/`Preparing`/`Ready`) | `restaurant/orders.php`, `restaurant/update_order.php` |
| **REQ-4.5.3** | Delivery agent updates status (`Picked Up` -> `Delivered`) | `delivery/order_detail.php`, `delivery/update_status.php` |
| **REQ-4.5.4** | Automated notifications to customer on status change | `restaurant/update_order.php`, `delivery/update_status.php` |
| **REQ-4.6.1** | Star rating (1-5) and review submission after delivery | `customer/rate_order.php` |
| **REQ-4.6.2** | Recalculation of restaurant average rating | `customer/rate_order.php` |
| **REQ-4.6.3** | Single review limit per completed order | `customer/rate_order.php` |
| **REQ-4.7.1** | Admin account view and activation / deactivation | `admin/users.php` |
| **REQ-4.7.2** | Admin approval required before new restaurant is visible | `admin/restaurants.php`, `auth/register.php` |
| **REQ-4.7.3** | Platform summary reports of total orders, revenue, active stores | `admin/reports.php`, `admin/dashboard.php` |
| **Business Rule 1** | Only registered & logged-in customers can order | `customer/checkout.php`, `customer/place_order.php` |
| **Business Rule 2** | Restaurant must be approved before visible | `customer/restaurants.php`, `index.php` (`status='approved'`) |
| **Business Rule 3** | Customer cannot cancel once order is marked `Preparing` | `customer/track_order.php`, `customer/cancel_order.php` |
| **Business Rule 4** | Exactly 1 review per delivered order | `customer/rate_order.php` |

---

## 📂 Project Structure
```
online-food-ordering-system/
├── admin/                  # Administrator Panel (Approvals, Users, Reports)
│   ├── dashboard.php
│   ├── orders.php
│   ├── reports.php
│   ├── restaurants.php
│   └── users.php
├── api/                    # AJAX JSON Endpoints
│   ├── cart_action.php
│   ├── notifications.php
│   └── order_status.php
├── assets/                 # Frontend Assets
│   ├── css/
│   │   ├── print.css       # Clean printable invoice styling
│   │   └── style.css       # Responsive design system
│   └── js/
│       ├── cart.js         # Interactive cart manager
│       ├── main.js         # Modals, ratings, dropdowns, toasts
│       └── tracker.js      # Polling live order tracker
├── auth/                   # Authentication & Password Recovery
│   ├── forgot_password.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   └── reset_password.php
├── config/                 # App Settings & DB Constants
│   └── config.php
├── customer/               # Customer Storefront & Ordering
│   ├── cancel_order.php
│   ├── cart.php
│   ├── checkout.php
│   ├── index.php
│   ├── invoice.php
│   ├── order_success.php
│   ├── orders.php
│   ├── place_order.php
│   ├── profile.php
│   ├── rate_order.php
│   ├── restaurant.php
│   ├── restaurants.php
│   └── track_order.php
├── database/               # Database Scripts & SQLite Seed
│   ├── database.sql        # Full MySQL DDL + DML
│   ├── food_ordering.sqlite# Portable SQLite database
│   ├── schema.sql          # Clean DDL table schemas
│   ├── seed.sql            # Realistic seed records
│   └── setup_db.php        # CLI/Web migration script
├── delivery/               # Delivery Partner Portal
│   ├── dashboard.php
│   ├── order_detail.php
│   ├── orders.php
│   └── update_status.php
├── includes/               # Reusable Core Modules
│   ├── auth.php            # RBAC session authentication
│   ├── db.php              # Dual MySQL / SQLite PDO connection
│   ├── footer.php          # Common HTML footer & demo bar
│   ├── header.php          # Common responsive header & nav
│   └── helpers.php         # Formatting, cart totals, sanitization
├── index.php               # Landing Page & Featured Hotspots
├── install.php             # 1-Click Web Diagnostics & Reset Tool
├── README.md               # Developer & Technical Documentation
└── user_manual.md          # User & Role Operational Manual
```
