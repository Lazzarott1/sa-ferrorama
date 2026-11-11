<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trilho.php';
include __DIR__ . '/../listagem.php';

$erros = [];
$aberto = -1;
$novo = ['nome_trilho' => '', 'descricao_trilho' => '', 'km_trilho' => '', 'status_trilho' => 'ATIVO', 'cep' => '', 'logradouro' => '', 'bairro' => '', 'cidade' => '', 'uf' => ''];

$trens_ativos = "(SELECT COUNT(*) FROM trens WHERE trens.id_trilho = trilhos.id_trilho AND trens.status_trem <> 'INATIVO')";
$sensores_ativos = "(SELECT COUNT(*) FROM sensores WHERE sensores.id_trilho = trilhos.id_trilho AND sensores.status_sensor <> 'INATIVO')";


//    ATIVAR / INATIVAR TRILHOS

if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {

    if (!mudarStatus($conexao, 'trilhos', 'id_trilho', 'status_trilho', "$trens_ativos = 0 AND $sensores_ativos = 0")) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    $erros[] = "Trilhos com trens ou sensores ativos não foram inativados. Inative ou altere esses itens antes.";
}


//    CADASTRAR / EDITAR TRILHO

if ($admin && isset($_POST['salvar'])) {

    $id = (int) $_POST['id'];
    [$form, $erros] = validarTrilho();

    if ($id && !$erros && $form['status_trilho'] == 'INATIVO') {
        $total = mysqli_fetch_row(mysqli_query($conexao, "SELECT $trens_ativos, $sensores_ativos FROM trilhos WHERE id_trilho = $id"));

        if ($total[0] > 0 || $total[1] > 0) {
            $erros[] = "Este trilho tem " . $total[0] . " trem(ns) e " . $total[1] . " sensor(es) ativo(s). Inative ou altere esses itens antes de inativar o trilho.";
        }
    }

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, $id
                ? "UPDATE trilhos SET nome_trilho = ?, descricao_trilho = ?, km_trilho = ?, status_trilho = ?, cep = ?, logradouro = ?, bairro = ?, cidade = ?, uf = ? WHERE id_trilho = $id"
                : "INSERT INTO trilhos (nome_trilho, descricao_trilho, km_trilho, status_trilho, cep, logradouro, bairro, cidade, uf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssssss", ...array_values($form));

            if (mysqli_stmt_execute($stmt)) {
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível salvar o trilho. Tente novamente.";
    }

    $aberto = $id;
}


//    BUSCAR DADOS

$where = filtrar('status_trilho');

try {
    $trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT trilhos.*, $trens_ativos AS total_trens, $sensores_ativos AS total_sensores,
        (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens WHERE trens.id_trilho = trilhos.id_trilho AND trens.status_trem <> 'INATIVO') AS nomes_trens
        FROM trilhos $where ORDER BY id_trilho DESC"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trilhos = [];
    $erros[] = "Não foi possível carregar os trilhos. Tente novamente.";
}

function formTrilho($trilho)
{
    $id = $trilho['id_trilho'] ?? 0; ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeTrilho<?php echo $id; ?>" class="form-label">NOME DO TRILHO</label>
                <input type="text" name="nome" id="nomeTrilho<?php echo $id; ?>" class="form-control" placeholder="Ex: Trilho Norte 01" value="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="kmTrilho<?php echo $id; ?>" class="form-label">KM</label>
                <input type="number" name="km" id="kmTrilho<?php echo $id; ?>" class="form-control" step="0.01" min="0.01" placeholder="Ex: 12.5" value="<?php echo htmlspecialchars($trilho['km_trilho']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="descricaoTrilho<?php echo $id; ?>" class="form-label">DESCRIÇÃO</label>
                <input type="text" name="descricao" id="descricaoTrilho<?php echo $id; ?>" class="form-control" placeholder="Ex: Trecho entre os pátios A e B" value="<?php echo htmlspecialchars($trilho['descricao_trilho']); ?>" required>
            </div>
            <div class="col-md-3">
                <label for="statusTrilho<?php echo $id; ?>" class="form-label">STATUS</label>
                <select name="status" id="statusTrilho<?php echo $id; ?>" class="form-select" required>
                    <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                        <option value="<?php echo $valor; ?>" <?php if ($trilho['status_trilho'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="dataTrilho<?php echo $id; ?>" class="form-label">DATA DE CRIAÇÃO</label>
                <input type="text" id="dataTrilho<?php echo $id; ?>" class="form-control" value="<?php echo date('d/m/Y', strtotime($trilho['data_criacao'] ?? 'now')); ?>" disabled>
            </div>
            <div class="col-12 border-top pt-3">
                <span class="form-label">LOCALIZAÇÃO DA ESTAÇÃO (OPCIONAL)</span>
            </div>
            <div class="col-md-3">
                <label for="cep<?php echo $id; ?>" class="form-label">CEP</label>
                <input type="text" name="cep" id="cep<?php echo $id; ?>" class="form-control" inputmode="numeric" maxlength="9" placeholder="Ex: 89201-100" value="<?php echo preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', htmlspecialchars($trilho['cep'])); ?>" onblur="buscarCep(this)">
                <div class="form-text">Digite o CEP para preencher o endereço.</div>
            </div>
            <?php foreach (['logradouro' => ['LOGRADOURO', 4], 'bairro' => ['BAIRRO', 2], 'cidade' => ['CIDADE', 2], 'uf' => ['UF', 1]] as $campo => [$rotulo, $tamanho]) { ?>
                <div class="col-md-<?php echo $tamanho; ?>">
                    <label for="<?php echo $campo . $id; ?>" class="form-label"><?php echo $rotulo; ?></label>
                    <input type="text" name="<?php echo $campo; ?>" id="<?php echo $campo . $id; ?>" class="form-control" maxlength="<?php echo $campo == 'uf' ? 2 : 100; ?>" value="<?php echo htmlspecialchars($trilho[$campo]); ?>">
                </div>
            <?php } ?>
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
    <title>Trilhos</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Trilhos</h3>
                <p>Gerencie os trilhos cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO TRILHO</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <div class="caixa collapse <?php if ($aberto === 0 || isset($_GET['novo'])) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TRILHO</div>
                <?php formTrilho($aberto === 0 ? $form : $novo); ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <?php topo('TRILHOS CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><?php if ($admin) marcarTodos(); ?><th>ID</th><th>NOME</th><th>DESCRIÇÃO</th><th>KM</th><th>LOCAL</th><th>TRENS</th><th>CRIADO EM</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trilhos as $trilho) { ?>
                            <tr>
                                <?php if ($admin) marcar($trilho['id_trilho'], 'o trilho ' . $trilho['nome_trilho'], $trilho['total_trens'] || $trilho['total_sensores'] ? 'Ele tem ' . $trilho['total_trens'] . ' trem(ns) e ' . $trilho['total_sensores'] . ' sensor(es) ativo(s). Inative ou altere esses itens antes de inativá-lo.' : ''); ?>
                                <td><?php echo $trilho['id_trilho']; ?></td>
                                <td><?php echo htmlspecialchars($trilho['nome_trilho']); ?></td>
                                <td><?php echo htmlspecialchars($trilho['descricao_trilho']); ?></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($trilho['km_trilho']); ?> km</td>
                                <td><?php echo $trilho['cidade'] ? htmlspecialchars($trilho['cidade'] . '/' . $trilho['uf']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($trilho['nomes_trens'] ?? 'Sem trem'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($trilho['data_criacao'])); ?></td>
                                <td><span class="badge status status-<?php echo $trilho['status_trilho']; ?>"><?php echo htmlspecialchars($trilho['status_trilho']); ?></span></td>
                                <td class="text-center text-nowrap"><?php if ($admin) acoes($trilho['id_trilho'], $trilho['status_trilho'] == 'INATIVO'); else echo '-'; ?></td>
                            </tr>
                            <?php if ($admin) { ?>
                                <tr class="collapse <?php if ($aberto == $trilho['id_trilho']) echo 'show'; ?>" id="editar<?php echo $trilho['id_trilho']; ?>">
                                    <td colspan="10" class="p-0"><?php formTrilho($aberto == $trilho['id_trilho'] ? $form + $trilho : $trilho); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <?php if (!$trilhos) { ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">Nenhum trilho cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php modalStatus(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../script/cep.js"></script>

</body>

</html>
