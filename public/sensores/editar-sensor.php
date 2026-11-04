<?php
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-sensores.php';

$erros = [];
$id_sensor = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conexao, "SELECT * FROM sensores WHERE id_sensor = ?");
mysqli_stmt_bind_param($stmt, "i", $id_sensor);
mysqli_stmt_execute($stmt);
$sensor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sensor) {
    header("Location: tela-cadastro-sensores.php");
    exit;
}

$id_trilho_atual = (int) $sensor['id_trilho'];

// SALVAR ALTERAÇÕES
if (isset($_POST['editar'])) {
    [$dados, $erros] = validarSensor($conexao, $id_trilho_atual);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "UPDATE sensores SET nome_sensor = ?, tipo_sensor = ?, id_trilho = ?, status_sensor = ? WHERE id_sensor = ?");
            mysqli_stmt_bind_param($stmt, "ssisi", $dados['nome_sensor'], $dados['tipo_sensor'], $dados['id_trilho'], $dados['status_sensor'], $id_sensor);
            mysqli_stmt_execute($stmt);

            header("Location: tela-cadastro-sensores.php");
            exit;
        } catch (Exception $e) {
            $erros[] = "Não foi possível salvar o sensor. Tente novamente.";
        }
    }
}

$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' OR id_trilho = $id_trilho_atual ORDER BY nome_trilho");

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sensor</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Editar sensor</h3>
                <p>Atualize as informações do sensor selecionado</p>
            </div>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">EDITAR SENSOR #<?php echo $sensor['id_sensor']; ?></div>
            <form method="POST" class="caixa-corpo">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeSensor" class="form-label">NOME DO SENSOR</label>
                        <input type="text" name="nome" id="nomeSensor" class="form-control" value="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="tipoSensor" class="form-label">TIPO</label>
                        <select name="tipo" id="tipoSensor" class="form-select" required>
                            <?php foreach (['Velocidade', 'Localização', 'Temperatura'] as $tipo) { ?>
                                <option <?php if ($sensor['tipo_sensor'] == $tipo) echo 'selected'; ?>><?php echo $tipo; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="trilhoSensor" class="form-label">TRILHO</label>
                        <select name="trilho" id="trilhoSensor" class="form-select" required>
                            <option value="">Selecione um trilho</option>
                            <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trilho['id_trilho'] == $sensor['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="statusSensor" class="form-label">STATUS</label>
                        <select name="status" id="statusSensor" class="form-select" required>
                            <?php foreach (['ATIVO' => 'Ativo', 'ALERTA' => 'Alerta', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($sensor['status_sensor'] == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="editar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    <a href="tela-cadastro-sensores.php" class="btn btn-secondary">CANCELAR</a>
                </div>
            </form>
        </div>

    </main>

</body>

</html>
