<?php

include '../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_trilho'])) {
    header("Location: tela-cadastro-trilhos.php");
    exit;
}

$id_trilho = isset($_POST['id_trilho'])
    ? (int) $_POST['id_trilho']
    : (int) $_GET['id'];


//    ATUALIZAR TRILHO

if (isset($_POST['editar'])) {

    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $km = $_POST['km'];
    $status = $_POST['status'];

    $sql = "UPDATE trilhos
            SET
                nome_trilho = ?,
                descricao_trilho = ?,
                km_trilho = ?,
                status_trilho = ?
            WHERE id_trilho = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar atualização: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssi",
        $nome,
        $descricao,
        $km,
        $status,
        $id_trilho
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar trilho: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-trilhos.php");
    exit;
}


//    BUSCAR TRILHO PARA PREENCHER O FORMULÁRIO

$sql = "SELECT * FROM trilhos WHERE id_trilho = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar busca: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id_trilho);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao buscar trilho: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    header("Location: tela-cadastro-trilhos.php");
    exit;
}

$trilho = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Trilho</title>

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
                                    href="tela-trilhos.php">
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
                    Editar Trilho
                </h3>

                <p class="subtitulo-sensores">
                    Atualize as informações do trilho selecionado
                </p>

            </div>


            <!-- BOTÃO VOLTAR -->

            <a href="tela-trilhos.php"
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
                        EDITAR TRILHO #<?php echo $trilho['id_trilho']; ?>
                    </span>

                </div>

                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">

                    <input type="hidden"
                        name="id_trilho"
                        value="<?php echo $trilho['id_trilho']; ?>">


                    <div class="row g-4">


                        <!-- NOME -->

                        <div class="col-md-6">

                            <label for="nomeTrilho"
                                class="form-label">

                                NOME DO TRILHO

                            </label>

                            <input type="text"
                                name="nome"
                                id="nomeTrilho"
                                class="form-control"
                                value="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>"
                                placeholder="Ex: Trilho Norte 01"
                                required>

                        </div>


                        <!-- KM -->

                        <div class="col-md-6">

                            <label for="kmTrilho"
                                class="form-label">

                                KM

                            </label>

                            <input type="text"
                                name="km"
                                id="kmTrilho"
                                class="form-control"
                                value="<?php echo htmlspecialchars($trilho['km_trilho']); ?>"
                                placeholder="Ex: KM 10 - KM 25"
                                required>

                        </div>


                        <!-- DESCRIÇÃO -->

                        <div class="col-md-8">

                            <label for="descricaoTrilho"
                                class="form-label">

                                DESCRIÇÃO

                            </label>

                            <input type="text"
                                name="descricao"
                                id="descricaoTrilho"
                                class="form-control"
                                value="<?php echo htmlspecialchars($trilho['descricao_trilho']); ?>"
                                placeholder="Ex: Trecho entre os pátios A e B"
                                required>

                        </div>


                        <!-- STATUS -->

                        <div class="col-md-4">

                            <label for="statusTrilho"
                                class="form-label">

                                STATUS

                            </label>

                            <select name="status"
                                id="statusTrilho"
                                class="form-select"
                                required>

                                <option value="ATIVO"
                                    <?php echo $trilho['status_trilho'] === 'ATIVO' ? 'selected' : ''; ?>>
                                    Ativo
                                </option>

                                <option value="MANUTENÇÃO"
                                    <?php echo $trilho['status_trilho'] === 'MANUTENÇÃO' ? 'selected' : ''; ?>>
                                    Manutenção
                                </option>

                                <option value="INATIVO"
                                    <?php echo $trilho['status_trilho'] === 'INATIVO' ? 'selected' : ''; ?>>
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


                        <a href="tela-trilhos.php"
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