<?php
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-sensores.php';
include __DIR__ . '/../listagem.php';

$erros = [];
$aberto = -1;
$novo = ['nome_sensor' => '', 'tipo_sensor' => '', 'id_trilho' => 0, 'status_sensor' => 'ATIVO'];

// ATIVAR / INATIVAR SENSORES
if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {
    mudarStatus($conexao, 'sensores', 'id_sensor', 'status_sensor');
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// CADASTRAR / EDITAR SENSOR
if ($admin && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $atual = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id_trilho FROM sensores WHERE id_sensor = $id"));
    [$form, $erros] = validarSensor($conexao, (int) ($atual['id_trilho'] ?? 0));

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, $id
                ? "UPDATE sensores SET nome_sensor = ?, tipo_sensor = ?, id_trilho = ?, status_sensor = ? WHERE id_sensor = $id"
                : "INSERT INTO sensores (nome_sensor, tipo_sensor, id_trilho, status_sensor) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", ...array_values($form));
            mysqli_stmt_execute($stmt);

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Exception $e) {
            $erros[] = "Não foi possível salvar o sensor. Tente novamente.";
        }
    }

    $aberto = $id;
}

// BUSCAR SENSORES
$where = filtrar('sensores.status_sensor');

$sensores = mysqli_fetch_all(mysqli_query($conexao, "SELECT sensores.*, trilhos.nome_trilho
    FROM sensores
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    $where
    ORDER BY sensores.id_sensor DESC"), MYSQLI_ASSOC);

$trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trilho, nome_trilho, status_trilho FROM trilhos ORDER BY nome_trilho"), MYSQLI_ASSOC);

function formSensor($sensor, $trilhos)
{
    $id = $sensor['id_sensor'] ?? 0; ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeSensor<?php echo $id; ?>" class="form-label">NOME DO SENSOR</label>
                <input type="text" name="nome" id="nomeSensor<?php echo $id; ?>" class="form-control" placeholder="Ex: Sensor 01" value="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="tipoSensor<?php echo $id; ?>" class="form-label">TIPO</label>
                <select name="tipo" id="tipoSensor<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o tipo</option>
                    <?php foreach (['Velocidade', 'Localização', 'Temperatura'] as $tipo) { ?>
                        <option <?php if ($sensor['tipo_sensor'] == $tipo) echo 'selected'; ?>><?php echo $tipo; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="trilhoSensor<?php echo $id; ?>" class="form-label">TRILHO</label>
                <select name="trilho" id="trilhoSensor<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione um trilho</option>
                    <?php foreach ($trilhos as $trilho) { if ($trilho['status_trilho'] != 'INATIVO' || $trilho['id_trilho'] == $sensor['id_trilho']) { ?>
                        <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trilho['id_trilho'] == $sensor['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                    <?php } } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="statusSensor<?php echo $id; ?>" class="form-label">STATUS</label>
                <select name="status" id="statusSensor<?php echo $id; ?>" class="form-select" required>
                    <?php foreach (['ATIVO' => 'Ativo', 'ALERTA' => 'Alerta', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                        <option value="<?php echo $valor; ?>" <?php if ($sensor['status_sensor'] == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" name="salvar" class="btn btn-primary"><?php echo $id ? 'SALVAR ALTERAÇÕES' : 'CADASTRAR'; ?></button>
            <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editar<?php echo $id; ?>">CANCELAR</button>
        </div>
    </form>
<?php }

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
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO SENSOR</button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <div class="caixa collapse <?php if ($aberto === 0) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO SENSOR</div>
                <?php formSensor($aberto === 0 ? $form : $novo, $trilhos); ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <?php topo('SENSORES CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr><?php if ($admin) marcarTodos(); ?><th>ID</th><th>NOME</th><th>TIPO</th><th>TRILHO</th><th>STATUS</th><th class="text-center">AÇÕES</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensores as $sensor) { ?>
                            <tr>
                                <?php if ($admin) marcar($sensor['id_sensor'], 'o sensor ' . $sensor['nome_sensor']); ?>
                                <td><?php echo $sensor['id_sensor']; ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_sensor']); ?></td>
                                <td><?php echo htmlspecialchars($sensor['tipo_sensor']); ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_trilho'] ?? '-'); ?></td>
                                <td><span class="badge status status-<?php echo $sensor['status_sensor']; ?>"><?php echo htmlspecialchars($sensor['status_sensor']); ?></span></td>
                                <td class="text-center text-nowrap"><?php if ($admin) acoes($sensor['id_sensor'], $sensor['status_sensor'] == 'INATIVO'); else echo '-'; ?></td>
                            </tr>
                            <?php if ($admin) { ?>
                                <tr class="collapse <?php if ($aberto == $sensor['id_sensor']) echo 'show'; ?>" id="editar<?php echo $sensor['id_sensor']; ?>">
                                    <td colspan="7" class="p-0"><?php formSensor($aberto == $sensor['id_sensor'] ? $form + $sensor : $sensor, $trilhos); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <?php if (!$sensores) { ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum sensor cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php modalStatus(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
