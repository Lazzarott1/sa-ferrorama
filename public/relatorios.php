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

/* Helper: classe da badge conforme status */
function badgeStatus(string $status): string {
    switch (strtoupper($status)) {
        case 'PRONTO':       return 'badge-status badge-pronto';
        case 'PROCESSANDO':  return 'badge-status badge-processando';
        case 'ERRO':         return 'badge-status badge-erro';
        default:             return 'badge-status badge-processando';
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

<style>
    :root{
        --fm-navy: #1c3d5a;
        --fm-navy-dark: #16324a;
        --fm-gold: #f0ab00;
        --fm-gold-dark: #d99700;
        --fm-bg: #eef2f6;
        --fm-border: #dbe3ea;
    }
    body{ background-color: var(--fm-bg); }

    .fm-navbar{ background-color: var(--fm-navy); }
    .fm-brand{ line-height: 1.1; }
    .fm-brand .fm-brand-title{ font-weight: 700; font-size: 1.05rem; color:#fff; }
    .fm-brand .fm-brand-sub{ font-size: .65rem; letter-spacing: .05em; color:#b9c7d4; display:block; }
    .fm-navbar .nav-link{
        color: #cfdae4; font-size:.9rem; padding: .5rem .9rem; border-radius:.375rem;
    }
    .fm-navbar .nav-link i{ margin-right:.35rem; }
    .fm-navbar .nav-link:hover{ color:#fff; }
    .fm-navbar .nav-link.active{
        background-color: var(--fm-gold); color:#1a1a1a; font-weight:600;
    }
    .fm-user-badge{
        background:#28516f; color:#fff; border-radius:2rem; padding:.35rem .8rem; font-size:.85rem;
    }
    .btn-sair{
        background: transparent; border:1px solid #4c6b83; color:#dbe6ee; font-size:.85rem;
    }
    .btn-sair:hover{ background:#28516f; color:#fff; }

    /* ---------- Breadcrumb ---------- */
    .fm-breadcrumb{ background:#fff; border-bottom:1px solid var(--fm-border); font-size:.8rem; }
    .fm-breadcrumb .active-crumb{ color: var(--fm-navy); font-weight:600; }

    /* ---------- Page header ---------- */
    .fm-page-title{ color: var(--fm-navy); font-weight:700; }
    .fm-page-sub{ color:#6c7a89; font-size:.9rem; }
    .btn-fm-primary{
        background-color: var(--fm-navy); border-color: var(--fm-navy); color:#fff; font-weight:600;
    }
    .btn-fm-primary:hover{ background-color: var(--fm-navy-dark); border-color: var(--fm-navy-dark); color:#fff; }

    .fm-card{
        background:#fff; border:1px solid var(--fm-border); border-radius:.5rem;
    }
    .fm-card-header{
        background:#f4f7fa; border-bottom:1px solid var(--fm-border);
        font-weight:700; color: var(--fm-navy); font-size:.9rem;
        border-radius:.5rem .5rem 0 0;
    }

    .table-fm thead th{
        font-size:.72rem; text-transform:uppercase; letter-spacing:.03em;
        color:#7c8a99; background:#f8fafc; border-bottom:1px solid var(--fm-border);
        font-weight:700;
    }
    .table-fm td{ vertical-align: middle; font-size:.9rem; color:#2c3e50; }
    .table-fm tbody tr:hover{ background:#f7fafd; }

    .badge-status{
        font-size:.72rem; font-weight:600; padding:.3rem .6rem; border-radius:.35rem;
        border:1px solid transparent;
    }
    .badge-pronto{ background:#e9f8ee; color:#1c8a43; border-color:#bfe8cd; }
    .badge-processando{ background:#fff6e0; color:#a5700a; border-color:#f3dfa5; }
    .badge-erro{ background:#fdeaea; color:#c0392b; border-color:#f4bcb6; }

    .btn-icon-view{
        color: var(--fm-navy); background:transparent; border:none;
    }
    .btn-icon-view:hover{ color: var(--fm-gold-dark); }

    #formGerarRelatorio{ display:none; }
    #formGerarRelatorio.show{ display:block; }
    .form-label-fm{ font-size:.8rem; font-weight:600; color:#3b4b5a; }
    .form-label-fm .text-req{ color:#c0392b; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fm-navbar py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center fm-brand" href="tela-geral-home.php">
            <span class="bg-warning bg-opacity-100 rounded d-inline-flex align-items-center justify-content-center me-2"
                  style="width:34px;height:34px;background:var(--fm-gold)!important;">
                <i class="bi bi-train-front-fill text-dark"></i>
            </span>
            <span>
                <span class="fm-brand-title d-block">FerroMonitor</span>
                <span class="fm-brand-sub">SISTEMA FERROVIÁRIO</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#fmNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="fmNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="tela-geral-home.php"><i class="bi bi-grid-1x2-fill"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link" href="sensores/tela-cadastro-sensores.php"><i class="bi bi-broadcast"></i>Sensores</a></li>
                <li class="nav-item"><a class="nav-link" href="trens/tela-trens.php"><i class="bi bi-train-front"></i>Trens</a></li>
                <li class="nav-item"><a class="nav-link" href="trilhos/tela-cadastro-trilhos.php"><i class="bi bi-signpost-split"></i>Trilhos</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoramento/tela-monitoramento.php"><i class="bi bi-display"></i>Monitoramento</a></li>
                <li class="nav-item"><a class="nav-link active" href="relatorios.php"><i class="bi bi-file-earmark-text-fill"></i>Relatórios</a></li>
                <li class="nav-item"><a class="nav-link" href="usuarios/tela-cadastro-user.php"><i class="bi bi-people-fill"></i>Usuários</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="fm-user-badge"><i class="bi bi-person-circle me-1"></i>Administrador</span>
                <a href="tela-login.php" class="btn btn-sm btn-sair"><i class="bi bi-box-arrow-right me-1"></i>Sair</a>
            </div>
        </div>
    </div>
</nav>

<div class="fm-breadcrumb py-2">
    <div class="container-fluid px-4">
        <span class="active-crumb">RELATÓRIOS</span>
        <span class="text-muted mx-1">&raquo;</span>
        <span class="text-muted">FerroMonitor Sistema Ferroviário</span>
    </div>
</div>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fm-page-title mb-1">Relatórios</h3>
            <p class="fm-page-sub mb-0">Gere e visualize análises da operação ferroviária</p>
        </div>
        <button type="button" class="btn btn-fm-primary" id="btnToggleForm">
            <i class="bi bi-plus-lg me-1"></i>NOVO RELATÓRIO
        </button>
    </div>

    <?php if ($msgErro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($msgErro) ?></div>
    <?php endif; ?>

    <div class="fm-card mb-4" id="formGerarRelatorio">
        <div class="fm-card-header px-3 py-2">
            <i class="bi bi-file-earmark-plus me-1"></i>GERAR NOVO RELATÓRIO
        </div>
        <div class="p-3">
            <form method="post" action="relatorios.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-fm">TÍTULO <span class="text-req">*</span></label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ex: Relatório Semanal" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-fm">DATA INÍCIO <span class="text-req">*</span></label>
                        <input type="date" name="data_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-fm">DATA FIM <span class="text-req">*</span></label>
                        <input type="date" name="data_fim" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-fm">TREM (OPCIONAL)</label>
                        <select name="trem_id" class="form-select">
                            <option value="">Todos os trens</option>
                            <?php if ($trens): while ($tr = $trens->fetch_assoc()): ?>
                                <option value="<?= (int) $tr['id_trem'] ?>"><?= htmlspecialchars($tr['nome_trem']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-fm">TRILHO (OPCIONAL)</label>
                        <select name="trilho_id" class="form-select">
                            <option value="">Todos os trilhos</option>
                            <?php if ($trilhos): while ($tl = $trilhos->fetch_assoc()): ?>
                                <option value="<?= (int) $tl['id_trilho'] ?>"><?= htmlspecialchars($tl['nome_trilho']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-fm">TIPO DE DADO</label>
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
                    <button type="button" class="btn btn-outline-secondary" id="btnCancelarForm">CANCELAR</button>
                    <button type="submit" name="gerar_relatorio" class="btn btn-fm-primary">GERAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtro por tipo -->
    <form method="get" action="relatorios.php" class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <label class="form-label-fm mb-0">FILTRAR POR TIPO:</label>
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


    <div class="fm-card">
        <div class="fm-card-header px-3 py-2">
            <i class="bi bi-file-earmark-text me-1"></i>RELATÓRIOS GERADOS
        </div>
        <div class="table-responsive">
            <table class="table table-fm mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">TÍTULO</th>
                        <th>TIPO</th>
                        <th>PERÍODO</th>
                        <th>STATUS</th>
                        <th>GERADO EM</th>
                        <th class="text-center">AÇÃO</th>
                    </tr>
                </thead>
                <tbody>
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
                                <a href="relatorio_detalhe.php?id=<?= (int) $rel['id'] ?>" class="btn btn-sm btn-icon-view" title="Visualizar">
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
<script>

    const btnToggle = document.getElementById('btnToggleForm');
    const btnCancelar = document.getElementById('btnCancelarForm');
    const formGerar = document.getElementById('formGerarRelatorio');

    btnToggle.addEventListener('click', () => {
        formGerar.classList.toggle('show');
    });
    btnCancelar.addEventListener('click', () => {
        formGerar.classList.remove('show');
    });
</script>
</body>
</html>