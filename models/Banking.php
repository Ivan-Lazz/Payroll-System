<?php

class Banking{
    private $conn;
    private $table_name = "employee_banking_details";

    public $employee_id;
    public $preferred_bank;
    public $bank_account_number;
    public $bank_account_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->employee_id) || empty($this->preferred_bank) || 
            empty($this->bank_account_number) || empty($this->bank_account_name)) {
            return false;
        }
        
        // Sanitize input
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->preferred_bank = htmlspecialchars(strip_tags($this->preferred_bank));
        $this->bank_account_number = htmlspecialchars(strip_tags($this->bank_account_number));
        $this->bank_account_name = htmlspecialchars(strip_tags($this->bank_account_name));
        
        return true;
    }

    public function create(){
        if (!$this->validateInput()) {
            return false;
        }

        // Check if account already exists
        if ($this->accountExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET employee_id = :employee_id,
                    preferred_bank = :preferred_bank,
                    bank_account_number = :bank_account_number,
                    bank_account_name = :bank_account_name";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":preferred_bank", $this->preferred_bank);
        $stmt->bindParam(":bank_account_number", $this->bank_account_number);
        $stmt->bindParam(":bank_account_name", $this->bank_account_name);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function accountExists() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE employee_id = :employee_id AND bank_account_number = :bank_account_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":bank_account_number", $this->bank_account_number);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readSingle($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_id", $id);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        if (!$this->validateInput()) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET preferred_bank = :preferred_bank,
                    bank_account_number = :bank_account_number,
                    bank_account_name = :bank_account_name
                WHERE employee_id = :employee_id";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":preferred_bank", $this->preferred_bank);
        $stmt->bindParam(":bank_account_number", $this->bank_account_number);
        $stmt->bindParam(":bank_account_name", $this->bank_account_name);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_id", $this->employee_id);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}

?>