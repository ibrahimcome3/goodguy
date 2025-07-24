<?php
// Include common admin header (session start, login check, variable setup, and navbar)
include 'includes/admin_header.php';
// The variables $admin_username and $is_super_admin are now available from admin_header.php
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Page Content -->
        <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <i class="bi bi-calendar3"></i> This week
                    </button>
                </div>
            </div>

            <!-- Example Dashboard Widgets/Cards -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-cart-check-fill"></i> New Orders</h5>
                            <p class="card-text">XX</p> <!-- Replace XX with dynamic data -->
                            <a href="order_manager.php?status=pending" class="text-white">View Details <i
                                    class="bi bi-arrow-right-circle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people-fill"></i> Registered Users</h5>
                            <p class="card-text">YY</p> <!-- Replace YY with dynamic data -->
                            <a href="customer_manager.php" class="text-white">View Details <i
                                    class="bi bi-arrow-right-circle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-box-seam"></i> Total Products</h5>
                            <p class="card-text">ZZ</p> <!-- Replace ZZ with dynamic data -->
                            <a href="product_manager.php" class="text-white">View Details <i
                                    class="bi bi-arrow-right-circle"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- You can add charts or more detailed reports here -->
            <h2>Recent Activity</h2>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Activity</th>
                            <th scope="col">User</th>
                            <th scope="col">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>New product added</td>
                            <td>AdminUser1</td>
                            <td>2023-10-27 10:00</td>
                        </tr>
                        <!-- Add more rows dynamically -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- <script src="script.js"></script> --> <!-- Your custom JavaScript if needed -->
</body>

</html>