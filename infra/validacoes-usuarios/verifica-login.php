<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$pasta_public = realpath(__DIR__ . '/../../public');
$pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

$tela_login = ($pasta_pagina === $pasta_public) ? 'tela-login.php' : '../tela-login.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . $tela_login);
    exit();
}

include __DIR__ . '/../conexao.php';

$stmt = mysqli_prepare($conexao, "SELECT perfil, status_usuario FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_usuario']);
mysqli_stmt_execute($stmt);
$usuario_logado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario_logado || $usuario_logado['status_usuario'] !== 'ATIVO') {
    $_SESSION = [];
    session_destroy();
    header("Location: " . $tela_login);
    exit();
}

$_SESSION['perfil'] = $usuario_logado['perfil'];

$admin = $_SESSION['perfil'] === 'ADMINISTRADOR';
