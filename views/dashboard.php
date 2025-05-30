<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/html/header.html';
?>

<div class="container-fluid py-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-gauge-high me-3"></i> <!-- Changed from fa-tachometer-alt -->
            Dashboard Overview
        </h1>
        <p class="dashboard-subtitle">Welcome back! Here's what's happening in your store today.</p>
    </div>

    <!-- ❌ REMOVED: Statistics Cards Section -->
    
    <!-- Quick Actions Section -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h3>
        </div>
        
        <div class="quick-actions">
            <a href="add_product.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-circle-plus"></i> <!-- Changed from fa-plus-circle -->
                </div>
                <h4 class="quick-action-title">Add New Product</h4>
                <p class="quick-action-desc">Add a new item to your inventory</p>
            </a>
            
            <a href="products.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-cubes"></i> <!-- Changed from fa-boxes -->
                </div>
                <h4 class="quick-action-title">Manage Products</h4>
                <p class="quick-action-desc">View and edit your product catalog</p>
            </a>
            
            <a href="transactions.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-simple"></i> <!-- Changed from fa-chart-line -->
                </div>
                <h4 class="quick-action-title">View Transactions</h4>
                <p class="quick-action-desc">Monitor sales and transaction history</p>
            </a>
            
            <a href="reports.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-chart-column"></i> <!-- Changed from fa-chart-bar -->
                </div>
                <h4 class="quick-action-title">Generate Reports</h4>
                <p class="quick-action-desc">Create detailed business reports</p>
            </a>
        </div>
    </div>

    <!-- Store Management Section -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-gears"></i> <!-- Changed from fa-cogs -->
                Store Management
            </h3>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="d-grid">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-cube"></i> <!-- Changed from fa-box -->
                        Manage Inventory
                    </a>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-grid">
                    <a href="reports.php" class="btn btn-success">
                        <i class="fas fa-chart-pie"></i> <!-- Same icon -->
                        Sales Reports
                    </a>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-grid">
                    <a href="MainDashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-shop"></i> <!-- Changed from fa-storefront -->
                        View Store Front
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../assets/html/footer.html'; ?>
