<?php

include __DIR__ . '/../../infra/verifica-login.php';

function buscar($conexao, $sql, $params = []) {
    $stmt = $conexao->prepare($sql);
    if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function data($d, $formato = 'd/m/Y') {
    return date($formato, strtotime($d));
}

$msgErro = '';

if (isset($_POST['gerar_relatorio'])) {
    $titulo   = trim($_POST['titulo'] ?? '');
    $inicio   = $_POST['data_inicio'] ?? '';
    $fim      = $_POST['data_fim'] ?? '';
    $tipoDado = trim($_POST['tipo_dado'] ?? '');
    $tipo     = $tipoDado ?: 'Geral';
    $idTrem   = ($_POST['id_trem'] ?? '') ?: null;
    $idTrilho = ($_POST['id_trilho'] ?? '') ?: null;

    if ($titulo === '' || $inicio === '' || $fim === '') {
        $msgErro = 'Preencha ao menos Título, Data Início e Data Fim.';
    } elseif ($fim < $inicio) {
        $msgErro = 'A Data Fim não pode ser antes da Data Início.';
    } else {
        $stmt = $conexao->prepare("INSERT INTO relatorios (titulo, tipo, data_inicio, data_fim, id_trem, id_trilho, tipo_dado, status_relatorio) VALUES (?, ?, ?, ?, ?, ?, ?, 'PRONTO')");
        $stmt->bind_param('ssssiis', $titulo, $tipo, $inicio, $fim, $idTrem, $idTrilho, $tipoDado);
        $stmt->execute();
        header('Location: relatorios.php?ver=' . $stmt->insert_id);
        exit;
    }
}

$conexao->query("UPDATE relatorios SET status_relatorio = 'PRONTO' WHERE status_relatorio = 'PROCESSANDO'");

$rel = buscar($conexao, "SELECT r.*, t.nome_trem, tr.nome_trilho FROM relatorios r LEFT JOIN trens t ON t.id_trem = r.id_trem LEFT JOIN trilhos tr ON tr.id_trilho = r.id_trilho WHERE r.id_relatorio = ?", [(int) ($_GET['ver'] ?? 0)])[0] ?? null;

if ($rel) {
    $trem = $rel['id_trem'];
    $trilho = $rel['id_trilho'];
    $dado = $rel['tipo_dado'] ?? '';
    $falhas = $dado === 'Falhas';

    $sql = "SELECT t.nome_trem AS Nome, t.modelo_trem AS Modelo, t.capacidade_trem AS Capacidade, tr.nome_trilho AS Trilho, t.status_trem AS Status FROM trens t LEFT JOIN trilhos tr ON tr.id_trilho = t.id_trilho WHERE t.status_trem <> 'INATIVO'";
    $p = [];
    if ($trem)   { $sql .= " AND t.id_trem = ?";   $p[] = $trem; }
    if ($trilho) { $sql .= " AND t.id_trilho = ?"; $p[] = $trilho; }
    if ($falhas) $sql .= " AND t.status_trem = 'MANUTENCAO'";
    $secoes['Trens'] = buscar($conexao, "$sql ORDER BY Nome", $p);

    $sql = "SELECT nome_trilho AS Nome, descricao_trilho AS Descrição, km_trilho AS KM, status_trilho AS Status FROM trilhos WHERE status_trilho <> 'INATIVO'";
    $p = [];
    if ($trilho) { $sql .= " AND id_trilho = ?"; $p[] = $trilho; }
    if ($trem)   { $sql .= " AND id_trilho = (SELECT id_trilho FROM trens WHERE id_trem = ?)"; $p[] = $trem; }
    if ($falhas) $sql .= " AND status_trilho = 'MANUTENCAO'";
    $secoes['Trilhos'] = buscar($conexao, "$sql ORDER BY Nome", $p);

    $sql = "SELECT s.nome_sensor AS Nome, s.categoria_sensor AS Categoria, s.tipo_sensor AS Tipo, COALESCE(t.nome_trem, tr.nome_trilho) AS Local, s.status_sensor AS Status FROM sensores s LEFT JOIN trens t ON t.id_trem = s.id_trem LEFT JOIN trilhos tr ON tr.id_trilho = s.id_trilho WHERE s.status_sensor <> 'INATIVO'";
    $p = [];
    if ($trem)   { $sql .= " AND s.id_trem = ?"; $p[] = $trem; }
    if ($trilho) { $sql .= " AND (s.id_trilho = ? OR t.id_trilho = ?)"; array_push($p, $trilho, $trilho); }
    if ($falhas) $sql .= " AND s.status_sensor = 'ALERTA'";
    elseif ($dado !== '') { $sql .= " AND s.tipo_sensor = ?"; $p[] = $dado; }
    $secoes['Sensores'] = buscar($conexao, "$sql ORDER BY Nome", $p);

    $totais = array_map('count', $secoes) + ['Alertas' => count(array_filter($secoes['Sensores'], fn($s) => $s['Status'] === 'ALERTA'))];
}

$filtroTipo = $_GET['tipo'] ?? 'Todos';
$relatorios = $filtroTipo !== 'Todos' && $filtroTipo !== ''
    ? buscar($conexao, "SELECT * FROM relatorios WHERE tipo = ? ORDER BY gerado_em DESC", [$filtroTipo])
    : buscar($conexao, "SELECT * FROM relatorios ORDER BY gerado_em DESC");
$tiposDisponiveis = array_column(buscar($conexao, "SELECT DISTINCT tipo FROM relatorios ORDER BY tipo"), 'tipo');
$trens = buscar($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem");
$trilhos = buscar($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho");
$badges = ['PRONTO' => 'badge-pronto', 'ERRO' => 'badge-erro'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        .fm-page-title { color: #1b3f53; font-weight: 700; }
        .fm-page-sub { color: #6c7a89; font-size: .9rem; }
        .btn-fm-primary { background-color: #1b3f53; border-color: #1b3f53; color: #fff; font-weight: 600; }
        .btn-fm-primary:hover { background-color: #142f3f; border-color: #142f3f; color: #fff; }
        .fm-card { background: #fff; border: 1px solid #dbe3ea; border-radius: .5rem; }
        .fm-card-header { background: #f4f7fa; border-bottom: 1px solid #dbe3ea; font-weight: 700; color: #1b3f53; font-size: .9rem; border-radius: .5rem .5rem 0 0; }
        .table-fm thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #7c8a99; background: #f8fafc; border-bottom: 1px solid #dbe3ea; font-weight: 700; }
        .table-fm td { vertical-align: middle; font-size: .9rem; color: #2c3e50; }
        .badge-status { font-size: .72rem; font-weight: 600; padding: .3rem .6rem; border-radius: .35rem; border: 1px solid #f3dfa5; background: #fff6e0; color: #a5700a; }
        .badge-pronto { background: #e9f8ee; color: #1c8a43; border-color: #bfe8cd; }
        .badge-erro { background: #fdeaea; color: #c0392b; border-color: #f4bcb6; }
        #formGerarRelatorio:not(.show) { display: none; }
        .form-label-fm { font-size: .8rem; font-weight: 600; color: #3b4b5a; }
        .text-req { color: #c0392b; }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="container-fluid px-4 py-4">

        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h3 class="fm-page-title mb-1">Relatórios</h3>
                <p class="fm-page-sub mb-0">Gere e visualize análises da operação ferroviária</p>
            </div>
            <button type="button" class="btn btn-fm-primary" onclick="formGerarRelatorio.classList.toggle('show')">
                <i class="bi bi-plus-lg me-1"></i>NOVO RELATÓRIO
            </button>
        </div>

        <?php if ($msgErro): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($msgErro) ?></div>
        <?php endif; ?>

        <div class="fm-card mb-4 <?= $msgErro ? 'show' : '' ?>" id="formGerarRelatorio">
            <div class="fm-card-header px-3 py-2"><i class="bi bi-file-earmark-plus me-1"></i>GERAR NOVO RELATÓRIO</div>
            <form method="post" class="p-3">
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
                        <select name="id_trem" class="form-select">
                            <option value="">Todos os trens</option>
                            <?php foreach ($trens as $t): ?>
                                <option value="<?= $t['id_trem'] ?>"><?= htmlspecialchars($t['nome_trem']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-fm">TRILHO (OPCIONAL)</label>
                        <select name="id_trilho" class="form-select">
                            <option value="">Todos os trilhos</option>
                            <?php foreach ($trilhos as $t): ?>
                                <option value="<?= $t['id_trilho'] ?>"><?= htmlspecialchars($t['nome_trilho']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-fm">TIPO DE DADO</label>
                        <select name="tipo_dado" class="form-select">
                            <option value="">Todos os dados</option>
                            <?php foreach (['Velocidade', 'Temperatura', 'Localização', 'Consumo de Energia', 'Falhas'] as $t): ?>
                                <option><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="formGerarRelatorio.classList.remove('show')">CANCELAR</button>
                    <button type="submit" name="gerar_relatorio" class="btn btn-fm-primary">GERAR</button>
                </div>
            </form>
        </div>

        <?php if ($rel): ?>
            <div class="modal fade" id="modalRelatorio" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header fm-card-header">
                            <span><i class="bi bi-file-earmark-bar-graph me-1"></i><?= htmlspecialchars($rel['titulo']) ?></span>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="fm-page-sub">
                                Período: <?= data($rel['data_inicio']) ?> a <?= data($rel['data_fim']) ?>
                                &middot; Trem: <?= htmlspecialchars($rel['nome_trem'] ?? 'Todos') ?>
                                &middot; Trilho: <?= htmlspecialchars($rel['nome_trilho'] ?? 'Todos') ?>
                                &middot; Dados: <?= htmlspecialchars($dado ?: 'Todos') ?>
                                &middot; Gerado em <?= data($rel['gerado_em'], 'd/m/Y H:i') ?>
                            </p>

                            <div class="row g-3 mb-4 text-center">
                                <?php foreach ($totais as $nome => $qtd): ?>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded py-2">
                                            <div class="fs-3 fw-bold" style="color: <?= $nome === 'Alertas' && $qtd ? '#c0392b' : '#1b3f53' ?>;"><?= $qtd ?></div>
                                            <div class="form-label-fm text-uppercase"><?= $nome ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php foreach ($secoes as $nome => $linhas): ?>
                                <h6 class="fm-page-title"><?= $nome ?></h6>
                                <?php if (!$linhas): ?>
                                    <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                                <?php else: ?>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-fm table-sm mb-0">
                                            <thead><tr><?php foreach (array_keys($linhas[0]) as $c): ?><th><?= $c ?></th><?php endforeach; ?></tr></thead>
                                            <tbody>
                                                <?php foreach ($linhas as $l): ?>
                                                    <tr><?php foreach ($l as $v): ?><td><?= htmlspecialchars($v ?? '-') ?></td><?php endforeach; ?></tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>new bootstrap.Modal(modalRelatorio).show();</script>
        <?php endif; ?>

        <form method="get" class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <label class="form-label-fm mb-0">FILTRAR POR TIPO:</label>
            <select name="tipo" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <?php foreach (array_merge(['Todos'], $tiposDisponiveis) as $t): ?>
                    <option <?= $filtroTipo === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="text-muted small"><?= count($relatorios) ?> relatório(s)</span>
        </form>

        <div class="fm-card">
            <div class="fm-card-header px-3 py-2"><i class="bi bi-file-earmark-text me-1"></i>RELATÓRIOS GERADOS</div>
            <div class="table-responsive">
                <table class="table table-fm mb-0">
                    <thead>
                        <tr><th class="ps-3">TÍTULO</th><th>TIPO</th><th>PERÍODO</th><th>STATUS</th><th>GERADO EM</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorios as $r): ?>
                            <tr>
                                <td class="ps-3"><?= htmlspecialchars($r['titulo']) ?></td>
                                <td><a href="?tipo=<?= urlencode($r['tipo']) ?>" class="text-decoration-none"><?= htmlspecialchars($r['tipo']) ?></a></td>
                                <td><?= data($r['data_inicio']) ?> &ndash; <?= data($r['data_fim']) ?></td>
                                <td><span class="badge-status <?= $badges[$r['status_relatorio']] ?? '' ?>"><?= htmlspecialchars($r['status_relatorio']) ?></span></td>
                                <td><?= data($r['gerado_em'], 'd/m/Y H:i') ?></td>
                                <td class="text-end pe-3"><a href="?ver=<?= $r['id_relatorio'] ?>" class="btn btn-sm btn-fm-primary"><i class="bi bi-eye me-1"></i>VER</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$relatorios): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>

</html>
