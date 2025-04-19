<?php

include_once '../generator/payslipIDGen.php';

class Payslip{
    private $conn;
    private $table_name = "payslip";
    private $payslip_no_gen = new PayslipIDGen();

    public $payslip_no;
    public $employee_id;
    public $bank_account;
    public $salary;
    public $bonus;
    public $total_salary;
    public $person_in_charge;
    public $cutoff_date;
    public $date_of_payment;
    public $payment_status;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->employee_id) || empty($this->bank_account) || 
            empty($this->salary) || empty($this->bonus) || empty($this->total_salary) || 
            empty($this->person_in_charge) || empty($this->cutoff_date) || 
            empty($this->date_of_payment) || empty($this->payment_status)) {
            return false;
        }
        
        // Sanitize input
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->bank_account = htmlspecialchars(strip_tags($this->bank_account));
        $this->salary = htmlspecialchars(strip_tags($this->salary));
        $this->bonus = htmlspecialchars(strip_tags($this->bonus));
        $this->total_salary = htmlspecialchars(strip_tags($this->total_salary));
        $this->person_in_charge = htmlspecialchars(strip_tags($this->person_in_charge));
        $this->cutoff_date = htmlspecialchars(strip_tags($this->cutoff_date));
        $this->date_of_payment = htmlspecialchars(strip_tags($this->date_of_payment));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        
        return true;
    }

    public function create(){
        if (!$this->validateInput()) {
            return false;
        }

        $this->payslip_no = $this->payslip_no_gen->generate_payslip_no();

        $query = "INSERT INTO " . $this->table_name . "
                SET payslip_no = :payslip_no,
                    employee_id = :employee_id,
                    bank_account = :bank_account,
                    salary = :salary,
                    bonus = :bonus,
                    total_salary = :total_salary,
                    person_in_charge = :person_in_charge,
                    cutoff_date = :cutoff_date,
                    date_of_payment = :date_of_payment,
                    payment_status = :payment_status";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":payslip_no", $this->payslip_no);
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":bank_account", $this->bank_account);
        $stmt->bindParam(":salary", $this->salary);
        $stmt->bindParam(":bonus", $this->bonus);
        $stmt->bindParam(":total_salary", $this->total_salary);
        $stmt->bindParam(":person_in_charge", $this->person_in_charge);
        $stmt->bindParam(":cutoff_date", $this->cutoff_date);
        $stmt->bindParam(":date_of_payment", $this->date_of_payment);
        $stmt->bindParam(":payment_status", $this->payment_status);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function readAll(){
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY date_of_payment DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readSingle($payslip_no) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE payslip_no = :payslip_no";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":payslip_no", $payslip_no);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        if (!$this->validateInput()) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET employee_id = :employee_id,
                    bank_account = :bank_account,
                    salary = :salary,
                    bonus = :bonus,
                    total_salary = :total_salary,
                    person_in_charge = :person_in_charge,
                    cutoff_date = :cutoff_date,
                    date_of_payment = :date_of_payment,
                    payment_status = :payment_status
                WHERE payslip_no = :payslip_no";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":payslip_no", $this->payslip_no);
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":bank_account", $this->bank_account);
        $stmt->bindParam(":salary", $this->salary);
        $stmt->bindParam(":bonus", $this->bonus);
        $stmt->bindParam(":total_salary", $this->total_salary);
        $stmt->bindParam(":person_in_charge", $this->person_in_charge);
        $stmt->bindParam(":cutoff_date", $this->cutoff_date);
        $stmt->bindParam(":date_of_payment", $this->date_of_payment);
        $stmt->bindParam(":payment_status", $this->payment_status);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE payslip_no = :payslip_no";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":payslip_no", $this->payslip_no);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}

?>