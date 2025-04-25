<?php

require_once './models/Accounts.php';

class AccountsController {
    private $accountsModel;

    public function __construct($db) {
        $this->accountsModel = new Account($db);
    }

    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getAccountById($id);
                } else if(isset($_GET['page']) && isset($_GET['records_per_page'])) {
                    $page = $_GET['page'];
                    $records_per_page = $_GET['records_per_page'];
                    $search = isset($_GET['search']) ? $_GET['search'] : null;
                    $type = isset($_GET['type']) ? $_GET['type'] : null;
                    $this->getPaginatedAccounts($page, $records_per_page, $search, $type);
                } else {
                    $this->getAllAccounts();
                }
                break;
            case 'POST':
                $this->createAccount();
                break;
            case 'PUT':
                if ($id) {
                    $this->accountsModel->account_id = $id;
                    $this->updateAccount();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Account ID is required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->accountsModel->account_id = $id;
                    $this->deleteAccount();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "Account ID is required for deletion."));
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(array("message" => "Method not allowed."));
        }
    }

    private function getAllAccounts() {
        $stmt = $this->accountsModel->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $accounts_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $account_item = array(
                    "account_id" => $account_id,
                    "employee_id" => $employee_id,
                    "account_email" => $account_email,
                    "account_pass" => $account_pass,
                    "account_type" => $account_type,
                    "account_status" => $account_status
                );
                array_push($accounts_arr, $account_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Accounts retrieved successfully.",
                "data" => $accounts_arr));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false,
                "message" => "No accounts found."));
        }
    }

    private function getAccountById($id) {
        $stmt = $this->accountsModel->readSingle($id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            extract($row);
            $account_item = array(
                "account_id" => $account_id,
                "employee_id" => $employee_id,
                "account_email" => $account_email,
                "account_pass" => $account_pass,
                "account_type" => $account_type,
                "account_status" => $account_status
            );
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Account retrieved successfully.",
                "data" => $account_item));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false,
                "message" => "Account not found."));
        }
    }

    private function getPaginatedAccounts($page, $records_per_page, $search, $type) {
        $this->accountsModel->pages = max(1, (int)$page);
        $this->accountsModel->records_per_page = max(1, (int)$records_per_page);
        $stmt = $this->accountsModel->readPaginated($search, $type);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $accounts_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $account_item = array(
                    "account_id" => $account_id,
                    "employee_id" => $employee_id,
                    "account_email" => $account_email,
                    "account_pass" => $account_pass,
                    "account_type" => $account_type,
                    "account_status" => $account_status
                );
                array_push($accounts_arr, $account_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Accounts retrieved successfully.",
                "data" => $accounts_arr));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false,
                "message" => "No accounts found."));
        }
    }

    private function createAccount() {
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->employee_id) && isset($data->account_email) && isset($data->account_pass) && isset($data->account_type) && isset($data->account_status)) {
            $this->accountsModel->employee_id = $data->employee_id;
            $this->accountsModel->account_email = $data->account_email;
            $this->accountsModel->account_pass = $data->account_pass;
            $this->accountsModel->account_type = $data->account_type;
            $this->accountsModel->account_status = isset($data->account_status) ? $data->account_status : "ACTIVE";

            if ($this->accountsModel->create()) {
                http_response_code(201);
                echo json_encode(array(
                    "status_code" => 201,
                    "success" => true,
                    "message" => "Account created successfully."));
            } else {
                http_response_code(503);
                echo json_encode(array(
                    "status_code" => 503,
                    "success" => false,
                    "message" => "Unable to create account."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array(
                "status_code" => 400,
                "success" => false,
                "message" => "Incomplete data."));
        }
    }

    private function updateAccount() {
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->employee_id) && isset($data->account_email) && isset($data->account_pass) && isset($data->account_type) && isset($data->account_status)) {
            $this->accountsModel->employee_id = $data->employee_id;
            $this->accountsModel->account_email = $data->account_email;
            $this->accountsModel->account_pass = $data->account_pass;
            $this->accountsModel->account_type = $data->account_type;
            $this->accountsModel->account_status = $data->account_status;

            if ($this->accountsModel->update()) {
                http_response_code(200);
                echo json_encode(array(
                    "status_code" => 200,
                    "success" => true,
                    "message" => "Account updated successfully."));
            } else {
                http_response_code(503);
                echo json_encode(array(
                    "status_code" => 503,
                    "success" => false,
                    "message" => "Unable to update account."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array(
                "status_code" => 400,
                "success" => false,
                "message" => "Incomplete data."));
        }
    }

    private function deleteAccount() {
        if ($this->accountsModel->delete()) {
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Account deleted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "status_code" => 503,
                "success" => false,
                "message" => "Unable to delete account."));
        }
    }

}

?>