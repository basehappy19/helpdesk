<?php
session_start();
require_once __DIR__ . '/db/conn.php';

function load_page($page, $data = []) {
    extract($data);
    require __DIR__ . "/pages/{$page}.php";
}

$page = isset($_GET['page']) ? $_GET['page'] : '';

switch ($page) {
    case 'report':
        load_page('report');
        break;
    case 'daily-work':
        load_page('daily-work');
        break;
    case 'login':
        load_page('login');
        break;
    case 'register':
        load_page('register');
        break;
    case '':
        load_page('home');
        break;
    default:
        http_response_code(404);
        load_page('404');
        break;
}