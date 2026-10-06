<?php
include '../../infra/conexao.php';

// EXCLUIR SENSOR
if (isset($_POST['excluir'])) {
    $id_sensor = (int) $_POST['id_sensor'];

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
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "INSERT INTO sensores (nome_sensor, categoria_sensor, tipo_sensor, trilho_sensor, status_sensor)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $nome, $categoria, $tipo, $trilho, $status);
    mysqli_stmt_execute($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}

// BUSCAR SENSORES
$resultado = mysqli_query($conexao, "SELECT * FROM sensores ORDER BY id_sensor DESC");
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

    <!-- HEADER -->
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

            <button class="btn-sair">Sair</button>
        </div>
    </header>

    <main class="container px-4 mt-4">

        <!-- TÍTULO -->
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="titulo-sensores">Sensores</h3>
                <p class="subtitulo-sensores">Gerencie os sensores cadastrados na ferrovia</p>
            </div>

            <button type="button" class="btn btn-navbar" data-bs-toggle="collapse" data-bs-target="#formSensor">
                + NOVO SENSOR
            </button>
        </div>

        <!-- FORMULÁRIO DE CADASTRO -->
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
                        <select name="categoria" class="form-select" required>
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
                        <label class="form-label">TRILHO</label>
                        <input type="text" name="trilho" class="form-control" placeholder="Ex: TR-01" required>
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

        <!-- TABELA DE SENSORES -->
        <div class="card shadow-sm mb-4">
            <div class="p-2 fw-bold" style="background-color: #1b3f53; color: #ffffff; font-size: 0.7rem;">
                SENSORES CADASTRADOS
            </div>

            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;">
                        <th>ID</th>
                        <th>NOME</th>
                        <th>CATEGORIA</th>
                        <th>TIPO</th>
                        <th>TRILHO</th>
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
                            <td><?php echo htmlspecialchars($sensor['trilho_sensor']); ?></td>
                            <td><span class="badge <?php echo $cor; ?>"><?php echo htmlspecialchars($sensor['status_sensor']); ?></span></td>
                            <td class="text-center">
                                <a href="editar-sensor.php?id=<?php echo $sensor['id_sensor']; ?>" class="btn btn-sm btn-outline-navbar">EDITAR</a>

                                <form method="POST" class="d-inline" onsubmit="return confirm('Deseja excluir este sensor?');">
                                    <input type="hidden" name="id_sensor" value="<?php echo $sensor['id_sensor']; ?>">
                                    <button type="submit" name="excluir" class="btn btn-sm btn-outline-danger">X</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
