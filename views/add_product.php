<?php
include '../assets/html/header.html';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Add New Product</h1>
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form id="addProductForm" action="../controllers/ProductController.php?action=addProduct" method="POST">
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
                        <label for="stock" class="form-label">Initial Stock</label>
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
                    <button type="submit" class="btn btn-success">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../assets/html/footer.html'; ?>
