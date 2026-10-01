<?php

declare(strict_types=1);

require_once __DIR__ . '/configs/db_connection.php';
require_once __DIR__ . '/utils/DateHelper.php';
require_once __DIR__ . '/models/UserModel.php';

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

ob_start();

// --- Load current user ---
$user = null;
$sessionUserId = $_SESSION['user']['id'] ?? null;
if ($sessionUserId !== null) {
    $userModel = new UserModel($pdo);
    $user = $userModel->getById((int)$sessionUserId);

    // Session ชี้ไป user ที่ไม่มีในระบบแล้ว → clear session
    if (!$user) {
        $_SESSION = [];
        session_destroy();
        header('Location: ./?page=login');
        exit;
    }
}

// --- Simple page router ---
function load_page(string $filePath, array $data = []): void
{
    global $pdo;
    extract($data, EXTR_SKIP);
    $fullPath = __DIR__ . "/pages/{$filePath}.php";
    if (!is_file($fullPath)) {
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
        return;
    }
    require $fullPath;
}

$page = trim($_GET['page'] ?? '');
if ($page === '') $page = 'home';

// --- Logout ---
if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: ./?page=login');
    exit;
}

// --- Route table: file => roles ('ALL' = no auth required) ---
$routes = [
    'home'                    => ['file' => 'home',                        'roles' => ['ALL']],
    'login'                   => ['file' => 'login',                       'roles' => ['ALL']],
    'register'                => ['file' => 'register',                    'roles' => ['ALL']],
    'report'                  => ['file' => 'report',                      'roles' => ['ALL']],
    'reports'                 => ['file' => 'reports',                     'roles' => ['ALL']],
    'report-detail'           => ['file' => 'report-detail',               'roles' => ['ALL']],
    'ticket'                  => ['file' => 'report',                      'roles' => ['ALL']],
    'tickets'                 => ['file' => 'reports',                     'roles' => ['ALL']],
    'ticket-detail'           => ['file' => 'report-detail',               'roles' => ['ALL']],

    'profile'                 => ['file' => 'profile',                     'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'daily-works'             => ['file' => 'daily-works',                 'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'statistics'              => ['file' => 'statistics',                  'roles' => ['SYSTEM', 'ADMIN', 'SERVICE', 'MEMBER']],
    'work-categories'         => ['file' => 'work-categories',             'roles' => ['SYSTEM', 'ADMIN']],

    'manage-request-types'    => ['file' => 'admin/manage-request-types',    'roles' => ['SYSTEM', 'ADMIN']],
    'manage-issue-categories' => ['file' => 'admin/manage-issue-categories', 'roles' => ['SYSTEM', 'ADMIN']],
    'manage-issue-symptoms'   => ['file' => 'admin/manage-issue-symptoms',   'roles' => ['SYSTEM', 'ADMIN']],

    'manage-users'            => ['file' => 'system/manage-users',           'roles' => ['SYSTEM']],
];

if (!array_key_exists($page, $routes)) {
    http_response_code(404);
    load_page('404', ['user' => $user]);
    exit;
}

$route        = $routes[$page];
$allowedRoles = $route['roles'];

if (!in_array('ALL', $allowedRoles, true)) {
    if (!$user) {
        header('Location: ./?page=login');
        exit;
    }
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        load_page('404', ['user' => $user]);
        exit;
    }
}

load_page($route['file'], ['user' => $user]);