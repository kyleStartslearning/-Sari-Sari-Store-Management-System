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

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAllProducts':
        try {
            header('Content-Type: application/json');
            
            $products = $product->getAllProducts();
           
            $categories = array_unique(array_column($products, 'category'));
            sort($categories);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'categories' => $categories
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'getById':
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

    case 'addProduct':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $name = $_POST['product_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $cost_price = (float)($_POST['cost_price'] ?? 0);
            $expiry_date = $_POST['expiry_date'] ?? null;

            if (empty($name) || empty($category) || $price <= 0 || $stock < 0) {
                throw new Exception('Please fill in all required fields');
            }

            $result = $product->addProduct($name, $description, $category, $price, $stock, $cost_price, $expiry_date);

            if ($result && isset($result['success']) && $result['success']) {
                $_SESSION['success'] = 'Product added successfully!';
                header("Location: ../views/products.php");
            } else {
                throw new Exception($result['message'] ?? 'Failed to add product');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../views/add_product.php");
        }
        exit;
        break;

    case 'updateProduct':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $id = (int)($_POST['product_id'] ?? 0);
            $name = $_POST['product_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $cost_price = (float)($_POST['cost_price'] ?? 0);
            $expiry_date = $_POST['expiry_date'] ?? null;

            if ($id <= 0 || empty($name) || empty($category) || $price <= 0 || $stock < 0) {
                throw new Exception('Please fill in all required fields');
            }

            $result = $product->updateProduct($id, $name, $description, $category, $price, $stock, $cost_price, $expiry_date);
            if ($result && isset($result['success']) && $result['success']) {
                $_SESSION['success'] = 'Product updated successfully!';
                header("Location: ../views/products.php");
                exit;
            } else {
                // Only set error if actually failed
                $_SESSION['error'] = $result['message'] ?? 'Failed to update product';
                header("Location: ../views/products.php");
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../views/products.php");
            exit;
        }
        break;

    case 'deleteProduct':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid product ID');
            }

            $result = $product->deleteProduct($id);

            if ($result && isset($result['success']) && $result['success']) {
                $_SESSION['success'] = 'Product deleted successfully!';
            } else {
                throw new Exception($result['message'] ?? 'Failed to delete product');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header("Location: ../views/products.php");
        exit;
        break;

    case 'addToCart':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            header('Content-Type: application/json');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON data');
            }
            
            $productId = $input['product_id'] ?? null;
            $quantity = $input['quantity'] ?? 1;
            
            if (!$productId) {
                throw new Exception('Product ID is required');
            }
            
            if ($quantity < 1) {
                throw new Exception('Quantity must be at least 1');
            }
            
            // Initialize cart in session if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Get product details to validate stock
            $productDetails = $product->getProductById($productId);
            
            if (!$productDetails) {
                throw new Exception('Product not found');
            }
            
            if ($productDetails['stock_quantity'] < $quantity) {
                throw new Exception('Insufficient stock. Available: ' . $productDetails['stock_quantity']);
            }
            
            // Check if product already in cart
            $existingKey = null;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $existingKey = $key;
                    break;
                }
            }
            
            if ($existingKey !== null) {
                // Update existing item quantity
                $newQuantity = $_SESSION['cart'][$existingKey]['quantity'] + $quantity;
                
                if ($newQuantity > $productDetails['stock_quantity']) {
                    throw new Exception('Total quantity exceeds available stock');
                }
                
                $_SESSION['cart'][$existingKey]['quantity'] = $newQuantity;
                $_SESSION['cart'][$existingKey]['total_price'] = $newQuantity * $productDetails['price'];
            } else {
                // Add new item to cart
                $_SESSION['cart'][] = [
                    'product_id' => $productId,
                    'product_name' => $productDetails['product_name'],
                    'price' => $productDetails['price'],
                    'quantity' => $quantity,
                    'total_price' => $quantity * $productDetails['price']
                ];
            }
            
            // Calculate total cart count
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'cart_count' => $cartCount
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'updateCart':
        try {
            header('Content-Type: application/json');
            
            $index = (int)($_GET['index'] ?? -1);
            $change = (int)($_GET['change'] ?? 0);

            if (!isset($_SESSION['cart']) || $index < 0 || !isset($_SESSION['cart'][$index])) {
                throw new Exception('Invalid cart item');
            }

            $newQuantity = $_SESSION['cart'][$index]['quantity'] + $change;
            
            if ($newQuantity <= 0) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            } else {
                $_SESSION['cart'][$index]['quantity'] = $newQuantity;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cart updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'removeFromCart':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            header('Content-Type: application/json');
            
            $input = json_decode(file_get_contents('php://input'), true);
            $index = (int)($input['index'] ?? -1);
            
            if (!isset($_SESSION['cart']) || $index < 0 || !isset($_SESSION['cart'][$index])) {
                throw new Exception('Invalid cart item');
            }
            
            // Remove item from cart
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
            
            // Calculate total cart count
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Product removed from cart',
                'cart_count' => $cartCount
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'clearCart':
        try {
            header('Content-Type: application/json');
            
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'getCartCount':
        try {
            header('Content-Type: application/json');
            
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            echo json_encode([
                'success' => true,
                'cart_count' => $cartCount
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'checkout':
        header("Location: ../views/checkout.php");
        exit;
        break;

    case 'processCheckout':
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                throw new Exception('Cart is empty');
            }

            $customerName = $_POST['customer_name'] ?? 'Guest Customer';
            $paymentMethod = $_POST['payment_method'] ?? '';

            if (empty($paymentMethod)) {
                throw new Exception('Please select a payment method');
            }

            // Calculate total
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create customer
            $customerModel = new Customer($db);
            $customerId = $customerModel->createCustomer($customerName);

            // Process transaction
            $salesTransaction = new SalesTransaction($db);
            $items = [];
            foreach ($_SESSION['cart'] as $item) {
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ];
            }

            $result = $salesTransaction->processSaleTransaction(
                $customerId,
                $total,
                $paymentMethod,
                0, 0,
                $customerName,
                $items
            );

            if ($result) {
                $_SESSION['cart'] = [];
                $_SESSION['success'] = 'Order processed successfully!';
                header("Location: ../views/MainDashboard.php");
            } else {
                throw new Exception('Failed to process order');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: ../views/checkout.php");
        }
        exit;
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