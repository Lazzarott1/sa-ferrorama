<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trilho.php';

$erros = [];
$novo = ['nome_trilho' => '', 'descricao_trilho' => '', 'km_trilho' => '', 'status_trilho' => 'ATIVO', 'cep' => '', 'logradouro' => '', 'bairro' => '', 'cidade' => '', 'uf' => ''];

$associacoes = "(SELECT COUNT(*) FROM trens WHERE trens.id_trilho = trilhos.id_trilho AND trens.status_trem <> 'INATIVO') AS total_trens,
    (SELECT COUNT(*) FROM sensores WHERE sensores.id_trilho = trilhos.id_trilho AND sensores.status_sensor <> 'INATIVO') AS total_sensores";


//    INATIVAR TRILHO

if ($admin && isset($_POST['inativar'])) {

    $id_trilho = (int) $_POST['id_trilho'];

    try {
        $stmt = mysqli_prepare($conexao, "SELECT $associacoes FROM trilhos WHERE id_trilho = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_trilho);
        mysqli_stmt_execute($stmt);
        $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($total && ($total['total_trens'] > 0 || $total['total_sensores'] > 0)) {
            header("Location: tela-cadastro-trilhos.php?bloqueado=" . $id_trilho);
            exit;
        }

        $stmt = mysqli_prepare($conexao, "UPDATE trilhos SET status_trilho = 'INATIVO' WHERE id_trilho = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_trilho);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: tela-cadastro-trilhos.php");
            exit;
        }
    } catch (Exception $e) {
    }

    $erros[] = "Não foi possível inativar o trilho. Tente novamente.";
}


//    CADASTRAR TRILHO

if ($admin && isset($_POST['cadastrar'])) {

    [$novo, $erros] = validarTrilho();

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trilhos (nome_trilho, descricao_trilho, km_trilho, status_trilho, cep, logradouro, bairro, cidade, uf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssssss", $novo['nome_trilho'], $novo['descricao_trilho'], $novo['km_trilho'], $novo['status_trilho'], $novo['cep'], $novo['logradouro'], $novo['bairro'], $novo['cidade'], $novo['uf']);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: tela-cadastro-trilhos.php");
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível cadastrar o trilho. Tente novamente.";
    }
}


//    BUSCAR DADOS

$filtro = $_GET['filtro'] ?? 'ativos';

$where = [
    'ativos' => "WHERE status_trilho <> 'INATIVO'",
    'inativos' => "WHERE status_trilho = 'INATIVO'"
][$filtro] ?? "";

try {
    $trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT trilhos.*, $associacoes,
        (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens WHERE trens.id_trilho = trilhos.id_trilho AND trens.status_trem <> 'INATIVO') AS nomes_trens
        FROM trilhos $where ORDER BY id_trilho DESC"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trilhos = [];
    $erros[] = "Não foi possível carregar os trilhos. Tente novamente.";
}

$trilhoBloqueado = null;

foreach ($trilhos as $linha) {
    if (isset($_GET['bloqueado']) && (int) $linha['id_trilho'] === (int) $_GET['bloqueado']) {
        $trilhoBloqueado = $linha;
    }
}

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
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formTrilho">+ NOVO TRILHO</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
        <div class="caixa collapse <?php if (($erros && isset($_POST['cadastrar'])) || isset($_GET['novo'])) echo 'show'; ?>" id="formTrilho">
            <div class="caixa-topo">CADASTRAR NOVO TRILHO</div>
            <form method="POST" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeTrilho" class="form-label">NOME DO TRILHO</label>
                        <input type="text" name="nome" id="nomeTrilho" class="form-control" placeholder="Ex: Trilho Norte 01" value="<?php echo htmlspecialchars($novo['nome_trilho']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="kmTrilho" class="form-label">KM</label>
                        <input type="number" name="km" id="kmTrilho" class="form-control" step="0.01" min="0.01" placeholder="Ex: 12.5" value="<?php echo htmlspecialchars($novo['km_trilho']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="descricaoTrilho" class="form-label">DESCRIÇÃO</label>
                        <input type="text" name="descricao" id="descricaoTrilho" class="form-control" placeholder="Ex: Trecho entre os pátios A e B" value="<?php echo htmlspecialchars($novo['descricao_trilho']); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="statusTrilho" class="form-label">STATUS</label>
                        <select name="status" id="statusTrilho" class="form-select" required>
                            <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($novo['status_trilho'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dataTrilho" class="form-label">DATA DE CRIAÇÃO</label>
                        <input type="text" id="dataTrilho" class="form-control" value="<?php echo date('d/m/Y'); ?>" disabled>
                    </div>
                    <?php $endereco = $novo; include __DIR__ . '/campos-cep.php'; ?>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formTrilho">CANCELAR</button>
                </div>
            </form>
        </div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">
                TRILHOS CADASTRADOS
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
                        <tr><th>ID</th><th>NOME</th><th>DESCRIÇÃO</th><th>KM</th><th>LOCAL</th><th>TRENS</th><th>CRIADO EM</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trilhos as $trilho) { ?>
                            <tr>
                                <td><?php echo $trilho['id_trilho']; ?></td>
                                <td><?php echo htmlspecialchars($trilho['nome_trilho']); ?></td>
                                <td><?php echo htmlspecialchars($trilho['descricao_trilho']); ?></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($trilho['km_trilho']); ?> km</td>
                                <td><?php echo $trilho['cidade'] ? htmlspecialchars($trilho['cidade'] . '/' . $trilho['uf']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($trilho['nomes_trens'] ?? 'Sem trem'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($trilho['data_criacao'])); ?></td>
                                <td><span class="badge status status-<?php echo $trilho['status_trilho']; ?>"><?php echo htmlspecialchars($trilho['status_trilho']); ?></span></td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <a href="tela-editar-trilhos.php?id=<?php echo $trilho['id_trilho']; ?>" class="btn btn-sm btn-outline-primary">EDITAR</a>
                                        <?php if ($trilho['status_trilho'] != 'INATIVO' && ($trilho['total_trens'] > 0 || $trilho['total_sensores'] > 0)) { ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTrilhoAssociado" data-nome="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>" data-trens="<?php echo (int) $trilho['total_trens']; ?>" data-sensores="<?php echo (int) $trilho['total_sensores']; ?>">INATIVAR</button>
                                        <?php } elseif ($trilho['status_trilho'] != 'INATIVO') { ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja inativar este trilho?');">
                                                <input type="hidden" name="id_trilho" value="<?php echo $trilho['id_trilho']; ?>">
                                                <button type="submit" name="inativar" class="btn btn-sm btn-outline-danger">INATIVAR</button>
                                            </form>
                                        <?php } ?>
                                    <?php } else { echo '-'; } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!$trilhos) { ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum trilho cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div class="modal fade" id="modalTrilhoAssociado" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold">INATIVAÇÃO NÃO PERMITIDA</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>O trilho <span id="modalNomeTrilho"><?php echo htmlspecialchars($trilhoBloqueado['nome_trilho'] ?? ''); ?></span> possui associações.</strong><br>
                        Inative ou altere os trens e sensores ativos vinculados a ele antes de inativá-lo.
                    </div>
                    <table class="table table-sm mb-0 tabela">
                        <thead><tr><th>ASSOCIAÇÃO</th><th class="text-center">QUANTIDADE</th></tr></thead>
                        <tbody>
                            <tr><td>Trens</td><td class="text-center" id="modalTotalTrens"><?php echo (int) ($trilhoBloqueado['total_trens'] ?? 0); ?></td></tr>
                            <tr><td>Sensores</td><td class="text-center" id="modalTotalSensores"><?php echo (int) ($trilhoBloqueado['total_sensores'] ?? 0); ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">ENTENDI</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        modalTrilhoAssociado.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            if (botao) {
                modalNomeTrilho.textContent = botao.dataset.nome;
                modalTotalTrens.textContent = botao.dataset.trens;
                modalTotalSensores.textContent = botao.dataset.sensores;
            }
        });

        <?php if ($trilhoBloqueado) { ?>
            new bootstrap.Modal(modalTrilhoAssociado).show();
            history.replaceState(null, '', 'tela-cadastro-trilhos.php');
        <?php } ?>
    </script>

</body>

</html>
