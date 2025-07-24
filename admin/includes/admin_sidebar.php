<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>"
                    href="index.php">
                    <i class="bi bi-house-door-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'product_manager.php') ? 'active' : ''; ?>"
                    href="product_manager.php">
                    <i class="bi bi-box-seam-fill"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'add-product.php') ? 'active' : ''; ?>"
                    href="add-product.php">
                    <i class="bi bi-plus-square-fill"></i> Add Product
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'order_manager.php') ? 'active' : ''; ?>"
                    href="order_manager.php">
                    <i class="bi bi-card-list"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'customer_manager.php') ? 'active' : ''; ?>"
                    href="customer_manager.php">
                    <i class="bi bi-people-fill"></i> Customers
                </a>
            </li>
            <?php if ($is_super_admin): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_management.php') ? 'active' : ''; ?>"
                        href="admin_management.php">
                        <i class="bi bi-person-lock"></i> Admin Users
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-bar-chart-line-fill"></i> Reports
                </a>
            </li>
        </ul>
    </div>
</nav>