<?php
require_once __DIR__ . '/../config.php';
date_default_timezone_set(APP_TIMEZONE);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
