<?php
include '../../infra/conexao.php';
include __DIR__ . '/../../infra/verifica-login.php';

// VERIFICA SE O TRILHO OU TREM ESCOLHIDO ESTÁ CADASTRADO
function referenciaExiste($conexao, $categoria, $id) {
    if ($categoria == 'TREM') {
        $sql = "SELECT 1 FROM trens WHERE id_trem = ?";
    } elseif ($categoria == 'TRILHO') {
        $sql = "SELECT 1 FROM trilhos WHERE id_trilho = ?";
    } else {
        return false;
    }

    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
}

// EXCLUIR SENSOR
if (isset($_POST['excluir'])) {
    $id_sensor = (int) $_POST['id_sensor'];

    $stmt = mysqli_prepare($conexao, "SELECT
            (id_trem IS NOT NULL) AS total_trens,
            (id_trilho IS NOT NULL) AS total_trilhos
        FROM sensores WHERE id_sensor = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_sensor);
    mysqli_stmt_execute($stmt);
    $associacoes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($associacoes && ($associacoes['total_trens'] > 0 || $associacoes['total_trilhos'] > 0)) {
        header("Location: tela-cadastro-sensores.php?bloqueado=" . $id_sensor);
        exit;
    }

    $stmt = mysqli_prepare($conexao, "DELETE FROM sensores WHERE id_sensor = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_sensor);
    mysqli_stmt_execute($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}

// CADASTRAR SENSOR
if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nome'];
    $categoria = $_POST['categoria'];
    $tipo = $_POST['tipo'];
    $vinculo = (int) ($_POST['trilho'] ?? 0);
    $status = $_POST['status'];

    if (!referenciaExiste($conexao, $categoria, $vinculo)) {
        header("Location: tela-cadastro-sensores.php?erro=1");
        exit;
    }

    $id_trem = $categoria == 'TREM' ? $vinculo : null;
    $id_trilho = $categoria == 'TRILHO' ? $vinculo : null;

    $sql = "INSERT INTO sensores (nome_sensor, categoria_sensor, tipo_sensor, id_trem, id_trilho, status_sensor)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sssiis", $nome, $categoria, $tipo, $id_trem, $id_trilho, $status);
    mysqli_stmt_execute($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}

// BUSCAR SENSORES
$resultado = mysqli_query($conexao, "SELECT sensores.*, trens.nome_trem, trilhos.nome_trilho,
        (sensores.id_trem IS NOT NULL) AS total_trens,
        (sensores.id_trilho IS NOT NULL) AS total_trilhos
    FROM sensores
    LEFT JOIN trens ON trens.id_trem = sensores.id_trem
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    ORDER BY sensores.id_sensor DESC");

$sensorBloqueado = null;

if (isset($_GET['bloqueado'])) {
    $idBloqueado = (int) $_GET['bloqueado'];

    while ($linha = mysqli_fetch_assoc($resultado)) {
        if ((int) $linha['id_sensor'] === $idBloqueado) {
            $sensorBloqueado = $linha;
            break;
        }
    }

    mysqli_data_seek($resultado, 0);
}

$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos ORDER BY nome_trilho");
$trens = mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens ORDER BY nome_trem");
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

    
    <header class="container-fluid p-2" style="background-color: #1b3f53; color: #ffffff;">
        <div id="header" class="hstack gap-3 px-2">

            <div class="d-flex" id="logo">
                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo">
                <div class="nome-sistema">
                    <h2 class="mb-0 text-white">FerroMonitor</h2>
                    <p>SISTEMA FERROVIÁRIO</p>
                </div>
            </div>

            <nav class="navbar navbar-expand-lg navbar-dark">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link text-white" href="../tela-geral-home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="tela-cadastro-sensores.php">Sensores</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../trens/tela-trens.php">Trens</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../trilhos/tela-cadastro-trilhos.php">Trilhos</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../relatorios/relatorios.php">Relatórios</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../usuarios/tela-cadastro-user.php">Usuários</a></li>
                </ul>
            </nav>

            <div>
                <button class="btn-sair" onclick="window.location.href='../../infra/logout.php'">Sair</button>
            </div>
        </div>
    </header>

    <main class="container px-4 mt-4">

        <!-- TÍTULO -->
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="titulo-sensores">Sensores</h3>
                <p class="subtitulo-sensores">Gerencie os sensores cadastrados na ferrovia</p>
            </div>

            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formSensor">
                + NOVO SENSOR
            </button>
        </div>

        <?php if (isset($_GET['erro'])) { ?>
            <div class="alert alert-danger">Selecione um trilho ou trem cadastrado no sistema.</div>
        <?php } ?>

        <div class="collapse mb-4" id="formSensor">
            <div class="cardcadastro p-3">
                <span class="spancadastrosensor">CADASTRAR NOVO SENSOR</span>
            </div>

            <form method="POST" class="p-4 bg-white border">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">NOME DO SENSOR</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Sensor 01" required>
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
                    <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#formSensor">CANCELAR</button>
                </div>
            </form>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="bg-primary-subtle text-primary-emphasis p-2 fw-bold" style="font-size: 0.7rem;">
                SENSORES CADASTRADOS
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
                                <a href="editar-sensor.php?id=<?php echo $sensor['id_sensor']; ?>" class="btn btn-sm btn-outline-primary">EDITAR</a>

                                <?php if ($sensor['total_trens'] > 0 || $sensor['total_trilhos'] > 0) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#modalSensorAssociado"
                                        data-nome="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>"
                                        data-trens="<?php echo (int) $sensor['total_trens']; ?>"
                                        data-trilhos="<?php echo (int) $sensor['total_trilhos']; ?>">X</button>
                                <?php } else { ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Deseja excluir este sensor?');">
                                        <input type="hidden" name="id_sensor" value="<?php echo $sensor['id_sensor']; ?>">
                                        <button type="submit" name="excluir" class="btn btn-sm btn-outline-danger">X</button>
                                    </form>
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
                        ATENÇÃO: EXCLUSÃO NÃO PERMITIDA
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning rounded-1 d-flex gap-2 mb-3 py-2" role="alert" style="border-left: 4px solid #daa301;">
                        <span class="fw-bold" style="font-size: 1.2rem; line-height: 1.3;">&#9888;</span>
                        <div>
                            <strong>
                                O sensor
                                <span id="modalNomeSensor"><?php echo $sensorBloqueado ? htmlspecialchars($sensorBloqueado['nome_sensor']) : ''; ?></span>
                                possui associações.
                            </strong>
                            <br>
                            <span style="font-size: 0.9rem;">Remova ou altere o trem ou trilho vinculado a ele antes de excluí-lo.</span>
                        </div>
                    </div>

                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr class="text-secondary">
                                <th class="fw-semibold">ASSOCIAÇÃO</th>
                                <th class="fw-semibold text-center" style="width: 110px;">QUANTIDADE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Trens</td>
                                <td class="text-center fw-bold" id="modalTotalTrens"><?php echo $sensorBloqueado ? (int) $sensorBloqueado['total_trens'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Trilhos</td>
                                <td class="text-center fw-bold" id="modalTotalTrilhos"><?php echo $sensorBloqueado ? (int) $sensorBloqueado['total_trilhos'] : 0; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm px-3 fw-bold" style="background-color: #daa301; color: #1b3f53;" data-bs-dismiss="modal">ENTENDI</button>
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

        categoria.addEventListener('change', atualizarCampo);
        atualizarCampo();

        const modalSensorAssociado = document.getElementById('modalSensorAssociado');

        modalSensorAssociado.addEventListener('show.bs.modal', function (evento) {
            const botao = evento.relatedTarget;

            if (!botao) {
                return;
            }

            document.getElementById('modalNomeSensor').textContent = botao.dataset.nome;
            document.getElementById('modalTotalTrens').textContent = botao.dataset.trens;
            document.getElementById('modalTotalTrilhos').textContent = botao.dataset.trilhos;
        });

        <?php if ($sensorBloqueado) { ?>
        new bootstrap.Modal(modalSensorAssociado).show();
        history.replaceState(null, '', 'tela-cadastro-sensores.php');
        <?php } ?>
    </script>
</body>

</html>