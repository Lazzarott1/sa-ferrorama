<?php

include __DIR__ . '/../../infra/verifica-login.php';
exigir_admin(); // somente administradores gerenciam usuários

include __DIR__ . '/../../infra/conexao.php';
require_once __DIR__ . '/../../infra/usuarios.php';


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_usuario'])) {
    header("Location: tela-cadastro-user.php");
    exit;
}

$id_usuario = isset($_POST['id_usuario'])
    ? (int) $_POST['id_usuario']
    : (int) $_GET['id'];

$usuario = buscar_usuario($conexao, $id_usuario);

if (!$usuario) {
    header("Location: tela-cadastro-user.php");
    exit;
}

$erros = [];


//    ATUALIZAR USUÁRIO

if (isset($_POST['editar'])) {

    if (!csrf_valido()) {
        $erros['geral'] = 'Requisição inválida. Atualize a página e tente novamente.';
    } else {
        $resultado = atualizar_usuario($conexao, $id_usuario, $_POST);

        if ($resultado['sucesso']) {

            // Se o usuário editou o próprio cadastro, atualiza os dados da sessão
            if ($id_usuario === (int) $_SESSION['id_usuario']) {
                $atualizado = buscar_usuario($conexao, $id_usuario);
                $_SESSION['usuario'] = $atualizado['nome_usuario'];
                $_SESSION['perfil_usuario'] = $atualizado['perfil_usuario'];
            }

            $_SESSION['mensagem_usuarios'] = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso.'];
            header("Location: tela-cadastro-user.php");
            exit;
        }

        $erros = $resultado['erros'];
    }

    // Mantém no formulário o que foi digitado (menos a senha)
    $usuario['nome_usuario'] = trim((string) ($_POST['nome_usuario'] ?? ''));
    $usuario['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
    $usuario['perfil_usuario'] = (string) ($_POST['perfil_usuario'] ?? $usuario['perfil_usuario']);
}

function classe_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function mensagem_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? '<div class="invalid-feedback">' . e($erros[$campo]) . '</div>' : '';
}

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

    <div class="card shadow-sm border-1 p-0" style="width: 600px; max-width: calc(100vw - 32px); border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (✎) EDITAR USUÁRIO
        </div>

        <div class="p-4">

            <?php if (isset($erros['geral'])) { ?>
                <div class="alert alert-danger"><?php echo e($erros['geral']); ?></div>
            <?php } elseif ($erros) { ?>
                <div class="alert alert-danger">Corrija os campos destacados.</div>
            <?php } ?>

            <form method="POST" novalidate>

                <?php echo campo_csrf(); ?>

                <input type="hidden" name="id_usuario"
                    value="<?php echo (int) $id_usuario; ?>">

                <div class="mb-3">
                    <label for="email_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        EMAIL
                    </label>

                    <input type="email" id="email_usuario" name="email_usuario"
                        class="form-control<?php echo classe_erro($erros, 'email_usuario'); ?>"
                        value="<?php echo e($usuario['email_usuario']); ?>" maxlength="200" required>
                    <?php echo mensagem_erro($erros, 'email_usuario'); ?>
                </div>

                <div class="mb-3">
                    <label for="nome_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOME DE USUÁRIO
                    </label>

                    <input type="text" id="nome_usuario" name="nome_usuario"
                        class="form-control<?php echo classe_erro($erros, 'nome_usuario'); ?>"
                        value="<?php echo e($usuario['nome_usuario']); ?>" minlength="3" maxlength="50" required>
                    <?php echo mensagem_erro($erros, 'nome_usuario'); ?>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOVA SENHA
                    </label>

                    <input type="password" id="senha" name="senha"
                        class="form-control<?php echo classe_erro($erros, 'senha'); ?>"
                        placeholder="Deixe em branco para manter a senha atual" maxlength="72" autocomplete="new-password">
                    <?php echo mensagem_erro($erros, 'senha'); ?>
                </div>

                <div class="mb-3">
                    <label for="perfil_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        PERFIL
                    </label>

                    <select id="perfil_usuario" name="perfil_usuario"
                        class="form-select<?php echo classe_erro($erros, 'perfil_usuario'); ?>" required>
                        <option value="FUNCIONARIO" <?php echo $usuario['perfil_usuario'] === 'FUNCIONARIO' ? 'selected' : ''; ?>>Funcionário</option>
                        <option value="ADMIN" <?php echo $usuario['perfil_usuario'] === 'ADMIN' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                    <?php echo mensagem_erro($erros, 'perfil_usuario'); ?>
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
