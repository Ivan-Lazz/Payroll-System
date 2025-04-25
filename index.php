<?php

require_once 'config/database.php';
require_once 'controllers/UserController.php';
require_once 'controllers/EmployeeController.php';
require_once 'controllers/AccountsController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Split URL: /api/users/3 => ['api', 'users', '3']
$uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

$resource = $uri[1] ?? null; // 'users'
$id = $uri[2] ?? null;       // optional ID like 3

$method = $_SERVER['REQUEST_METHOD'];

$database = new Database();
$db = $database->getConnection();

switch ($resource) {
    case 'users':
        $controller = new UserController($db);
        $controller->handleRequest($method, $id);
        break;

    case 'employees':
        $controller = new EmployeeController($db);
        $controller->handleRequest($method, $id);
        break;

    case 'accounts':
        $controller = new AccountsController($db);
        $controller->handleRequest($method, $id);
        break;

    default:
        http_response_code(404);
        $response = array(
            "status" => 404,
            "success" => false,
            "message" => "Endpoint not found."
        );
        echo json_encode($response);
        break;
}

?>