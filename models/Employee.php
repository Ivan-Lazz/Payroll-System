<?php

class Employee {
    private $conn;
    private $table_name = "employees";

    public $employee_id;
    public $firstname;
    public $lastname;
    public $contact_number;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->firstname) || empty($this->lastname) || 
            empty($this->contact_number) || empty($this->email)) {
            return false;
        }
        
        // Sanitize input
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        
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
                    email = :email";

        $stmt = $this->conn->prepare($query);

        // Hash password
        // $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);

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
                WHERE employee_id = :employee_id";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);
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