<?php
class SalesTransaction {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }public function processSaleTransaction($customer_id, $total_amount, $payment_method, $discount_amount, $tax_amount, $cashier_name, $items) {
        $result = $this->db->callProcedureSingle('ProcessSaleTransaction', [
            $customer_id, $total_amount, $payment_method, $discount_amount, 
            $tax_amount, $cashier_name, json_encode($items)
        ]);
        return $result[0] ?? null;
    }

    public function getTransactionDetails($transaction_id) {
        $results = $this->db->callProcedureMulti('GetTransactionDetails', [$transaction_id]);
        return [
            'transaction' => $results[0][0] ?? null,
            'items' => $results[1] ?? []
        ];
    }

    public function getMonthlySalesReport($year, $month) {
        $results = $this->db->callProcedureMulti('GetMonthlySalesReport', [$year, $month]);
        return [
            'daily_sales' => $results[0] ?? [],
            'summary' => $results[1][0] ?? null
        ];
    }

    public function getProductSalesReport($start_date, $end_date) {
        return $this->db->callProcedureSingle('GetProductSalesReport', [$start_date, $end_date]);
    }

    public function getAllCustomerTransactions($start_date, $end_date) {
        return $this->db->callProcedureSingle('GetAllCustomerTransactions', [$start_date, $end_date]);
    }
}
