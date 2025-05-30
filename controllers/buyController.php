<?php
require_once '../config/Database.php';
require_once '../models/Product.php';
require_once '../models/SalesTransaction.php';
require_once '../models/Customer.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$product = new Product($db);
$salesTransaction = new SalesTransaction($db);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getProduct':
        try {
            header('Content-Type: application/json');
            
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid product ID');
            }

            $result = $product->getProductById($id);
            if (!$result) {
                throw new Exception('Product not found');
            }

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'processPurchase':
        try {
            header('Content-Type: application/json');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 0);
            $paymentMethod = $input['payment_method'] ?? '';

            if ($productId <= 0 || $quantity <= 0 || empty($paymentMethod)) {
                throw new Exception('Invalid input data');
            }

            $productDetails = $product->getProductById($productId);
            
            if (!$productDetails || $productDetails['stock_quantity'] < $quantity) {
                throw new Exception('Product not available or insufficient stock');
            }

            $customerModel = new Customer($db);
            $customerId = $customerModel->createCustomer('Guest Customer');
            
            $total = $productDetails['price'] * $quantity;
            
            $items = [
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $productDetails['price']
                ]
            ];

            $result = $salesTransaction->processSaleTransaction(
                $customerId,
                $total,
                $paymentMethod,
                0, 0,
                'Guest Customer',
                $items
            );

            if (!$result) {
                throw new Exception('Failed to process purchase');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Purchase completed successfully',
                'redirect' => '../views/MainDashboard.php'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}
?>