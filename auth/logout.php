<?php
/**
 * Cierre de sesión seguro
 * Barbería Premium — auth/logout.php
 */
require_once __DIR__ . '/../includes/functions.php';
iniciarSesion();
$_SESSION = [];
session_destroy();
header('Location: /Barberia/auth/login.php');
exit;
