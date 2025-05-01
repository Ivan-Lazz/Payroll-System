<?php

include_once '../generator/payslipIDGen.php';

class Payslip{
    private $conn;
    private $table_name = "payslip";
    private $payslip_no_gen = new PayslipIDGen();

    public $payslip_no;
    public $employee_id;
    public $bank_account;
    // public $salary;
    // public $bonus;
    public $total_salary;
    public $person_in_charge;
    public $cutoff_date;
    public $date_of_payment;
    public $payment_status;

    public $errors = [];
    public $pages;
    public $records_per_page;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateInput() {
        if (empty($this->employee_id)) $this->errors[] = "Employee ID is required.";
        if (empty($this->bank_account)) $this->errors[] = "Bank account is required.";
        // if (empty($this->salary)) $this->errors[] = "Salary is required.";
        // if (empty($this->bonus)) $this->errors[] = "Bonus is required.";
        if (empty($this->total_salary)) $this->errors[] = "Salary is required.";
        if (empty($this->person_in_charge)) $this->errors[] = "Person in charge is required.";
        if (empty($this->cutoff_date)) $this->errors[] = "Cutoff date is required.";
        if (empty($this->date_of_payment)) $this->errors[] = "Date of payment is required.";
        if (empty($this->payment_status)) $this->errors[] = "Payment status is required.";

        if (!empty($this->errors)) return false;
        
        // Sanitize input
        $this->employee_id = htmlspecialchars(strip_tags($this->employee_id));
        $this->bank_account = htmlspecialchars(strip_tags($this->bank_account));
        // $this->salary = htmlspecialchars(strip_tags($this->salary));
        // $this->bonus = htmlspecialchars(strip_tags($this->bonus));
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
                    salary = :total_salary,
                    person_in_charge = :person_in_charge,
                    cutoff_date = :cutoff_date,
                    date_of_payment = :date_of_payment,
                    payment_status = :payment_status";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":payslip_no", $this->payslip_no);
        $stmt->bindParam(":employee_id", $this->employee_id);
        $stmt->bindParam(":bank_account", $this->bank_account);
        // $stmt->bindParam(":salary", $this->salary);
        // $stmt->bindParam(":bonus", $this->bonus);
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
        $query = "SELECT * FROM " . $this->table_name . " WHERE payslip_no = :payslip_no LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":payslip_no", $payslip_no);
        $stmt->execute();
        return $stmt;
    }

    public function readPaginated($search = ""){
        $offset = ($this->pages - 1) * $this->records_per_page;

        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE payslip_no LIKE :search OR emp_name LIKE :search OR emp_id LIKE :search OR person_in_charge LIKE :search OR cutoff LIKE :search OR paid_date LIKE :search";
        }

        // Count total records (with filter)
        $count_res = $this->readDetailed($search);
        $total_records = $count_res->rowCount();
        $total_pages = ceil($total_records / $this->records_per_page);

        // Get paginated records (with filter)
        $query = "SELECT 
            pay.payslip_no AS payslip_no
            pay.employee_id AS emp_id
            CONCAT_WS(' ', emp.first_name, emp.last_name) AS emp_name
            pay.bank_acct AS bank_acct
            bank.bank_details AS bank
            pay.amount AS amount
            pay.person_in_charge AS person_in_charge
            pay.cutoff_date AS cutoff
            pay.date_of_payment AS paid_date
            pay.payment_status AS status
        FROM payslip AS pay
        INNER JOIN employee_account AS emp
            ON pay.employee_id = emp.employee_id
        INNER JOIN employee_banking_details AS bank
            ON pay.bank_acct = bank.bank_account
        ". $where_clause . "
        ORDER BY pay.date_of_payment DESC
        LIMIT :offset, :records_per_page
        ";
    }

    public function readDetailed($search = "") {
        // Prepare search query
        $search = htmlspecialchars(strip_tags($search));
        $search_term = "%" . $search . "%";
        $where_clause = "";
        if (!empty($search)) {
            $where_clause = "WHERE payslip_no LIKE :search OR emp_name LIKE :search OR emp_id LIKE :search OR person_in_charge LIKE :search OR cutoff LIKE :search OR paid_date LIKE :search";
        }

        $query = "SELECT 
            pay.payslip_no AS payslip_no
            pay.employee_id AS emp_id
            CONCAT_WS(' ', emp.first_name, emp.last_name) AS emp_name
            pay.bank_acct AS bank_acct
            bank.bank_details AS bank
            pay.amount AS amount
            pay.person_in_charge AS person_in_charge
            pay.cutoff_date AS cutoff
            pay.date_of_payment AS paid_date
            pay.payment_status AS status
        FROM payslip AS pay
        INNER JOIN employee_account AS emp
            ON pay.employee_id = emp.employee_id
        INNER JOIN employee_banking_details AS bank
            ON pay.bank_acct = bank.bank_account
        ". $where_clause . "
        ORDER BY pay.date_of_payment DESC
        ";
        $stmt = $this->conn->prepare($query);
        if (!empty($search)) {
            $stmt->bindParam(":search", $search_term);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readDetailedPay($payslip_no) {
        $query = "SELECT 
            pay.payslip_no AS payslip_no
            pay.employee_id AS emp_id
            CONCAT_WS(' ', emp.first_name, emp.last_name) AS emp_name
            pay.bank_acct AS bank_acct
            bank.bank_details AS bank
            pay.amount AS amount
            pay.person_in_charge AS person_in_charge
            pay.cutoff_date AS cutoff
            pay.date_of_payment AS paid_date
            pay.payment_status AS status
        FROM payslip AS pay
        INNER JOIN employee_account AS emp
            ON pay.employee_id = emp.employee_id
        INNER JOIN employee_banking_details AS bank
            ON pay.bank_acct = bank.bank_account
        WHERE pay.payslip_no = :payslip_no
        ORDER BY pay.date_of_payment DESC
        LIMIT 1
        ";
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
                    salary = :total_salary,
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
        // $stmt->bindParam(":salary", $this->salary);
        // $stmt->bindParam(":bonus", $this->bonus);
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