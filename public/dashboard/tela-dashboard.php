<?php

// dashboard: qualquer usuário logado pode ver
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-leitura.php';

$erros = [];


// REGISTRAR LEITURA

if ($admin && isset($_POST['registrar'])) {
    // só o admin registra leitura na mão; aceita vírgula no valor (12,5)
    $id_sensor = (int) $_POST['sensor'];
    $valor = str_replace(',', '.', trim($_POST['valor']));

    $erros = validarLeitura($conexao, $id_sensor, $valor);

    // sem erro: salva e recarrega a página limpa
    if (count($erros) == 0) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO leituras (id_sensor, valor) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "id", $id_sensor, $valor);
        mysqli_stmt_execute($stmt);

        header("Location: tela-dashboard.php");
        exit();
    }
}


// DADOS EM JSON (o script/dashboard.js chama tela-dashboard.php?json=1 a cada 3 segundos)

if (isset($_GET['json'])) {
    // os totais dos quadradinhos de cima
    $resultado = mysqli_query($conexao, "SELECT
        (SELECT COUNT(*) FROM leituras) AS leituras,
        (SELECT COUNT(*) FROM leituras WHERE DATE(data_leitura) = CURDATE()) AS leituras_hoje,
        (SELECT COUNT(*) FROM sensores WHERE status_sensor <> 'INATIVO') AS sensores_ativos,
        (SELECT COUNT(*) FROM sensores WHERE status_sensor = 'ALERTA') AS alertas");
    $resumo = mysqli_fetch_assoc($resultado);

    // cada sensor ativo junto com a última leitura dele (a de maior id)
    $sensores = [];
    $resultado = mysqli_query($conexao, "SELECT sensores.nome_sensor, sensores.tipo_sensor,
        sensores.status_sensor, trilhos.nome_trilho, leituras.valor,
        DATE_FORMAT(leituras.data_leitura, '%d/%m/%Y %H:%i:%s') AS data
        FROM sensores
        LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
        LEFT JOIN leituras ON leituras.id_leitura = (
            SELECT MAX(id_leitura)
            FROM leituras
            WHERE leituras.id_sensor = sensores.id_sensor
        )
        WHERE sensores.status_sensor <> 'INATIVO'
        ORDER BY sensores.nome_sensor");
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $sensores[] = $linha;
    }

    // as 100 leituras mais recentes
    $leituras = [];
    $resultado = mysqli_query($conexao, "SELECT leituras.id_leitura,
        DATE_FORMAT(leituras.data_leitura, '%d/%m/%Y %H:%i:%s') AS data,
        sensores.nome_sensor, sensores.tipo_sensor, trilhos.nome_trilho, leituras.valor,
        sensores.status_sensor
        FROM leituras
        JOIN sensores ON sensores.id_sensor = leituras.id_sensor
        LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
        ORDER BY leituras.id_leitura DESC
        LIMIT 100");
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $leituras[] = $linha;
    }

    $dados = [
        'resumo' => $resumo,
        'sensores' => $sensores,
        'leituras' => $leituras
    ];

    header('Content-Type: application/json');
    echo json_encode($dados);
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body>

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Dashboard</h3>
                <p>
                    Leituras dos sensores em tempo real ·
                    <span id="atualizado">Carregando...</span>
                </p>
            </div>
        </div>

        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <!-- os 4 totais; os números começam em 0 e o dashboard.js troca -->
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="caixa py-2 mb-0">
                    <div class="fs-3 fw-bold texto-azul" id="totalLeituras">0</div>
                    <div class="form-label mb-0">LEITURAS</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="caixa py-2 mb-0">
                    <div class="fs-3 fw-bold texto-azul" id="totalHoje">0</div>
                    <div class="form-label mb-0">LEITURAS HOJE</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="caixa py-2 mb-0">
                    <div class="fs-3 fw-bold texto-azul" id="totalSensores">0</div>
                    <div class="form-label mb-0">SENSORES ATIVOS</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="caixa py-2 mb-0">
                    <div class="fs-3 fw-bold texto-azul" id="totalAlertas">0</div>
                    <div class="form-label mb-0">ALERTAS</div>
                </div>
            </div>
        </div>

        <!-- cartões dos sensores e a tabela de leituras também são preenchidos pelo -->
        <!-- dashboard.js -->
        <div class="row g-3 mb-4" id="sensores"></div>

        <div class="caixa">
            <div class="caixa-topo">ÚLTIMAS LEITURAS</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>DATA E HORA</th>
                            <th>SENSOR</th>
                            <th>TIPO</th>
                            <th>TRILHO</th>
                            <th>VALOR</th>
                            <th>STATUS DO SENSOR</th>
                        </tr>
                    </thead>
                    <tbody id="leituras"></tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="../../script/dashboard.js"></script>

</body>

</html>
