<?php
session_start();

if (!isset($_SESSION['last_order'])) {
    header("Location: MainDashboard.php");
    exit;
}

$order = $_SESSION['last_order'];
?>

<?php include_once '../assets/html/header.html'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-body">
                <!-- Store Header -->
                <div class="text-center mb-4">
                    <h2 class="mb-1">Sari-Sari Store</h2>
                    <p class="mb-0">Your Neighborhood One-Stop Shop</p>
                </div>

                <!-- Receipt Details -->
                <div class="border-bottom pb-2 mb-4">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Receipt #:</strong> <?php echo htmlspecialchars($order['receipt_number']); ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['date'])); ?></p>
                            <p class="mb-1"><strong>Payment:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Thank You Message -->
                <div class="text-center mt-4">
                    <h4 class="text-success">Thank you for shopping!</h4>
                    <p class="text-muted mb-4">Come again!</p>
                    
                    <div class="mt-4">
                        <a href="MainDashboard.php" class="btn btn-success btn-lg">Shop Again</a>
                        <button onclick="window.print()" class="btn btn-outline-primary btn-lg ms-2">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .navbar, .btn, footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .container {
        max-width: 100% !important;
    }
}
</style>

<?php 
// Clear the last order from session after displaying
unset($_SESSION['last_order']);
include_once '../assets/html/footer.html'; 
?>