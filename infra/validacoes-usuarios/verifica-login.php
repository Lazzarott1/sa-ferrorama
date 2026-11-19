<?php

// incluído no começo de todas as telas que precisam de login

// só abre a sessão se ela ainda não foi aberta
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// não deixa o navegador guardar a página, assim o voltar não mostra a tela depois de sair
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// As telas ficam em public/ ou em uma pasta dentro de public/
if (basename(dirname($_SERVER['SCRIPT_FILENAME'])) == 'public') {
    $caminho = '';
} else {
    $caminho = '../';
}

// não está logado: vai pro login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . $caminho . "tela-login.php");
    exit();
}

include __DIR__ . '/../conexao.php';

// busca o usuário de novo no banco, porque o admin pode ter inativado ou trocado o perfil dele
$id_logado = (int) $_SESSION['id_usuario'];
$resultado = mysqli_query($conexao, "SELECT perfil, status_usuario
    FROM usuarios
    WHERE id_usuario = $id_logado");
$usuario_logado = mysqli_fetch_assoc($resultado);

// foi inativado: derruba a sessão na hora
if (!$usuario_logado || $usuario_logado['status_usuario'] != 'ATIVO') {
    session_destroy();
    header("Location: " . $caminho . "tela-login.php");
    exit();
}

$_SESSION['perfil'] = $usuario_logado['perfil'];

// $admin é usado nas telas e na navbar pra mostrar ou esconder as coisas de administrador
if ($usuario_logado['perfil'] == 'ADMINISTRADOR') {
    $admin = true;
} else {
    $admin = false;
}
