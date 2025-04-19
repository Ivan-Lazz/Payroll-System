<?php

class Account{
    private $conn;
    private $table_name = "employee_account";

    public $account_id;
    public $employee_id;
    public $account_email;
    public $account_pass;
    public $account_type;
    public $account_status;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->employee_id) || empty($this->account_email) || 
            empty($this->account_pass) || empty($this->account_type) || empty($this->account_status)) {
            return false;
        }
        
        // Sanitize input
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->account_email = filter_var($this->account_email, FILTER_SANITIZE_EMAIL);
        $this->account_pass = htmlspecialchars(strip_tags($this->account_pass));
        $this->account_type = htmlspecialchars(strip_tags($this->account_type));
        $this->account_status = htmlspecialchars(strip_tags($this->account_status));
        
        return true;
    }

    public function create(){
        if (!$this->validateInput()) {
            return false;
        }

        // Check if username exists
        if ($this->accountExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET account_id = :account_id,
                    employee_id = :employee_id,
                    account_email = :account_email,
                    account_password = :account_password,
                    account_type = :account_type,
                    account_status = :account_status";

        $stmt = $this->conn->prepare($query);

        // Hash password
        $password_hash = password_hash($this->account_pass, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":account_id", $this->account_id);
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":account_email", $this->account_email);
        $stmt->bindParam(":account_password", $password_hash);
        $stmt->bindParam(":account_type", $this->account_type);
        $stmt->bindParam(":account_status", $this->account_status);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readSingle($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $id);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        if (empty($this->firstname) || empty($this->lastname) || empty($this->id)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET account_email = :account_email,
                    account_password = :account_password,
                    account_type = :account_type,
                    account_status = :account_status
                WHERE account_id = :account_id";

        $stmt = $this->conn->prepare($query);

        // Hash password
        $password_hash = password_hash($this->account_pass, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":account_email", $this->account_email);
        $stmt->bindParam(":account_password", $password_hash);
        $stmt->bindParam(":account_type", $this->account_type);
        $stmt->bindParam(":account_status", $this->account_status);
        $stmt->bindParam(":account_id", $this->account_id);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $this->account_id);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function accountExists() {
        $query = "SELECT account_id FROM " . $this->table_name . " WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $this->account_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}

?>