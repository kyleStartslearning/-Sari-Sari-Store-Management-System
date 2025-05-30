<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/html/header.html';

// Get filter parameters for display only
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Sales Reports</h2>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card shadow mb-4 report-panel">
        <div class="card-header">
            <h5 class="m-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form id="reportFilterForm" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select name="year" id="yearFilter" class="form-select">
                        <?php 
                        $currentYear = date('Y');
                        for($y = $currentYear; $y >= $currentYear - 2; $y--) {
                            $selected = $y == $year ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Month</label>
                    <select name="month" id="monthFilter" class="form-select">
                        <?php 
                        for($m = 1; $m <= 12; $m++) {
                            $selected = $m == $month ? 'selected' : '';
                            echo "<option value='$m' $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="startDateFilter" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" id="endDateFilter" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="fas fa-chart-line me-2"></i>Generate Report
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading reports...</p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="d-none">
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage">Failed to load reports</span>
        </div>
    </div>

    <!-- Reports Container -->
    <div id="reportsContainer" class="d-none">
        <!-- Monthly Summary Cards -->
        <div class="row mb-4" id="summaryCards">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Sales</h6>
                        <h3 class="mb-0" id="totalSales">₱0.00</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Transactions</h6>
                        <h3 class="mb-0" id="totalTransactions">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Average Transaction</h6>
                        <h3 class="mb-0" id="averageTransaction">₱0.00</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Highest Sale</h6>
                        <h3 class="mb-0" id="highestSale">₱0.00</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Sales Chart -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="m-0">Daily Sales Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart"></canvas>
            </div>
        </div>

        <!-- Product Sales Report -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="m-0">Product Sales Report</h5>
            </div>
            <div class="card-body">
                <!-- Loading State for Product Report -->
                <div id="productReportLoading" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading product sales...</span>
                </div>
                
                <div id="productReportContainer" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productSalesTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-center">Units Sold</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-end">Current Price</th>
                                </tr>
                            </thead>
                            <tbody id="productSalesTableBody">
                                <!-- Product sales will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Global variables
let currentMonthlyReport = null;
let currentProductReport = null;
let dailySalesChart = null;

// Load reports when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadReports();
    initializeEventListeners();
});

function initializeEventListeners() {
    document.getElementById('reportFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadReports();
    });
}

function loadReports() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    const startDate = document.getElementById('startDateFilter').value;
    const endDate = document.getElementById('endDateFilter').value;
    
    // Show loading state
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('reportsContainer').classList.add('d-none');
    document.getElementById('errorState').classList.add('d-none');
    
    // Load monthly report
    loadMonthlySalesReport(year, month);
    
    // Load product report
    loadProductSalesReport(startDate, endDate);
}

function loadMonthlySalesReport(year, month) {
    fetch(`../controllers/ReportsController.php?action=getMonthlySalesReport&year=${year}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentMonthlyReport = data.data;
                displayMonthlySummary(data.data);
                displayDailySalesChart(data.data.daily_sales);
                showReportsContainer();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load monthly sales report');
        });
}

function loadProductSalesReport(startDate, endDate) {
    document.getElementById('productReportLoading').classList.remove('d-none');
    document.getElementById('productReportContainer').classList.add('d-none');
    
    fetch(`../controllers/ReportsController.php?action=getProductSalesReport&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentProductReport = data.data;
                displayProductSalesReport(data.data);
                document.getElementById('productReportLoading').classList.add('d-none');
                document.getElementById('productReportContainer').classList.remove('d-none');
            } else {
                document.getElementById('productReportLoading').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading product report: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('productReportLoading').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    Failed to load product sales report
                </div>
            `;
        });
}

function showReportsContainer() {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('reportsContainer').classList.remove('d-none');
}

function showError(message) {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
}

function displayMonthlySummary(report) {
    const summary = report.summary || {};
    
    document.getElementById('totalSales').textContent = 
        '₱' + (parseFloat(summary.total_sales || 0)).toLocaleString('en-US', {minimumFractionDigits: 2});
    
    document.getElementById('totalTransactions').textContent = 
        (parseInt(summary.total_transactions || 0)).toLocaleString();
    
    document.getElementById('averageTransaction').textContent = 
        '₱' + (parseFloat(summary.average_transaction || 0)).toLocaleString('en-US', {minimumFractionDigits: 2});
    
    document.getElementById('highestSale').textContent = 
        '₱' + (parseFloat(summary.highest_sale || 0)).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function displayDailySalesChart(dailySales) {
    const ctx = document.getElementById('dailySalesChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (dailySalesChart) {
        dailySalesChart.destroy();
    }
    
    const chartData = dailySales || [];
    
    dailySalesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(day => new Date(day.sale_date).toLocaleDateString()),
            datasets: [
                {
                    label: 'Daily Sales (₱)',
                    data: chartData.map(day => parseFloat(day.daily_sales)),
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1,
                    yAxisID: 'y',
                    order: 1
                },
                {
                    label: 'Number of Transactions',
                    data: chartData.map(day => parseInt(day.total_transactions)),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1,
                    type: 'bar',
                    yAxisID: 'y1',
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Sales Amount (₱)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Transactions'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Daily Sales and Transactions'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function displayProductSalesReport(productReport) {
    const tbody = document.getElementById('productSalesTableBody');
    tbody.innerHTML = '';
    
    if (!productReport || productReport.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2"></i>No product sales data found for the selected period
                </td>
            </tr>
        `;
        return;
    }
    
    productReport.forEach(product => {
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>${escapeHtml(product.product_name)}</td>
            <td>${escapeHtml(product.category)}</td>
            <td class="text-center">${parseInt(product.total_sold || 0).toLocaleString()}</td>
            <td class="text-end">₱${parseFloat(product.total_revenue || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="text-center">
                <span class="badge ${product.current_stock < 10 ? 'bg-danger' : 'bg-success'}">
                    ${parseInt(product.current_stock || 0)}
                </span>
            </td>
            <td class="text-end">₱${parseFloat(product.current_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
        `;
        
        tbody.appendChild(row);
    });
}

function resetFilters() {
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;
    
    document.getElementById('yearFilter').value = currentYear;
    document.getElementById('monthFilter').value = currentMonth;
    document.getElementById('startDateFilter').value = new Date(currentYear, currentMonth - 1, 1).toISOString().slice(0, 10);
    document.getElementById('endDateFilter').value = new Date(currentYear, currentMonth, 0).toISOString().slice(0, 10);
    
    loadReports();
}

// Utility function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../assets/html/footer.html'; ?>
