<?php

include __DIR__ . '/../../infra/verifica-login.php';
include '../../infra/conexao.php';


$msgErro = '';

// GERAR RELATÓRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_relatorio'])) {
    $titulo     = trim($_POST['titulo'] ?? '');
    $dataInicio = $_POST['data_inicio'] ?? '';
    $dataFim    = $_POST['data_fim'] ?? '';
    $idTrem     = !empty($_POST['id_trem'])   ? (int) $_POST['id_trem']   : null;
    $idTrilho   = !empty($_POST['id_trilho']) ? (int) $_POST['id_trilho'] : null;
    $tipoDado   = trim($_POST['tipo_dado'] ?? '');
    $tipo       = $tipoDado !== '' ? $tipoDado : 'Geral';

    if ($titulo === '' || $dataInicio === '' || $dataFim === '') {
        $msgErro = 'Preencha ao menos Título, Data Início e Data Fim.';
    } elseif ($dataFim < $dataInicio) {
        $msgErro = 'A Data Fim não pode ser antes da Data Início.';
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO relatorios (titulo, tipo, data_inicio, data_fim, id_trem, id_trilho, tipo_dado, status_relatorio)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'PROCESSANDO')"
        );
        $stmt->bind_param('ssssiis', $titulo, $tipo, $dataInicio, $dataFim, $idTrem, $idTrilho, $tipoDado);
        $stmt->execute();
        $stmt->close();

        header('Location: relatorios.php');
        exit;
    }
}


// BUSCAR RELATÓRIOS
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

$trens = $conexao->query("SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem");
$trilhos = $conexao->query("SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho");

function badgeStatus(string $status): string {
    switch (strtoupper($status)) {
        case 'PRONTO': return 'badge-status badge-pronto';
        case 'ERRO':   return 'badge-status badge-erro';
        default:       return 'badge-status badge-processando';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Relatórios</title>

    <link rel="stylesheet"
        href="../../assets/img/style/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        .fm-page-title { color: #1b3f53; font-weight: 700; }
        .fm-page-sub { color: #6c7a89; font-size: .9rem; }

        .btn-fm-primary { background-color: #1b3f53; border-color: #1b3f53; color: #fff; font-weight: 600; }
        .btn-fm-primary:hover { background-color: #142f3f; border-color: #142f3f; color: #fff; }

        .fm-card { background: #fff; border: 1px solid #dbe3ea; border-radius: .5rem; }
        .fm-card-header {
            background: #f4f7fa; border-bottom: 1px solid #dbe3ea;
            font-weight: 700; color: #1b3f53; font-size: .9rem;
            border-radius: .5rem .5rem 0 0;
        }

        .table-fm thead th {
            font-size: .72rem; text-transform: uppercase; letter-spacing: .03em;
            color: #7c8a99; background: #f8fafc; border-bottom: 1px solid #dbe3ea;
            font-weight: 700;
        }
        .table-fm td { vertical-align: middle; font-size: .9rem; color: #2c3e50; }

        .badge-status { font-size: .72rem; font-weight: 600; padding: .3rem .6rem; border-radius: .35rem; border: 1px solid transparent; }
        .badge-pronto { background: #e9f8ee; color: #1c8a43; border-color: #bfe8cd; }
        .badge-processando { background: #fff6e0; color: #a5700a; border-color: #f3dfa5; }
        .badge-erro { background: #fdeaea; color: #c0392b; border-color: #f4bcb6; }

        #formGerarRelatorio { display: none; }
        #formGerarRelatorio.show { display: block; }
        .form-label-fm { font-size: .8rem; font-weight: 600; color: #3b4b5a; }
        .form-label-fm .text-req { color: #c0392b; }
    </style>

</head>

<body>


         <!-- HEADER -->

    <header class="container-fluid p-2 rounded-0"
        style="background-color: #1b3f53; color: #ffffff;">

        <div id="header"
            class="hstack gap-3 px-2">


            <!-- LOGO -->

            <div class="d-flex"
                id="logo">

                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png"
                    alt="Logo">

                <div class="nome-sistema">

                    <h2 class="mb-0 text-white">
                        FerroMonitor
                    </h2>

                    <p>
                        SISTEMA FERROVIÁRIO
                    </p>

                </div>

            </div>


            <!-- NAVBAR -->

            <nav class="navbar navbar-expand-lg navbar-dark"
                style="background-color: #1b3f53;">

                <div class="container-fluid">

                    <div class="collapse navbar-collapse"
                        id="navbarNav">

                        <ul class="navbar-nav">
                            <li class="nav-item"><a class="nav-link text-white" href="../tela-geral-home.php">Home</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="../sensores/tela-cadastro-sensores.php">Sensores</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="../trens/tela-trens.php">Trens</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="../trilhos/tela-cadastro-trilhos.php">Trilhos</a></li>
                            <li class="nav-item"><a class="nav-link text-white" href="../relatorios/relatorios.php">Relatórios</a></li>
                            <?php if ($admin) { ?>
                                <li class="nav-item"><a class="nav-link text-white" href="../usuarios/tela-cadastro-user.php">Usuários</a></li>
                            <?php } ?>
                        </ul>

                    </div>

                </div>

            </nav>


            <!-- SAIR -->

            <div>
                <button class="btn-sair" onclick="window.location.href='../../infra/logout.php'">Sair</button>
            </div>

        </div>

    </header>


         <!-- CONTEÚDO PRINCIPAL -->

    <main class="container-fluid px-4 py-4">

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

        <div class="fm-card mb-4 <?= $msgErro ? 'show' : '' ?>" id="formGerarRelatorio">
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
                            <select name="id_trem" class="form-select">
                                <option value="">Todos os trens</option>
                                <?php while ($trem = $trens->fetch_assoc()): ?>
                                    <option value="<?= (int) $trem['id_trem'] ?>"><?= htmlspecialchars($trem['nome_trem']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-fm">TRILHO (OPCIONAL)</label>
                            <select name="id_trilho" class="form-select">
                                <option value="">Todos os trilhos</option>
                                <?php while ($trilho = $trilhos->fetch_assoc()): ?>
                                    <option value="<?= (int) $trilho['id_trilho'] ?>"><?= htmlspecialchars($trilho['nome_trilho']) ?></option>
                                <?php endwhile; ?>
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
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($totalRelatorios > 0): ?>
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
                                <td><span class="<?= badgeStatus($rel['status_relatorio']) ?>"><?= htmlspecialchars($rel['status_relatorio']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($rel['gerado_em'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const formGerar = document.getElementById('formGerarRelatorio');

        document.getElementById('btnToggleForm').addEventListener('click', () => {
            formGerar.classList.toggle('show');
        });
        document.getElementById('btnCancelarForm').addEventListener('click', () => {
            formGerar.classList.remove('show');
        });
    </script>

</body>

</html>
