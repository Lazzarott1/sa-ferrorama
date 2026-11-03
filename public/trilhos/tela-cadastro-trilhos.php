<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trilho.php';

$erros = [];
$novo = ['nome_trilho' => '', 'descricao_trilho' => '', 'km_trilho' => '', 'tempo_previsto_min' => '', 'id_trem' => 0, 'status_trilho' => 'ATIVO'];

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

    [$novo, $erros] = validarTrilho($conexao, true);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trilhos (nome_trilho, descricao_trilho, km_trilho, tempo_previsto_min, status_trilho) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssis", $novo['nome_trilho'], $novo['descricao_trilho'], $novo['km_trilho'], $novo['tempo_previsto_min'], $novo['status_trilho']);
            mysqli_stmt_execute($stmt);

            $id_trilho = mysqli_insert_id($conexao);

            $stmt = mysqli_prepare($conexao, "UPDATE trens SET id_trilho = ? WHERE id_trem = ?");
            mysqli_stmt_bind_param($stmt, "ii", $id_trilho, $novo['id_trem']);

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

    $trens = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trilhos = $trens = [];
    $erros[] = "Não foi possível carregar os trilhos. Tente novamente.";
}

$trilhoBloqueado = null;

foreach ($trilhos as $linha) {
    if (isset($_GET['bloqueado']) && (int) $linha['id_trilho'] === (int) $_GET['bloqueado']) {
        $trilhoBloqueado = $linha;
    }
}

$cores = [
    'ATIVO' => 'bg-success-subtle text-success-emphasis border-success-subtle',
    'MANUTENCAO' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle'
];

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

    <main class="container-fluid px-4 mt-4">

        <!-- TÍTULO -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>
                <h3 class="titulo-trilhos">Trilhos</h3>
                <p class="subtitulo-trilhos">Gerencie os trilhos cadastrados na malha ferroviária</p>
            </div>

            <?php if ($admin) { ?>
                <button type="button" class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2" data-bs-toggle="collapse" data-bs-target="#collapseCadastroTrilho" aria-expanded="false" aria-controls="collapseCadastroTrilho">
                    <span class="botaonovotrilho">+</span>
                    NOVO TRILHO
                </button>
            <?php } ?>

        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro) { ?>
                    <div><?php echo $erro; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- FORMULÁRIO -->

        <?php if ($admin) { ?>

        <div class="collapse mb-4 <?php if ($erros && isset($_POST['cadastrar'])) echo 'show'; ?>" id="collapseCadastroTrilho">

            <div class="card border-0">

                <div class="cardcadastro p-3">
                    <span class="spancadastrotrilho">CADASTRAR NOVO TRILHO</span>
                </div>

                <form method="POST" class="p-4 bg-white" style="border: 1px solid #BCCCDC; border-top: none;">

                    <div class="row g-4">

                        <div class="col-md-6">
                            <label for="nomeTrilho" class="form-label">NOME DO TRILHO</label>
                            <input type="text" name="nome" id="nomeTrilho" class="form-control" placeholder="Ex: Trilho Norte 01" value="<?php echo htmlspecialchars($novo['nome_trilho']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="kmTrilho" class="form-label">KM</label>
                            <input type="number" name="km" id="kmTrilho" class="form-control" step="0.01" min="0.01" placeholder="Ex: 12.5" value="<?php echo htmlspecialchars($novo['km_trilho']); ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label for="descricaoTrilho" class="form-label">DESCRIÇÃO</label>
                            <input type="text" name="descricao" id="descricaoTrilho" class="form-control" placeholder="Ex: Trecho entre os pátios A e B" value="<?php echo htmlspecialchars($novo['descricao_trilho']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="statusTrilho" class="form-label">STATUS</label>
                            <select name="status" id="statusTrilho" class="form-select" required>
                                <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                    <option value="<?php echo $valor; ?>" <?php if ($novo['status_trilho'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="tremTrilho" class="form-label">TREM VINCULADO</label>
                            <select name="id_trem" id="tremTrilho" class="form-select" required>
                                <option value="">Selecione o trem</option>
                                <?php foreach ($trens as $trem) { ?>
                                    <option value="<?php echo $trem['id_trem']; ?>" <?php if ($novo['id_trem'] == $trem['id_trem']) echo 'selected'; ?>><?php echo htmlspecialchars($trem['nome_trem']); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="tempoTrilho" class="form-label">TEMPO PREVISTO (MIN)</label>
                            <input type="number" name="tempo" id="tempoTrilho" class="form-control" min="1" placeholder="Ex: 15" value="<?php echo htmlspecialchars($novo['tempo_previsto_min']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="dataTrilho" class="form-label">DATA DE CRIAÇÃO</label>
                            <input type="text" id="dataTrilho" class="form-control" value="<?php echo date('d/m/Y'); ?>" disabled>
                        </div>

                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseCadastroTrilho">CANCELAR</button>
                    </div>

                </form>

            </div>

        </div>

        <?php } ?>

        <!-- TABELA -->

        <div class="d-flex flex-column align-items-center gap-5 w-100" style="padding-top: 60px; min-height: 100vh; background-color: #f8f9fa;">

            <div class="card shadow-sm border-1 p-0" style="width: 1200px; border-radius: 4px;">

                <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold d-flex justify-content-between align-items-center" style="font-size: 0.7rem;">

                    TRILHOS CADASTRADOS

                    <form method="GET">
                        <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'todos' => 'Todos'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($filtro == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </form>

                </div>

                <table class="table table-bordered table-hover mb-0 align-middle">

                    <thead class="table-light">
                        <tr class="text-secondary" style="font-size: 0.75rem;">
                            <?php foreach (['ID', 'NOME', 'DESCRIÇÃO', 'KM', 'TEMPO', 'TRENS', 'CRIADO EM', 'STATUS'] as $coluna) { ?>
                                <th class="fw-semibold"><?php echo $coluna; ?></th>
                            <?php } ?>
                            <th class="fw-semibold text-center">AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody style="font-size: 0.85rem;">

                        <?php foreach ($trilhos as $trilho) { ?>

                            <tr>
                                <td class="text-primary-emphasis fw-bold"><?php echo $trilho['id_trilho']; ?></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($trilho['nome_trilho']); ?></td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trilho['descricao_trilho']); ?></td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trilho['km_trilho']); ?> km</td>
                                <td class="text-body-tertiary"><?php echo (int) $trilho['tempo_previsto_min']; ?> min</td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trilho['nomes_trens'] ?? 'Sem trem'); ?></td>
                                <td class="text-body-tertiary"><?php echo date('d/m/Y', strtotime($trilho['data_criacao'])); ?></td>
                                <td>
                                    <span class="badge border rounded-1 <?php echo $cores[$trilho['status_trilho']] ?? 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'; ?>">
                                        <?php echo htmlspecialchars($trilho['status_trilho']); ?>
                                    </span>
                                </td>
                                <td class="text-center">

                                    <?php if ($admin) { ?>

                                        <a href="tela-editar-trilhos.php?id=<?php echo $trilho['id_trilho']; ?>" class="btn btn-sm btn-outline-primary me-1">EDITAR</a>

                                        <?php if ($trilho['status_trilho'] != 'INATIVO' && ($trilho['total_trens'] > 0 || $trilho['total_sensores'] > 0)) { ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTrilhoAssociado" data-nome="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>" data-trens="<?php echo (int) $trilho['total_trens']; ?>" data-sensores="<?php echo (int) $trilho['total_sensores']; ?>">INATIVAR</button>
                                        <?php } elseif ($trilho['status_trilho'] != 'INATIVO') { ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja inativar este trilho?');">
                                                <input type="hidden" name="id_trilho" value="<?php echo $trilho['id_trilho']; ?>">
                                                <button type="submit" name="inativar" class="btn btn-sm btn-outline-danger">INATIVAR</button>
                                            </form>
                                        <?php } ?>

                                    <?php } else { ?>
                                        -
                                    <?php } ?>

                                </td>
                            </tr>

                        <?php } ?>

                        <?php if (!$trilhos) { ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Nenhum trilho cadastrado.</td>
                            </tr>
                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <!-- AVISO -->

    <div class="modal fade" id="modalTrilhoAssociado" tabindex="-1" aria-labelledby="tituloModalTrilhoAssociado" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-1 border-0">

                <div class="modal-header rounded-top-1 text-white py-2" style="background-color: #1b3f53; border-bottom: 3px solid #daa301;">
                    <h6 class="modal-title fw-bold mb-0" id="tituloModalTrilhoAssociado">ATENÇÃO: INATIVAÇÃO NÃO PERMITIDA</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <div class="alert alert-warning rounded-1 mb-3 py-2" role="alert" style="border-left: 4px solid #daa301;">
                        <strong>O trilho <span id="modalNomeTrilho"><?php echo htmlspecialchars($trilhoBloqueado['nome_trilho'] ?? ''); ?></span> possui associações.</strong>
                        <br>
                        <span style="font-size: 0.9rem;">Inative ou altere os trens e sensores ativos vinculados a ele antes de inativá-lo.</span>
                    </div>

                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr class="text-secondary">
                                <th class="fw-semibold">ASSOCIAÇÃO</th>
                                <th class="fw-semibold text-center" style="width: 110px;">QUANTIDADE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Trens</td>
                                <td class="text-center fw-bold" id="modalTotalTrens"><?php echo (int) ($trilhoBloqueado['total_trens'] ?? 0); ?></td>
                            </tr>
                            <tr>
                                <td>Sensores</td>
                                <td class="text-center fw-bold" id="modalTotalSensores"><?php echo (int) ($trilhoBloqueado['total_sensores'] ?? 0); ?></td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm px-3 fw-bold" style="background-color: #daa301; color: #1b3f53;" data-bs-dismiss="modal">ENTENDI</button>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const modalTrilhoAssociado = document.getElementById('modalTrilhoAssociado');

        modalTrilhoAssociado.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            if (botao) {
                document.getElementById('modalNomeTrilho').textContent = botao.dataset.nome;
                document.getElementById('modalTotalTrens').textContent = botao.dataset.trens;
                document.getElementById('modalTotalSensores').textContent = botao.dataset.sensores;
            }
        });

        <?php if ($trilhoBloqueado) { ?>
            new bootstrap.Modal(modalTrilhoAssociado).show();
            history.replaceState(null, '', 'tela-cadastro-trilhos.php');
        <?php } ?>
    </script>

</body>

</html>
