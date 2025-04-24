<?php

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $firstname;
    public $lastname;
    public $username;
    public $password;
    
    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        // Validation
        if (empty($this->firstname)) $this->errors[] = "First name is required.";
        if (empty($this->lastname)) $this->errors[] = "Last name is required.";
        if (empty($this->username)) $this->errors[] = "Username is required.";
        if (empty($this->password)) $this->errors[] = "Password is required.";
        
        if (!empty($this->errors)) return false;
        
        // Sanitize input
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = htmlspecialchars(strip_tags($this->password));
        
        return true;
    }

    public function create() {
        if (!$this->validateInput()) {
            return false;
        }

        // Check if username exists
        if ($this->usernameExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                SET firstname = :firstname,
                    lastname = :lastname,
                    username = :username,
                    password = :password";

        $stmt = $this->conn->prepare($query);

        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $password_hash);

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
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        // Modified validation for update
        if (empty($this->firstname) || empty($this->lastname) || empty($this->id)) {
            return false;
        }
        
        // Sanitize input
        $this->firstname = htmlspecialchars(strip_tags($this->firstname));
        $this->lastname = htmlspecialchars(strip_tags($this->lastname));

        // First, get the current password if a new one isn't provided
        if (empty($this->password)) {
            $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->password = $row['password'];
            $password_set = false;
        } else {
            $password_set = true;
        }

        $query = "UPDATE " . $this->table_name . "
                SET firstname = :firstname,
                    lastname = :lastname,
                    password = :password
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Only hash password if it's been changed
        $password_to_save = $password_set ? password_hash($this->password, PASSWORD_BCRYPT) : $this->password;

        // Bind values
        $stmt->bindParam(":firstname", $this->firstname);
        $stmt->bindParam(":lastname", $this->lastname);
        $stmt->bindParam(":password", $password_to_save);
        $stmt->bindParam(":id", $this->id);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>