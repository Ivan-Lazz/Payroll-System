<?php

require_once './models/Employee.php';

class EmployeeController{
    private $employeeModel;

    public function __construct($db) {
        $this->employeeModel = new Employee($db);
    }
    
    public function handleRequest($method, $employee_id = null) {
        switch ($method) {
            case 'GET':
                if ($employee_id) {
                    $this->getEmployeeById($employee_id);
                } else if(isset($_GET['page']) && isset($_GET['records_per_page'])) {
                    $page = $_GET['page'];
                    $records_per_page = $_GET['records_per_page'];
                    $search = isset($_GET['search']) ? $_GET['search'] : null;
                    $this->getPaginatedEmployees($page, $records_per_page, $search);
                } else {
                    $this->getAllEmployee();
                }
                break;
            case 'POST':
                $this->createEmployee();
                break;
            case 'PUT':
                if ($employee_id) {
                    $this->employeeModel->employee_id = $employee_id;
                    $this->updateEmployee();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Employee ID is required for update."));
                }
                break;
            case 'DELETE':
                if ($employee_id) {
                    $this->employeeModel->employee_id = $employee_id;
                    $this->deleteEmployee();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Employee ID is required for deletion."));
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(array("message" => "Method not allowed."));
        }
    }

    private function getAllEmployee() {
        $stmt = $this->employeeModel->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $employees_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $employee_item = array(
                    "employee_id" => $employee_id,
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "contact_number" => $contact_number,
                    "email" => $email,
                    "accounts" => $accounts
                );
                array_push($employees_arr, $employee_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status" => 200,
                "success" => true,
                "message" => "Employees retrieved successfully.",
                "data" => $employees_arr));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status" => 404,
                "success" => false,
                "message" => "No employees found."));
        }
    }

    private function getEmployeeById($employee_id) {
        $stmt = $this->employeeModel->readSingle($employee_id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            extract($row);
            $employee_item = array(
                "employee_id" => $employee_id,
                "firstname" => $firstname,
                "lastname" => $lastname,
                "contact_number" => $contact_number,
                "email" => $email,
                "accounts" => $accounts
            );
            http_response_code(200);
            echo json_encode(array(
                "status" => 200,
                "success" => true,
                "message" => "Employee retrieved successfully.",
                "data" => $employee_item));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status" => 404,
                "success" => false,
                "message" => "Employee not found."));
        }
    }

    private function getPaginatedEmployees($page, $records_per_page, $search) {
        $this->employeeModel->pages = max(1, (int)$page);
        $this->employeeModel->records_per_page = max(1, (int)$records_per_page);
        $stmt = $this->employeeModel->readPaginated($search);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $employees_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $employee_item = array(
                    "employee_id" => $employee_id,
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "contact_number" => $contact_number,
                    "email" => $email,
                    "accounts" => $accounts
                );
                array_push($employees_arr, $employee_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status" => 200,
                "success" => true,
                "message" => "Employees retrieved successfully.",
                "data" => $employees_arr));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status" => 404,
                "success" => false,
                "message" => "No employees found."));
        }
    }

    private function createEmployee() {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->firstname) || empty($data->lastname) || empty($data->employee_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
            return;
        }

        $this->employeeModel->firstname = $data->firstname;
        $this->employeeModel->lastname = $data->lastname;
        $this->employeeModel->contact_number = $data->contact_number;
        $this->employeeModel->email = $data->email;
        $this->employeeModel->accounts = $data->accounts;

        if ($this->employeeModel->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Employee created successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create employee."));
        }
    }

    private function updateEmployee() {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->firstname) || empty($data->lastname)) {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
            return;
        }

        $this->employeeModel->firstname = $data->firstname;
        $this->employeeModel->lastname = $data->lastname;
        $this->employeeModel->contact_number = $data->contact_number;
        $this->employeeModel->email = $data->email;
        $this->employeeModel->accounts = $data->accounts;

        if ($this->employeeModel->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Employee updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update employee."));
        }
    }

    private function deleteEmployee() {
        if ($this->employeeModel->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Employee deleted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete employee."));
        }
    }
}

?>