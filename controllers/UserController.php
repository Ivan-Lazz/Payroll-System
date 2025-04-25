<?php

require_once './models/User.php';

class UserController{
    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
    }
    
    public function handleRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getUserById($id);
                } else if(isset($_GET['page']) && isset($_GET['records_per_page'])) {
                    $page = $_GET['page'];
                    $records_per_page = $_GET['records_per_page'];
                    $search = isset($_GET['search']) ? $_GET['search'] : null;
                    $this->getPaginatedUsers($page, $records_per_page, $search);
                } else {
                    $this->getAllUsers();
                }
                break;
            case 'POST':
                $this->createUser();
                break;
            case 'PUT':
                if ($id) {
                    $this->userModel->id = $id;
                    $this->updateUser();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "User ID is required for update."));
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->userModel->id = $id;
                    $this->deleteUser();
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "User ID is required for deletion."));
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(array("message" => "Method not allowed."));
        }
    }

    private function getAllUsers() {
        $stmt = $this->userModel->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $users_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user_item = array(
                    "id" => $id,
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "username" => $username,
                );
                array_push($users_arr, $user_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Users retrieved successfully.", 
                "data" => $users_arr));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false, 
                "message" => "No users found."));
        }
    }

    private function getUserById($id) {
        $stmt = $this->userModel->readSingle($id);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "User retrieved successfully.", 
                "data" => array(
                    "id" => $row['id'],
                    "firstname" => $row['firstname'],
                    "lastname" => $row['lastname'],
                    "username" => $row['username'],
                    "password" => $row['password']
                )
            ));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false, 
                "message" => "User not found."
            ));
        }
    }

    private function getPaginatedUsers($page, $records_per_page, $search) {
        $this->userModel->pages = max(1, (int)$page);
        $this->userModel->records_per_page = max(1, (int)$records_per_page);
        $stmt = $this->userModel->readPaginated($search);
        $num = $stmt->rowCount();

        $count_stmt = $this->userModel->countData($search);
        $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);

        if ($num > 0) {
            $users_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user_item = array(
                    "id" => $id,
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "username" => $username,
                );
                array_push($users_arr, $user_item);
            }
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "Users retrieved successfully.",
                "data" => array(
                    "current_page" => $this->userModel->pages,
                    "records_per_page" => $this->userModel->records_per_page,
                    "total_records" => $count_row['total'],
                    "total_pages" => ceil($count_row['total'] / $this->userModel->records_per_page),
                    "data" => $users_arr
                )
            ));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "status_code" => 404,
                "success" => false, 
                "message" => "No users found."
            ));
        }
    }

    private function createUser() {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->firstname) || empty($data->lastname) || empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array(
                "status_code" => 400,
                "success" => false,
                "message" => "Incomplete data."));
            return;
        }

        $this->userModel->firstname = $data->firstname;
        $this->userModel->lastname = $data->lastname;
        $this->userModel->username = $data->username;
        $this->userModel->password = $data->password;

        if ($this->userModel->create()) {
            http_response_code(201);
            echo json_encode(array(
                "status_code" => 201,
                "success" => true,
                "message" => "User created successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "status_code" => 503,
                "success" => false,
                "message" => "Unable to create user."));
        }
    }

    private function updateUser() {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->firstname) || empty($data->lastname) || empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(array(
                "status_code" => 400,
                "success" => false,
                "message" => "Incomplete data."));
            return;
        }

        $this->userModel->firstname = $data->firstname;
        $this->userModel->lastname = $data->lastname;
        $this->userModel->username = $data->username;
        $this->userModel->password = $data->password;

        if ($this->userModel->update()) {
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "User updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "status_code" => 503,
                "success" => false,
                "message" => "Unable to update user."));
        }
    }

    private function deleteUser() {
        if ($this->userModel->delete()) {
            http_response_code(200);
            echo json_encode(array(
                "status_code" => 200,
                "success" => true,
                "message" => "User deleted successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "status_code" => 503,
                "success" => false,
                "message" => "Unable to delete user."));
        }
    }
}

?>