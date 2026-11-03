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
            $sql = "INSERT INTO sensores (nome_sensor, categoria_sensor, tipo_sensor, id_trem, id_trilho, status_sensor)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sssiis", $novo['nome_sensor'], $novo['categoria_sensor'], $novo['tipo_sensor'], $novo['id_trem'], $novo['id_trilho'], $novo['status_sensor']);
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

$resultado = mysqli_query($conexao, "SELECT sensores.*, trens.nome_trem, trilhos.nome_trilho
    FROM sensores
    LEFT JOIN trens ON trens.id_trem = sensores.id_trem
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    $where
    ORDER BY sensores.id_sensor DESC");

$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho");
$trens = mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem");
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

    <main class="container px-4 mt-4">

        <!-- TÍTULO -->
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="titulo-sensores">Sensores</h3>
                <p class="subtitulo-sensores">Gerencie os sensores cadastrados na ferrovia</p>
            </div>

            <?php if ($admin) { ?>
                <button type="button" class="btn btn-navbar" data-bs-toggle="collapse" data-bs-target="#formSensor">
                    + NOVO SENSOR
                </button>
            <?php } ?>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro) { ?>
                    <div><?php echo $erro; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($admin) { ?>
        <div class="collapse mb-4 <?php if ($erros && isset($_POST['cadastrar'])) echo 'show'; ?>" id="formSensor">
            <div class="cardcadastro p-3">
                <span class="spancadastrosensor">CADASTRAR NOVO SENSOR</span>
            </div>

            <form method="POST" class="p-4 bg-white border">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">NOME DO SENSOR</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Sensor 01" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">CATEGORIA</label>
                        <select name="categoria" id="categoria" class="form-select" required>
                            <option value="">Selecione uma categoria</option>
                            <option value="TREM">Trem</option>
                            <option value="TRILHO">Trilho</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">TIPO</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            <option value="Velocidade">Velocidade</option>
                            <option value="Localização">Localização</option>
                            <option value="Temperatura">Temperatura</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" id="label-trilho">TRILHO</label>

                        <select id="select-vazio" class="form-select" disabled>
                            <option>Selecione uma categoria primeiro</option>
                        </select>

                        <select name="trilho" id="select-trilho" class="form-select" style="display: none;" required disabled>
                            <option value="">Selecione um trilho</option>
                            <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>"><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>

                        <select name="trilho" id="select-trem" class="form-select" style="display: none;" required disabled>
                            <option value="">Selecione um trem</option>
                            <?php while ($trem = mysqli_fetch_assoc($trens)) { ?>
                                <option value="<?php echo $trem['id_trem']; ?>"><?php echo htmlspecialchars($trem['nome_trem']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">STATUS</label>
                        <select name="status" class="form-select" required>
                            <option value="ATIVO">Ativo</option>
                            <option value="ALERTA">Alerta</option>
                            <option value="INATIVO">Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="cadastrar" class="btn btn-navbar">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formSensor">CANCELAR</button>
                </div>
            </form>
        </div>
        <?php } ?>

        <div class="card shadow-sm mb-4">
            <div class="p-2 fw-bold d-flex justify-content-between align-items-center" style="background-color: #1b3f53; color: #ffffff; font-size: 0.7rem;">
                SENSORES CADASTRADOS

                <form method="GET">
                    <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="ativos" <?php if ($filtro == 'ativos') echo 'selected'; ?>>Ativos</option>
                        <option value="inativos" <?php if ($filtro == 'inativos') echo 'selected'; ?>>Inativos</option>
                        <option value="todos" <?php if ($filtro == 'todos') echo 'selected'; ?>>Todos</option>
                    </select>
                </form>
            </div>

            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;">
                        <th>ID</th>
                        <th>NOME</th>
                        <th>CATEGORIA</th>
                        <th>TIPO</th>
                        <th>TRILHO / TREM</th>
                        <th>STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>

                <tbody style="font-size: 0.85rem;">
                    <?php if (mysqli_num_rows($resultado) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum sensor cadastrado.</td>
                        </tr>
                    <?php } ?>

                    <?php while ($sensor = mysqli_fetch_assoc($resultado)) { ?>
                        <?php
                        // cor da etiqueta de status
                        if ($sensor['status_sensor'] == 'ATIVO') {
                            $cor = 'bg-success';
                        } elseif ($sensor['status_sensor'] == 'ALERTA') {
                            $cor = 'bg-warning';
                        } else {
                            $cor = 'bg-secondary';
                        }
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $sensor['id_sensor']; ?></td>
                            <td><?php echo htmlspecialchars($sensor['nome_sensor']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['categoria_sensor']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['tipo_sensor']); ?></td>
                            <td><?php echo htmlspecialchars($sensor['nome_trem'] ?? $sensor['nome_trilho'] ?? '-'); ?></td>
                            <td><span class="badge <?php echo $cor; ?>"><?php echo htmlspecialchars($sensor['status_sensor']); ?></span></td>
                            <td class="text-center">
                                <?php if ($admin) { ?>
                                    <a href="editar-sensor.php?id=<?php echo $sensor['id_sensor']; ?>" class="btn btn-sm btn-outline-navbar">EDITAR</a>

                                    <?php if ($sensor['status_sensor'] != 'INATIVO') { ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#modalSensorAssociado"
                                            data-id="<?php echo $sensor['id_sensor']; ?>"
                                            data-nome="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>"
                                            data-vinculo="<?php echo htmlspecialchars(($sensor['categoria_sensor'] == 'TREM' ? 'Trem: ' : 'Trilho: ') . ($sensor['nome_trem'] ?? $sensor['nome_trilho'] ?? '-')); ?>">INATIVAR</button>
                                    <?php } ?>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </main>

    <div class="modal fade" id="modalSensorAssociado" tabindex="-1" aria-labelledby="tituloModalSensorAssociado" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-1 border-0">
                <div class="modal-header rounded-top-1 text-white py-2" style="background-color: #1b3f53; border-bottom: 3px solid #daa301;">
                    <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="tituloModalSensorAssociado">
                        <span style="color: #daa301; font-size: 1.1rem; line-height: 1;">&#9888;</span>
                        ATENÇÃO: SENSOR COM ASSOCIAÇÃO
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning rounded-1 d-flex gap-2 mb-0 py-2" role="alert" style="border-left: 4px solid #daa301;">
                        <span class="fw-bold" style="font-size: 1.2rem; line-height: 1.3;">&#9888;</span>
                        <div>
                            <strong>O sensor <span id="modalNomeSensor"></span> está ligado a:</strong>
                            <br>
                            <span id="modalVinculoSensor"></span>
                            <br>
                            <span style="font-size: 0.9rem;">Deseja inativar mesmo assim?</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="id_sensor" id="modalIdSensor">
                        <button type="submit" name="inativar" class="btn btn-sm px-3 fw-bold" style="background-color: #daa301; color: #1b3f53;">INATIVAR MESMO ASSIM</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const categoria = document.getElementById('categoria');
        const label = document.getElementById('label-trilho');
        const selectVazio = document.getElementById('select-vazio');
        const selectTrilho = document.getElementById('select-trilho');
        const selectTrem = document.getElementById('select-trem');

        function atualizarCampo() {
            const ehTrem = categoria.value === 'TREM';
            const ehTrilho = categoria.value === 'TRILHO';

            label.textContent = ehTrem ? 'TREM' : 'TRILHO';

            selectVazio.style.display = ehTrem || ehTrilho ? 'none' : '';

            selectTrilho.style.display = ehTrilho ? '' : 'none';
            selectTrilho.disabled = !ehTrilho;

            selectTrem.style.display = ehTrem ? '' : 'none';
            selectTrem.disabled = !ehTrem;
        }

        if (categoria) {
            categoria.addEventListener('change', atualizarCampo);
            atualizarCampo();
        }

        const modalSensorAssociado = document.getElementById('modalSensorAssociado');

        modalSensorAssociado.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            document.getElementById('modalIdSensor').value = botao.dataset.id;
            document.getElementById('modalNomeSensor').textContent = botao.dataset.nome;
            document.getElementById('modalVinculoSensor').textContent = botao.dataset.vinculo;
        });
    </script>
</body>

</html>
