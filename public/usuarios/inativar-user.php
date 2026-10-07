<?php

include __DIR__ . '/../../infra/verifica-admin.php';
include __DIR__ . '/../../infra/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function voltar($msg)
{
    header("Location: tela-cadastro-user.php?msg=" . $msg);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    voltar('invalido');
}

if (!isset($_POST['token']) || !hash_equals($_SESSION['token'] ?? '', $_POST['token'])) {
    voltar('invalido');
}

$id_usuario = filter_var($_POST['id_usuario'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id_usuario === false) {
    voltar('invalido');
}

if ($id_usuario === (int) $_SESSION['id_usuario']) {
    voltar('proprio');
}

try {
    $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET status_usuario = 'INATIVO' WHERE id_usuario = ? AND status_usuario = 'ATIVO'");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    $alterados = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    voltar($alterados === 1 ? 'inativado' : 'inexistente');

} catch (mysqli_sql_exception $e) {
    voltar('erro');
}
