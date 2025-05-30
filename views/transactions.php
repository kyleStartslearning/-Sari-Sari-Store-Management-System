<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/html/header.html';

// Get filter parameters for display only
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transaction History</h2>
        
        <!-- Filters -->
        <div class="d-flex gap-2">
            <form id="filterForm" class="row g-2">
                <div class="col-auto">
                    <label class="form-label small">From:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $startDate; ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small">To:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $endDate; ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small">Search:</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-magnifying-glass"></i> <!-- ADD: Search icon -->
                        </span>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search receipt/customer" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                </div>
                <div class="col-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-arrows-rotate me-1"></i>Reset <!-- UPDATED: Icon name -->
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
        <p class="mt-2">
            <i class="fas fa-clock me-2"></i> <!-- ADD: Loading icon -->
            Loading transactions...
        </p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="d-none">
        <div class="alert alert-danger text-center">
            <i class="fas fa-triangle-exclamation me-2"></i> <!-- UPDATED: Icon name -->
            <span id="errorMessage">Failed to load transactions</span>
            <div class="mt-3">
                <button type="button" class="btn btn-primary" onclick="loadTransactions()">
                    <i class="fas fa-arrows-rotate me-2"></i>Try Again <!-- UPDATED: Icon name -->
                </button>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div id="transactionsContainer" class="d-none">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i> <!-- ADD: List icon -->
                    Transaction Records
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="transactionsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <!-- Transactions will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i> <!-- ADD: Receipt icon -->
                    Customer Receipt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="transactionDetails">
                    <!-- Transaction details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close <!-- ADD: Close icon -->
                </button>
                <button type="button" class="btn btn-success" onclick="printTransaction()">
                    <i class="fas fa-print me-1"></i>Print <!-- Icon already there -->
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let allTransactions = [];
let filteredTransactions = [];

// Load transactions when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    initializeEventListeners();
});

function loadTransactions() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`../controllers/TransactionController.php?action=getAllTransactions&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allTransactions = data.data;
                filteredTransactions = data.data;
                displayTransactions(filteredTransactions);
                showTransactionsContainer();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load transactions');
        });
}

function showTransactionsContainer() {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('transactionsContainer').classList.remove('d-none');
}

function showError(message) {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
}

function displayTransactions(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    tbody.innerHTML = '';
    
    if (transactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2"></i>No transactions found
                </td>
            </tr>
        `;
        return;
    }
    
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        
        // Format date
        const transactionDate = new Date(transaction.transaction_date);
        const formattedDate = transactionDate.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });
        
        // Payment method badge with icons
        const paymentBadge = getPaymentBadge(transaction.payment_method);
        
        row.innerHTML = `
            <td>
                ${formattedDate}
            </td>
            <td>
                ${escapeHtml(transaction.customer_info || 'Walk-in')}
            </td>
            <td>
                <span class="text-muted" title="${escapeHtml(transaction.items)}">
                    ${truncateText(transaction.items, 50)}
                </span>
            </td>
            <td>${paymentBadge}</td>
            <td>
                <i class="fas fa-peso-sign me-1 text-success"></i>
                <strong>₱${parseFloat(transaction.total_amount).toFixed(2)}</strong>
            </td>
            <td>
                <button type="button" class="btn btn-success btn-sm"
                        onclick="viewTransactionDetails('${transaction.transaction_id}')"
                        data-bs-toggle="tooltip" title="View Details">
                    <i class="fas fa-eye"></i> View
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Initialize tooltips
    initializeTooltips();
}

function getPaymentBadge(paymentMethod) {
    const method = (paymentMethod || 'cash').toLowerCase();
    const badges = {
        'cash': '<span class="badge bg-success"><i class="fas fa-money-bills me-1"></i>Cash</span>',
        'gcash': '<span class="badge bg-primary"><i class="fab fa-google-pay me-1"></i>GCash</span>',
        'paymaya': '<span class="badge bg-info"><i class="fas fa-mobile-alt me-1"></i>PayMaya</span>',
        'credit': '<span class="badge bg-warning"><i class="fas fa-credit-card me-1"></i>Credit</span>',
        'debit': '<span class="badge bg-secondary"><i class="fas fa-credit-card me-1"></i>Debit</span>'
    };
    
    return badges[method] || `<span class="badge bg-info"><i class="fas fa-wallet me-1"></i>${capitalizeFirst(method)}</span>`;
}

function initializeEventListeners() {
    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadTransactions();
    });
    
    // Search input for real-time filtering
    document.getElementById('search').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        if (searchTerm === '') {
            displayTransactions(allTransactions);
        } else {
            const filtered = allTransactions.filter(transaction => 
                transaction.customer_info.toLowerCase().includes(searchTerm) ||
                transaction.items.toLowerCase().includes(searchTerm) ||
                transaction.transaction_id.toString().includes(searchTerm)
            );
            displayTransactions(filtered);
        }
    });
}

function resetFilters() {
    document.getElementById('start_date').value = new Date().toISOString().slice(0, 10).slice(0, 8) + '01';
    document.getElementById('end_date').value = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().slice(0, 10);
    document.getElementById('search').value = '';
    loadTransactions();
}

function viewTransactionDetails(transactionId) {
    // Show loading in modal
    document.getElementById('transactionDetails').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading transaction details...</p>
        </div>
    `;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    modal.show();
    
    // Fetch transaction details
    fetch(`../controllers/TransactionController.php?action=getDetails&id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTransactionDetails(data.data);
            } else {
                document.getElementById('transactionDetails').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i>
                        Error loading transaction details: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('transactionDetails').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-triangle-exclamation me-2"></i>
                    An error occurred while loading transaction details
                </div>
            `;
        });
}

function displayTransactionDetails(details) {
    const transactionDate = new Date(details.transaction_date);
    
    let html = `
        <div class="transaction-details">
            <div class="text-center mb-3">
                <h4><i class="fas fa-receipt me-2"></i>Transaction Receipt</h4>
                <div class="row mt-3">
                    <div class="col-6">
                        <p class="mb-1"><strong><i class="fas fa-calendar me-1"></i>Date:</strong> ${transactionDate.toLocaleString()}</p>
                        <p class="mb-1"><strong><i class="fas fa-user me-1"></i>Customer:</strong> ${details.customer_name || 'Walk-in Customer'}</p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><strong><i class="fas fa-credit-card me-1"></i>Payment:</strong> ${capitalizeFirst(details.payment_method)}</p>
                        <p class="mb-1"><strong><i class="fas fa-hashtag me-1"></i>Receipt #:</strong> ${details.receipt_number || 'N/A'}</p>
                    </div>
                </div>
            </div>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-box me-1"></i>Item</th>
                        <th class="text-center"><i class="fas fa-hashtag me-1"></i>Qty</th>
                        <th class="text-end"><i class="fas fa-peso-sign me-1"></i>Price</th>
                        <th class="text-end"><i class="fas fa-calculator me-1"></i>Total</th>
                    </tr>
                </thead>
                <tbody>`;
    
    details.items.forEach(item => {
        html += `
            <tr>
                <td><i class="fas fa-cube me-1 text-muted"></i>${escapeHtml(item.product_name)}</td>
                <td class="text-center"><span class="badge bg-light text-dark">${item.quantity}</span></td>
                <td class="text-end">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                <td class="text-end"><strong>₱${parseFloat(item.total_price).toFixed(2)}</strong></td>
            </tr>`;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong><i class="fas fa-calculator me-1"></i>Total Amount:</strong></td>
                        <td class="text-end"><strong class="text-success fs-5">₱${parseFloat(details.total_amount).toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
    
    document.getElementById('transactionDetails').innerHTML = html;
}

function printTransaction() {
    const printContent = document.getElementById('transactionDetails').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Transaction Details</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .no-print { display: none; }
                        body { padding: 20px; }
                    }
                </style>
            </head>
            <body class="p-4">
                <h2 class="text-center mb-4">Sari-Sari Store - Customer Receipt</h2>
                ${printContent}
            </body>
        </html>
    `);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
</script>

<?php include '../assets/html/footer.html'; ?>
