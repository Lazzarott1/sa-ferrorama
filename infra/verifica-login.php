<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id_usuario'])) {

    $pasta_public = realpath(__DIR__ . '/../public');
    $pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

    $tela_login = ($pasta_pagina === $pasta_public) ? 'tela-login.php' : '../tela-login.php';

    header("Location: " . $tela_login);
    exit();
}