<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';

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
    $trem = (int) $rel['id_trem'];
    $trilho = (int) $rel['id_trilho'];
    $dado = $rel['tipo_dado'] ?? '';
    $falhas = $dado === 'Falhas';

    if (!$trilho && $trem) $trilho = (int) buscar($conexao, "SELECT id_trilho FROM trens WHERE id_trem = ?", [$trem])[0]['id_trilho'];
    $filtrado = $trem || $rel['id_trilho'];
    $doTrem = $trem ? "t.id_trem = $trem" : ($trilho ? "t.id_trilho = $trilho" : "1");
    $doTrilho = $filtrado ? "tr.id_trilho = $trilho" : "1";

    $secoes['Trens'] = buscar($conexao, "SELECT t.nome_trem AS Nome, t.modelo_trem AS Modelo, t.capacidade_trem AS Capacidade, tr.nome_trilho AS Trilho, t.status_trem AS Status FROM trens t LEFT JOIN trilhos tr ON tr.id_trilho = t.id_trilho WHERE t.status_trem <> 'INATIVO' AND $doTrem" . ($falhas ? " AND t.status_trem = 'MANUTENCAO'" : "") . " ORDER BY Nome");

    $secoes['Trilhos'] = buscar($conexao, "SELECT nome_trilho AS Nome, descricao_trilho AS Descrição, km_trilho AS KM, status_trilho AS Status FROM trilhos tr WHERE status_trilho <> 'INATIVO' AND $doTrilho" . ($falhas ? " AND status_trilho = 'MANUTENCAO'" : "") . " ORDER BY Nome");

    $sql = "SELECT s.nome_sensor AS Nome, s.tipo_sensor AS Tipo, tr.nome_trilho AS Trilho, s.status_sensor AS Status FROM sensores s LEFT JOIN trilhos tr ON tr.id_trilho = s.id_trilho WHERE s.status_sensor <> 'INATIVO'";
    if ($filtrado) $sql .= " AND (s.id_trilho = $trilho OR s.id_trilho IN (SELECT id_trilho FROM trens t WHERE $doTrem))";
    $p = [];
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

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Relatórios</h3>
                <p>Gere e visualize análises da operação ferroviária</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formRelatorio">+ NOVO RELATÓRIO</button>
        </div>

        <?php if ($msgErro) { ?>
            <div class="alert alert-danger"><?= htmlspecialchars($msgErro) ?></div>
        <?php } ?>

        <div class="caixa collapse <?= $msgErro ? 'show' : '' ?>" id="formRelatorio">
            <div class="caixa-topo">GERAR NOVO RELATÓRIO</div>
            <form method="post" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="titulo" class="form-label">TÍTULO</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Ex: Relatório Semanal" required>
                    </div>
                    <div class="col-md-3">
                        <label for="dataInicio" class="form-label">DATA INÍCIO</label>
                        <input type="date" name="data_inicio" id="dataInicio" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="dataFim" class="form-label">DATA FIM</label>
                        <input type="date" name="data_fim" id="dataFim" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="tremRelatorio" class="form-label">TREM (OPCIONAL)</label>
                        <select name="id_trem" id="tremRelatorio" class="form-select">
                            <option value="">Todos os trens</option>
                            <?php foreach ($trens as $t) { ?>
                                <option value="<?= $t['id_trem'] ?>"><?= htmlspecialchars($t['nome_trem']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="trilhoRelatorio" class="form-label">TRILHO (OPCIONAL)</label>
                        <select name="id_trilho" id="trilhoRelatorio" class="form-select">
                            <option value="">Todos os trilhos</option>
                            <?php foreach ($trilhos as $t) { ?>
                                <option value="<?= $t['id_trilho'] ?>"><?= htmlspecialchars($t['nome_trilho']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tipoDado" class="form-label">TIPO DE DADO</label>
                        <select name="tipo_dado" id="tipoDado" class="form-select">
                            <option value="">Todos os dados</option>
                            <?php foreach (['Velocidade', 'Temperatura', 'Localização', 'Consumo de Energia', 'Falhas'] as $t) { ?>
                                <option><?= $t ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="gerar_relatorio" class="btn btn-primary">GERAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formRelatorio">CANCELAR</button>
                </div>
            </form>
        </div>

        <div class="caixa">
            <div class="caixa-topo">
                RELATÓRIOS GERADOS (<?= count($relatorios) ?>)
                <form method="get">
                    <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach (array_merge(['Todos'], $tiposDisponiveis) as $t) { ?>
                            <option <?= $filtroTipo === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><th>TÍTULO</th><th>TIPO</th><th>PERÍODO</th><th>STATUS</th><th>GERADO EM</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorios as $r) { ?>
                            <tr>
                                <td><?= htmlspecialchars($r['titulo']) ?></td>
                                <td><a href="?tipo=<?= urlencode($r['tipo']) ?>"><?= htmlspecialchars($r['tipo']) ?></a></td>
                                <td><?= data($r['data_inicio']) ?> a <?= data($r['data_fim']) ?></td>
                                <td><span class="badge status status-<?= $r['status_relatorio'] ?>"><?= htmlspecialchars($r['status_relatorio']) ?></span></td>
                                <td><?= data($r['gerado_em'], 'd/m/Y H:i') ?></td>
                                <td class="text-center"><a href="?ver=<?= $r['id_relatorio'] ?>" class="btn btn-sm btn-outline-primary">VER</a></td>
                            </tr>
                        <?php } ?>
                        <?php if (!$relatorios) { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php if ($rel) { ?>
        <div class="modal fade" id="modalRelatorio" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-topo py-2">
                        <h6 class="modal-title fw-bold"><?= htmlspecialchars($rel['titulo']) ?></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Período: <?= data($rel['data_inicio']) ?> a <?= data($rel['data_fim']) ?>
                            &middot; Trem: <?= htmlspecialchars($rel['nome_trem'] ?? 'Todos') ?>
                            &middot; Trilho: <?= htmlspecialchars($rel['nome_trilho'] ?? 'Todos') ?>
                            &middot; Dados: <?= htmlspecialchars($dado ?: 'Todos') ?>
                            &middot; Gerado em <?= data($rel['gerado_em'], 'd/m/Y H:i') ?>
                        </p>
                        <div class="row g-3 mb-4 text-center">
                            <?php foreach ($totais as $nome => $qtd) { ?>
                                <div class="col-6 col-md-3">
                                    <div class="caixa py-2 mb-0">
                                        <div class="fs-3 fw-bold <?= $nome === 'Alertas' && $qtd ? 'text-danger' : 'texto-azul' ?>"><?= $qtd ?></div>
                                        <div class="form-label text-uppercase mb-0"><?= $nome ?></div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <?php foreach ($secoes as $nome => $linhas) { ?>
                            <h6 class="texto-azul fw-bold"><?= $nome ?></h6>
                            <?php if (!$linhas) { ?>
                                <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                            <?php } else { ?>
                                <div class="caixa table-responsive">
                                    <table class="table table-sm mb-0 tabela">
                                        <thead><tr><?php foreach (array_keys($linhas[0]) as $c) { ?><th><?= $c ?></th><?php } ?></tr></thead>
                                        <tbody>
                                            <?php foreach ($linhas as $l) { ?>
                                                <tr><?php foreach ($l as $v) { ?><td><?= htmlspecialchars($v ?? '-') ?></td><?php } ?></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($rel) { ?>
        <script>new bootstrap.Modal(modalRelatorio).show();</script>
    <?php } ?>

</body>

</html>
