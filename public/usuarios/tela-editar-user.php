<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../../infra/conexao.php';


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_usuario'])) {
    header("Location: tela-cadastro-user.php");
    exit;
}

$id_usuario = isset($_POST['id_usuario'])
    ? (int) $_POST['id_usuario']
    : (int) $_GET['id'];

$proprio_usuario = $id_usuario === (int) $_SESSION['id_usuario'];


//    ATUALIZAR USUÁRIO

if (isset($_POST['editar'])) {

    $nome_usuario = trim($_POST['nome_usuario']);
    $email_usuario = trim($_POST['email_usuario']);
    $senha = $_POST['senha'];
    $perfil = $_POST['perfil'] ?? '';
    $status_usuario = $_POST['status_usuario'] ?? '';

    if ($proprio_usuario) {
        $perfil = 'ADMINISTRADOR';
        $status_usuario = 'ATIVO';
    }

    if (!in_array($perfil, ['ADMINISTRADOR', 'OPERADOR'], true) || !in_array($status_usuario, ['ATIVO', 'INATIVO'], true)) {
        die("Perfil ou status inválido.");
    }

    if ($nome_usuario === '' || !filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        die("Preencha o nome e um e-mail válido.");
    }

    if ($senha !== '' && strlen($senha) < 8) {
        die("A senha deve ter pelo menos 8 caracteres.");
    }

    if ($status_usuario === 'INATIVO') {
        $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) FROM trens WHERE id_usuario = ? AND status_trem <> 'INATIVO'");
        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);
        $total_trens = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

        if ($total_trens > 0) {
            die("Este usuário é responsável por " . $total_trens . " trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.");
        }
    }

    if ($senha !== '') {

        // Senha preenchida: atualiza nome, e-mail e senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?, senha = ?, perfil = ?, status_usuario = ?
                WHERE id_usuario = ?";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            die("Erro ao preparar atualização: " . mysqli_error($conexao));
        }

        mysqli_stmt_bind_param($stmt, "sssssi", $nome_usuario, $email_usuario, $senha_hash, $perfil, $status_usuario, $id_usuario);

    } else {

        // Senha em branco: mantém a senha atual
        $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?
                WHERE id_usuario = ?";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            die("Erro ao preparar atualização: " . mysqli_error($conexao));
        }

        mysqli_stmt_bind_param($stmt, "ssssi", $nome_usuario, $email_usuario, $perfil, $status_usuario, $id_usuario);
    }

    try {
        mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        die($e->getCode() === 1062 ? "Esse nome de usuário já existe." : "Erro ao atualizar usuário.");
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-user.php?msg=editado");
    exit;
}


//    BUSCAR USUÁRIO PARA PREENCHER O FORMULÁRIO

$sql = "SELECT id_usuario, nome_usuario, email_usuario, perfil, status_usuario FROM usuarios WHERE id_usuario = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar busca: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id_usuario);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao buscar usuário: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    header("Location: tela-cadastro-user.php");
    exit;
}

$usuario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Editar usuário</h3>
                <p>Atualize as informações do usuário selecionado</p>
            </div>
        </div>

        <div class="caixa">
            <div class="caixa-topo">EDITAR USUÁRIO #<?php echo $usuario['id_usuario']; ?></div>
            <form method="POST" class="caixa-corpo">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome_usuario" class="form-label">NOME DE USUÁRIO</label>
                        <input type="text" id="nome_usuario" name="nome_usuario" class="form-control" value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email_usuario" class="form-label">E-MAIL</label>
                        <input type="email" id="email_usuario" name="email_usuario" class="form-control" value="<?php echo htmlspecialchars($usuario['email_usuario']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="senha" class="form-label">NOVA SENHA</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Deixe em branco para manter a senha atual" minlength="8">
                    </div>
                    <div class="col-md-3">
                        <label for="perfil" class="form-label">PERFIL</label>
                        <select id="perfil" name="perfil" class="form-select" <?php if ($proprio_usuario) echo 'disabled'; ?>>
                            <?php foreach (['OPERADOR' => 'Operador', 'ADMINISTRADOR' => 'Administrador'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($usuario['perfil'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_usuario" class="form-label">STATUS</label>
                        <select id="status_usuario" name="status_usuario" class="form-select" <?php if ($proprio_usuario) echo 'disabled'; ?>>
                            <?php foreach (['ATIVO' => 'Ativo', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($usuario['status_usuario'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php if ($proprio_usuario) { ?>
                        <div class="col-12 form-text mt-1">Você não pode alterar o próprio perfil nem o próprio status.</div>
                    <?php } ?>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="editar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    <a href="tela-cadastro-user.php" class="btn btn-secondary">CANCELAR</a>
                </div>
            </form>
        </div>

    </main>

</body>

</html>
