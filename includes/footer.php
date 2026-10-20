<?php
/**
 * Shared Footer Component
 * Online Food Ordering System - Version 1.0
 */
?>
    </main>

    <!-- Quick Role Switcher / Demo Credentials Helper Bar -->
    <div class="no-print" style="background:#0f172a;color:#94a3b8;font-size:0.8rem;padding:0.6rem 0;border-top:1px solid #1e293b;">
        <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
            <div>
                <strong style="color:#f8fafc;">Demo Accounts (Password: <code>Password123</code>):</strong>
                <span style="margin-left:8px;">Admin: <code>admin@foodie.com</code></span> |
                <span style="margin-left:8px;">Restaurant: <code>owner.punjab@foodie.com</code></span> |
                <span style="margin-left:8px;">Delivery: <code>delivery.vikram@foodie.com</code></span> |
                <span style="margin-left:8px;">Customer: <code>tanmay@example.com</code></span>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/install.php" style="color:#ff642f;font-weight:600;">Reset/Seed DB</a>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="brand" style="color:white;margin-bottom:1rem;">
                        <span class="brand-icon">🍽️</span>
                        <span>Foodie<span class="brand-accent">Express</span></span>
                    </div>
                    <p style="color:#94a3b8;font-size:0.9rem;line-height:1.6;margin-bottom:1rem;">
                        A full-featured web-based Online Food Ordering & Delivery System engineered strictly according to IEEE 830 Software Requirements Specifications.
                    </p>
                    <p style="color:#cbd5e1;font-size:0.85rem;">
                        <strong>Academic Project:</strong> Tanmay Gole (Reg. No. 2430030098)<br>
                        Manipal University Jaipur, BTech Information Technology
                    </p>
                </div>
                <div class="footer-col">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/customer/restaurants.php">All Restaurants</a></li>
                        <li><a href="<?= BASE_URL ?>/customer/cart.php">My Cart</a></li>
                        <li><a href="<?= BASE_URL ?>/customer/orders.php">Order History</a></li>
                        <li><a href="<?= BASE_URL ?>/auth/login.php">Account Login</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Partner With Us</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/auth/register.php?role=restaurant_owner">Register Restaurant</a></li>
                        <li><a href="<?= BASE_URL ?>/auth/register.php?role=delivery_agent">Become Delivery Agent</a></li>
                        <li><a href="<?= BASE_URL ?>/restaurant/dashboard.php">Restaurant Dashboard</a></li>
                        <li><a href="<?= BASE_URL ?>/delivery/dashboard.php">Delivery Portal</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Administration</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
                        <li><a href="<?= BASE_URL ?>/admin/restaurants.php">Approve Restaurants</a></li>
                        <li><a href="<?= BASE_URL ?>/admin/users.php">Manage Users</a></li>
                        <li><a href="<?= BASE_URL ?>/admin/reports.php">Platform Reports</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> FoodieExpress. Online Food Ordering System v<?= APP_VERSION ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
</body>
</html>
