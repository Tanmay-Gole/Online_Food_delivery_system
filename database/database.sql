-- =================================================================
-- Online Food Ordering System - Full Database Schema and Seed Data
-- Version 1.0 (IEEE 830 SRS Compliant)
-- Author: Tanmay Gole (2430030098) - Manipal University Jaipur
-- =================================================================

CREATE DATABASE IF NOT EXISTS food_ordering_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_ordering_db;

-- Online Food Ordering & Delivery System - Database Schema
-- Version 1.0 (SRS Compliant)
-- Target RDBMS: MySQL 8.0 / MariaDB (XAMPP compatible)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'restaurant_owner', 'delivery_agent', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'utensils'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    cuisine VARCHAR(100) NOT NULL,
    price_range ENUM('$', '$$', '$$$') DEFAULT '$$',
    delivery_time VARCHAR(50) DEFAULT '30-40 min',
    delivery_fee DECIMAL(10,2) DEFAULT 40.00,
    min_order DECIMAL(10,2) DEFAULT 150.00,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    image VARCHAR(255) DEFAULT '',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    is_veg TINYINT(1) DEFAULT 1,
    is_available TINYINT(1) DEFAULT 1,
    image VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_agent_id INT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_phone VARCHAR(20) NOT NULL,
    delivery_instructions TEXT NULL,
    order_status ENUM('placed', 'accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'cancelled', 'rejected') DEFAULT 'placed',
    cancellation_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_agent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes VARCHAR(255) NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('card', 'upi', 'wallet', 'cod') NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    masked_card VARCHAR(30) NULL,
    payment_details TEXT NULL,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    restaurant_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Seed Data for Online Food Ordering System
-- Default password for all seeded users is: Password123
-- Password hash: $2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy

-- 1. USERS
INSERT INTO users (id, name, email, phone, password, role, status, address) VALUES
(1, 'System Administrator', 'admin@foodie.com', '9800000001', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'admin', 'active', 'Admin Headquarters, Jaipur, Rajasthan'),
(2, 'Rahul Sharma', 'owner.punjab@foodie.com', '9800000002', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'MI Road, C-Scheme, Jaipur, Rajasthan'),
(3, 'Marco Rossi', 'owner.bella@foodie.com', '9800000003', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'Tonk Road, Bapu Nagar, Jaipur, Rajasthan'),
(4, 'Li Wei', 'owner.dragon@foodie.com', '9800000004', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'Raja Park, Jaipur, Rajasthan'),
(5, 'Sarah Jenkins', 'owner.burger@foodie.com', '9800000005', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'Vaishali Nagar, Jaipur, Rajasthan'),
(6, 'Pooja Verma', 'owner.sweet@foodie.com', '9800000006', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'Mansarovar, Jaipur, Rajasthan'),
(7, 'Ankit Mehra', 'owner.spice@foodie.com', '9800000007', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'restaurant_owner', 'active', 'Civil Lines, Jaipur, Rajasthan'),
(8, 'Vikram Singh', 'delivery.vikram@foodie.com', '9876543210', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'delivery_agent', 'active', 'Delivery Hub 1, Jaipur'),
(9, 'Amit Patel', 'delivery.amit@foodie.com', '9876543211', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'delivery_agent', 'active', 'Delivery Hub 2, Jaipur'),
(10, 'Tanmay Gole', 'tanmay@example.com', '9123456780', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'customer', 'active', 'Flat 402, Sunshine Heights, Vidyadhar Nagar, Jaipur, RJ 302039'),
(11, 'Priya Sharma', 'priya@example.com', '9123456781', '$2y$10$2beP7aZvJw1lzQAW1lg2JOGIG7V6Ym1ZnO4o6VIGkrFwDUbcHSRjy', 'customer', 'active', 'B-12, Malviya Nagar, Jaipur, RJ 302017');

-- 2. CATEGORIES
INSERT INTO categories (id, name, slug, icon) VALUES
(1, 'Starters & Appetizers', 'starters', 'utensils'),
(2, 'Main Course', 'main-course', 'bowl-rice'),
(3, 'Breads & Rice', 'breads-rice', 'wheat-awn'),
(4, 'Pizzas & Pastas', 'pizzas-pastas', 'pizza-slice'),
(5, 'Burgers & Wraps', 'burgers-wraps', 'burger'),
(6, 'Beverages & Shakes', 'beverages-shakes', 'glass-water'),
(7, 'Desserts & Sweets', 'desserts-sweets', 'ice-cream');

-- 3. RESTAURANTS
INSERT INTO restaurants (id, owner_id, name, cuisine, price_range, delivery_time, delivery_fee, min_order, address, phone, image, status, rating, rating_count) VALUES
(1, 2, 'The Royal Punjab', 'North Indian', '$$', '30-40 min', 40.00, 200.00, 'Plot 14, C-Scheme, MI Road, Jaipur', '9800000002', 'punjab.jpg', 'approved', 4.8, 120),
(2, 3, 'Bella Italia Ristorante', 'Italian', '$$$', '35-45 min', 50.00, 250.00, 'Shop 5, Tonk Road, Bapu Nagar, Jaipur', '9800000003', 'bella.jpg', 'approved', 4.6, 95),
(3, 4, 'Golden Dragon Wok', 'Chinese', '$$', '25-35 min', 35.00, 180.00, 'Lane 3, Raja Park, Jaipur', '9800000004', 'chinese.jpg', 'approved', 4.5, 84),
(4, 5, 'Burger Craft & Shakes', 'Fast Food', '$', '20-30 min', 30.00, 120.00, 'Central Spine, Vaishali Nagar, Jaipur', '9800000005', 'burger.jpg', 'approved', 4.7, 210),
(5, 6, 'Sweet Tooth Delights', 'Desserts & Bakery', '$', '15-25 min', 25.00, 100.00, 'Near Metro Pillar 12, Mansarovar, Jaipur', '9800000006', 'dessert.jpg', 'approved', 4.9, 140),
(6, 7, 'Spice Route Kitchen', 'Mughlai & Biryani', '$$', '35-45 min', 45.00, 200.00, 'Civil Lines Road, Jaipur', '9800000007', 'spiceroute.jpg', 'pending', 0.0, 0);

-- 4. MENU ITEMS
INSERT INTO menu_items (id, restaurant_id, category_id, name, description, price, is_veg, is_available, image) VALUES
-- The Royal Punjab (Restaurant 1)
(1, 1, 1, 'Paneer Tikka Angara', 'Smoked cottage cheese cubes marinated in tandoori spices and roasted to perfection.', 260.00, 1, 1, 'paneer_tikka.jpg'),
(2, 1, 1, 'Amritsari Fish Fry', 'Crispy carom-seed spiced river fish fritters served with mint chutney.', 320.00, 0, 1, 'fish_fry.jpg'),
(3, 1, 2, 'Butter Chicken Makhani', 'Tender tandoori chicken simmered in rich creamy tomato and butter gravy.', 380.00, 0, 1, 'butter_chicken.jpg'),
(4, 1, 2, 'Dal Makhani Heritage', 'Slow-cooked black lentils simmered overnight with cream, butter, and mild spices.', 240.00, 1, 1, 'dal_makhani.jpg'),
(5, 1, 2, 'Kadhai Paneer Special', 'Paneer and bell peppers tossed in fresh freshly roasted coriander and red chili masala.', 290.00, 1, 1, 'kadhai_paneer.jpg'),
(6, 1, 3, 'Garlic Butter Naan', 'Refined flour leavened flatbread topped with minced garlic, fresh coriander, and butter.', 65.00, 1, 1, 'garlic_naan.jpg'),
(7, 1, 3, 'Jeera Basmati Rice', 'Fragrant long-grain basmati tempered with cumin seeds and pure desi ghee.', 150.00, 1, 1, 'jeera_rice.jpg'),
(8, 1, 6, 'Patiala Sweet Lassi', 'Thick churned yogurt drink topped with clotted cream (malai) and saffron.', 90.00, 1, 1, 'lassi.jpg'),

-- Bella Italia Ristorante (Restaurant 2)
(9, 2, 4, 'Margherita Classica Pizza', 'San Marzano tomato sauce, fresh buffalo mozzarella, fresh basil, and extra virgin olive oil.', 340.00, 1, 1, 'margherita.jpg'),
(10, 2, 4, 'Pepperoni & Jalapeno Pizza', 'Classic hand-stretched pizza loaded with smoky chicken pepperoni, pickled jalapenos, and mozzarella.', 450.00, 0, 1, 'pepperoni.jpg'),
(11, 2, 4, 'Creamy Fettuccine Alfredo', 'Fettuccine pasta tossed in silky parmesan cream sauce with sauteed mushrooms.', 360.00, 1, 1, 'alfredo.jpg'),
(12, 2, 4, 'Penne All Arrabbiata', 'Al dente penne in spicy garlic tomato sauce with black olives and fresh herbs.', 320.00, 1, 1, 'arrabbiata.jpg'),
(13, 2, 1, 'Cheesy Garlic Bread', 'Toasted rustic baguette slices loaded with roasted garlic butter and melted mozzarella.', 180.00, 1, 1, 'garlic_bread.jpg'),
(14, 2, 7, 'Classic Italian Tiramisu', 'Savoiardi ladyfingers soaked in espresso coffee layered with rich mascarpone cream.', 220.00, 1, 1, 'tiramisu.jpg'),

-- Golden Dragon Wok (Restaurant 3)
(15, 3, 1, 'Crispy Spring Rolls', 'Golden fried pastry sheets stuffed with wok-tossed shredded vegetables and glass noodles.', 190.00, 1, 1, 'spring_rolls.jpg'),
(16, 3, 1, 'Chilli Chicken Dry', 'Crispy diced chicken stir-fried with scallions, capsicum, and oriental spicy soy sauce.', 290.00, 0, 1, 'chilli_chicken.jpg'),
(17, 3, 2, 'Hakka Noodles Veg', 'Street style stir-fried noodles with crunchy julienned veggies, soya, and white pepper.', 210.00, 1, 1, 'hakka_noodles.jpg'),
(18, 3, 2, 'Schezwan Chicken Fried Rice', 'Fragrant wok-tossed rice with shredded chicken, egg, and fiery Schezwan chili paste.', 260.00, 0, 1, 'schezwan_rice.jpg'),
(19, 3, 2, 'Veg Manchurian Gravy', 'Crisp vegetable dumplings simmered in savory garlic, ginger, and soy sauce gravy.', 240.00, 1, 1, 'manchurian.jpg'),
(20, 3, 6, 'Iced Peach Lemon Tea', 'Brewed black tea infused with sweet peach nectar and a splash of fresh lemon.', 120.00, 1, 1, 'peach_tea.jpg'),

-- Burger Craft & Shakes (Restaurant 4)
(21, 4, 5, 'Smoky BBQ Bacon & Cheese Burger', 'Juicy grilled patty topped with cheddar slice, crispy turkey bacon, caramelized onions, and BBQ sauce.', 260.00, 0, 1, 'bbq_burger.jpg'),
(22, 4, 5, 'Crispy Paneer Supreme Burger', 'Spicy crumb-fried cottage cheese patty with thousand island slaw and melted cheese.', 210.00, 1, 1, 'paneer_burger.jpg'),
(23, 4, 1, 'Loaded Peri-Peri Fries', 'Double fried golden potato fries seasoned with hot peri-peri dust and cheese sauce.', 150.00, 1, 1, 'loaded_fries.jpg'),
(24, 4, 5, 'Grilled Chicken Fiesta Wrap', 'Tortilla filled with spiced grilled chicken strips, salsa, crisp lettuce, and chipotle mayo.', 230.00, 0, 1, 'chicken_wrap.jpg'),
(25, 4, 6, 'Belgian Dark Chocolate Shake', 'Decadent thick shake prepared with pure Belgian cocoa, vanilla ice cream, and chocolate fudge.', 180.00, 1, 1, 'choco_shake.jpg'),

-- Sweet Tooth Delights (Restaurant 5)
(26, 5, 7, 'Red Velvet Slice Cake', 'Velvety crimson sponge layered with silky cream cheese frosting.', 160.00, 1, 1, 'red_velvet.jpg'),
(27, 5, 7, 'Sizzling Hot Walnut Brownie', 'Fudgy dark chocolate brownie loaded with toasted walnuts.', 150.00, 1, 1, 'brownie.jpg'),
(28, 5, 7, 'Death By Chocolate Sundae', 'Trio of dark chocolate scoops, brownie bits, hot fudge, and roasted almonds.', 210.00, 1, 1, 'sundae.jpg'),
(29, 5, 6, 'Cold Coffee with Ice Cream', 'Creamy blended iced coffee topped with a scoop of vanilla ice cream and chocolate drizzle.', 140.00, 1, 1, 'cold_coffee.jpg');

-- 5. SAMPLE COMPLETED ORDER (For instant reviews & tracking test)
INSERT INTO orders (id, order_number, customer_id, restaurant_id, delivery_agent_id, subtotal, tax, delivery_fee, total_amount, delivery_address, delivery_phone, delivery_instructions, order_status, created_at) VALUES
(1, 'ORD-20261019-1001', 10, 1, 8, 640.00, 32.00, 40.00, 712.00, 'Flat 402, Sunshine Heights, Vidyadhar Nagar, Jaipur, RJ 302039', '9123456780', 'Ring the doorbell and leave at door', 'delivered', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'ORD-20261020-1002', 10, 4, 8, 410.00, 20.50, 30.00, 460.50, 'Flat 402, Sunshine Heights, Vidyadhar Nagar, Jaipur, RJ 302039', '9123456780', 'Please call before arriving', 'preparing', NOW());

-- 6. ORDER ITEMS
INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity, subtotal) VALUES
(1, 3, 'Butter Chicken Makhani', 380.00, 1, 380.00),
(1, 6, 'Garlic Butter Naan', 65.00, 4, 260.00),
(2, 21, 'Smoky BBQ Bacon & Cheese Burger', 260.00, 1, 260.00),
(2, 23, 'Loaded Peri-Peri Fries', 150.00, 1, 150.00);

-- 7. ORDER STATUS HISTORY
INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES
(1, 'placed', 'Order placed by customer', 10, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'accepted', 'Order accepted by restaurant', 2, DATE_SUB(NOW(), INTERVAL 23 HOUR)),
(1, 'preparing', 'Kitchen has begun preparing food', 2, DATE_SUB(NOW(), INTERVAL 22 HOUR)),
(1, 'ready_for_pickup', 'Order packed and ready for delivery partner', 2, DATE_SUB(NOW(), INTERVAL 21 HOUR)),
(1, 'out_for_delivery', 'Delivery agent Vikram Singh picked up the order', 8, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(1, 'delivered', 'Order successfully delivered to customer', 8, DATE_SUB(NOW(), INTERVAL 19 HOUR)),
(2, 'placed', 'Order placed by customer', 10, NOW()),
(2, 'accepted', 'Order accepted by restaurant', 5, NOW()),
(2, 'preparing', 'Kitchen is preparing burger and fries', 5, NOW());

-- 8. PAYMENTS
INSERT INTO payments (order_id, payment_method, transaction_id, amount, payment_status, masked_card, paid_at) VALUES
(1, 'card', 'TXN-CARD-99882211', 712.00, 'success', '**** **** **** 4242', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'upi', 'TXN-UPI-33441122', 460.50, 'success', NULL, NOW());

-- 9. REVIEWS
INSERT INTO reviews (order_id, restaurant_id, customer_id, rating, comment, created_at) VALUES
(1, 1, 10, 5, 'Butter chicken was exceptionally delicious and arrived piping hot! Naan was soft and fresh.', DATE_SUB(NOW(), INTERVAL 18 HOUR));

-- 10. NOTIFICATIONS
INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES
(10, 'Order Delivered!', 'Your order #ORD-20261019-1001 from The Royal Punjab was delivered.', 'customer/invoice.php?id=1', 1, DATE_SUB(NOW(), INTERVAL 19 HOUR)),
(10, 'Order Preparing', 'The Royal Punjab is preparing your order #ORD-20261020-1002.', 'customer/track_order.php?id=2', 0, NOW()),
(2, 'New Order Received', 'You have received order #ORD-20261020-1002 for ₹460.50.', 'restaurant/orders.php', 0, NOW()),
(8, 'Delivery Assigned', 'You have been assigned order #ORD-20261020-1002 from Burger Craft.', 'delivery/order_detail.php?id=2', 0, NOW());

