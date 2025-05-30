<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'sari_sari_store';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return $this->conn;
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
            exit;
        }
    }

    public function query($query) {
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function executeProcedure($procedureName, $params = []) {
        try {
            $paramPlaceholders = str_repeat('?,', count($params));
            $paramPlaceholders = rtrim($paramPlaceholders, ',');
            $query = "CALL $procedureName(" . $paramPlaceholders . ")";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Error executing procedure $procedureName: " . $e->getMessage());
        }
    }

    public function callProcedureSingle($procedureName, $params = []) {
        $stmt = $this->executeProcedure($procedureName, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function callProcedureMulti($procedureName, $params = []) {
        $stmt = $this->executeProcedure($procedureName, $params);
        $results = [];
        do {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                $results[] = $result;
            }
        } while ($stmt->nextRowset());
        return $results;
    }
}
?>
