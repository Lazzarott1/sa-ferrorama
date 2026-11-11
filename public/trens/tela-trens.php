<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trem.php';
include __DIR__ . '/../listagem.php';

$erros = [];
$aberto = -1;
$novo = ['nome_trem' => '', 'modelo_trem' => '', 'capacidade_trem' => '', 'id_trilho' => 0, 'id_usuario' => 0, 'status_trem' => 'ATIVO'];


//    ATIVAR / INATIVAR TRENS

if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {
    mudarStatus($conexao, 'trens', 'id_trem', 'status_trem');
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}


//    CADASTRAR / EDITAR TREM

if ($admin && isset($_POST['salvar'])) {

    $id = (int) $_POST['id'];
    $atual = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id_trilho, id_usuario FROM trens WHERE id_trem = $id"));
    [$form, $erros] = validarTrem($conexao, (int) ($atual['id_trilho'] ?? 0), (int) ($atual['id_usuario'] ?? 0));

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, $id
                ? "UPDATE trens SET nome_trem = ?, modelo_trem = ?, capacidade_trem = ?, id_trilho = ?, id_usuario = ?, status_trem = ? WHERE id_trem = $id"
                : "INSERT INTO trens (nome_trem, modelo_trem, capacidade_trem, id_trilho, id_usuario, status_trem) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiiis", ...array_values($form));

            if (mysqli_stmt_execute($stmt)) {
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível salvar o trem. Tente novamente.";
    }

    $aberto = $id;
}


//    BUSCAR DADOS

$where = filtrar('trens.status_trem');

try {
    $trens = mysqli_fetch_all(mysqli_query($conexao, "SELECT trens.*, trilhos.nome_trilho, usuarios.nome_usuario
        FROM trens
        LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
        LEFT JOIN usuarios ON usuarios.id_usuario = trens.id_usuario
        $where
        ORDER BY trens.id_trem DESC"), MYSQLI_ASSOC);

    $trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trilho AS id, nome_trilho AS nome, status_trilho AS status FROM trilhos ORDER BY nome_trilho"), MYSQLI_ASSOC);

    $usuarios = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_usuario AS id, nome_usuario AS nome, status_usuario AS status FROM usuarios ORDER BY nome_usuario"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trens = $trilhos = $usuarios = [];
    $erros[] = "Não foi possível carregar os trens. Tente novamente.";
}

function opcoes($lista, $atual)
{
    foreach ($lista as $item) {
        if ($item['status'] != 'INATIVO' || $item['id'] == $atual) { ?>
            <option value="<?php echo $item['id']; ?>" <?php if ($item['id'] == $atual) echo 'selected'; ?>><?php echo htmlspecialchars($item['nome']); ?></option>
        <?php }
    }
}

function formTrem($trem, $trilhos, $usuarios)
{
    $id = $trem['id_trem'] ?? 0; ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeTrem<?php echo $id; ?>" class="form-label">NOME DO TREM</label>
                <input type="text" name="nome" id="nomeTrem<?php echo $id; ?>" class="form-control" placeholder="Ex: Trem 01" value="<?php echo htmlspecialchars($trem['nome_trem']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="modeloTrem<?php echo $id; ?>" class="form-label">MODELO</label>
                <select name="modelo" id="modeloTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o modelo</option>
                    <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>
                        <option <?php if ($trem['modelo_trem'] === $opcao) echo 'selected'; ?>><?php echo $opcao; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="capacidadeTrem<?php echo $id; ?>" class="form-label">CAPACIDADE</label>
                <input type="number" name="capacidade" id="capacidadeTrem<?php echo $id; ?>" class="form-control" value="<?php echo htmlspecialchars($trem['capacidade_trem']); ?>" min="1" required>
            </div>
            <div class="col-md-6">
                <label for="trilhoTrem<?php echo $id; ?>" class="form-label">TRILHO ATUAL</label>
                <select name="id_trilho" id="trilhoTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o trilho</option>
                    <?php opcoes($trilhos, $trem['id_trilho']); ?>
                </select>
                <div class="form-text">O trem precisa de um trilho. <a href="../trilhos/tela-cadastro-trilhos.php?novo=1">Cadastrar trilho</a></div>
            </div>
            <div class="col-md-6">
                <label for="responsavelTrem<?php echo $id; ?>" class="form-label">RESPONSÁVEL</label>
                <select name="id_usuario" id="responsavelTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o responsável</option>
                    <?php opcoes($usuarios, $trem['id_usuario']); ?>
                </select>
                <div class="form-text">O trem precisa de um responsável. <a href="../usuarios/tela-cadastro-user.php">Cadastrar usuário</a></div>
            </div>
            <div class="col-md-6">
                <label for="statusTrem<?php echo $id; ?>" class="form-label">STATUS</label>
                <select name="status" id="statusTrem<?php echo $id; ?>" class="form-select" required>
                    <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                        <option value="<?php echo $valor; ?>" <?php if ($trem['status_trem'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" name="salvar" class="btn btn-primary"><?php echo $id ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR'; ?></button>
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
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO TREM</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <div class="caixa collapse <?php if ($aberto === 0) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TREM</div>
                <?php formTrem($aberto === 0 ? $form : $novo, $trilhos, $usuarios); ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <?php topo('TRENS CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><?php if ($admin) marcarTodos(); ?><th>ID</th><th>NOME</th><th>MODELO</th><th>CAPACIDADE</th><th>TRILHO</th><th>RESPONSÁVEL</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trens as $trem) { ?>
                            <tr>
                                <?php if ($admin) marcar($trem['id_trem'], 'o trem ' . $trem['nome_trem']); ?>
                                <td><?php echo $trem['id_trem']; ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['modelo_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['capacidade_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trilho'] ?? 'Sem trilho'); ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_usuario'] ?? 'Sem responsável'); ?></td>
                                <td><span class="badge status status-<?php echo $trem['status_trem']; ?>"><?php echo htmlspecialchars($trem['status_trem']); ?></span></td>
                                <td class="text-center text-nowrap"><?php if ($admin) acoes($trem['id_trem'], $trem['status_trem'] == 'INATIVO'); else echo '-'; ?></td>
                            </tr>
                            <?php if ($admin) { ?>
                                <tr class="collapse <?php if ($aberto == $trem['id_trem']) echo 'show'; ?>" id="editar<?php echo $trem['id_trem']; ?>">
                                    <td colspan="9" class="p-0"><?php formTrem($aberto == $trem['id_trem'] ? $form + $trem : $trem, $trilhos, $usuarios); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <?php if (!$trens) { ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum trem cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php modalStatus(); ?>

    <script src="../../script/capacidade.js"></script>

</body>

</html>
