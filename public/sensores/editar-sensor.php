<?php
include __DIR__ . '/../../infra/verifica-admin.php';
include __DIR__ . '/../../infra/valida-sensor.php';

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
$id_trem_atual = (int) $sensor['id_trem'];

// SALVAR ALTERAÇÕES
if (isset($_POST['editar'])) {
    [$dados, $erros] = validarSensor($conexao, $id_trem_atual, $id_trilho_atual);

    if (!$erros) {
        try {
            $sql = "UPDATE sensores
                    SET nome_sensor = ?, categoria_sensor = ?, tipo_sensor = ?, id_trem = ?, id_trilho = ?, status_sensor = ?
                    WHERE id_sensor = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sssiisi", $dados['nome_sensor'], $dados['categoria_sensor'], $dados['tipo_sensor'], $dados['id_trem'], $dados['id_trilho'], $dados['status_sensor'], $id_sensor);
            mysqli_stmt_execute($stmt);

            header("Location: tela-cadastro-sensores.php");
            exit;
        } catch (Exception $e) {
            $erros[] = "Não foi possível salvar o sensor. Tente novamente.";
        }
    }
}

$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' OR id_trilho = $id_trilho_atual ORDER BY nome_trilho");
$trens = mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' OR id_trem = $id_trem_atual ORDER BY nome_trem");

$ehTrem = $sensor['categoria_sensor'] == 'TREM';
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

    <main class="container px-4 mt-4">

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="titulo-sensores">Editar Sensor</h3>
                <p class="subtitulo-sensores">Atualize as informações do sensor selecionado</p>
            </div>

            <a href="tela-cadastro-sensores.php" class="btn btn-secondary">VOLTAR</a>
        </div>

        <div class="cardcadastro p-3">
            <span class="spancadastrosensor">EDITAR SENSOR #<?php echo $sensor['id_sensor']; ?></span>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger mt-3">
                <?php foreach ($erros as $erro) { ?>
                    <div><?php echo $erro; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <form method="POST" class="p-4 bg-white border mb-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">NOME DO SENSOR</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">CATEGORIA</label>
                    <select name="categoria" id="categoria" class="form-select" required>
                        <option value="TREM" <?php if ($sensor['categoria_sensor'] == 'TREM') echo 'selected'; ?>>Trem</option>
                        <option value="TRILHO" <?php if ($sensor['categoria_sensor'] == 'TRILHO') echo 'selected'; ?>>Trilho</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">TIPO</label>
                    <select name="tipo" class="form-select" required>
                        <option value="Velocidade" <?php if ($sensor['tipo_sensor'] == 'Velocidade') echo 'selected'; ?>>Velocidade</option>
                        <option value="Localização" <?php if ($sensor['tipo_sensor'] == 'Localização') echo 'selected'; ?>>Localização</option>
                        <option value="Temperatura" <?php if ($sensor['tipo_sensor'] == 'Temperatura') echo 'selected'; ?>>Temperatura</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" id="label-trilho"><?php echo $ehTrem ? 'TREM' : 'TRILHO'; ?></label>

                    <select name="trilho" id="select-trilho" class="form-select" <?php if ($ehTrem) echo 'style="display: none;" disabled'; ?> required>
                        <option value="">Selecione um trilho</option>
                        <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>
                            <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trilho['id_trilho'] == $sensor['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                        <?php } ?>
                    </select>

                    <select name="trilho" id="select-trem" class="form-select" <?php if (!$ehTrem) echo 'style="display: none;" disabled'; ?> required>
                        <option value="">Selecione um trem</option>
                        <?php while ($trem = mysqli_fetch_assoc($trens)) { ?>
                            <option value="<?php echo $trem['id_trem']; ?>" <?php if ($trem['id_trem'] == $sensor['id_trem']) echo 'selected'; ?>><?php echo htmlspecialchars($trem['nome_trem']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">STATUS</label>
                    <select name="status" class="form-select" required>
                        <option value="ATIVO" <?php if ($sensor['status_sensor'] == 'ATIVO') echo 'selected'; ?>>Ativo</option>
                        <option value="ALERTA" <?php if ($sensor['status_sensor'] == 'ALERTA') echo 'selected'; ?>>Alerta</option>
                        <option value="INATIVO" <?php if ($sensor['status_sensor'] == 'INATIVO') echo 'selected'; ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="editar" class="btn btn-navbar">SALVAR ALTERAÇÕES</button>
                <a href="tela-cadastro-sensores.php" class="btn btn-secondary">CANCELAR</a>
            </div>
        </form>

    </main>

    <script>
        // Troca o campo Trilho ou Trem conforme a categoria escolhida.
        const categoria = document.getElementById('categoria');
        const label = document.getElementById('label-trilho');
        const selectTrilho = document.getElementById('select-trilho');
        const selectTrem = document.getElementById('select-trem');

        categoria.addEventListener('change', function () {
            const ehTrem = categoria.value === 'TREM';

            label.textContent = ehTrem ? 'TREM' : 'TRILHO';

            selectTrilho.style.display = ehTrem ? 'none' : '';
            selectTrilho.disabled = ehTrem;

            selectTrem.style.display = ehTrem ? '' : 'none';
            selectTrem.disabled = !ehTrem;
        });
    </script>

</body>

</html>