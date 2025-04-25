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

    public $errors = [];
    public $pages;
    public $records_per_page;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        // Validation
        if (empty($this->account_id)) $this->errors[] = "Account ID is required.";
        if (empty($this->employee_id)) $this->errors[] = "Employee ID is required.";
        if (empty($this->account_email)) $this->errors[] = "Email is required.";
        if (empty($this->account_pass)) $this->errors[] = "Password is required.";
        if (empty($this->account_type)) $this->errors[] = "Account type is required.";
        if (empty($this->account_status)) $this->errors[] = "Account status is required.";

        $this->account_email = filter_var($this->account_email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($this->account_email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format.";
        }

        if (!empty($this->errors)) return false;
        
        // Sanitize input
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
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

    public function searchAccount($search = "", $acct_type = "") {
        $type_clause = "";
        $type = htmlspecialchars(strip_tags($acct_type));
        $type_term = "%{$type}%";
        if (!empty($acct_type)) {
            $type_clause = "AND account_type LIKE :acct_type";
        }
        $query = "SELECT * FROM " . $this->table_name . " WHERE account_email LIKE :search OR account_id LIKE :search " . $type_clause;
        $search = htmlspecialchars(strip_tags($search));
        $stmt = $this->conn->prepare($query);
        $search = "%{$search}%";
        $stmt->bindParam(":search", $search);
        if (!empty($acct_type)) {
            $stmt->bindParam(":account_type", $acct_type);
        }
        $stmt->execute();
        return $stmt;
    }

    public function countData($search = "", $acct_type = "") {
        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE firstname LIKE :search OR lastname LIKE :search";
        }
        // Prepare account type query
        $type_clause = "";
        $type = htmlspecialchars(strip_tags($acct_type));
        $type_term = "%{$type}%";
        if (!empty($acct_type)) {
            $type_clause = "AND account_type LIKE :acct_type";
            $where_clause .= " " . $type_clause;
        }
        // Count total records (with filter)
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $count_stmt = $this->conn->prepare($count_query);
        if (!empty($search)) {
            $count_stmt->bindParam(":search", $search_term);
        }
        if (!empty($acct_type)) {
            $count_stmt->bindParam(":account_type", $type_term);
        }
        $count_stmt->execute();

        return $count_stmt;
    }

    public function readPaginated($search = "", $acct_type = "") {
        $offset = ($this->pages - 1) * $this->records_per_page;
    
        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE firstname LIKE :search OR lastname LIKE :search OR username LIKE :search";
        }

        // Prepare account type query
        $type_clause = "";
        $type = htmlspecialchars(strip_tags($acct_type));
        $type_term = "%{$type}%";
        if (!empty($acct_type)) {
            $type_clause = "AND account_type LIKE :acct_type";
            $where_clause .= " " . $type_clause; 
        }
        
        // Count total records (with filter)
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $count_stmt = $this->conn->prepare($count_query);
        if (!empty($search)) {
            $count_stmt->bindParam(":search", $search_term);
        }
        if (!empty($acct_type)) {
            $count_stmt->bindParam(":account_type", $type_term);
        }
        $count_stmt->execute();
    
        // Fetch paginated data
        $query = "SELECT * FROM " . $this->table_name . " " . $where_clause . " LIMIT :offset, :records_per_page";
        $stmt = $this->conn->prepare($query);
        if (!empty($search)) {
            $stmt->bindParam(":search", $search_term);
        }
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":records_per_page", $this->records_per_page, PDO::PARAM_INT);
    
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
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