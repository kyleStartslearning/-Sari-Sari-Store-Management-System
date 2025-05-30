<?php
class Product {
    private $db;
    private $id;
    private $name;
    private $description;
    private $category;
    private $price;
    private $stock_quantity;
    private $cost_price;
    private $expiry_date;
    private $status;

    public function __construct(Database $db) {
        $this->db = $db;
    }public function getAllProducts() {
        return $this->db->callProcedureSingle('GetAllProducts');
    }

    public function getProductById($productId) {
        try {
            
            $connection = $this->db->getConnection();
            $query = "SELECT * FROM products WHERE product_id = :product_id AND status = 'active'";
            $stmt = $connection->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function addProduct($name, $description, $category, $price, $stock, $cost_price, $expiry_date) {
        $result = $this->db->callProcedureSingle('AddProduct', [
            $name, $description, $category, $price, $stock, $cost_price, $expiry_date
        ]);
        return $result[0] ?? null;
    }

    public function updateProduct($id, $name, $description, $category, $price, $stock, $cost_price, $expiry_date) {
        try {
            $result = $this->db->callProcedureSingle('UpdateProduct', [
                $id, $name, $description, $category, $price, $stock, $cost_price, $expiry_date
            ]);
            
          
            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteProduct($id) {
        $result = $this->db->callProcedureSingle('DeleteProduct', [$id]);
        return $result[0] ?? null;
    }
}
