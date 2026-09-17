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
<title><?= htmlspecialchars($relatorio['titulo']) ?> · FerroMonitor</title>

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

    .fm-breadcrumb{ background:#fff; border-bottom:1px solid var(--fm-border); font-size:.8rem; }
    .fm-breadcrumb .active-crumb{ color: var(--fm-navy); font-weight:600; }

    .fm-page-title{ color: var(--fm-navy); font-weight:700; }
    .fm-page-sub{ color:#6c7a89; font-size:.9rem; }
    .btn-fm-primary{
        background-color: var(--fm-navy); border-color: var(--fm-navy); color:#fff; font-weight:600;
    }
    .btn-fm-primary:hover{ background-color: var(--fm-navy-dark); border-color: var(--fm-navy-dark); color:#fff; }

    .fm-card{ background:#fff; border:1px solid var(--fm-border); border-radius:.5rem; }
    .fm-card-header{
        background:#f4f7fa; border-bottom:1px solid var(--fm-border);
        font-weight:700; color: var(--fm-navy); font-size:.9rem;
        border-radius:.5rem .5rem 0 0;
    }

    .fm-field-label{ font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:#7c8a99; font-weight:700; }
    .fm-field-value{ font-size:.95rem; color:#2c3e50; font-weight:600; }

    .badge-status{
        font-size:.72rem; font-weight:600; padding:.3rem .6rem; border-radius:.35rem;
        border:1px solid transparent;
    }
    .badge-pronto{ background:#e9f8ee; color:#1c8a43; border-color:#bfe8cd; }
    .badge-processando{ background:#fff6e0; color:#a5700a; border-color:#f3dfa5; }
    .badge-erro{ background:#fdeaea; color:#c0392b; border-color:#f4bcb6; }
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
        <a href="relatorios.php" class="text-decoration-none active-crumb">RELATÓRIOS</a>
        <span class="text-muted mx-1">&raquo;</span>
        <span class="text-muted"><?= htmlspecialchars($relatorio['titulo']) ?></span>
    </div>
</div>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fm-page-title mb-1"><?= htmlspecialchars($relatorio['titulo']) ?></h3>
            <p class="fm-page-sub mb-0">Detalhes do relatório gerado</p>
        </div>
        <a href="relatorios.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>VOLTAR
        </a>
    </div>

    <div class="fm-card mb-4">
        <div class="fm-card-header px-3 py-2">
            <i class="bi bi-info-circle me-1"></i>INFORMAÇÕES DO RELATÓRIO
        </div>
        <div class="p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Tipo</div>
                    <div class="fm-field-value"><?= htmlspecialchars($relatorio['tipo']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Status</div>
                    <div><span class="<?= badgeStatus($relatorio['status']) ?>"><?= htmlspecialchars($relatorio['status']) ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Período</div>
                    <div class="fm-field-value">
                        <?= date('d/m/Y', strtotime($relatorio['data_inicio'])) ?>
                        &ndash;
                        <?= date('d/m/Y', strtotime($relatorio['data_fim'])) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Gerado em</div>
                    <div class="fm-field-value"><?= date('d/m/Y H:i', strtotime($relatorio['gerado_em'])) ?></div>
                </div>

                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Trem</div>
                    <div class="fm-field-value"><?= htmlspecialchars($relatorio['nome_trem'] ?? 'Todos os trens') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Trilho</div>
                    <div class="fm-field-value"><?= htmlspecialchars($relatorio['nome_trilho'] ?? 'Todos os trilhos') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fm-field-label mb-1">Tipo de dado</div>
                    <div class="fm-field-value"><?= htmlspecialchars($relatorio['tipo_dado'] ?: 'Todos os dados') ?></div>
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
