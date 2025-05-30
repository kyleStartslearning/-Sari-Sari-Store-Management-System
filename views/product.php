<?php
// Start session for notifications
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../assets/html/header.html';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Product Management</h1>
        <a href="add_product.php" class="btn btn-success">
            <i class="fas fa-circle-plus"></i> Add New Product <!-- Changed from fa-plus -->
        </a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-circle-check me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading products...</p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="d-none">
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage">Failed to load products</span>
        </div>
    </div>

    <!-- Products Container -->
    <div id="productsContainer" class="d-none">
        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label for="categoryFilter" class="form-label me-3 mb-0">
                                <i class="fas fa-filter me-2"></i>Filter by Category: <!-- Same icon -->
                            </label>
                            <select id="categoryFilter" class="form-select" style="width: auto;">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end">
                            <span class="me-3">
                                <i class="fas fa-cube me-1"></i> <!-- Changed from fa-box -->
                                <span id="productCount">0</span> products
                            </span>
                            <div class="input-group" style="width: 250px;">
                                <span class="input-group-text">
                                    <i class="fas fa-magnifying-glass"></i> <!-- Changed from fa-search -->
                                </span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Cost Price</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Products will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-trash-can me-2"></i>Confirm Delete <!-- Changed from fa-trash-alt -->
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-triangle-exclamation text-warning fa-3x mb-3"></i> <!-- Changed from fa-exclamation-triangle -->
                    <h5>Are you sure you want to delete this product?</h5>
                    <p class="text-muted mb-0" id="deleteProductName"></p>
                    <p class="text-danger mt-2">
                        <strong>This action cannot be undone!</strong>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-can me-2"></i>Delete Product <!-- Changed from fa-trash-alt -->
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let allProducts = [];
let filteredProducts = [];
let productToDelete = null;

// Load products when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    initializeEventListeners();
});

function initializeEventListeners() {
    // Category filter
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    
    // Search filter
    document.getElementById('searchInput').addEventListener('input', applyFilters);
    
    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteProduct);
}

function loadProducts() {
    fetch('../controllers/ProductController.php?action=getAllProducts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allProducts = data.data.products; // Instead of data.data
                populateCategories(data.data.categories); // Pass categories directly
                applyFilters();
                showProductsContainer();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load products');
        });
}

function showProductsContainer() {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('productsContainer').classList.remove('d-none');
}

function showError(message) {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
}

function populateCategories(categories) {
    const categoryFilter = document.getElementById('categoryFilter');
    
    // Clear existing options except "All Categories"
    categoryFilter.innerHTML = '<option value="">All Categories</option>';
    
    if (categories && categories.length > 0) {
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categoryFilter.appendChild(option);
        });
    }
}

function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    filteredProducts = allProducts.filter(product => {
        const matchesCategory = !categoryFilter || product.category === categoryFilter;
        const matchesSearch = !searchTerm || 
            product.product_name.toLowerCase().includes(searchTerm) ||
            product.product_description.toLowerCase().includes(searchTerm) ||
            product.category.toLowerCase().includes(searchTerm);
        
        return matchesCategory && matchesSearch;
    });
    
    displayProducts(filteredProducts);
    updateProductCount(filteredProducts.length);
}

function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = '';
    
    if (products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="fas fa-box-open me-2"></i>No products found
                </td>
            </tr>
        `;
        return;
    }
    
    products.forEach(product => {
        const row = document.createElement('tr');
        
        // Status badge
        const statusBadge = getStatusBadge(product);
        
        // Stock badge
        const stockClass = product.stock_quantity < 10 ? 'text-danger' : 'text-success';
        
        // Expiry date formatting
        const expiryDate = product.expiry_date ? 
            new Date(product.expiry_date).toLocaleDateString() : 
            '<span class="text-muted">N/A</span>';
        
        row.innerHTML = `
            <td class="fw-bold">${product.product_id}</td>
            <td>
                <div class="fw-semibold">${escapeHtml(product.product_name)}</div>
            </td>
            <td>
                <span class="badge bg-info text-dark">${escapeHtml(product.category)}</span>
            </td>
            <td>
                <div class="text-truncate" style="max-width: 200px;" title="${escapeHtml(product.product_description)}">
                    ${escapeHtml(product.product_description)}
                </div>
            </td>
            <td class="fw-bold text-success">₱${parseFloat(product.price).toFixed(2)}</td>
            <td class="${stockClass} fw-bold">${product.stock_quantity}</td>
            <td class="text-muted">₱${parseFloat(product.cost_price || 0).toFixed(2)}</td>
            <td>${expiryDate}</td>
            <td>${statusBadge}</td>
            <td class="text-center">
                <div class="btn-group" role="group">
                    <a href="edit_product.php?id=${product.product_id}" 
                       class="btn btn-sm btn-outline-primary" 
                       title="Edit Product">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            onclick="confirmDelete(${product.product_id}, '${escapeHtml(product.product_name)}')"
                            title="Delete Product">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function getStatusBadge(product) {
    const status = product.status || 'active';
    const isExpired = product.expiry_date && new Date(product.expiry_date) < new Date();
    const isLowStock = product.stock_quantity < 10;
    
    if (status === 'inactive') {
        return '<span class="badge bg-secondary">Inactive</span>';
    } else if (isExpired) {
        return '<span class="badge bg-danger">Expired</span>';
    } else if (product.stock_quantity <= 0) {
        return '<span class="badge bg-warning text-dark">Out of Stock</span>';
    } else if (isLowStock) {
        return '<span class="badge bg-warning text-dark">Low Stock</span>';
    } else {
        return '<span class="badge bg-success">Active</span>';
    }
}

function updateProductCount(count) {
    document.getElementById('productCount').textContent = count;
}

function confirmDelete(productId, productName) {
    productToDelete = productId;
    document.getElementById('deleteProductName').textContent = productName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

function deleteProduct() {
    if (!productToDelete) return;
    
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn.innerHTML;
    
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    
    fetch(`../controllers/ProductController.php?action=deleteProduct&id=${productToDelete}`)
        .then(response => {
            if (response.redirected) {
                window.location.reload();
            } else {
                throw new Error('Delete operation failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete product. Please try again.');
            
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalText;
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


setInterval(() => {
    loadProducts();
}, 30000);
</script>

<?php include_once '../assets/html/footer.html'; ?>
