<?php


require_once '../infra/conexao.php';


$msgErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_relatorio'])) {
    $titulo      = trim($_POST['titulo'] ?? '');
    $dataInicio  = $_POST['data_inicio'] ?? '';
    $dataFim     = $_POST['data_fim'] ?? '';
    $tremId      = !empty($_POST['trem_id'])   ? (int) $_POST['trem_id']   : null;
    $trilhoId    = !empty($_POST['trilho_id']) ? (int) $_POST['trilho_id'] : null;
    $tipoDado    = trim($_POST['tipo_dado'] ?? '');
    $tipo        = $tipoDado !== '' ? $tipoDado : 'Geral';

    if ($titulo === '' || $dataInicio === '' || $dataFim === '') {
        $msgErro = 'Preencha ao menos Título, Data Início e Data Fim.';
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO relatorios (titulo, tipo, data_inicio, data_fim, trem_id, trilho_id, tipo_dado, status, gerado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'PRONTO', NOW())"
        );
        $stmt->bind_param('ssssiis', $titulo, $tipo, $dataInicio, $dataFim, $tremId, $trilhoId, $tipoDado);
        $stmt->execute();
        $stmt->close();

        header('Location: relatorios.php');
        exit;
    }
}


$filtroTipo = $_GET['tipo'] ?? 'Todos';

if ($filtroTipo !== 'Todos' && $filtroTipo !== '') {
    $stmt = $conexao->prepare("SELECT * FROM relatorios WHERE tipo = ? ORDER BY gerado_em DESC");
    $stmt->bind_param('s', $filtroTipo);
    $stmt->execute();
    $resultRelatorios = $stmt->get_result();
} else {
    $resultRelatorios = $conexao->query("SELECT * FROM relatorios ORDER BY gerado_em DESC");
}
$totalRelatorios = $resultRelatorios ? $resultRelatorios->num_rows : 0;


$tiposDisponiveis = [];
$resTipos = $conexao->query("SELECT DISTINCT tipo FROM relatorios ORDER BY tipo");
if ($resTipos) {
    while ($t = $resTipos->fetch_assoc()) {
        $tiposDisponiveis[] = $t['tipo'];
    }
}


$trens = $conexao->query("SELECT id_trem, nome_trem FROM trens ORDER BY nome_trem");
$trilhos = $conexao->query("SELECT id_trilho, nome_trilho FROM trilhos ORDER BY nome_trilho");

/* Helper: classes Bootstrap (badge "subtle") conforme status */
function badgeStatus(string $status): string {
    switch (strtoupper($status)) {
        case 'PRONTO':
            return 'badge border rounded-1 bg-success-subtle text-success-emphasis border-success-subtle';
        case 'PROCESSANDO':
            return 'badge border rounded-1 bg-warning-subtle text-warning-emphasis border-warning-subtle';
        case 'ERRO':
            return 'badge border rounded-1 bg-danger-subtle text-danger-emphasis border-danger-subtle';
        default:
            return 'badge border rounded-1 bg-secondary-subtle text-secondary-emphasis border-secondary-subtle';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatórios · FerroMonitor</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/img/style/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="tela-geral-home.php">
            <span class="bg-warning rounded d-inline-flex align-items-center justify-content-center me-2"
                  style="width:34px;height:34px;">
                <i class="bi bi-train-front-fill text-dark"></i>
            </span>
            <span class="lh-sm">
                <span class="fw-bold d-block">FerroMonitor</span>
                <small class="text-white-50" style="font-size:.65rem;letter-spacing:.05em;">SISTEMA FERROVIÁRIO</small>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#fmNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="fmNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="tela-geral-home.php"><i class="bi bi-grid-1x2-fill me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link" href="sensores/tela-cadastro-sensores.php"><i class="bi bi-broadcast me-1"></i>Sensores</a></li>
                <li class="nav-item"><a class="nav-link" href="trens/tela-trens.php"><i class="bi bi-train-front me-1"></i>Trens</a></li>
                <li class="nav-item"><a class="nav-link" href="trilhos/tela-cadastro-trilhos.php"><i class="bi bi-signpost-split me-1"></i>Trilhos</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoramento/tela-monitoramento.php"><i class="bi bi-display me-1"></i>Monitoramento</a></li>
                <li class="nav-item">
                    <a class="nav-link active bg-warning text-dark fw-semibold rounded px-3" href="relatorios.php">
                        <i class="bi bi-file-earmark-text-fill me-1"></i>Relatórios
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="usuarios/tela-cadastro-user.php"><i class="bi bi-people-fill me-1"></i>Usuários</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-secondary px-3 py-2">
                    <i class="bi bi-person-circle me-1"></i>Administrador
                </span>
                <a href="tela-login.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>Sair
                </a>
            </div>
        </div>
    </div>
</nav>

<nav aria-label="breadcrumb" class="bg-white border-bottom">
    <div class="container-fluid px-4 py-2">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item fw-semibold text-primary-emphasis">RELATÓRIOS</li>
            <li class="breadcrumb-item text-muted">FerroMonitor Sistema Ferroviário</li>
        </ol>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-primary-emphasis fw-bold mb-1">Relatórios</h3>
            <p class="text-muted mb-0">Gere e visualize análises da operação ferroviária</p>
        </div>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                data-bs-toggle="collapse" data-bs-target="#collapseGerarRelatorio"
                aria-expanded="false" aria-controls="collapseGerarRelatorio">
            <i class="bi bi-plus-lg"></i>NOVO RELATÓRIO
        </button>
    </div>

    <?php if ($msgErro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($msgErro) ?></div>
    <?php endif; ?>

    <div class="collapse mb-4" id="collapseGerarRelatorio">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary-subtle text-primary-emphasis fw-bold" style="font-size:.85rem;">
                <i class="bi bi-file-earmark-plus me-1"></i>GERAR NOVO RELATÓRIO
            </div>
            <div class="card-body">
                <form method="post" action="relatorios.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">TÍTULO <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control" placeholder="Ex: Relatório Semanal" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">DATA INÍCIO <span class="text-danger">*</span></label>
                            <input type="date" name="data_inicio" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">DATA FIM <span class="text-danger">*</span></label>
                            <input type="date" name="data_fim" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">TREM (OPCIONAL)</label>
                            <select name="trem_id" class="form-select">
                                <option value="">Todos os trens</option>
                                <?php if ($trens): while ($tr = $trens->fetch_assoc()): ?>
                                    <option value="<?= (int) $tr['id_trem'] ?>"><?= htmlspecialchars($tr['nome_trem']) ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">TRILHO (OPCIONAL)</label>
                            <select name="trilho_id" class="form-select">
                                <option value="">Todos os trilhos</option>
                                <?php if ($trilhos): while ($tl = $trilhos->fetch_assoc()): ?>
                                    <option value="<?= (int) $tl['id_trilho'] ?>"><?= htmlspecialchars($tl['nome_trilho']) ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">TIPO DE DADO</label>
                            <select name="tipo_dado" class="form-select">
                                <option value="">Todos os dados</option>
                                <option value="Velocidade">Velocidade</option>
                                <option value="Temperatura">Temperatura</option>
                                <option value="Localização">Localização</option>
                                <option value="Consumo de Energia">Consumo de Energia</option>
                                <option value="Falhas">Falhas</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary"
                                data-bs-toggle="collapse" data-bs-target="#collapseGerarRelatorio">
                            CANCELAR
                        </button>
                        <button type="submit" name="gerar_relatorio" class="btn btn-primary">GERAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filtro por tipo -->
    <form method="get" action="relatorios.php" class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <label class="form-label fw-semibold mb-0">FILTRAR POR TIPO:</label>
        <select name="tipo" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="Todos" <?= $filtroTipo === 'Todos' ? 'selected' : '' ?>>Todos</option>
            <?php foreach ($tiposDisponiveis as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $filtroTipo === $t ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted small"><?= $totalRelatorios ?> relatório(s)</span>
    </form>


    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary-subtle text-primary-emphasis fw-bold" style="font-size:.85rem;">
            <i class="bi bi-file-earmark-text me-1"></i>RELATÓRIOS GERADOS
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-secondary" style="font-size:.75rem;">
                        <th class="ps-3 fw-semibold">TÍTULO</th>
                        <th class="fw-semibold">TIPO</th>
                        <th class="fw-semibold">PERÍODO</th>
                        <th class="fw-semibold">STATUS</th>
                        <th class="fw-semibold">GERADO EM</th>
                        <th class="fw-semibold text-center">AÇÃO</th>
                    </tr>
                </thead>
                <tbody style="font-size:.9rem;">
                <?php if ($resultRelatorios && $totalRelatorios > 0): ?>
                    <?php while ($rel = $resultRelatorios->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars($rel['titulo']) ?></td>
                            <td><a href="relatorios.php?tipo=<?= urlencode($rel['tipo']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($rel['tipo']) ?>
                                </a></td>
                            <td>
                                <?= date('d/m/Y', strtotime($rel['data_inicio'])) ?>
                                &ndash;
                                <?= date('d/m/Y', strtotime($rel['data_fim'])) ?>
                            </td>
                            <td><span class="<?= badgeStatus($rel['status']) ?>"><?= htmlspecialchars($rel['status']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($rel['gerado_em'])) ?></td>
                            <td class="text-center">
                                <a href="relatorio_detalhe.php?id=<?= (int) $rel['id'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="text-center text-muted small mt-4 pb-3">
        FerroMonitor &copy; 2026 &ndash; Sistema de Monitoramento Ferroviário &nbsp;·&nbsp; v1.0.0
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
