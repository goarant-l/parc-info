<?php
/**
 * À inclure en première ligne de chaque page :
 *   require __DIR__ . '/includes/bootstrap.php';
 */
date_default_timezone_set('Europe/Paris');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
