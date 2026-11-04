<?php
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-sensores.php';

$erros = [];

// INATIVAR SENSOR
if ($admin && isset($_POST['inativar'])) {
    $id_sensor = (int) $_POST['id_sensor'];

    try {
        $stmt = mysqli_prepare($conexao, "UPDATE sensores SET status_sensor = 'INATIVO' WHERE id_sensor = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_sensor);
        mysqli_stmt_execute($stmt);

        header("Location: tela-cadastro-sensores.php");
        exit;
    } catch (Exception $e) {
        $erros[] = "Não foi possível inativar o sensor. Tente novamente.";
    }
}

// CADASTRAR SENSOR
if ($admin && isset($_POST['cadastrar'])) {
    [$novo, $erros] = validarSensor($conexao);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "INSERT INTO sensores (nome_sensor, tipo_sensor, id_trilho, status_sensor) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", $novo['nome_sensor'], $novo['tipo_sensor'], $novo['id_trilho'], $novo['status_sensor']);
            mysqli_stmt_execute($stmt);

            header("Location: tela-cadastro-sensores.php");
            exit;
        } catch (Exception $e) {
            $erros[] = "Não foi possível cadastrar o sensor. Tente novamente.";
        }
    }
}

// BUSCAR SENSORES
$filtro = $_GET['filtro'] ?? 'ativos';

$where = "";

if ($filtro == 'ativos') {
    $where = "WHERE sensores.status_sensor <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE sensores.status_sensor = 'INATIVO'";
}

$resultado = mysqli_query($conexao, "SELECT sensores.*, trilhos.nome_trilho
    FROM sensores
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    $where
    ORDER BY sensores.id_sensor DESC");

$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho");

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensores</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Sensores</h3>
                <p>Gerencie os sensores cadastrados na ferrovia</p>
            </div>
            <?php if ($admin) { ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formSensor">+ NOVO SENSOR</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
        <div class="caixa collapse <?php if ($erros && isset($_POST['cadastrar'])) echo 'show'; ?>" id="formSensor">
            <div class="caixa-topo">CADASTRAR NOVO SENSOR</div>
            <form method="POST" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeSensor" class="form-label">NOME DO SENSOR</label>
                        <input type="text" name="nome" id="nomeSensor" class="form-control" placeholder="Ex: Sensor 01" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipoSensor" class="form-label">TIPO</label>
                        <select name="tipo" id="tipoSensor" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            <?php foreach (['Velocidade', 'Localização', 'Temperatura'] as $tipo) { ?>
                                <option><?php echo $tipo; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="trilhoSensor" class="form-label">TRILHO</label>
                        <select name="trilho" id="trilhoSensor" class="form-select" required>
                            <option value="">Selecione um trilho</option>
                            <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>"><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="statusSensor" class="form-label">STATUS</label>
                        <select name="status" id="statusSensor" class="form-select" required>
                            <?php foreach (['ATIVO' => 'Ativo', 'ALERTA' => 'Alerta', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>"><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formSensor">CANCELAR</button>
                </div>
            </form>
        </div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">
                SENSORES CADASTRADOS
                <form method="GET">
                    <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'todos' => 'Todos'] as $valor => $texto) { ?>
                            <option value="<?php echo $valor; ?>" <?php if ($filtro == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                        <?php } ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><th>ID</th><th>NOME</th><th>TIPO</th><th>TRILHO</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($sensor = mysqli_fetch_assoc($resultado)) { ?>
                            <tr>
                                <td><?php echo $sensor['id_sensor']; ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_sensor']); ?></td>
                                <td><?php echo htmlspecialchars($sensor['tipo_sensor']); ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_trilho'] ?? '-'); ?></td>
                                <td><span class="badge status status-<?php echo $sensor['status_sensor']; ?>"><?php echo htmlspecialchars($sensor['status_sensor']); ?></span></td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <a href="editar-sensor.php?id=<?php echo $sensor['id_sensor']; ?>" class="btn btn-sm btn-outline-primary">EDITAR</a>
                                        <?php if ($sensor['status_sensor'] != 'INATIVO') { ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalSensorAssociado" data-id="<?php echo $sensor['id_sensor']; ?>" data-nome="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>" data-vinculo="<?php echo htmlspecialchars('Trilho: ' . ($sensor['nome_trilho'] ?? '-')); ?>">INATIVAR</button>
                                        <?php } ?>
                                    <?php } else { echo '-'; } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (mysqli_num_rows($resultado) == 0) { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum sensor cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div class="modal fade" id="modalSensorAssociado" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold">SENSOR COM ASSOCIAÇÃO</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>O sensor <span id="modalNomeSensor"></span> está ligado a:</strong><br>
                        <span id="modalVinculoSensor"></span><br>
                        Deseja inativar mesmo assim?
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="id_sensor" id="modalIdSensor">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                        <button type="submit" name="inativar" class="btn btn-sm btn-danger">INATIVAR</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        modalSensorAssociado.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            modalIdSensor.value = botao.dataset.id;
            modalNomeSensor.textContent = botao.dataset.nome;
            modalVinculoSensor.textContent = botao.dataset.vinculo;
        });
    </script>

</body>

</html>
