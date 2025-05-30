<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<?php include_once '../assets/html/header.html'; ?>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Shopping Cart</h2>
                        
                        <?php if (empty($cart)): ?>
                            <div class="text-center py-5">
                                <h4>Your cart is empty</h4>
                                <a href="MainDashboard.php" class="btn btn-primary mt-3">Continue Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart as $index => $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <div class="input-group" style="width: 130px;">
                                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                                onclick="updateQuantity(<?php echo $index; ?>, -1)">-</button>
                                                        <input type="text" class="form-control text-center" 
                                                               value="<?php echo $item['quantity']; ?>" readonly>
                                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                                onclick="updateQuantity(<?php echo $index; ?>, 1)">+</button>
                                                    </div>
                                                </td>
                                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="removeItem(<?php echo $index; ?>)">Remove</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <a href="MainDashboard.php" class="btn btn-secondary">Continue Shopping</a>
                                    <button onclick="clearCart()" class="btn btn-danger ms-2">Clear Cart</button>
                                </div>
                                <form action="../controllers/ProductController.php" method="GET">
                                    <input type="hidden" name="action" value="checkout">
                                    <button type="submit" class="btn btn-success">Proceed to Checkout</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>    <script>
    function updateQuantity(index, change) {
        fetch(`../controllers/ProductController.php?action=updateCart&index=${index}&change=${change}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the cart');
            });
    }

    function removeItem(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            fetch(`../controllers/ProductController.php?action=removeFromCart&index=${index}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the item');
                });
        }
    }

    function clearCart() {
        if (confirm('Are you sure you want to clear your cart?')) {
            fetch('../controllers/ProductController.php?action=clearCart')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while clearing the cart');
                });
        }
    }
    </script>
<?php include_once '../assets/html/footer.html'; ?>
