<?php

include '../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    EXCLUIR SENSOR

if (isset($_POST['excluir'])) {

    $id_sensor = (int) $_POST['id_sensor'];

    $sql = "DELETE FROM sensores WHERE id_sensor = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar exclusão: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_sensor);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao excluir sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    CADASTRAR SENSOR

if (isset($_POST['cadastrar'])) {

    $nome = $_POST['nome'];
    $categoria = $_POST['categoria'];
    $tipo = $_POST['tipo'];
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "INSERT INTO sensores
            (
                nome_sensor,
                categoria_sensor,
                tipo_sensor,
                trilho_sensor,
                status_sensor
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar cadastro: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $nome,
        $categoria,
        $tipo,
        $trilho,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao cadastrar sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    BUSCAR SENSORES

$sql = "SELECT * FROM sensores ORDER BY id_sensor DESC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar sensores: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Sensores</title>

    <link rel="stylesheet"
        href="../assets/img/style/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body>


         <!-- HEADER -->

    <header class="container-fluid p-2 rounded-0"
        style="background-color: #1b3f53; color: #ffffff;">

        <div id="header"
            class="hstack gap-3 px-2">


            <!-- LOGO -->

            <div class="d-flex"
                id="logo">

                <img src="../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png"
                    alt="Logo">

                <div class="nome-sistema">

                    <h2 class="mb-0 text-white">
                        FerroMonitor
                    </h2>

                    <p>
                        SISTEMA FERROVIÁRIO
                    </p>

                </div>

            </div>


            <!-- NAVBAR -->

            <nav class="navbar navbar-expand-lg navbar-dark"
                style="background-color: #1b3f53;">

                <div class="container-fluid">

                    <div class="collapse navbar-collapse"
                        id="navbarNav">

                        <ul class="navbar-nav">

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-geral-home.php">
                                    Home
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-dashboard.php">
                                    Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-cadastro-sensores.php">
                                    Sensores
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-trens.php">
                                    Trens
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-cadastro-trilhos.php">
                                    Trilhos
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-monitoramento.php">
                                    Monitoramento
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-relatorios.php">
                                    Relatórios
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="tela-cadastro-user.php">
                                    Usuários
                                </a>
                            </li>

                        </ul>

                    </div>

                </div>

            </nav>


            <!-- SAIR -->

            <div>

                <button class="btn-sair">
                    Sair
                </button>

            </div>

        </div>

    </header>


         <!-- CONTEÚDO PRINCIPAL -->

    <main class="container-fluid px-4 mt-4">


        <!-- TÍTULO -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>

                <h3 class="titulo-sensores">
                    Sensores
                </h3>

                <p class="subtitulo-sensores">
                    Gerencie os sensores cadastrados na rodovia
                </p>

            </div>


            <!-- BOTÃO NOVO SENSOR -->

            <button type="button"
                class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2"
                data-bs-toggle="collapse"
                data-bs-target="#collapseCadastroSensor"
                aria-expanded="false"
                aria-controls="collapseCadastroSensor">

                <span class="botaonovosensor">
                    +
                </span>

                NOVO SENSOR

            </button>

        </div>


             <!-- FORMULÁRIO -->

        <div class="collapse mb-4"
            id="collapseCadastroSensor">

            <div class="card border-0">


                <!-- CABEÇALHO DO FORM -->

                <div class="cardcadastro p-3">

                    <span class="spancadastrosensor">
                        CADASTRAR NOVO SENSOR
                    </span>

                </div>


                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">


                    <div class="row g-4">


                        <!-- NOME -->

                        <div class="col-md-6">

                            <label for="nomeSensor"
                                class="form-label">

                                NOME DO SENSOR

                            </label>

                            <input type="text"
                                name="nome"
                                id="nomeSensor"
                                class="form-control"
                                placeholder="Ex: Sensor 01"
                                required>

                        </div>


                        <!-- CATEGORIA -->

                        <div class="col-md-6">

                            <label for="categoriaSensor"
                                class="form-label">

                                CATEGORIA

                            </label>

                            <select name="categoria"
                                id="categoriaSensor"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione uma categoria
                                </option>

                                <option value="TREM">
                                    Trem
                                </option>

                                <option value="TRILHO">
                                    Trilho
                                </option>

                            </select>

                        </div>


                        <!-- TIPO -->

                        <div class="col-md-6">

                            <label for="tipoSensor"
                                class="form-label">

                                TIPO

                            </label>

                            <select name="tipo"
                                id="tipoSensor"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione o tipo
                                </option>

                                <option value="Velocidade">
                                    Velocidade
                                </option>

                                <option value="Localização">
                                    Localização
                                </option>

                                <option value="Temperatura">
                                    Temperatura
                                </option>

                            </select>

                        </div>


                        <!-- TRILHO -->

                        <div class="col-md-6">

                            <label for="trilhoSensor"
                                class="form-label">

                                TRILHO

                            </label>

                            <input type="text"
                                name="trilho"
                                id="trilhoSensor"
                                class="form-control"
                                placeholder="Ex: TR-01"
                                required>

                        </div>


                        <!-- STATUS -->

                        <div class="col-md-6">

                            <label for="statusSensor"
                                class="form-label">

                                STATUS

                            </label>

                            <select name="status"
                                id="statusSensor"
                                class="form-select"
                                required>

                                <option value="ATIVO">
                                    Ativo
                                </option>

                                <option value="ALERTA">
                                    Alerta
                                </option>

                                <option value="INATIVO">
                                    Inativo
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="mt-4 d-flex gap-2">

                        <button type="submit"
                            name="cadastrar"
                            class="btn btn-primary">

                            CADASTRAR

                        </button>


                        <button type="button"
                            class="btn btn-secondary"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseCadastroSensor">

                            CANCELAR

                        </button>

                    </div>

                </form>

            </div>

        </div>


             <!-- TABELA -->

        <div class="d-flex flex-column align-items-center gap-5 w-100"
            style="padding-top: 60px; min-height: 100vh; background-color: #f8f9fa;">


            <div class="card shadow-sm border-1 p-0"
                style="width: 1000px; border-radius: 4px;">


                <!-- TÍTULO DA TABELA -->

                <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
                    style="font-size: 0.7rem;">

                    SENSORES CADASTRADOS

                </div>


                <!-- TABELA -->

                <table class="table table-bordered table-hover mb-0 align-middle">

                    <thead class="table-light">

                        <tr class="text-secondary"
                            style="font-size: 0.75rem;">

                            <th class="fw-semibold">
                                ID
                            </th>

                            <th class="fw-semibold">
                                NOME
                            </th>

                            <th class="fw-semibold">
                                CATEGORIA
                            </th>

                            <th class="fw-semibold">
                                TIPO
                            </th>

                            <th class="fw-semibold">
                                TRILHO
                            </th>

                            <th class="fw-semibold">
                                STATUS
                            </th>

                            <th class="fw-semibold text-center">
                                AÇÕES
                            </th>

                        </tr>

                    </thead>


                    <tbody style="font-size: 0.85rem;">


                        <?php if (mysqli_num_rows($resultado) > 0) { ?>


                            <?php while ($sensor = mysqli_fetch_assoc($resultado)) { ?>

                                <tr>


                                    <!-- ID -->

                                    <td class="text-primary-emphasis fw-bold">

                                        <?php
                                        echo $sensor['id_sensor'];
                                        ?>

                                    </td>


                                    <!-- NOME -->

                                    <td class="text-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $sensor['nome_sensor']
                                        );
                                        ?>

                                    </td>


                                    <!-- CATEGORIA -->

                                    <td class="text-center">

                                        <span class="badge border border-info-subtle
                                            bg-info-subtle text-info-emphasis rounded-1">

                                            <?php
                                            echo htmlspecialchars(
                                                $sensor['categoria_sensor']
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- TIPO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $sensor['tipo_sensor']
                                        );
                                        ?>

                                    </td>


                                    <!-- TRILHO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $sensor['trilho_sensor']
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status = $sensor['status_sensor'];

                                        if ($status == 'ATIVO') {

                                            $classeStatus =
                                                'bg-success-subtle text-success-emphasis border-success-subtle';

                                        } elseif ($status == 'ALERTA') {

                                            $classeStatus =
                                                'bg-warning-subtle text-warning-emphasis border-warning-subtle';

                                        } else {

                                            $classeStatus =
                                                'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle';

                                        }

                                        ?>

                                        <span class="badge border rounded-1
                                            <?php echo $classeStatus; ?>">

                                            <?php
                                            echo htmlspecialchars($status);
                                            ?>

                                        </span>

                                    </td>


                                    <!-- AÇÕES -->

                                    <td class="text-center">

                                        <!-- EDITAR -->

                                        <a href="editar-sensor.php?id=<?php echo $sensor['id_sensor']; ?>"
                                            class="btn btn-sm btn-outline-primary me-1">

                                            EDITAR

                                        </a>


                                        <!-- EXCLUIR -->

                                        <form method="POST"
                                            style="display: inline;"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este sensor?');">

                                            <input type="hidden"
                                                name="id_sensor"
                                                value="<?php echo $sensor['id_sensor']; ?>">

                                            <button type="submit"
                                                name="excluir"
                                                class="btn btn-sm btn-outline-danger">

                                                X

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php } ?>


                        <?php } else { ?>


                            <!-- NENHUM SENSOR -->

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted py-4">

                                    Nenhum sensor cadastrado.

                                </td>

                            </tr>


                        <?php } ?>

                    </body>

                </table>

            </div>

        </div>

    </main>


    <!-- BOOTSTRAP -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>