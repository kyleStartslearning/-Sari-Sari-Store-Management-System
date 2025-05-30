<?php
require_once '../config/Database.php';
require_once '../models/SalesTransaction.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$salesTransaction = new SalesTransaction($db);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getDetails':
        try {
            $transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if (!$transactionId) {
                throw new Exception("Transaction ID is required");
            }

            $details = $salesTransaction->getTransactionDetails($transactionId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => array_merge($details['transaction'], ['items' => $details['items']])
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

    case 'getAllTransactions':
        try {
            header('Content-Type: application/json');
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $transactions = $salesTransaction->getAllCustomerTransactions($startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'data' => $transactions
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

    case 'getMonthlyReport':
        try {
            header('Content-Type: application/json');
            
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = (int)($_GET['month'] ?? date('n'));
            
            $report = $salesTransaction->getMonthlySalesReport($year, $month);
            
            echo json_encode([
                'success' => true,
                'data' => $report
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

    case 'getProductSalesReport':
        try {
            header('Content-Type: application/json');
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $report = $salesTransaction->getProductSalesReport($startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'data' => $report
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
