<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../listagem.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$perfis = ['ADMINISTRADOR', 'OPERADOR'];
$erros = [];
$aberto = -1;
$eu = (int) $_SESSION['id_usuario'];
$trens_ativos = "(SELECT COUNT(*) FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND status_trem <> 'INATIVO')";


//    ATIVAR / INATIVAR USUÁRIOS

if (isset($_POST['ativar']) || isset($_POST['inativar'])) {

    if (!hash_equals($_SESSION['token'] ?? '', $_POST['token'] ?? '')) {
        $erros[] = "Solicitação inválida.";
    } elseif (!mudarStatus($conexao, 'usuarios', 'id_usuario', 'status_usuario', "id_usuario <> $eu AND $trens_ativos = 0")) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $erros[] = "Usuários responsáveis por trens ativos e a sua própria conta não foram inativados.";
    }
}


//    EDITAR USUÁRIO

if (isset($_POST['salvar'])) {

    $id = (int) $_POST['id'];
    $proprio = $id === $eu;
    $nome_usuario = trim($_POST['nome_usuario'] ?? '');
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $proprio ? 'ADMINISTRADOR' : ($_POST['perfil'] ?? '');
    $status_usuario = $proprio ? 'ATIVO' : ($_POST['status_usuario'] ?? '');

    if (!in_array($perfil, $perfis, true) || !in_array($status_usuario, ['ATIVO', 'INATIVO'], true)) {
        $erros[] = "Perfil ou status inválido.";
    } elseif ($nome_usuario === '' || !filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Preencha o nome e um e-mail válido.";
    } elseif ($senha !== '' && strlen($senha) < 8) {
        $erros[] = "A senha deve ter pelo menos 8 caracteres.";
    } elseif ($status_usuario === 'INATIVO' && ($total = mysqli_fetch_row(mysqli_query($conexao, "SELECT $trens_ativos FROM usuarios WHERE id_usuario = $id"))[0]) > 0) {
        $erros[] = "Este usuário é responsável por " . $total . " trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.";
    } else {
        try {
            $valores = [$nome_usuario, $email_usuario, $perfil, $status_usuario];

            if ($senha !== '') {
                $valores[] = password_hash($senha, PASSWORD_DEFAULT);
            }

            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?" . ($senha !== '' ? ", senha = ?" : "") . " WHERE id_usuario = $id");
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($valores)), ...$valores);
            mysqli_stmt_execute($stmt);

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } catch (mysqli_sql_exception $e) {
            $erros[] = $e->getCode() === 1062 ? "Esse nome de usuário já existe." : "Erro ao atualizar usuário.";
        }
    }

    $aberto = $id;
}


//    CADASTRAR USUÁRIO

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erros) {
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


$where = filtrar('status_usuario');

try {
    $usuarios = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_usuario, nome_usuario, email_usuario, perfil, status_usuario, data_cadastro,
        (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND status_trem <> 'INATIVO') AS nomes_trens,
        $trens_ativos AS total_trens
        FROM usuarios $where ORDER BY nome_usuario"), MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $usuarios = [];
    $erros[] = "Não foi possível carregar os usuários. Tente novamente mais tarde.";
}

function formUsuario($usuario, $proprio)
{
    $id = $usuario['id_usuario']; ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nome_usuario<?php echo $id; ?>" class="form-label">NOME DE USUÁRIO</label>
                <input type="text" id="nome_usuario<?php echo $id; ?>" name="nome_usuario" class="form-control" value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email_usuario<?php echo $id; ?>" class="form-label">E-MAIL</label>
                <input type="email" id="email_usuario<?php echo $id; ?>" name="email_usuario" class="form-control" value="<?php echo htmlspecialchars($usuario['email_usuario']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="senha<?php echo $id; ?>" class="form-label">NOVA SENHA</label>
                <input type="password" id="senha<?php echo $id; ?>" name="senha" class="form-control" placeholder="Deixe em branco para manter a senha atual" minlength="8">
            </div>
            <?php foreach (['perfil' => ['PERFIL', ['OPERADOR' => 'Operador', 'ADMINISTRADOR' => 'Administrador']], 'status_usuario' => ['STATUS', ['ATIVO' => 'Ativo', 'INATIVO' => 'Inativo']]] as $campo => [$rotulo, $opcoes]) { ?>
                <div class="col-md-3">
                    <label for="<?php echo $campo . $id; ?>" class="form-label"><?php echo $rotulo; ?></label>
                    <select id="<?php echo $campo . $id; ?>" name="<?php echo $campo; ?>" class="form-select" <?php if ($proprio) echo 'disabled'; ?>>
                        <?php foreach ($opcoes as $valor => $texto) { ?>
                            <option value="<?php echo $valor; ?>" <?php if ($usuario[$campo] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </div>
            <?php } ?>
            <?php if ($proprio) { ?>
                <div class="col-12 form-text mt-1">Você não pode alterar o próprio perfil nem o próprio status.</div>
            <?php } ?>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" name="salvar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
            <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editar<?php echo $id; ?>">CANCELAR</button>
        </div>
    </form>
<?php }

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

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
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
            <?php topo('USUÁRIOS CADASTRADOS', true); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><?php marcarTodos(); ?><th>ID</th><th>NOME DE USUÁRIO</th><th>E-MAIL</th><th>PERFIL</th><th>TRENS</th><th>STATUS</th><th>CADASTRADO EM</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario) { $id = (int) $usuario['id_usuario']; ?>
                            <tr>
                                <?php marcar($id, 'o usuário ' . $usuario['nome_usuario'], $id === $eu ? 'Você não pode inativar a própria conta.' : ($usuario['total_trens'] > 0 ? 'Ele é responsável por ' . $usuario['total_trens'] . ' trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.' : '')); ?>
                                <td><?php echo $id; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email_usuario']); ?></td>
                                <td><span class="badge status status-<?php echo $usuario['perfil']; ?>"><?php echo $usuario['perfil']; ?></span></td>
                                <td><?php echo htmlspecialchars($usuario['nomes_trens'] ?? 'Sem trem'); ?></td>
                                <td><span class="badge status status-<?php echo $usuario['status_usuario']; ?>"><?php echo $usuario['status_usuario']; ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                                <td class="text-center text-nowrap"><?php acoes($id, $usuario['status_usuario'] === 'INATIVO'); ?></td>
                            </tr>
                            <tr class="collapse <?php if ($aberto === $id) echo 'show'; ?>" id="editar<?php echo $id; ?>">
                                <td colspan="9" class="p-0"><?php formUsuario($aberto === $id ? $_POST + $usuario : $usuario, $id === $eu); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if (!$usuarios) { ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php modalStatus(); ?>

    <script src="../../script/validacao_cadastro_user.js"></script>

</body>

</html>
