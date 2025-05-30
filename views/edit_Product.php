<?php
// Start session for notifications
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/html/header.html';

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no product ID provided
if (!$productId) {
    $_SESSION['error'] = 'Product ID is required';
    header("Location: products.php");
    exit;
}
?>

<!-- Loading State -->
<div id="loadingState" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2">Loading product details...</p>
</div>

<!-- Error State -->
<div id="errorState" class="d-none">
    <div class="container-fluid py-4">
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage">Product not found</span>
            <div class="mt-3">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Products
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Form (Keep Your Original Design) -->
<div id="editProductContainer" class="d-none">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Edit Product</h1>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form id="editProductForm" action="../controllers/ProductController.php?action=updateProduct" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Beverages">Beverages</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Personal Care">Personal Care</option>
                                <option value="Household">Household</option>
                                <option value="Food Items">Food Items</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="stock" name="stock" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Load product data when page loads
document.addEventListener('DOMContentLoaded', function() {
    const productId = <?php echo $productId; ?>;
    loadProductData(productId);
});

function loadProductData(productId) {
    fetch(`../controllers/ProductController.php?action=getById&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateForm(data.data);
                showEditContainer();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load product details');
        });
}

function populateForm(product) {
    document.getElementById('product_name').value = product.product_name || '';
    document.getElementById('category').value = product.category || '';
    document.getElementById('description').value = product.product_description || '';
    document.getElementById('price').value = parseFloat(product.price || 0).toFixed(2);
    document.getElementById('cost_price').value = parseFloat(product.cost_price || 0).toFixed(2);
    document.getElementById('stock').value = product.stock_quantity || 0;
    
    // Handle expiry date
    if (product.expiry_date && product.expiry_date !== '0000-00-00') {
        document.getElementById('expiry_date').value = product.expiry_date;
    }
}

function showEditContainer() {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('editProductContainer').classList.remove('d-none');
}

function showError(message) {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
}
</script>

<?php include '../assets/html/footer.html'; ?>
