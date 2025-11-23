<?php
session_start();
ob_start();
require_once __DIR__ . '/db/conn.php';
require_once __DIR__ . '/functions/formatDate.php';
require_once __DIR__ . '/functions/users.php';

if (isset($_SESSION['user'])) {
    $user = getUser($_SESSION['user']['id']);
} else {
    $user = null;
}

function load_page($page, $data = [])
{
    extract($data);
    require __DIR__ . "/pages/{$page}.php";
}

$page = isset($_GET['page']) ? $_GET['page'] : '';

switch ($page) {
    case 'home':
        load_page('home', ['user' => $user]);
        break;
    case 'report':
        load_page('report', ['user' => $user]);
        break;
    case 'work':
        load_page('work', ['user' => $user]);
        break;
    case 'daily-works':
        load_page('daily-works', ['user' => $user]);
        break;
    case 'login':
        load_page('login', ['user' => $user]);
        break;
    case 'register':
        load_page('register', ['user' => $user]);
        break;
    case 'ticket':
        load_page('ticket', ['user' => $user]);
        break;
    case 'add-user':
        load_page('add-user', ['user' => $user]);
        break;
    case 'logout':
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: ./?page=home");
        break;
    case '':
        load_page('home', ['user' => $user]);
        break;
    default:
        http_response_code(404);
        load_page('404', ['user' => $user]);
        break;
}
