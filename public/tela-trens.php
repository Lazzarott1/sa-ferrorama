<?php

include '../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    EXCLUIR TREM

if (isset($_POST['excluir'])) {

    $id_trem = (int) $_POST['id_trem'];

    $sql = "DELETE FROM trens WHERE id_trem = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar exclusão: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_trem);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao excluir trem: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-trens.php");
    exit;
}


//    CADASTRAR TREM

if (isset($_POST['cadastrar'])) {

    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $capacidade = $_POST['capacidade'];
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "INSERT INTO trens
            (
                nome_trem,
                modelo_trem,
                capacidade_trem,
                trilho_trem,
                status_trem
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar cadastro: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssiss",
        $nome,
        $modelo,
        $capacidade,
        $trilho,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao cadastrar trem: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-trens.php");
    exit;
}


//    BUSCAR TRENS

$sql = "SELECT * FROM trens ORDER BY id_trem DESC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar trens: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Trens</title>

    <link rel="stylesheet"
        href="../assets/img/style/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modelo = document.getElementById("modeloTrem");
    const label = document.getElementById("labelCapacidade");
    const input = document.getElementById("capacidadeTrem");

    modelo.addEventListener("change", function () {

        if (this.value === "De carga") {
            label.textContent = "CAPACIDADE (KG)";
            input.placeholder = "Ex: 50000";
        }
        else if (this.value === "De passageiros") {
            label.textContent = "CAPACIDADE (PASSAGEIROS)";
            input.placeholder = "Ex: 300";
        }
        else {
            label.textContent = "CAPACIDADE";
            input.placeholder = "Selecione o modelo primeiro";
        }

    });

});
</script>

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

                <h3 class="titulo-trens">
                    Trens
                </h3>

                <p class="subtitulo-trens">
                    Gerencie os trens cadastrados na malha ferroviária
                </p>

            </div>


            <!-- BOTÃO NOVO TREM -->

            <button type="button"
                class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2"
                data-bs-toggle="collapse"
                data-bs-target="#collapseCadastroTrem"
                aria-expanded="false"
                aria-controls="collapseCadastroTrem">

                <span class="botaonovotrem">
                    +
                </span>

                NOVO TREM

            </button>

        </div>


             <!-- FORMULÁRIO -->

        <div class="collapse mb-4"
            id="collapseCadastroTrem">

            <div class="card border-0">


                <!-- CABEÇALHO DO FORM -->

                <div class="cardcadastro p-3">

                    <span class="spancadastrotrem">
                        CADASTRAR NOVO TREM
                    </span>

                </div>


                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">


                    <div class="row g-4">


                        <!-- NOME -->

                        <div class="col-md-6">

                            <label for="nomeTrem"
                                class="form-label">

                                NOME DO TREM

                            </label>

                            <input type="text"
                                name="nome"
                                id="nomeTrem"
                                class="form-control"
                                placeholder="Ex: Trem 01"
                                required>

                        </div>


                        <!-- MODELO -->

                        <div class="col-md-6">

                            <label for="modeloTrem"
                                class="form-label">

                                MODELO

                            </label>

                            <select name="modelo"
                                id="modeloTrem"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione o modelo
                                </option>

                                <option value="De passageiros">
                                    De passageiros
                                </option>

                                <option value="De carga">
                                    De carga
                                </option>

                            </select>

                        </div>


                        <!-- CAPACIDADE -->

                        <div class="col-md-6">

                            <label for="capacidadeTrem"
                                id="labelCapacidade"
                                class="form-label">

                                CAPACIDADE

                            </label>

                            <input type="number"
                                name="capacidade"
                                id="capacidadeTrem"
                                class="form-control"
                                placeholder="Selecione o modelo primeiro"
                                min="0"
                                required>

                        </div>


                        <!-- TRILHO -->

                        <div class="col-md-6">

                            <label for="trilhoTrem"
                                class="form-label">

                                TRILHO ATUAL

                            </label>

                            <input type="text"
                                name="trilho"
                                id="trilhoTrem"
                                class="form-control"
                                placeholder="Ex: TR-01"
                                required>

                        </div>


                        <!-- STATUS -->

                        <div class="col-md-6">

                            <label for="statusTrem"
                                class="form-label">

                                STATUS

                            </label>

                            <select name="status"
                                id="statusTrem"
                                class="form-select"
                                required>

                                <option value="ATIVO">
                                    Ativo
                                </option>

                                <option value="MANUTENCAO">
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
                            data-bs-target="#collapseCadastroTrem">

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

                    TRENS CADASTRADOS

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
                                MODELO
                            </th>

                            <th class="fw-semibold">
                                CAPACIDADE
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


                            <?php while ($trem = mysqli_fetch_assoc($resultado)) { ?>

                                <tr>


                                    <!-- ID -->

                                    <td class="text-primary-emphasis fw-bold">

                                        <?php
                                        echo $trem['id_trem'];
                                        ?>

                                    </td>


                                    <!-- NOME -->

                                    <td class="text-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trem['nome_trem']
                                        );
                                        ?>

                                    </td>


                                    <!-- MODELO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trem['modelo_trem']
                                        );
                                        ?>

                                    </td>


                                    <!-- CAPACIDADE -->

                                    <td class="text-center">

                                        <?php
                                        echo htmlspecialchars(
                                            $trem['capacidade_trem']
                                        );
                                        ?>

                                    </td>


                                    <!-- TRILHO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $trem['trilho_trem']
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status = $trem['status_trem'];

                                        if ($status == 'ATIVO') {

                                            $classeStatus =
                                                'bg-success-subtle text-success-emphasis border-success-subtle';

                                        } elseif ($status == 'MANUTENCAO') {

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


                                    <!-- EXCLUIR -->

                                    <td class="text-center">

                                        <form method="POST"
                                            style="display: inline;"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este trem?');">

                                            <input type="hidden"
                                                name="id_trem"
                                                value="<?php echo $trem['id_trem']; ?>">

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


                            <!-- NENHUM TREM -->

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted py-4">

                                    Nenhum trem cadastrado.

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