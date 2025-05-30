<?php
class Customer {
    private $db;
    private $id;
    private $name;
    private $created_at;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getCustomerById($id) {
        try {
            $stmt = $this->db->getConnection()->prepare("SELECT * FROM customers WHERE customer_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting customer: " . $e->getMessage());
        }
    }

    public function createCustomer($name) {
        try {
            $stmt = $this->db->getConnection()->prepare("INSERT INTO customers (customer_name) VALUES (?)");
            $stmt->execute([$name]);
            return $this->db->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating customer: " . $e->getMessage());
        }
    }

    public function getAllCustomers() {
        try {
            $stmt = $this->db->query("SELECT * FROM customers ORDER BY customer_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting customers: " . $e->getMessage());
        }
    }
}
