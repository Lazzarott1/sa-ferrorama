<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';

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

    if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Informe um e-mail válido.";
        exit();
    }

    if (strlen($senha) < 8) {
        http_response_code(400);
        echo "A senha deve ter pelo menos 8 caracteres.";
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
    'associado'    => ['warning', 'Este usuário é responsável por trens ativos. Troque o responsável desses trens antes de inativá-lo.'],
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
    $sql_lista = "SELECT id_usuario, nome_usuario, email_usuario, perfil, status_usuario, data_cadastro,
                  (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND status_trem <> 'INATIVO') AS nomes_trens,
                  (SELECT COUNT(*) FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND status_trem <> 'INATIVO') AS total_trens
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

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Usuários</h3>
                <p>Gerencie quem tem acesso ao sistema</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formUsuario">+ NOVO USUÁRIO</button>
        </div>

        <?php if ($mensagem) { ?>
            <div class="alert alert-<?php echo $mensagem[0]; ?>"><?php echo $mensagem[1]; ?></div>
        <?php } ?>

        <div class="caixa collapse" id="formUsuario">
            <div class="caixa-topo">CADASTRAR NOVO USUÁRIO</div>
            <form id="form-cadastro" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome_usuario" class="form-label">NOME DE USUÁRIO</label>
                        <input type="text" id="nome_usuario" name="nome_usuario" class="form-control" placeholder="Ex: joao.silva" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email_usuario" class="form-label">E-MAIL</label>
                        <input type="email" id="email_usuario" name="email_usuario" class="form-control" placeholder="Ex: joao@ferromonitor.com" required>
                    </div>
                    <div class="col-md-6">
                        <label for="senha" class="form-label">SENHA</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Mínimo 8 caracteres" minlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label for="perfil" class="form-label">PERFIL</label>
                        <select id="perfil" name="perfil" class="form-select" required>
                            <option value="OPERADOR">Operador</option>
                            <option value="ADMINISTRADOR">Administrador</option>
                        </select>
                    </div>
                </div>
                <div id="mensagem" class="mt-3"></div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formUsuario">CANCELAR</button>
                </div>
            </form>
        </div>

        <div class="caixa">
            <div class="caixa-topo">
                USUÁRIOS CADASTRADOS
                <form method="GET">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach (['' => 'Todos', 'ATIVO' => 'Ativos', 'INATIVO' => 'Inativos'] as $valor => $texto) { ?>
                            <option value="<?php echo $valor; ?>" <?php if ($filtro_status === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><th>ID</th><th>NOME DE USUÁRIO</th><th>E-MAIL</th><th>PERFIL</th><th>TRENS</th><th>STATUS</th><th>CADASTRADO EM</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario) { ?>
                            <tr>
                                <td><?php echo (int) $usuario['id_usuario']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email_usuario']); ?></td>
                                <td><span class="badge status status-<?php echo $usuario['perfil']; ?>"><?php echo $usuario['perfil']; ?></span></td>
                                <td><?php echo htmlspecialchars($usuario['nomes_trens'] ?? 'Sem trem'); ?></td>
                                <td><span class="badge status status-<?php echo $usuario['status_usuario']; ?>"><?php echo $usuario['status_usuario']; ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                                <td class="text-center text-nowrap">
                                    <a href="tela-editar-user.php?id=<?php echo (int) $usuario['id_usuario']; ?>" class="btn btn-sm btn-outline-primary">EDITAR</a>
                                    <?php if ((int) $usuario['id_usuario'] === (int) $_SESSION['id_usuario']) { ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Você não pode inativar a própria conta">INATIVAR</button>
                                    <?php } elseif ($usuario['status_usuario'] === 'ATIVO') { ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalInativarUsuario" data-id="<?php echo (int) $usuario['id_usuario']; ?>" data-nome="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" data-trens="<?php echo (int) $usuario['total_trens']; ?>">INATIVAR</button>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if ($erro_lista) { ?>
                            <tr><td colspan="8" class="text-center text-danger py-4">Não foi possível carregar os usuários. Tente novamente mais tarde.</td></tr>
                        <?php } elseif (!$usuarios) { ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div class="modal fade" id="modalInativarUsuario" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold">INATIVAR USUÁRIO</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong id="modalPerguntaUsuario"></strong><br>
                        <span id="modalTextoUsuario"></span>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <form method="POST" action="inativar-user.php" class="d-flex gap-2">
                        <input type="hidden" name="id_usuario" id="modalIdUsuario">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                        <button type="submit" id="modalBotaoInativar" class="btn btn-sm btn-danger">INATIVAR</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../script/validacao_cadastro_user.js"></script>

    <script>
        modalInativarUsuario.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;
            const trens = Number(botao.dataset.trens);

            modalIdUsuario.value = botao.dataset.id;
            modalPerguntaUsuario.textContent = (trens ? 'Não é possível inativar o usuário ' : 'Tem certeza que deseja inativar o usuário ') + botao.dataset.nome + (trens ? '.' : '?');
            modalTextoUsuario.textContent = trens ? 'Ele é responsável por ' + trens + ' trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.' : 'Ele não poderá mais entrar no sistema. Para reativar, use EDITAR.';
            modalBotaoInativar.hidden = trens > 0;
        });

        <?php if ($mensagem) { ?>
        history.replaceState(null, '', 'tela-cadastro-user.php<?php echo $filtro_status ? '?status=' . $filtro_status : ''; ?>');
        <?php } ?>
    </script>

</body>

</html>
