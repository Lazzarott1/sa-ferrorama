<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_trem'])) {
    header("Location: tela-trens.php");
    exit;
}

$id_trem = isset($_POST['id_trem'])
    ? (int) $_POST['id_trem']
    : (int) $_GET['id'];


//    ATUALIZAR TREM

if (isset($_POST['editar'])) {

    $nome = trim($_POST['nome'] ?? '');
    $modelo = $_POST['modelo'] ?? '';
    $capacidade = (int) ($_POST['capacidade'] ?? 0);
    $id_trilho = (int) ($_POST['id_trilho'] ?? 0);
    $status = $_POST['status'] ?? '';

    $modelosValidos = ['De passageiros', 'De carga'];
    $statusValidos = ['ATIVO', 'MANUTENCAO', 'INATIVO'];

    if (
        $nome === '' ||
        !in_array($modelo, $modelosValidos, true) ||
        $capacidade <= 0 ||
        $id_trilho <= 0 ||
        !in_array($status, $statusValidos, true)
    ) {
        die("Erro: preencha todos os campos corretamente.");
    }

    $sql = "UPDATE trens
            SET
                nome_trem = ?,
                modelo_trem = ?,
                capacidade_trem = ?,
                id_trilho = ?,
                status_trem = ?
            WHERE id_trem = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar atualização: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssiisi",
        $nome,
        $modelo,
        $capacidade,
        $id_trilho,
        $status,
        $id_trem
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar trem: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-trens.php");
    exit;
}


//    BUSCAR TREM PARA PREENCHER O FORMULÁRIO

$sql = "SELECT * FROM trens WHERE id_trem = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar busca: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id_trem);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao buscar trem: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    header("Location: tela-trens.php");
    exit;
}

$trem = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);


//    BUSCAR TRILHOS PARA A LISTA DO FORMULÁRIO

$sql = "SELECT id_trilho, nome_trilho, status_trilho
        FROM trilhos
        ORDER BY nome_trilho";

$resultadoTrilhos = mysqli_query($conexao, $sql);

if (!$resultadoTrilhos) {
    die("Erro ao buscar trilhos: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Trem</title>

    <link rel="stylesheet"
        href="../../assets/img/style/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modelo = document.getElementById("modeloTrem");
    const label = document.getElementById("labelCapacidade");
    const input = document.getElementById("capacidadeTrem");

    function atualizarCapacidade() {

        if (modelo.value === "De carga") {
            label.textContent = "CAPACIDADE (KG)";
            input.placeholder = "Ex: 50000";
        }
        else if (modelo.value === "De passageiros") {
            label.textContent = "CAPACIDADE (PASSAGEIROS)";
            input.placeholder = "Ex: 300";
        }
        else {
            label.textContent = "CAPACIDADE";
            input.placeholder = "Selecione o modelo primeiro";
        }

    }

    modelo.addEventListener("change", atualizarCapacidade);

    atualizarCapacidade();

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
                                    href="#">
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
                                    href="tela-trens.php">
                                    Trens
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../trilhos/tela-cadastro-trilhos.php">
                                    Trilhos
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../monitoramento/tela-monitoramento.php">
                                    Monitoramento
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../relatorios/relatorios.php">
                                    Relatórios
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link text-white"
                                    href="../usuarios/tela-cadastro-user.php">
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
                    Editar trem
                </h3>

                <p class="subtitulo-trens">
                    Altere os dados do trem selecionado
                </p>

            </div>

        </div>


             <!-- FORMULÁRIO -->

        <div class="card border-0 mb-4">


            <!-- CABEÇALHO DO FORM -->

            <div class="cardcadastro p-3">

                <span class="spancadastrotrem">
                    EDITAR TREM #<?php echo $trem['id_trem']; ?>
                </span>

            </div>


            <form method="POST"
                class="p-4 bg-white"
                style="border: 1px solid #BCCCDC; border-top: none;">

                <input type="hidden"
                    name="id_trem"
                    value="<?php echo $trem['id_trem']; ?>">


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
                            value="<?php echo htmlspecialchars($trem['nome_trem']); ?>"
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

                            <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>

                                <option value="<?php echo $opcao; ?>"
                                    <?php if ($trem['modelo_trem'] === $opcao) echo 'selected'; ?>>
                                    <?php echo $opcao; ?>
                                </option>

                            <?php } ?>

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
                            value="<?php echo (int) $trem['capacidade_trem']; ?>"
                            min="1"
                            required>

                    </div>


                    <!-- TRILHO -->

                    <div class="col-md-6">

                        <label for="trilhoTrem"
                            class="form-label">

                            TRILHO ATUAL

                        </label>

                        <select name="id_trilho"
                            id="trilhoTrem"
                            class="form-select"
                            required>

                            <option value="">
                                Selecione o trilho
                            </option>

                            <?php while ($trilho = mysqli_fetch_assoc($resultadoTrilhos)) { ?>

                                <option value="<?php echo $trilho['id_trilho']; ?>"
                                    <?php if ((int) $trem['id_trilho'] === (int) $trilho['id_trilho']) echo 'selected'; ?>>
                                    <?php
                                    echo htmlspecialchars($trilho['nome_trilho']);

                                    if ($trilho['status_trilho'] !== 'ATIVO') {
                                        echo ' (' . htmlspecialchars($trilho['status_trilho']) . ')';
                                    }
                                    ?>
                                </option>

                            <?php } ?>

                        </select>

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

                            <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>

                                <option value="<?php echo $valor; ?>"
                                    <?php if ($trem['status_trem'] === $valor) echo 'selected'; ?>>
                                    <?php echo $texto; ?>
                                </option>

                            <?php } ?>

                        </select>

                    </div>

                </div>


                <!-- BOTÕES -->

                <div class="mt-4 d-flex gap-2">

                    <button type="submit"
                        name="editar"
                        class="btn btn-primary">

                        SALVAR

                    </button>


                    <a href="tela-trens.php"
                        class="btn btn-secondary">

                        CANCELAR

                    </a>

                </div>

            </form>

        </div>

    </main>


    <!-- BOOTSTRAP -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
