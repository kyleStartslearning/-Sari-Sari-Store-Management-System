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
    case 'getMonthlySalesReport':
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

    case 'getDashboardStats':
        try {
            header('Content-Type: application/json');
            
            $currentYear = date('Y');
            $currentMonth = date('n');
            
            // Get current month stats
            $monthlyReport = $salesTransaction->getMonthlySalesReport($currentYear, $currentMonth);
            
            // Get basic stats from database
            $connection = $db->getConnection();
            
            $stmt = $connection->query("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
            $totalProducts = $stmt->fetch()['total_products'] ?? 0;
            
            $stmt = $connection->query("SELECT COUNT(*) as total_transactions FROM sales_transactions WHERE status = 'completed'");
            $totalTransactions = $stmt->fetch()['total_transactions'] ?? 0;
            
            $stats = [
                'total_products' => $totalProducts,
                'total_transactions' => $totalTransactions,
                'monthly_sales' => $monthlyReport['summary']['total_sales'] ?? 0,
                'monthly_transactions' => $monthlyReport['summary']['total_transactions'] ?? 0
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
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

    case 'getYearlyComparison':
        try {
            header('Content-Type: application/json');
            
            $year = (int)($_GET['year'] ?? date('Y'));
            
            $yearlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthReport = $salesTransaction->getMonthlySalesReport($year, $month);
                $yearlyData[] = [
                    'month' => $month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                    'total_sales' => $monthReport['summary']['total_sales'] ?? 0,
                    'total_transactions' => $monthReport['summary']['total_transactions'] ?? 0
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $yearlyData
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