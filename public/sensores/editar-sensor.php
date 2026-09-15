<?php

include '../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_sensor'])) {
    header("Location: tela-cadastro-sensores.php");
    exit;
}

$id_sensor = isset($_POST['id_sensor'])
    ? (int) $_POST['id_sensor']
    : (int) $_GET['id'];


//    ATUALIZAR SENSOR

if (isset($_POST['editar'])) {

    $nome = $_POST['nome'];
    $categoria = $_POST['categoria'];
    $tipo = $_POST['tipo'];
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "UPDATE sensores
            SET
                nome_sensor = ?,
                categoria_sensor = ?,
                tipo_sensor = ?,
                trilho_sensor = ?,
                status_sensor = ?
            WHERE id_sensor = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar atualização: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssi",
        $nome,
        $categoria,
        $tipo,
        $trilho,
        $status,
        $id_sensor
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    BUSCAR SENSOR PARA PREENCHER O FORMULÁRIO

$sql = "SELECT * FROM sensores WHERE id_sensor = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar busca: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id_sensor);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao buscar sensor: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    header("Location: tela-cadastro-sensores.php");
    exit;
}

$sensor = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Sensor</title>

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
                    Editar Sensor
                </h3>

                <p class="subtitulo-sensores">
                    Atualize as informações do sensor selecionado
                </p>

            </div>


            <!-- BOTÃO VOLTAR -->

            <a href="tela-cadastro-sensores.php"
                class="btn btn-secondary text-white px-3 py-2">

                VOLTAR

            </a>

        </div>


        <!-- FORMULÁRIO -->

        <div class="d-flex flex-column align-items-center gap-5 w-100"
            style="padding-top: 20px; min-height: 60vh; background-color: #f8f9fa;">

            <div class="card border-0"
                style="width: 1000px;">

                <!-- CABEÇALHO DO FORM -->

                <div class="cardcadastro p-3">

                    <span class="spancadastrosensor">
                        EDITAR SENSOR #<?php echo $sensor['id_sensor']; ?>
                    </span>

                </div>

                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">

                    <input type="hidden"
                        name="id_sensor"
                        value="<?php echo $sensor['id_sensor']; ?>">


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
                                value="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>"
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

                                <option value="TREM"
                                    <?php echo $sensor['categoria_sensor'] === 'TREM' ? 'selected' : ''; ?>>
                                    Trem
                                </option>

                                <option value="TRILHO"
                                    <?php echo $sensor['categoria_sensor'] === 'TRILHO' ? 'selected' : ''; ?>>
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

                                <option value="Velocidade"
                                    <?php echo $sensor['tipo_sensor'] === 'Velocidade' ? 'selected' : ''; ?>>
                                    Velocidade
                                </option>

                                <option value="Localização"
                                    <?php echo $sensor['tipo_sensor'] === 'Localização' ? 'selected' : ''; ?>>
                                    Localização
                                </option>

                                <option value="Temperatura"
                                    <?php echo $sensor['tipo_sensor'] === 'Temperatura' ? 'selected' : ''; ?>>
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
                                value="<?php echo htmlspecialchars($sensor['trilho_sensor']); ?>"
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

                                <option value="ATIVO"
                                    <?php echo $sensor['status_sensor'] === 'ATIVO' ? 'selected' : ''; ?>>
                                    Ativo
                                </option>

                                <option value="ALERTA"
                                    <?php echo $sensor['status_sensor'] === 'ALERTA' ? 'selected' : ''; ?>>
                                    Alerta
                                </option>

                                <option value="INATIVO"
                                    <?php echo $sensor['status_sensor'] === 'INATIVO' ? 'selected' : ''; ?>>
                                    Inativo
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="mt-4 d-flex gap-2">

                        <button type="submit"
                            name="editar"
                            class="btn btn-primary">

                            SALVAR ALTERAÇÕES

                        </button>


                        <a href="tela-cadastro-sensores.php"
                            class="btn btn-secondary">

                            CANCELAR

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </main>


    <!-- BOOTSTRAP -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
