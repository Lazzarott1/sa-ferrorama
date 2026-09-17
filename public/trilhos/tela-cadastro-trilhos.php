<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    EXCLUIR TRILHO

if (isset($_POST['excluir'])) {

    $id_trilho = (int) $_POST['id_trilho'];

    $sql = "DELETE FROM trilhos WHERE id_trilho = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar exclusão: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_trilho);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao excluir trilho: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-trilhos.php");
    exit;
}


//    CADASTRAR TRILHO

if (isset($_POST['cadastrar'])) {

    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $km = $_POST['km'];
    $status = $_POST['status'];

    $sql = "INSERT INTO trilhos
            (
                nome_trilho,
                descricao_trilho,
                km_trilho,
                status_trilho
            )
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar cadastro: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nome,
        $descricao,
        $km,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao cadastrar trilho: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-trilhos.php");
    exit;
}


//    BUSCAR TRILHOS

$sql = "SELECT * FROM trilhos ORDER BY id_trilho DESC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar trilhos: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Trilhos</title>

    <link rel="stylesheet"
        href="../../assets/img/style/style.css">

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

                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png"
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
                                    href="../tela-geral-home.php">
                                    Home
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../tela-dashboard.php">
                                    Dashboard
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../sensores/tela-cadastro-sensores.php">
                                    Sensores
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../trens/tela-trens.php">
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
                                    href="../tela-monitoramento.php">
                                    Monitoramento
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../tela-relatorios.php">
                                    Relatórios
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../tela-cadastro-user.php">
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

                <h3 class="titulo-trilhos">
                    Trilhos
                </h3>

                <p class="subtitulo-trilhos">
                    Gerencie os trilhos cadastrados na malha ferroviária
                </p>

            </div>


            <!-- BOTÃO NOVO TRILHO -->

            <button type="button"
                class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2"
                data-bs-toggle="collapse"
                data-bs-target="#collapseCadastroTrilho"
                aria-expanded="false"
                aria-controls="collapseCadastroTrilho">

                <span class="botaonovotrilho">
                    +
                </span>

                NOVO TRILHO

            </button>

        </div>


             <!-- FORMULÁRIO -->

        <div class="collapse mb-4"
            id="collapseCadastroTrilho">

            <div class="card border-0">


                <!-- CABEÇALHO DO FORM -->

                <div class="cardcadastro p-3">

                    <span class="spancadastrotrilho">
                        CADASTRAR NOVO TRILHO
                    </span>

                </div>


                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">


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

                                <option value="ATIVO">
                                    Ativo
                                </option>

                                <option value="MANUTENÇÃO">
                                    Manutenção
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
                            data-bs-target="#collapseCadastroTrilho">

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

                    TRILHOS CADASTRADOS

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
                                DESCRIÇÃO
                            </th>

                            <th class="fw-semibold">
                                KM
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


                            <?php while ($trilho = mysqli_fetch_assoc($resultado)) { ?>

                                <tr>


                                    <!-- ID -->

                                    <td class="text-primary-emphasis fw-bold">

                                        <?php
                                        echo $trilho['id_trilho'];
                                        ?>

                                    </td>


                                    <!-- NOME -->

                                    <td class="text-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trilho['nome_trilho']
                                        );
                                        ?>

                                    </td>


                                    <!-- DESCRIÇÃO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trilho['descricao_trilho']
                                        );
                                        ?>

                                    </td>


                                    <!-- KM -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trilho['km_trilho']
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status = $trilho['status_trilho'];

                                        if ($status == 'ATIVO') {

                                            $classeStatus =
                                                'bg-success-subtle text-success-emphasis border-success-subtle';

                                        } elseif ($status == 'MANUTENÇÃO') {

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

                                        <a href="tela-editar-trilhos.php?id=<?php echo $trilho['id_trilho']; ?>"
                                            class="btn btn-sm btn-outline-primary me-1">

                                            EDITAR

                                        </a>


                                        <!-- EXCLUIR -->

                                        <form method="POST"
                                            style="display: inline;"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este trilho?');">

                                            <input type="hidden"
                                                name="id_trilho"
                                                value="<?php echo $trilho['id_trilho']; ?>">

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


                            <!-- NENHUM TRILHO -->

                            <tr>

                                <td colspan="6"
                                    class="text-center text-muted py-4">

                                    Nenhum trilho cadastrado.

                                </td>

                            </tr>


                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>


    <!-- BOOTSTRAP -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>