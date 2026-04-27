<?php
session_start();
ob_start();

require_once __DIR__ . '/configs/db_connection.php';
require_once __DIR__ . '/utils/DateHelper.php';
require_once __DIR__ . '/models/UserModel.php'; 

$user = null;
if (isset($_SESSION['user']['id'])) {
    global $pdo;
    $userModel = new UserModel($pdo);
    $user = $userModel->getById($_SESSION['user']['id']);
}

function load_page($filePath, $data = [])
{
    extract($data);
    require __DIR__ . "/pages/{$filePath}.php";
}

$page = isset($_GET['page']) && $_GET['page'] !== '' ? $_GET['page'] : 'home';

if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ./?page=login");
    exit();
}

$routes = [
    'home'          => ['file' => 'home', 'roles' => ['ALL']],
    'login'         => ['file' => 'login', 'roles' => ['ALL']],
    'register'      => ['file' => 'register', 'roles' => ['ALL']],
    'report'        => ['file' => 'report', 'roles' => ['ALL']],
    'reports'       => ['file' => 'reports', 'roles' => ['ALL']],
    'report-detail' => ['file' => 'report-detail', 'roles' => ['ALL']],
    'ticket'        => ['file' => 'ticket', 'roles' => ['ALL']],

    'profile'       => ['file' => 'profile', 'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'daily-works'   => ['file' => 'daily-works', 'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'statistics'    => ['file' => 'statistics', 'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'work-categories'=> ['file' => 'work-categories', 'roles' => ['SYSTEM', 'ADMIN']], 

    'manage-request-types'    => ['file' => 'admin/manage-request-types', 'roles' => ['SYSTEM', 'ADMIN']],
    'manage-issue-categories' => ['file' => 'admin/manage-issue-categories', 'roles' => ['SYSTEM', 'ADMIN']],
    'manage-issue-symptoms'   => ['file' => 'admin/manage-issue-symptoms', 'roles' => ['SYSTEM', 'ADMIN']],

    'manage-users'  => ['file' => 'system/manage-users', 'roles' => ['SYSTEM']],
];

if (array_key_exists($page, $routes)) {
    $route = $routes[$page];
    $allowedRoles = $route['roles'];

    if (!in_array('ALL', $allowedRoles)) {
        
        if (!$user) {
            header("Location: ./?page=login");
            exit();
        }

        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            load_page('404', ['user' => $user]); 
            exit();
        }
    }

    load_page($route['file'], ['user' => $user]);

} else {
    http_response_code(404);
    load_page('404', ['user' => $user]);
}