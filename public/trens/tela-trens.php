<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trem.php';

$erros = [];
$novo = ['nome_trem' => '', 'modelo_trem' => '', 'capacidade_trem' => '', 'id_trilho' => 0, 'id_usuario' => 0, 'status_trem' => 'ATIVO'];


//    INATIVAR TREM

if ($admin && isset($_POST['inativar'])) {

    $id_trem = (int) $_POST['id_trem'];

    try {
        $stmt = mysqli_prepare($conexao, "UPDATE trens SET status_trem = 'INATIVO' WHERE id_trem = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_trem);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: tela-trens.php");
            exit;
        }
    } catch (Exception $e) {
    }

    $erros[] = "Não foi possível inativar o trem. Tente novamente.";
}


//    CADASTRAR TREM

if ($admin && isset($_POST['cadastrar'])) {

    [$novo, $erros] = validarTrem($conexao);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trens (nome_trem, modelo_trem, capacidade_trem, id_trilho, id_usuario, status_trem) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiiis", $novo['nome_trem'], $novo['modelo_trem'], $novo['capacidade_trem'], $novo['id_trilho'], $novo['id_usuario'], $novo['status_trem']);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: tela-trens.php");
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível cadastrar o trem. Tente novamente.";
    }
}


//    BUSCAR DADOS

$filtro = $_GET['filtro'] ?? 'ativos';

$where = [
    'ativos' => "WHERE trens.status_trem <> 'INATIVO'",
    'inativos' => "WHERE trens.status_trem = 'INATIVO'"
][$filtro] ?? "";

try {
    $trens = mysqli_fetch_all(mysqli_query($conexao, "SELECT trens.*, trilhos.nome_trilho, usuarios.nome_usuario
        FROM trens
        LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
        LEFT JOIN usuarios ON usuarios.id_usuario = trens.id_usuario
        $where
        ORDER BY trens.id_trem DESC"), MYSQLI_ASSOC);

    $trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho"), MYSQLI_ASSOC);

    $usuarios = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_usuario, nome_usuario FROM usuarios WHERE status_usuario = 'ATIVO' ORDER BY nome_usuario"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trens = $trilhos = $usuarios = [];
    $erros[] = "Não foi possível carregar os trens. Tente novamente.";
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trens</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Trens</h3>
                <p>Gerencie os trens cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formTrem">+ NOVO TREM</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
        <div class="caixa collapse <?php if ($erros && isset($_POST['cadastrar'])) echo 'show'; ?>" id="formTrem">
            <div class="caixa-topo">CADASTRAR NOVO TREM</div>
            <form method="POST" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeTrem" class="form-label">NOME DO TREM</label>
                        <input type="text" name="nome" id="nomeTrem" class="form-control" placeholder="Ex: Trem 01" value="<?php echo htmlspecialchars($novo['nome_trem']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="modeloTrem" class="form-label">MODELO</label>
                        <select name="modelo" id="modeloTrem" class="form-select" required>
                            <option value="">Selecione o modelo</option>
                            <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>
                                <option <?php if ($novo['modelo_trem'] === $opcao) echo 'selected'; ?>><?php echo $opcao; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="capacidadeTrem" id="labelCapacidade" class="form-label">CAPACIDADE</label>
                        <input type="number" name="capacidade" id="capacidadeTrem" class="form-control" value="<?php echo htmlspecialchars($novo['capacidade_trem']); ?>" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label for="trilhoTrem" class="form-label">TRILHO ATUAL</label>
                        <select name="id_trilho" id="trilhoTrem" class="form-select" required>
                            <option value="">Selecione o trilho</option>
                            <?php foreach ($trilhos as $trilho) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($novo['id_trilho'] == $trilho['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                        <div class="form-text"><?php echo $trilhos ? 'O trem precisa de um trilho.' : 'Nenhum trilho ativo cadastrado.'; ?> <a href="../trilhos/tela-cadastro-trilhos.php?novo=1">Cadastrar trilho</a></div>
                    </div>
                    <div class="col-md-6">
                        <label for="responsavelTrem" class="form-label">RESPONSÁVEL</label>
                        <select name="id_usuario" id="responsavelTrem" class="form-select" required>
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($usuarios as $usuario) { ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>" <?php if ($novo['id_usuario'] == $usuario['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($usuario['nome_usuario']); ?></option>
                            <?php } ?>
                        </select>
                        <div class="form-text"><?php echo $usuarios ? 'O trem precisa de um responsável.' : 'Nenhum usuário ativo cadastrado.'; ?> <a href="../usuarios/tela-cadastro-user.php">Cadastrar usuário</a></div>
                    </div>
                    <div class="col-md-6">
                        <label for="statusTrem" class="form-label">STATUS</label>
                        <select name="status" id="statusTrem" class="form-select" required>
                            <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($novo['status_trem'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formTrem">CANCELAR</button>
                </div>
            </form>
        </div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">
                TRENS CADASTRADOS
                <form method="GET">
                    <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'todos' => 'Todos'] as $valor => $texto) { ?>
                            <option value="<?php echo $valor; ?>" <?php if ($filtro == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><th>ID</th><th>NOME</th><th>MODELO</th><th>CAPACIDADE</th><th>TRILHO</th><th>RESPONSÁVEL</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trens as $trem) { ?>
                            <tr>
                                <td><?php echo $trem['id_trem']; ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['modelo_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['capacidade_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trilho'] ?? 'Sem trilho'); ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_usuario'] ?? 'Sem responsável'); ?></td>
                                <td><span class="badge status status-<?php echo $trem['status_trem']; ?>"><?php echo htmlspecialchars($trem['status_trem']); ?></span></td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <a href="tela-editar-trens.php?id=<?php echo $trem['id_trem']; ?>" class="btn btn-sm btn-outline-primary">EDITAR</a>
                                        <?php if ($trem['status_trem'] != 'INATIVO') { ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja inativar este trem?');">
                                                <input type="hidden" name="id_trem" value="<?php echo $trem['id_trem']; ?>">
                                                <button type="submit" name="inativar" class="btn btn-sm btn-outline-danger">INATIVAR</button>
                                            </form>
                                        <?php } ?>
                                    <?php } else { echo '-'; } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!$trens) { ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum trem cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../script/capacidade.js"></script>

</body>

</html>
