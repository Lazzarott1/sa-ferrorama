<?php

include __DIR__ . '/../../infra/verifica-admin.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../../infra/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$perfis = ['ADMINISTRADOR', 'OPERADOR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_usuario = trim($_POST['nome_usuario'] ?? '');
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? '';

    if ($nome_usuario === '' || $email_usuario === '' || $senha === '' || !in_array($perfil, $perfis, true)) {
        http_response_code(400);
        echo "Preencha todos os campos corretamente.";
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (nome_usuario, email_usuario, senha, perfil) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'ssss', $nome_usuario, $email_usuario, $senha_hash, $perfil);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "Usuário cadastrado com sucesso!";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            http_response_code(409);
            echo "Esse nome de usuário já existe.";
        } else {
            http_response_code(500);
            echo "Erro ao cadastrar usuário.";
        }
    }

    exit();
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$mensagens = [
    'inativado'    => ['success', 'Usuário inativado com sucesso.'],
    'editado'      => ['success', 'Usuário atualizado com sucesso.'],
    'inexistente'  => ['warning', 'Usuário não encontrado ou já inativo.'],
    'proprio'      => ['warning', 'Você não pode inativar a própria conta.'],
    'invalido'     => ['danger', 'Solicitação inválida.'],
    'erro'         => ['danger', 'Erro ao inativar usuário. Tente novamente.'],
];

$mensagem = $mensagens[$_GET['msg'] ?? ''] ?? null;

$filtro_status = $_GET['status'] ?? '';

if (!in_array($filtro_status, ['ATIVO', 'INATIVO'], true)) {
    $filtro_status = '';
}

$usuarios = [];
$erro_lista = false;

try {
    $sql_lista = "SELECT id_usuario, nome_usuario, email_usuario, perfil, status_usuario, data_cadastro
                  FROM usuarios
                  WHERE (? = '' OR status_usuario = ?)
                  ORDER BY nome_usuario";
    $stmt = mysqli_prepare($conexao, $sql_lista);
    mysqli_stmt_bind_param($stmt, "ss", $filtro_status, $filtro_status);
    mysqli_stmt_execute($stmt);
    $usuarios = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
    $erro_lista = true;
}
?>

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

    <div class="card shadow-sm border-1 p-0" style="width: 600px; border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (+) CADASTRO DE USUÁRIOS
        </div>

        <div class="p-4">

            <form id="form-cadastro">

                <div class="mb-3">
                    <label for="nome_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        NOME DE USUÁRIO
                    </label>

                    <input type="text" id="nome_usuario" name="nome_usuario" class="form-control"
                        placeholder="Usuário" required>
                </div>

                <div class="mb-3">
                    <label for="email_usuario" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        EMAIL
                    </label>

                    <input type="email" id="email_usuario" name="email_usuario" class="form-control"
                        placeholder="exemplo@123.com" required>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        SENHA
                    </label>

                    <input type="password" id="senha" name="senha" class="form-control"
                        placeholder="Senha" required>
                </div>

                <div class="mb-3">
                    <label for="perfil" class="form-label text-secondary fw-semibold"
                        style="font-size: 0.8rem;">
                        PERFIL
                    </label>

                    <select id="perfil" name="perfil" class="form-select" required>
                        <option value="OPERADOR">Operador</option>
                        <option value="ADMINISTRADOR">Administrador</option>
                    </select>
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

    <div class="card shadow-sm border-1 p-0" style="width: 900px; border-radius: 4px;">

        <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
            style="font-size: 0.7rem;">
            (∞) USUÁRIOS CADASTRADOS
        </div>

        <form method="GET" class="d-flex align-items-center gap-2 p-2 border-bottom">
            <label for="status" class="text-secondary fw-semibold mb-0" style="font-size: 0.75rem;">
                FILTRAR POR STATUS
            </label>

            <select id="status" name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="" <?php echo $filtro_status === '' ? 'selected' : ''; ?>>Todos</option>
                <option value="ATIVO" <?php echo $filtro_status === 'ATIVO' ? 'selected' : ''; ?>>Ativo</option>
                <option value="INATIVO" <?php echo $filtro_status === 'INATIVO' ? 'selected' : ''; ?>>Inativo</option>
            </select>
        </form>

        <?php if ($mensagem) { ?>
            <div class="alert alert-<?php echo $mensagem[0]; ?> rounded-0 mb-0 py-2" style="font-size: 0.85rem;">
                <?php echo $mensagem[1]; ?>
            </div>
        <?php } ?>

        <table class="table table-bordered table-hover mb-0 align-middle">

            <thead class="table-light">
                <tr class="text-secondary" style="font-size: 0.75rem;">
                    <th class="fw-semibold">ID</th>
                    <th class="fw-semibold">NOME DE USUÁRIO</th>
                    <th class="fw-semibold">E-MAIL</th>
                    <th class="fw-semibold">PERFIL</th>
                    <th class="fw-semibold">STATUS</th>
                    <th class="fw-semibold">DATA DE CADASTRO</th>
                    <th class="fw-semibold text-center">AÇÕES</th>
                </tr>
            </thead>

            <tbody style="font-size: 0.85rem;">

                <?php if ($erro_lista) { ?>

                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            Não foi possível carregar os usuários. Tente novamente mais tarde.
                        </td>
                    </tr>

                <?php } elseif (count($usuarios) > 0) { ?>

                    <?php foreach ($usuarios as $usuario) { ?>

                        <tr>

                            <td class="text-primary-emphasis fw-bold">
                                <?php echo (int) $usuario['id_usuario']; ?>
                            </td>

                            <td class="text-secondary">
                                <?php echo htmlspecialchars($usuario['nome_usuario']); ?>
                            </td>

                            <td class="text-body-tertiary">
                                <?php echo htmlspecialchars($usuario['email_usuario']); ?>
                            </td>

                            <td>
                                <?php if ($usuario['perfil'] === 'ADMINISTRADOR') { ?>
                                    <span class="badge rounded-1" style="background-color: #1b3f53;">ADMINISTRADOR</span>
                                <?php } else { ?>
                                    <span class="badge rounded-1" style="background-color: #daa301; color: #1b3f53;">OPERADOR</span>
                                <?php } ?>
                            </td>

                            <td>
                                <?php if ($usuario['status_usuario'] === 'ATIVO') { ?>
                                    <span class="badge rounded-1 text-bg-success">ATIVO</span>
                                <?php } else { ?>
                                    <span class="badge rounded-1 text-bg-secondary">INATIVO</span>
                                <?php } ?>
                            </td>

                            <td class="text-secondary">
                                <?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?>
                            </td>

                            <td class="text-center">

                                <a href="tela-editar-user.php?id=<?php echo (int) $usuario['id_usuario']; ?>"
                                    class="btn btn-sm btn-outline-primary me-1">
                                    EDITAR
                                </a>

                                <?php if ((int) $usuario['id_usuario'] === (int) $_SESSION['id_usuario']) { ?>

                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                        title="Você não pode inativar a própria conta">
                                        INATIVAR
                                    </button>

                                <?php } elseif ($usuario['status_usuario'] === 'ATIVO') { ?>

                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalInativarUsuario"
                                        data-id="<?php echo (int) $usuario['id_usuario']; ?>"
                                        data-nome="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>">
                                        INATIVAR
                                    </button>

                                <?php } ?>

                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            Nenhum usuário cadastrado.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>
    </div>

</main>

    <div class="modal fade" id="modalInativarUsuario" tabindex="-1"
        aria-labelledby="tituloModalInativarUsuario" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content rounded-1 border-0">

                <div class="modal-header rounded-top-1 text-white py-2"
                    style="background-color: #1b3f53; border-bottom: 3px solid #daa301;">

                    <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2"
                        id="tituloModalInativarUsuario">
                        <span style="color: #daa301; font-size: 1.1rem; line-height: 1;">&#9888;</span>
                        INATIVAR USUÁRIO
                    </h6>

                    <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Fechar"></button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-warning rounded-1 d-flex gap-2 mb-0 py-2"
                        role="alert" style="border-left: 4px solid #daa301;">

                        <span class="fw-bold" style="font-size: 1.2rem; line-height: 1.3;">&#9888;</span>

                        <div>
                            <strong>
                                Tem certeza que deseja inativar o usuário
                                <span id="modalNomeUsuario"></span>?
                            </strong>
                            <br>
                            <span style="font-size: 0.9rem;">
                                Ele não poderá mais entrar no sistema. Para reativar, use EDITAR.
                            </span>
                        </div>

                    </div>

                </div>

                <div class="modal-footer py-2">

                    <form method="POST" action="inativar-user.php" class="d-flex gap-2">

                        <input type="hidden" name="id_usuario" id="modalIdUsuario">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

                        <button type="button" class="btn btn-sm btn-outline-secondary px-3 fw-bold"
                            data-bs-dismiss="modal">
                            CANCELAR
                        </button>

                        <button type="submit" class="btn btn-sm btn-danger px-3 fw-bold">
                            INATIVAR
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../script/validacao_cadastro_user.js"></script>

    <script>
        document.getElementById('modalInativarUsuario').addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            document.getElementById('modalIdUsuario').value = botao.dataset.id;
            document.getElementById('modalNomeUsuario').textContent = botao.dataset.nome;
        });

        <?php if ($mensagem) { ?>
        history.replaceState(null, '', 'tela-cadastro-user.php<?php echo $filtro_status ? '?status=' . $filtro_status : ''; ?>');
        <?php } ?>
    </script>

</body>

</html>
