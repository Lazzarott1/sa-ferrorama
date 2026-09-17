<?php

require_once '../infra/conexao.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conexao->prepare(
    "SELECT r.*, t.nome_trem, tl.nome_trilho
     FROM relatorios r
     LEFT JOIN trens t   ON t.id_trem   = r.trem_id
     LEFT JOIN trilhos tl ON tl.id_trilho = r.trilho_id
     WHERE r.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$relatorio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$relatorio) {
    header('Location: relatorios.php');
    exit;
}

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
<title><?= htmlspecialchars($relatorio['titulo']) ?> · FerroMonitor</title>

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
            <li class="breadcrumb-item"><a href="relatorios.php" class="text-decoration-none fw-semibold">RELATÓRIOS</a></li>
            <li class="breadcrumb-item text-muted active" aria-current="page"><?= htmlspecialchars($relatorio['titulo']) ?></li>
        </ol>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-primary-emphasis fw-bold mb-1"><?= htmlspecialchars($relatorio['titulo']) ?></h3>
            <p class="text-muted mb-0">Detalhes do relatório gerado</p>
        </div>
        <a href="relatorios.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>VOLTAR
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary-subtle text-primary-emphasis fw-bold" style="font-size:.85rem;">
            <i class="bi bi-info-circle me-1"></i>INFORMAÇÕES DO RELATÓRIO
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Tipo</div>
                    <div class="fw-semibold"><?= htmlspecialchars($relatorio['tipo']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Status</div>
                    <div><span class="<?= badgeStatus($relatorio['status']) ?>"><?= htmlspecialchars($relatorio['status']) ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Período</div>
                    <div class="fw-semibold">
                        <?= date('d/m/Y', strtotime($relatorio['data_inicio'])) ?>
                        &ndash;
                        <?= date('d/m/Y', strtotime($relatorio['data_fim'])) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Gerado em</div>
                    <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($relatorio['gerado_em'])) ?></div>
                </div>

                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Trem</div>
                    <div class="fw-semibold"><?= htmlspecialchars($relatorio['nome_trem'] ?? 'Todos os trens') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Trilho</div>
                    <div class="fw-semibold"><?= htmlspecialchars($relatorio['nome_trilho'] ?? 'Todos os trilhos') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase fw-semibold small mb-1">Tipo de dado</div>
                    <div class="fw-semibold"><?= htmlspecialchars($relatorio['tipo_dado'] ?: 'Todos os dados') ?></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted small mt-4 pb-3">
        FerroMonitor &copy; 2026 &ndash; Sistema de Monitoramento Ferroviário &nbsp;·&nbsp; v1.0.0
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
