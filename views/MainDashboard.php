<?php
// Start session for cart functionality
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../assets/html/header.html';
?>

<style>
/* ✅ OVERRIDE: Force content to be visible */
.dashboard-container {
    opacity: 1 !important;
    transform: none !important;
}
</style>

<div class="dashboard-container">
    <div class="main-card">
        <div class="dashboard-header">
            <!-- Dashboard Header -->
            <h1 class="dashboard-title">
                <i class="fas fa-gauge-high me-3"></i>
                Store Front
            </h1>
            <p class="dashboard-subtitle">Discover fresh products and amazing deals in our neighborhood store</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="d-none">
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span id="errorMessage">Failed to load products</span>
                <div class="mt-3">
                    <button onclick="location.reload()" class="btn btn-primary">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Container -->
        <div id="productsContainer">
            <!-- Category Filter Section -->
            <div class="filter-section mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 1.5rem; border-radius: 12px; border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label for="categoryFilter" class="form-label me-3 mb-0 fw-semibold text-dark">
                                <i class="fas fa-filter me-2 text-primary"></i>Filter by Category:
                            </label>
                            <select id="categoryFilter" class="form-select shadow-sm" style="width: auto; min-width: 200px;">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-cube me-1"></i>
                            <span id="productCount">0</span> products available
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="productsTable" class="table table-striped table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading products...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add to Cart Modal -->
<div class="modal fade" id="addToCartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h6 id="modalProductName" class="fw-bold"></h6>
                    <p class="text-muted mb-1">Price: ₱<span id="modalProductPrice"></span></p>
                    <p class="text-muted">Available Stock: <span id="modalProductStock" class="badge bg-info"></span></p>
                </div>
                <div class="mb-3">
                    <label for="quantityInput" class="form-label fw-semibold">
                        <i class="fas fa-hashtag me-1"></i>Quantity:
                    </label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center" id="quantityInput" value="1" min="1">
                        <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>Maximum quantity: <span id="maxQuantity"></span>
                    </div>
                </div>
                <div class="text-center">
                    <p class="fw-bold">Total: ₱<span id="modalTotalPrice">0.00</span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="addToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let allProducts = [];
let currentProductForCart = null;

// Load data when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    loadCartCount();
    initializeEventListeners();
});

function loadProducts() {
    console.log('Loading products...');
    
    fetch('../controllers/ProductController.php?action=getAllProducts')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.success) {
                allProducts = data.data.products;
                console.log('Products loaded:', allProducts.length);
                
                populateCategories(data.data.categories);
                displayProducts(allProducts);
                
                // Initialize DataTables after products are displayed
                initializeDataTable();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load products');
        });
}

function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = '';
    
    if (products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-box-open me-2"></i>No products available
                </td>
            </tr>
        `;
        return;
    }
    
    products.forEach(product => {
        const row = document.createElement('tr');
        const stockBadgeClass = product.stock_quantity < 10 ? 'bg-danger' : 'bg-success';
        const isOutOfStock = product.stock_quantity <= 0;
        
        row.innerHTML = `
            <td>${escapeHtml(product.product_name)}</td>
            <td>${escapeHtml(product.category)}</td>
            <td>${escapeHtml(product.product_description)}</td>
            <td>₱${parseFloat(product.price).toFixed(2)}</td>
            <td>
                <span class="badge ${stockBadgeClass}">
                    ${product.stock_quantity}
                </span>
            </td>
            <td>
                ${isOutOfStock ? 
                    '<button type="button" class="btn btn-sm btn-danger" disabled><i class="fas fa-circle-xmark me-1"></i>OUT OF STOCK</button>' :
                    `<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="buyProduct(${product.product_id})">
                            <i class="fas fa-bag-shopping me-1"></i>Buy
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="openAddToCartModal(${product.product_id}, '${escapeHtml(product.product_name)}', ${product.stock_quantity}, ${product.price})">
                            <i class="fas fa-cart-plus me-1"></i>Add to Cart
                        </button>
                    </div>`
                }
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    document.getElementById('productCount').textContent = products.length;
}

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#productsTable')) {
        $('#productsTable').DataTable().destroy();
    }
    
    $('#productsTable').DataTable({
        "pageLength": 10,
        "order": [[0, "asc"]],
        "columnDefs": [
            { "targets": [3], "className": "text-end" },
            { "targets": [4], "className": "text-center" },
            { "targets": [5], "orderable": false, "searchable": false, "className": "text-center" }
        ]
    });
}

function populateCategories(categories) {
    const categoryFilter = document.getElementById('categoryFilter');
    categoryFilter.innerHTML = '<option value="">All Categories</option>';
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
}

function initializeEventListeners() {
    document.getElementById('categoryFilter').addEventListener('change', function() {
        const selectedCategory = this.value;
        
        if (selectedCategory === '') {
            displayProducts(allProducts);
        } else {
            const filtered = allProducts.filter(product => product.category === selectedCategory);
            displayProducts(filtered);
        }
        
        // Reinitialize DataTable after filtering
        initializeDataTable();
    });
}

function loadCartCount() {
    fetch('../controllers/ProductController.php?action=getCartCount')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => console.error('Error loading cart count:', error));
}

function openAddToCartModal(productId, productName, stock, price) {
    currentProductForCart = {
        id: productId,
        name: productName,
        stock: stock,
        price: parseFloat(price)
    };
    
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalProductPrice').textContent = price;
    document.getElementById('modalProductStock').textContent = stock;
    document.getElementById('maxQuantity').textContent = stock;
    document.getElementById('quantityInput').max = stock;
    document.getElementById('quantityInput').value = 1;
    
    updateModalTotal();
    
    const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
    modal.show();
}

function increaseQuantity() {
    const input = document.getElementById('quantityInput');
    const maxStock = parseInt(input.max);
    const currentValue = parseInt(input.value);
    
    if (currentValue < maxStock) {
        input.value = currentValue + 1;
        updateModalTotal();
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantityInput');
    const currentValue = parseInt(input.value);
    
    if (currentValue > 1) {
        input.value = currentValue - 1;
        updateModalTotal();
    }
}

function updateModalTotal() {
    if (currentProductForCart) {
        const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
        const total = currentProductForCart.price * quantity;
        document.getElementById('modalTotalPrice').textContent = total.toFixed(2);
    }
}

function addToCart() {
    if (!currentProductForCart) return;
    
    const quantity = parseInt(document.getElementById('quantityInput').value);
    
    if (isNaN(quantity) || quantity < 1) {
        alert('Please enter a valid quantity');
        return;
    }
    
    const formData = {
        product_id: currentProductForCart.id,
        quantity: quantity
    };
    
    fetch('../controllers/ProductController.php?action=addToCart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Product added to cart successfully!');
            updateCartCount(data.cart_count);
            bootstrap.Modal.getInstance(document.getElementById('addToCartModal')).hide();
            
            currentProductForCart = null;
            document.getElementById('quantityInput').value = 1;
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred while adding to cart');
    });
}

function buyProduct(productId) {
    if (confirm('🛒 Are you sure you want to buy this product?')) {
        window.location.href = `buy.php?product_id=${productId}`;
    }
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}

function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
    document.getElementById('productsContainer').classList.add('d-none');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include_once '../assets/html/footer.html'; ?>
