<?php

include __DIR__ . '/../../infra/verifica-login.php';
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


//    ATUALIZAR USUÁRIO

if (isset($_POST['editar'])) {

    $nome_usuario = trim($_POST['nome_usuario']);
    $email_usuario = trim($_POST['email_usuario']);
    $senha = $_POST['senha'];

    if ($senha !== '') {

        // Senha preenchida: atualiza nome, e-mail e senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?, senha = ?
                WHERE id_usuario = ?";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            die("Erro ao preparar atualização: " . mysqli_error($conexao));
        }

        mysqli_stmt_bind_param($stmt, "sssi", $nome_usuario, $email_usuario, $senha_hash, $id_usuario);

    } else {

        // Senha em branco: mantém a senha atual
        $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?
                WHERE id_usuario = ?";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            die("Erro ao preparar atualização: " . mysqli_error($conexao));
        }

        mysqli_stmt_bind_param($stmt, "ssi", $nome_usuario, $email_usuario, $id_usuario);
    }

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar usuário: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-user.php");
    exit;
}


//    BUSCAR USUÁRIO PARA PREENCHER O FORMULÁRIO

$sql = "SELECT id_usuario, nome_usuario, email_usuario FROM usuarios WHERE id_usuario = ?";

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

<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>


<body class="bg-light">
    <header class="container-fluid p-2 rounded-0" style="background-color: #1b3f53; color: #ffffff;">
        <div id="header" class="hstack gap-3 px-2">
            <div class="d-flex" id="logo">
                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo">
                <div class="nome-sistema">
                    <h2 class="mb-0 text-white">FerroMonitor</h2>
                    <p>SISTEMA FERROVIÁRIO</p>
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1b3f53;">
                <div class="container-fluid">
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <div class="d-flex">
                            <ul class="navbar-nav">
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" aria-current="page"
                                            href="../tela-geral-home.php">Home</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="../sensores/tela-cadastro-sensores.php">Sensores</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="../trens/tela-trens.php">Trens</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="../trilhos/tela-cadastro-trilhos.php">Trilhos</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="../monitoramento/tela-monitoramento.php">Monitoramento</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="../relatorios/tela-relatorios.php">Relatórios</a>
                                    </li>
                                </div>
                                <div>
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="tela-cadastro-user.php">Usuários</a>
                                    </li>
                                </div>

                            </ul>
                        </div>

                    </div>
                </div>
            </nav>

            <div>
                <button class="btn-sair" onclick="window.location.href='../../infra/logout.php'">Sair</button>
            </div>
        </div>
    </header>

<main class="d-flex flex-column align-items-center gap-5 w-100"
    style="padding-top: 60px; min-height: 100vh; background-color: #f8f9fa;">

    <div class="card shadow-sm border-1 p-0" style="width: 600px; border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (✎) EDITAR USUÁRIO
        </div>

        <div class="p-4">

            <form method="POST">

                <input type="hidden" name="id_usuario"
                    value="<?php echo $usuario['id_usuario']; ?>">

                <div class="mb-3">
                    <label for="email_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        EMAIL
                    </label>

                    <input type="email" id="email_usuario" name="email_usuario" class="form-control"
                        value="<?php echo htmlspecialchars($usuario['email_usuario']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="nome_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOME DE USUÁRIO
                    </label>

                    <input type="text" id="nome_usuario" name="nome_usuario" class="form-control"
                        value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOVA SENHA
                    </label>

                    <input type="password" id="senha" name="senha" class="form-control"
                        placeholder="Deixe em branco para manter a senha atual">
                </div>

                <div class="d-flex gap-2">

                    <a href="tela-cadastro-user.php"
                        class="btn btn-outline-secondary w-50 fw-semibold"
                        style="border-radius: 4px; font-size: 0.85rem;">
                        CANCELAR
                    </a>

                    <button type="submit" name="editar"
                        class="btn btn-primary w-50 fw-semibold"
                        style="background-color: #1b3f53; border: none; border-radius: 4px; font-size: 0.85rem;">
                        SALVAR
                    </button>

                </div>

            </form>

        </div>
    </div>

</main>

</body>

</html>
