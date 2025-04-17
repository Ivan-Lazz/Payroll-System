<?php

class PayslipIDGen{

    private $database;
    private $conn;
    private $current_year;
    private $table_name = "payslip";

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
        $payslip_no = $this->current_year . $padded;

        return $payslip_no;
    }

    private function checkLastID() {
        $query = "SELECT payslip_no FROM " . $this->table_name . " WHERE payslip_no LIKE :yearPrefix ORDER BY payslip_no DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $like = $this->current_year . '%';
        $stmt->bindParam(':yearPrefix', $like);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['payslip_no'] : null;
    }

}

?>