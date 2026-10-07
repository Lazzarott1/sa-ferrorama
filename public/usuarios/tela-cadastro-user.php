<?php

include __DIR__ . '/../../infra/verifica-login.php';
exigir_admin(); // somente administradores gerenciam usuários

include __DIR__ . '/../../infra/conexao.php';
require_once __DIR__ . '/../../infra/usuarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //    EXCLUIR USUÁRIO

    if (isset($_POST['excluir'])) {

        if (!csrf_valido()) {
            $_SESSION['mensagem_usuarios'] = ['tipo' => 'danger', 'texto' => 'Requisição inválida. Atualize a página e tente novamente.'];
        } else {
            $resultado_exclusao = excluir_usuario($conexao, (int) ($_POST['id_usuario'] ?? 0), (int) $_SESSION['id_usuario']);

            $_SESSION['mensagem_usuarios'] = $resultado_exclusao['sucesso']
                ? ['tipo' => 'success', 'texto' => 'Usuário excluído com sucesso.']
                : ['tipo' => 'danger', 'texto' => $resultado_exclusao['erros']['geral']];
        }

        header("Location: tela-cadastro-user.php");
        exit;
    }

    //    CADASTRAR USUÁRIO (requisição do fetch, responde JSON)

    header('Content-Type: application/json; charset=utf-8');

    if (!csrf_valido()) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Requisição inválida. Atualize a página e tente novamente.', 'erros' => []]);
        exit;
    }

    $resultado_cadastro = cadastrar_usuario($conexao, $_POST);

    if ($resultado_cadastro['sucesso']) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário cadastrado com sucesso!', 'erros' => []]);
    } else {
        http_response_code(422);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Corrija os campos destacados.', 'erros' => $resultado_cadastro['erros']]);
    }
    exit;
}

$usuarios = listar_usuarios($conexao);

$mensagem = $_SESSION['mensagem_usuarios'] ?? null;
unset($_SESSION['mensagem_usuarios']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
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
            (+) CADASTRO DE USUÁRIOS
        </div>

        <div class="p-4">

            <form id="form-cadastro" novalidate>

                <?php echo campo_csrf(); ?>

                <div class="mb-3">
                    <label for="email_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        EMAIL
                    </label>

                    <input type="email" id="email_usuario" name="email_usuario" class="form-control"
                        placeholder="exemplo@123.com" maxlength="200" required>
                    <div class="invalid-feedback" data-erro="email_usuario"></div>
                </div>

                <div class="mb-3">
                    <label for="nome_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOME DE USUÁRIO
                    </label>

                    <input type="text" id="nome_usuario" name="nome_usuario" class="form-control"
                        placeholder="Usuário" minlength="3" maxlength="50" required>
                    <div class="invalid-feedback" data-erro="nome_usuario"></div>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        SENHA
                    </label>

                    <input type="password" id="senha" name="senha" class="form-control"
                        placeholder="Senha" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <div class="form-text" style="font-size: 0.75rem;">
                        Mínimo de 8 caracteres, com letra maiúscula, letra minúscula e número.
                    </div>
                    <div class="invalid-feedback" data-erro="senha"></div>
                </div>

                <div class="mb-3">
                    <label for="perfil_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        PERFIL
                    </label>

                    <select id="perfil_usuario" name="perfil_usuario" class="form-select" required>
                        <option value="FUNCIONARIO" selected>Funcionário</option>
                        <option value="ADMIN">Administrador</option>
                    </select>
                    <div class="invalid-feedback" data-erro="perfil_usuario"></div>
                </div>

                <button type="submit"
                    class="btn btn-primary w-100 fw-semibold"
                    style="background-color: #1b3f53; border: none; border-radius: 4px; font-size: 0.85rem;">
                    CADASTRAR
                </button>

            </form>

            <div id="mensagem" class="mt-3"></div>

        </div>
    </div>

    <div class="card shadow-sm border-1 p-0" style="width: 900px; max-width: calc(100vw - 32px); border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (∞) USUÁRIOS CADASTRADOS
        </div>

        <?php if ($mensagem) { ?>
            <div class="alert alert-<?php echo e($mensagem['tipo']); ?> m-2 mb-0" role="alert">
                <?php echo e($mensagem['texto']); ?>
            </div>
        <?php } ?>

        <table class="table table-bordered table-hover mb-0 align-middle">

            <thead class="table-light">
                <tr class="text-secondary" style="font-size: 0.75rem;">
                    <th class="fw-semibold">ID</th>
                    <th class="fw-semibold">LOGIN</th>
                    <th class="fw-semibold">E-MAIL</th>
                    <th class="fw-semibold">PERFIL</th>
                    <th class="fw-semibold text-center">AÇÕES</th>
                </tr>
            </thead>

            <tbody style="font-size: 0.85rem;">

                <?php if (count($usuarios) > 0) { ?>

                    <?php foreach ($usuarios as $usuario) { ?>

                        <tr>

                            <td class="text-primary-emphasis fw-bold">
                                <?php echo (int) $usuario['id_usuario']; ?>
                            </td>

                            <td class="text-secondary">
                                <?php echo e($usuario['nome_usuario']); ?>
                            </td>

                            <td class="text-body-tertiary">
                                <?php echo e($usuario['email_usuario']); ?>
                            </td>

                            <td>
                                <?php if ($usuario['perfil_usuario'] === 'ADMIN') { ?>
                                    <span class="badge" style="background-color: #daa301; color: #1b3f53;">ADMINISTRADOR</span>
                                <?php } else { ?>
                                    <span class="badge text-bg-secondary">FUNCIONÁRIO</span>
                                <?php } ?>
                            </td>

                            <td class="text-center">

                                <a href="tela-editar-user.php?id=<?php echo (int) $usuario['id_usuario']; ?>"
                                    class="btn btn-sm btn-outline-primary me-1">
                                    EDITAR
                                </a>

                                <form method="POST" style="display: inline;"
                                    onsubmit="return confirm('Tem certeza que deseja deletar este usuário?');">

                                    <?php echo campo_csrf(); ?>

                                    <input type="hidden" name="id_usuario"
                                        value="<?php echo (int) $usuario['id_usuario']; ?>">

                                    <button type="submit" name="excluir"
                                        class="btn btn-sm btn-outline-danger">
                                        X
                                    </button>

                                </form>

                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">
                            Nenhum usuário cadastrado.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>
    </div>

</main>

    <script src="../../script/validacao_cadastro_user.js"></script>

</body>

</html>
