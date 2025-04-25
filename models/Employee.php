<?php

class Employee {
    private $conn;
    private $table_name = "employees";

    public $employee_id;
    public $firstname;
    public $lastname;
    public $contact_number;
    public $email;
    public $accounts;

    public $errors = [];
    public $pages;
    public $records_per_page;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->employee_id)) $this->errors[] = "Employee ID is required.";
        if (empty($this->firstname)) $this->errors[] = "First name is required.";
        if (empty($this->lastname)) $this->errors[] = "Last name is required.";
        if (empty($this->contact_number)) $this->errors[] = "Contact number is required.";
        if (empty($this->email)) $this->errors[] = "Email is required.";
        if (empty($this->accounts)) $this->errors[] = "Accounts are required.";

        if (!empty($this->errors)) return false;
        
        // Sanitize input
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->accounts = htmlspecialchars(strip_tags($this->accounts));
        
        return true;
    }

    public function create() {
        if (!$this->validateInput()) {
            return false;
        }

        // Check if username exists
        if ($this->employeeExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET employee_id = :employee_id,
                    firstname = :firstname,
                    lastname = :lastname,
                    contact_number = :contact_number,
                    email = :email
                    accounts = :accounts";

        $stmt = $this->conn->prepare($query);

        // Hash password
        // $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":accounts", $this->accounts);

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

    public function readSingle($employee_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE employee_id = :employee_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_id", $employee_id);
        $stmt->execute();
        return $stmt;
    }

    
    public function searchEmployee($search = ""){
        $query = "SELECT * FROM " . $this->table_name . " WHERE firstname LIKE :search OR lastname LIKE :search OR employee_id LIKE :search";
        $stmt = $this->conn->prepare($query);
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%$search%";
        $stmt->bindParam(":search", $search_term);
        $stmt->execute();
        return $stmt;
    }

    public function countData($search = "") {
        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE firstname LIKE :search OR lastname LIKE :search";
        }
        
        // Count total records (with filter)
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $count_stmt = $this->conn->prepare($count_query);
        if (!empty($search)) {
            $count_stmt->bindParam(":search", $search_term);
        }
        $count_stmt->execute();

        return $count_stmt;
    }

    public function readPaginated($search = "") {
        // $this->pages = max(1, (int)$page);
        // $this->records_per_page = max(1, (int)$records_per_page);
        $offset = ($this->pages - 1) * $this->records_per_page;
    
        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE firstname LIKE :search OR lastname LIKE :search OR employee_id LIKE :search";
        }
    
        // Count total records (with filter)
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        $count_stmt = $this->conn->prepare($count_query);
        if (!empty($search)) {
            $count_stmt->bindParam(":search", $search_term);
        }
        $count_stmt->execute();
        $total_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_records = (int)$total_row['total'];
        $total_pages = ceil($total_records / $this->records_per_page);
    
        // Get paginated data (with filter)
        $query = "SELECT * FROM " . $this->table_name . " " . $where_clause . " ORDER BY id ASC LIMIT :offset, :records_per_page";
        $stmt = $this->conn->prepare($query);
        if (!empty($search)) {
            $stmt->bindParam(":search", $search_term);
        }
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":records_per_page", $this->records_per_page, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt;
    }

    public function update() {
        // Modified validation for update
        if (empty($this->firstname) || empty($this->lastname) || empty($this->employee_id)) {
            return false;
        }
        
        // Sanitize input
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);

        $query = "UPDATE " . $this->table_name . "
                SET firstname = :firstname,
                    lastname = :lastname,
                    contact_number = :contact_number,
                    email = :email
                    accounts = :accounts
                WHERE employee_id = :employee_id";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":accounts", $this->accounts);
        $stmt->bindParam(":employee_id", $this->employee_id);

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

    public function employeeExists() {
        $query = "SELECT employee_id FROM " . $this->table_name . " WHERE first_name = :first_name AND last_name = :last_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":first_name", $this->firstname);
        $stmt->bindParam(":last_name", $this->lastname);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>