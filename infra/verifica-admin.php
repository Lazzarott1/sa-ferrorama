<?php

include __DIR__ . '/verifica-login.php';
include __DIR__ . '/conexao.php';

$stmt = mysqli_prepare($conexao, "SELECT perfil, status_usuario FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_usuario']);
mysqli_stmt_execute($stmt);
$usuario_logado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario_logado || $usuario_logado['status_usuario'] !== 'ATIVO') {
    header("Location: ../../infra/logout.php");
    exit();
}

$_SESSION['perfil'] = $usuario_logado['perfil'];

if ($_SESSION['perfil'] !== 'ADMINISTRADOR') {
    header("Location: ../tela-geral-home.php?acesso=negado");
    exit();
}
