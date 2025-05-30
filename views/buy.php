<?php
// Start session for error/success messages only
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get product ID from URL
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Redirect if no product ID provided
if (!$productId) {
    header("Location: MainDashboard.php");
    exit;
}
?>

<?php include_once '../assets/html/header.html'; ?>

<div class="container-fluid py-4">
    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading product details...</p>
    </div>

    <!-- Error State -->
    <div id="errorState" class="d-none">
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage">Product not found</span>
        </div>
    </div>

    <!-- Product Buy Form -->
    <div id="buyProductContainer" class="d-none">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Buy Product</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <h4 id="productName"></h4>
                                <p class="text-muted" id="productDescription"></p>
                                <p><strong>Category:</strong> <span id="productCategory"></span></p>
                                <p><strong>Price:</strong> ₱<span id="productPrice"></span></p>
                                <p>
                                    <strong>Stock:</strong> 
                                    <span id="stockBadge" class="badge"></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <form id="buyProductForm" class="needs-validation" novalidate>
                                    <input type="hidden" id="product_id" value="<?php echo $productId; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" type="button" onclick="decreaseBuyQuantity()">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control text-center" id="quantity" 
                                                   value="1" min="1" required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="increaseBuyQuantity()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" id="payment_method" required>
                                            <option value="">Select payment method</option>
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <option value="paymaya">PayMaya</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5>Total Amount: ₱<span id="totalAmount">0.00</span></h5>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary" id="confirmPurchaseBtn">
                                            <i class="fas fa-shopping-cart me-2"></i>Confirm Purchase
                                        </button>
                                        <a href="MainDashboard.php" class="btn btn-secondary ms-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load product data when page loads
document.addEventListener('DOMContentLoaded', function() {
    const productId = document.getElementById('product_id').value;
    loadProductDetails(productId);
    initializeBuyForm();
});

function loadProductDetails(productId) {
    fetch(`../controllers/BuyController.php?action=getProduct&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProductDetails(data.data);
            } else {
                showError(data.message);
            }
        })
        .catch(error => showError('Failed to load product details'));
}

function displayProductDetails(product) {
    document.getElementById('loadingState').classList.add('d-none');
    
    document.getElementById('productName').textContent = product.product_name;
    document.getElementById('productDescription').textContent = product.product_description;
    document.getElementById('productCategory').textContent = product.category;
    document.getElementById('productPrice').textContent = parseFloat(product.price).toFixed(2);
    
    const stockBadge = document.getElementById('stockBadge');
    stockBadge.textContent = product.stock_quantity;
    stockBadge.className = `badge ${product.stock_quantity < 10 ? 'bg-danger' : 'bg-success'}`;
    
    document.getElementById('quantity').max = product.stock_quantity;
    document.getElementById('buyProductContainer').classList.remove('d-none');
    
    updateBuyTotalAmount(product.price);
    window.productData = product;
}

function showError(message) {
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorState').classList.remove('d-none');
}

function increaseBuyQuantity() {
    const quantityInput = document.getElementById('quantity');
    const maxStock = parseInt(quantityInput.max);
    const currentValue = parseInt(quantityInput.value);
    
    if (currentValue < maxStock) {
        quantityInput.value = currentValue + 1;
        updateBuyTotalAmount();
    }
}

function decreaseBuyQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
        updateBuyTotalAmount();
    }
}

function updateBuyTotalAmount() {
    if (window.productData) {
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        const price = parseFloat(window.productData.price);
        const total = quantity * price;
        
        document.getElementById('totalAmount').textContent = total.toFixed(2);
    }
}

function initializeBuyForm() {
    document.getElementById('buyProductForm').addEventListener('submit', function(event) {
        event.preventDefault();
        submitBuyForm();
    });
    
    document.getElementById('quantity').addEventListener('input', updateBuyTotalAmount);
}

function submitBuyForm() {
    const formData = {
        product_id: document.getElementById('product_id').value,
        quantity: document.getElementById('quantity').value,
        payment_method: document.getElementById('payment_method').value
    };
    
    fetch('../controllers/BuyController.php?action=processPurchase', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Purchase completed successfully!');
            window.location.href = data.redirect || 'MainDashboard.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred while processing your purchase');
    });
}
</script>

<?php include_once '../assets/html/footer.html'; ?>
