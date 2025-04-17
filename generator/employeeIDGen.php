<?php

class EmployeeIDGen{
    private $numbers = '0123456789';

    private $database;
    private $conn;
    private $current_year;
    private $table_name = "employees";

    public function __construct() {
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        $this->current_year = date("Y");
    }

    public function generate_employee_id() {
        $last_id = $this->checkLastID();

        if ($last_id) {
            // Extract the last 5 digits and increment
            $last_number = (int)substr($last_id, -5);
            $next_number = $last_number + 1;
        } else {
            // If no ID exists, start from 1
            $next_number = 1;
        }

        // Check if max has been reached
        if ($next_number > 99999) {
            throw new Exception("Maximum employee ID limit reached for year {$this->current_year}.");
        }

        // Pad number to 5 digits and return new ID
        $padded = str_pad($next_number, 5, '0', STR_PAD_LEFT);
        $employee_id = $this->current_year . $padded;

        return $employee_id;
    }

    private function checkLastID() {
        $query = "SELECT employee_id FROM " . $this->table_name . " WHERE employee_id LIKE :yearPrefix ORDER BY employee_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $like = $this->current_year . '%';
        $stmt->bindParam(':yearPrefix', $like);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['employee_id'] : null;
    }
}


?>