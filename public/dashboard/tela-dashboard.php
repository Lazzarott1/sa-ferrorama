<?php
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-leitura.php';

$erros = [];

// REGISTRAR LEITURA
if ($admin && isset($_POST['registrar'])) {
    $erros = registrarLeitura($conexao);

    if (!$erros) {
        header("Location: tela-dashboard.php");
        exit;
    }
}

// BUSCAR SENSORES E LEITURAS
function lista($conexao, $sql) {
    return mysqli_fetch_all(mysqli_query($conexao, $sql), MYSQLI_ASSOC);
}

$sensores = lista($conexao, "SELECT s.id_sensor, s.nome_sensor, s.tipo_sensor, s.status_sensor, t.nome_trilho, l.valor, DATE_FORMAT(l.data_leitura, '%d/%m/%Y %H:%i:%s') AS data
    FROM sensores s
    LEFT JOIN trilhos t ON t.id_trilho = s.id_trilho
    LEFT JOIN leituras l ON l.id_leitura = (SELECT MAX(id_leitura) FROM leituras WHERE id_sensor = s.id_sensor)
    WHERE s.status_sensor <> 'INATIVO'
    ORDER BY s.nome_sensor");

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'unidades' => ['Velocidade' => 'km/h', 'Localização' => 'km', 'Temperatura' => '°C'],
        'resumo' => lista($conexao, "SELECT
            (SELECT COUNT(*) FROM leituras) AS 'Leituras',
            (SELECT COUNT(*) FROM leituras WHERE DATE(data_leitura) = CURDATE()) AS 'Leituras hoje',
            (SELECT COUNT(*) FROM sensores WHERE status_sensor <> 'INATIVO') AS 'Sensores ativos',
            (SELECT COUNT(*) FROM sensores WHERE status_sensor = 'ALERTA') AS 'Alertas'")[0],
        'sensores' => $sensores,
        'leituras' => lista($conexao, "SELECT l.id_leitura, DATE_FORMAT(l.data_leitura, '%d/%m/%Y %H:%i:%s') AS data, s.nome_sensor, s.tipo_sensor, t.nome_trilho, l.valor, s.status_sensor
            FROM leituras l
            JOIN sensores s ON s.id_sensor = l.id_sensor
            LEFT JOIN trilhos t ON t.id_trilho = s.id_trilho
            ORDER BY l.id_leitura DESC LIMIT 100")
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Dashboard</h3>
                <p>Leituras dos sensores em tempo real · <span id="atualizado">Carregando...</span></p>
            </div>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <div class="row g-3 mb-4 text-center" id="resumo"></div>

        <div class="row g-3 mb-4" id="sensores"></div>

        <div class="caixa">
            <div class="caixa-topo">ÚLTIMAS LEITURAS</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><th>ID</th><th>DATA E HORA</th><th>SENSOR</th><th>TIPO</th><th>TRILHO</th><th>VALOR</th><th>STATUS DO SENSOR</th></tr>
                    </thead>
                    <tbody id="leituras"></tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="../../script/dashboard.js"></script>

</body>

</html>
