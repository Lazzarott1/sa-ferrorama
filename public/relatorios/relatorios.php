<?php

// tela de relatórios, qualquer usuário logado pode gerar e ver
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';

$erro = '';


// GERAR RELATÓRIO (só grava os filtros; o conteúdo é montado quando alguém clica em VER)

if (isset($_POST['gerar_relatorio'])) {
    // pega os campos do formulário
    $titulo = trim($_POST['titulo']);
    $inicio = $_POST['data_inicio'];
    $fim = $_POST['data_fim'];
    $tipo_dado = $_POST['tipo_dado'];

    // se não escolheu tipo de dado o relatório é Geral
    $tipo = 'Geral';
    if ($tipo_dado != '') {
        $tipo = $tipo_dado;
    }

    // trem e trilho são opcionais: vazio vira null no banco
    $id_trem = null;
    if ($_POST['id_trem'] != '') {
        $id_trem = (int) $_POST['id_trem'];
    }

    $id_trilho = null;
    if ($_POST['id_trilho'] != '') {
        $id_trilho = (int) $_POST['id_trilho'];
    }

    // validações
    if ($titulo == '' || $inicio == '' || $fim == '') {
        $erro = 'Preencha ao menos Título, Data Início e Data Fim.';
    } elseif ($fim < $inicio) {
        $erro = 'A Data Fim não pode ser antes da Data Início.';
    } elseif ($tipo_dado != '' && $tipo_dado != 'Velocidade' && $tipo_dado != 'Temperatura' && $tipo_dado != 'Localização' && $tipo_dado != 'Consumo de Energia' && $tipo_dado != 'Falhas') {
        $erro = 'Selecione um tipo de dado válido.';
    } else {
        $stmt = mysqli_prepare($conexao, "INSERT INTO relatorios (titulo, tipo, data_inicio, data_fim, id_trem, id_trilho, tipo_dado, status_relatorio) VALUES (?, ?, ?, ?, ?, ?, ?, 'PRONTO')");
        mysqli_stmt_bind_param($stmt, "ssssiis", $titulo, $tipo, $inicio, $fim, $id_trem, $id_trilho, $tipo_dado);
        mysqli_stmt_execute($stmt);

        // já abre o relatório que acabou de ser criado
        header("Location: relatorios.php?ver=" . mysqli_insert_id($conexao));
        exit();
    }
}


// VER RELATÓRIO (monta as tabelas com os filtros que foram gravados)

$relatorio = null;

if (isset($_GET['ver'])) {
    $id = (int) $_GET['ver'];

    $resultado = mysqli_query($conexao, "SELECT relatorios.*, trens.nome_trem, trilhos.nome_trilho
        FROM relatorios
        LEFT JOIN trens ON trens.id_trem = relatorios.id_trem
        LEFT JOIN trilhos ON trilhos.id_trilho = relatorios.id_trilho
        WHERE relatorios.id_relatorio = $id");
    $relatorio = mysqli_fetch_assoc($resultado);
}

if ($relatorio) {
    $id_trem = (int) $relatorio['id_trem'];
    $id_trilho = (int) $relatorio['id_trilho'];
    $tipo_dado = $relatorio['tipo_dado'];
    $inicio = $relatorio['data_inicio'];
    $fim = $relatorio['data_fim'];

    // Se escolheu só o trem, o trilho do relatório é o trilho desse trem
    $trilho_do_trem = 0;
    if ($id_trem != 0) {
        $trem = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id_trilho FROM trens WHERE id_trem = $id_trem"));
        $trilho_do_trem = (int) $trem['id_trilho'];
    }
    if ($id_trilho == 0) {
        $id_trilho = $trilho_do_trem;
    }

    // cada filtro é um pedaço de WHERE que é colado no final das consultas lá embaixo
    // Filtro dos trens
    $filtro_trens = "";
    if ($id_trem != 0) {
        $filtro_trens = " AND trens.id_trem = $id_trem";
    } elseif ($id_trilho != 0) {
        $filtro_trens = " AND trens.id_trilho = $id_trilho";
    }

    // Filtro dos trilhos
    $filtro_trilhos = "";
    if ($id_trilho != 0) {
        $filtro_trilhos = " AND trilhos.id_trilho = $id_trilho";
    }

    // Filtro dos sensores (vale também para as leituras)
    $filtro_sensores = "";
    if ($id_trilho != 0) {
        $filtro_sensores = " AND (sensores.id_trilho = $id_trilho OR sensores.id_trilho = $trilho_do_trem)";
    }

    // Falhas = só o que está em manutenção ou em alerta; outro tipo filtra os sensores daquele tipo
    if ($tipo_dado == 'Falhas') {
        $filtro_trens .= " AND trens.status_trem = 'MANUTENCAO'";
        $filtro_trilhos .= " AND trilhos.status_trilho = 'MANUTENCAO'";
        $filtro_sensores .= " AND sensores.status_sensor = 'ALERTA'";
    } elseif ($tipo_dado != '') {
        $filtro_sensores .= " AND sensores.tipo_sensor = '$tipo_dado'";
    }

    // as 4 tabelas do relatório (só as leituras usam o período escolhido)
    $trens = mysqli_query($conexao, "SELECT trens.nome_trem, trens.modelo_trem, trens.capacidade_trem, trilhos.nome_trilho, trens.status_trem
        FROM trens
        LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
        WHERE trens.status_trem <> 'INATIVO' $filtro_trens
        ORDER BY trens.nome_trem");

    $trilhos = mysqli_query($conexao, "SELECT nome_trilho, descricao_trilho, km_trilho, status_trilho
        FROM trilhos
        WHERE status_trilho <> 'INATIVO' $filtro_trilhos
        ORDER BY nome_trilho");

    $sensores = mysqli_query($conexao, "SELECT sensores.nome_sensor, sensores.tipo_sensor, trilhos.nome_trilho, sensores.status_sensor
        FROM sensores
        LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
        WHERE sensores.status_sensor <> 'INATIVO' $filtro_sensores
        ORDER BY sensores.nome_sensor");

    $leituras = mysqli_query($conexao, "SELECT DATE_FORMAT(leituras.data_leitura, '%d/%m/%Y %H:%i') AS data, sensores.nome_sensor, sensores.tipo_sensor, trilhos.nome_trilho, leituras.valor
        FROM leituras
        JOIN sensores ON sensores.id_sensor = leituras.id_sensor
        LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
        WHERE DATE(leituras.data_leitura) BETWEEN '$inicio' AND '$fim' $filtro_sensores
        ORDER BY leituras.data_leitura DESC");

    // quantos sensores estão em alerta, pro quadradinho vermelho
    $alertas = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(*) AS total
        FROM sensores
        WHERE sensores.status_sensor = 'ALERTA' $filtro_sensores"));
}


// LISTA DE RELATÓRIOS (com filtro por tipo)

$filtro_tipo = 'Todos';
if (isset($_GET['tipo'])) {
    $filtro_tipo = $_GET['tipo'];
}

if ($filtro_tipo == 'Todos') {
    $relatorios = mysqli_query($conexao, "SELECT * FROM relatorios ORDER BY gerado_em DESC");
} else {
    $stmt = mysqli_prepare($conexao, "SELECT * FROM relatorios WHERE tipo = ? ORDER BY gerado_em DESC");
    mysqli_stmt_bind_param($stmt, "s", $filtro_tipo);
    mysqli_stmt_execute($stmt);
    $relatorios = mysqli_stmt_get_result($stmt);
}

// tipos que já existem (pro filtro) e trens/trilhos ativos (pros selects do formulário)
$tipos = mysqli_query($conexao, "SELECT DISTINCT tipo FROM relatorios ORDER BY tipo");
$lista_trens = mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem");
$lista_trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho");


// funções pequenas pra mostrar as datas no jeito brasileiro
function mostrarData($data)
{
    return date('d/m/Y', strtotime($data));
}

function mostrarDataHora($data)
{
    return date('d/m/Y H:i', strtotime($data));
}

// quando o filtro ficou vazio mostra "Todos"
function textoOuTodos($texto)
{
    if ($texto == null || $texto == '') {
        return 'Todos';
    }

    return htmlspecialchars($texto);
}

// quadradinho com um total no topo do relatório
function cartaoTotal($nome, $total, $cor)
{
    ?>
    <div class="col-6 col-md">
        <div class="caixa py-2 mb-0">
            <div class="fs-3 fw-bold <?php echo $cor; ?>"><?php echo $total; ?></div>
            <div class="form-label text-uppercase mb-0"><?php echo $nome; ?></div>
        </div>
    </div>
    <?php
}

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

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Relatórios</h3>
                <p>Gere e visualize análises da operação ferroviária</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formRelatorio">+ NOVO RELATÓRIO</button>
        </div>

        <!-- erro do formulário -->
        <?php if ($erro != '') { ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php } ?>

        <!-- formulário de novo relatório; se deu erro ele volta aberto -->
        <div class="caixa collapse <?php if ($erro != '') echo 'show'; ?>" id="formRelatorio">
            <div class="caixa-topo">GERAR NOVO RELATÓRIO</div>
            <form method="POST" class="caixa-corpo">
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
                            <?php while ($trem = mysqli_fetch_assoc($lista_trens)) { ?>
                                <option value="<?php echo $trem['id_trem']; ?>"><?php echo htmlspecialchars($trem['nome_trem']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="trilhoRelatorio" class="form-label">TRILHO (OPCIONAL)</label>
                        <select name="id_trilho" id="trilhoRelatorio" class="form-select">
                            <option value="">Todos os trilhos</option>
                            <?php while ($trilho = mysqli_fetch_assoc($lista_trilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>"><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tipoDado" class="form-label">TIPO DE DADO</label>
                        <select name="tipo_dado" id="tipoDado" class="form-select">
                            <option value="">Todos os dados</option>
                            <option>Velocidade</option>
                            <option>Temperatura</option>
                            <option>Localização</option>
                            <option>Consumo de Energia</option>
                            <option>Falhas</option>
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
                RELATÓRIOS GERADOS (<?php echo mysqli_num_rows($relatorios); ?>)
                <form method="GET">
                    <select name="tipo" class="form-select form-select-sm" onchange="enviarFiltro(this)">
                        <option value="Todos">TODOS</option>
                        <!-- uma opção pra cada tipo já gerado; o relatorios.js envia quando troca -->
                        <?php while ($linha = mysqli_fetch_assoc($tipos)) { ?>
                            <option value="<?php echo htmlspecialchars($linha['tipo']); ?>" <?php if ($filtro_tipo == $linha['tipo']) echo 'selected'; ?>><?php echo htmlspecialchars(mb_strtoupper($linha['tipo'])); ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <th>TÍTULO</th>
                            <th>TIPO</th>
                            <th>PERÍODO</th>
                            <th>STATUS</th>
                            <th>GERADO EM</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- um relatório por linha, o VER abre ele no modal -->
                        <?php while ($linha = mysqli_fetch_assoc($relatorios)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($linha['titulo']); ?></td>
                                <td><a href="?tipo=<?php echo urlencode($linha['tipo']); ?>"><?php echo htmlspecialchars($linha['tipo']); ?></a></td>
                                <td><?php echo mostrarData($linha['data_inicio']); ?> a <?php echo mostrarData($linha['data_fim']); ?></td>
                                <td><span class="badge status status-<?php echo $linha['status_relatorio']; ?>"><?php echo $linha['status_relatorio']; ?></span></td>
                                <td><?php echo mostrarDataHora($linha['gerado_em']); ?></td>
                                <td class="text-center"><a href="?ver=<?php echo $linha['id_relatorio']; ?>" class="btn btn-sm btn-outline-primary">VER</a></td>
                            </tr>
                        <?php } ?>
                        <?php if (mysqli_num_rows($relatorios) == 0) { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum relatório gerado ainda.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- modal com o conteúdo do relatório, só existe quando a url tem ?ver= (o relatorios.js abre ele sozinho) -->
    <?php if ($relatorio) { ?>
        <div class="modal fade" id="modalRelatorio" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-topo py-2">
                        <h6 class="modal-title fw-bold"><?php echo htmlspecialchars($relatorio['titulo']); ?></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Período: <?php echo mostrarData($relatorio['data_inicio']); ?> a <?php echo mostrarData($relatorio['data_fim']); ?>
                            &middot; Trem: <?php echo textoOuTodos($relatorio['nome_trem']); ?>
                            &middot; Trilho: <?php echo textoOuTodos($relatorio['nome_trilho']); ?>
                            &middot; Dados: <?php echo textoOuTodos($relatorio['tipo_dado']); ?>
                            &middot; Gerado em <?php echo mostrarDataHora($relatorio['gerado_em']); ?>
                        </p>

                        <div class="row g-3 mb-4 text-center">
                            <?php
                            cartaoTotal('Trens', mysqli_num_rows($trens), 'texto-azul');
                            cartaoTotal('Trilhos', mysqli_num_rows($trilhos), 'texto-azul');
                            cartaoTotal('Sensores', mysqli_num_rows($sensores), 'texto-azul');
                            cartaoTotal('Leituras', mysqli_num_rows($leituras), 'texto-azul');
                            if ($alertas['total'] > 0) {
                                cartaoTotal('Alertas', $alertas['total'], 'text-danger');
                            } else {
                                cartaoTotal('Alertas', 0, 'texto-azul');
                            }
                            ?>
                        </div>

                        <!-- daqui pra baixo são as 4 tabelas; todas seguem o mesmo jeito: se não tem linha mostra o aviso, senão monta a tabela -->
                        <h6 class="texto-azul fw-bold">Trens</h6>
                        <?php if (mysqli_num_rows($trens) == 0) { ?>
                            <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                        <?php } else { ?>
                            <div class="caixa table-responsive">
                                <table class="table table-sm mb-0 tabela">
                                    <thead>
                                        <tr><th>Nome</th><th>Modelo</th><th>Capacidade</th><th>Trilho</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($linha = mysqli_fetch_assoc($trens)) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($linha['nome_trem']); ?></td>
                                                <td><?php echo htmlspecialchars($linha['modelo_trem']); ?></td>
                                                <td><?php echo $linha['capacidade_trem']; ?></td>
                                                <td><?php echo htmlspecialchars($linha['nome_trilho']); ?></td>
                                                <td><?php echo $linha['status_trem']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                        <h6 class="texto-azul fw-bold">Trilhos</h6>
                        <?php if (mysqli_num_rows($trilhos) == 0) { ?>
                            <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                        <?php } else { ?>
                            <div class="caixa table-responsive">
                                <table class="table table-sm mb-0 tabela">
                                    <thead>
                                        <tr><th>Nome</th><th>Descrição</th><th>KM</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($linha = mysqli_fetch_assoc($trilhos)) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($linha['nome_trilho']); ?></td>
                                                <td><?php echo htmlspecialchars($linha['descricao_trilho']); ?></td>
                                                <td><?php echo htmlspecialchars($linha['km_trilho']); ?></td>
                                                <td><?php echo $linha['status_trilho']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                        <h6 class="texto-azul fw-bold">Sensores</h6>
                        <?php if (mysqli_num_rows($sensores) == 0) { ?>
                            <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                        <?php } else { ?>
                            <div class="caixa table-responsive">
                                <table class="table table-sm mb-0 tabela">
                                    <thead>
                                        <tr><th>Nome</th><th>Tipo</th><th>Trilho</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($linha = mysqli_fetch_assoc($sensores)) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($linha['nome_sensor']); ?></td>
                                                <td><?php echo $linha['tipo_sensor']; ?></td>
                                                <td><?php echo htmlspecialchars($linha['nome_trilho']); ?></td>
                                                <td><?php echo $linha['status_sensor']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                        <h6 class="texto-azul fw-bold">Leituras</h6>
                        <?php if (mysqli_num_rows($leituras) == 0) { ?>
                            <p class="text-muted small mb-4">Nenhum registro encontrado.</p>
                        <?php } else { ?>
                            <div class="caixa table-responsive">
                                <table class="table table-sm mb-0 tabela">
                                    <thead>
                                        <tr><th>Data e hora</th><th>Sensor</th><th>Tipo</th><th>Trilho</th><th>Valor</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($linha = mysqli_fetch_assoc($leituras)) { ?>
                                            <tr>
                                                <td><?php echo $linha['data']; ?></td>
                                                <td><?php echo htmlspecialchars($linha['nome_sensor']); ?></td>
                                                <td><?php echo $linha['tipo_sensor']; ?></td>
                                                <td><?php echo htmlspecialchars($linha['nome_trilho']); ?></td>
                                                <td><?php echo $linha['valor']; ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- filtro por tipo e abrir o modal -->
    <script src="../../script/relatorios.js"></script>

</body>

</html>
