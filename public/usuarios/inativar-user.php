<?php

// só administrador pode inativar usuário
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../../infra/conexao.php';

// aqui a gente quer que erro de banco vire exceção, pra cair no catch lá embaixo
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// volta pra lista de usuários com o código da mensagem que vai aparecer na tela
function voltar($msg)
{
    header("Location: tela-cadastro-user.php?msg=" . $msg);
    exit;
}

// só aceita o envio pelo formulário
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    voltar('invalido');
}

// confere o token da sessão pra ninguém inativar usuário por um link de fora
if (!isset($_POST['token']) || !hash_equals($_SESSION['token'] ?? '', $_POST['token'])) {
    voltar('invalido');
}

// o id tem que ser um número válido
$id_usuario = filter_var($_POST['id_usuario'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id_usuario === false) {
    voltar('invalido');
}

// ninguém pode inativar a si mesmo
if ($id_usuario === (int) $_SESSION['id_usuario']) {
    voltar('proprio');
}

try {
    // se ele for responsável por algum trem que ainda está ativo, não deixa inativar
    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) FROM trens WHERE id_usuario = ? AND status_trem <> 'INATIVO'");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);

    if (mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] > 0) {
        voltar('associado');
    }

    // não apaga do banco, só muda o status pra INATIVO
    $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET status_usuario = 'INATIVO' WHERE id_usuario = ? AND status_usuario = 'ATIVO'");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    $alterados = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    // se não mudou nenhuma linha é porque o usuário não existe ou já estava inativo
    voltar($alterados === 1 ? 'inativado' : 'inexistente');

} catch (mysqli_sql_exception $e) {
    voltar('erro');
}
