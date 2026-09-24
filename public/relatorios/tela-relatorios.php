<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}

$msgErro = '';


//    EXCLUIR RELATÓRIO

if (isset($_POST['excluir'])) {

    $id_relatorio = (int) $_POST['id_relatorio'];

    $sql = "DELETE FROM relatorios WHERE id_relatorio = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar exclusão: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_relatorio);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao excluir relatório: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-relatorios.php");
    exit;
}


//    GERAR RELATÓRIO

if (isset($_POST['gerar'])) {

    $titulo = trim($_POST['titulo']);
    $tipo = $_POST['tipo'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $id_trem = !empty($_POST['id_trem']) ? (int) $_POST['id_trem'] : null;
    $id_trilho = !empty($_POST['id_trilho']) ? (int) $_POST['id_trilho'] : null;

    if ($titulo === '' || $data_inicio === '' || $data_fim === '') {

        $msgErro = "Preencha o título e o período do relatório.";

    } elseif ($data_fim < $data_inicio) {

        $msgErro = "A data fim não pode ser anterior à data início.";

    } else {

        $sql = "INSERT INTO relatorios
                (
                    titulo_relatorio,
                    tipo_relatorio,
                    data_inicio,
                    data_fim,
                    id_trem,
                    id_trilho,
                    status_relatorio
                )
                VALUES (?, ?, ?, ?, ?, ?, 'PRONTO')";

        $stmt = mysqli_prepare($conexao, $sql);

        if (!$stmt) {
            die("Erro ao preparar relatório: " . mysqli_error($conexao));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssii",
            $titulo,
            $tipo,
            $data_inicio,
            $data_fim,
            $id_trem,
            $id_trilho
        );

        if (!mysqli_stmt_execute($stmt)) {
            die("Erro ao gerar relatório: " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);

        header("Location: tela-relatorios.php");
        exit;
    }
}


//    TIPOS DE RELATÓRIO

$tipos = [
    'Geral',
    'Velocidade',
    'Temperatura',
    'Localização',
    'Consumo de Energia',
    'Falhas'
];


//    BUSCAR RELATÓRIOS (COM FILTRO POR TIPO)

$filtroTipo = $_GET['tipo'] ?? '';

$sql = "SELECT r.*, t.nome_trem, tr.nome_trilho
        FROM relatorios r
        LEFT JOIN trens t ON t.id_trem = r.id_trem
        LEFT JOIN trilhos tr ON tr.id_trilho = r.id_trilho";

if (in_array($filtroTipo, $tipos, true)) {

    $stmt = mysqli_prepare($conexao, $sql . " WHERE r.tipo_relatorio = ? ORDER BY r.gerado_em DESC");
    mysqli_stmt_bind_param($stmt, "s", $filtroTipo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

} else {

    $filtroTipo = '';
    $resultado = mysqli_query($conexao, $sql . " ORDER BY r.gerado_em DESC");
}

if (!$resultado) {
    die("Erro ao buscar relatórios: " . mysqli_error($conexao));
}


//    BUSCAR TRENS E TRILHOS PARA OS FILTROS DO FORMULÁRIO

$trens = mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens ORDER BY nome_trem");
$trilhos = mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos ORDER BY nome_trilho");

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Relatórios</title>

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
                                <a class="nav-link active"
                                    href="tela-relatorios.php">
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

                <h3 class="titulo-relatorios">
                    Relatórios
                </h3>

                <p class="subtitulo-relatorios">
                    Gere e visualize análises da operação ferroviária
                </p>

            </div>


            <!-- BOTÃO NOVO RELATÓRIO -->

            <button type="button"
                class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2"
                data-bs-toggle="collapse"
                data-bs-target="#collapseGerarRelatorio"
                aria-expanded="false"
                aria-controls="collapseGerarRelatorio">

                <span class="botaonovorelatorio">
                    +
                </span>

                NOVO RELATÓRIO

            </button>

        </div>


        <!-- MENSAGEM DE ERRO -->

        <?php if ($msgErro) { ?>

            <div class="alert alert-danger py-2">
                <?php echo htmlspecialchars($msgErro); ?>
            </div>

        <?php } ?>


             <!-- FORMULÁRIO -->

        <div class="collapse mb-4 <?php echo $msgErro ? 'show' : ''; ?>"
            id="collapseGerarRelatorio">

            <div class="card border-0">


                <!-- CABEÇALHO DO FORM -->

                <div class="cardcadastro p-3">

                    <span class="spancadastrorelatorio">
                        GERAR NOVO RELATÓRIO
                    </span>

                </div>


                <form method="POST"
                    class="p-4 bg-white"
                    style="border: 1px solid #BCCCDC; border-top: none;">


                    <div class="row g-4">


                        <!-- TÍTULO -->

                        <div class="col-md-6">

                            <label for="tituloRelatorio"
                                class="form-label">

                                TÍTULO

                            </label>

                            <input type="text"
                                name="titulo"
                                id="tituloRelatorio"
                                class="form-control"
                                placeholder="Ex: Relatório Semanal"
                                required>

                        </div>


                        <!-- DATA INÍCIO -->

                        <div class="col-md-3">

                            <label for="dataInicio"
                                class="form-label">

                                DATA INÍCIO

                            </label>

                            <input type="date"
                                name="data_inicio"
                                id="dataInicio"
                                class="form-control"
                                required>

                        </div>


                        <!-- DATA FIM -->

                        <div class="col-md-3">

                            <label for="dataFim"
                                class="form-label">

                                DATA FIM

                            </label>

                            <input type="date"
                                name="data_fim"
                                id="dataFim"
                                class="form-control"
                                required>

                        </div>


                        <!-- TIPO -->

                        <div class="col-md-4">

                            <label for="tipoRelatorio"
                                class="form-label">

                                TIPO DE DADO

                            </label>

                            <select name="tipo"
                                id="tipoRelatorio"
                                class="form-select"
                                required>

                                <?php foreach ($tipos as $tipo) { ?>

                                    <option value="<?php echo htmlspecialchars($tipo); ?>">
                                        <?php echo htmlspecialchars($tipo); ?>
                                    </option>

                                <?php } ?>

                            </select>

                        </div>


                        <!-- TREM -->

                        <div class="col-md-4">

                            <label for="tremRelatorio"
                                class="form-label">

                                TREM (OPCIONAL)

                            </label>

                            <select name="id_trem"
                                id="tremRelatorio"
                                class="form-select">

                                <option value="">
                                    Todos os trens
                                </option>

                                <?php while ($trem = mysqli_fetch_assoc($trens)) { ?>

                                    <option value="<?php echo $trem['id_trem']; ?>">
                                        <?php echo htmlspecialchars($trem['nome_trem']); ?>
                                    </option>

                                <?php } ?>

                            </select>

                        </div>


                        <!-- TRILHO -->

                        <div class="col-md-4">

                            <label for="trilhoRelatorio"
                                class="form-label">

                                TRILHO (OPCIONAL)

                            </label>

                            <select name="id_trilho"
                                id="trilhoRelatorio"
                                class="form-select">

                                <option value="">
                                    Todos os trilhos
                                </option>

                                <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>

                                    <option value="<?php echo $trilho['id_trilho']; ?>">
                                        <?php echo htmlspecialchars($trilho['nome_trilho']); ?>
                                    </option>

                                <?php } ?>

                            </select>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="mt-4 d-flex gap-2">

                        <button type="submit"
                            name="gerar"
                            class="btn btn-primary">

                            GERAR

                        </button>


                        <button type="button"
                            class="btn btn-secondary"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseGerarRelatorio">

                            CANCELAR

                        </button>

                    </div>

                </form>

            </div>

        </div>


             <!-- TABELA -->

        <div class="d-flex flex-column align-items-center gap-3 w-100"
            style="padding-top: 30px; min-height: 100vh; background-color: #f8f9fa;">


            <!-- FILTRO POR TIPO -->

            <form method="GET"
                class="d-flex align-items-center gap-2"
                style="width: 1000px;">

                <label for="filtroTipo"
                    class="form-label mb-0 fw-semibold text-secondary"
                    style="font-size: 0.75rem;">

                    FILTRAR POR TIPO:

                </label>

                <select name="tipo"
                    id="filtroTipo"
                    class="form-select form-select-sm"
                    style="width: auto;"
                    onchange="this.form.submit()">

                    <option value="">
                        Todos
                    </option>

                    <?php foreach ($tipos as $tipo) { ?>

                        <option value="<?php echo htmlspecialchars($tipo); ?>"
                            <?php echo $filtroTipo === $tipo ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo); ?>
                        </option>

                    <?php } ?>

                </select>

                <span class="text-muted small">
                    <?php echo mysqli_num_rows($resultado); ?> relatório(s)
                </span>

            </form>


            <div class="card shadow-sm border-1 p-0"
                style="width: 1000px; border-radius: 4px;">


                <!-- TÍTULO DA TABELA -->

                <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold"
                    style="font-size: 0.7rem;">

                    RELATÓRIOS GERADOS

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
                                TÍTULO
                            </th>

                            <th class="fw-semibold">
                                TIPO
                            </th>

                            <th class="fw-semibold">
                                PERÍODO
                            </th>

                            <th class="fw-semibold">
                                TREM / TRILHO
                            </th>

                            <th class="fw-semibold">
                                STATUS
                            </th>

                            <th class="fw-semibold">
                                GERADO EM
                            </th>

                            <th class="fw-semibold text-center">
                                AÇÕES
                            </th>

                        </tr>

                    </thead>


                    <tbody style="font-size: 0.85rem;">


                        <?php if (mysqli_num_rows($resultado) > 0) { ?>


                            <?php while ($relatorio = mysqli_fetch_assoc($resultado)) { ?>

                                <tr>


                                    <!-- ID -->

                                    <td class="text-primary-emphasis fw-bold">

                                        <?php
                                        echo $relatorio['id_relatorio'];
                                        ?>

                                    </td>


                                    <!-- TÍTULO -->

                                    <td class="text-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $relatorio['titulo_relatorio']
                                        );
                                        ?>

                                    </td>


                                    <!-- TIPO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            $relatorio['tipo_relatorio']
                                        );
                                        ?>

                                    </td>


                                    <!-- PERÍODO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo date('d/m/Y', strtotime($relatorio['data_inicio']))
                                            . ' – '
                                            . date('d/m/Y', strtotime($relatorio['data_fim']));
                                        ?>

                                    </td>


                                    <!-- TREM / TRILHO -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo htmlspecialchars(
                                            ($relatorio['nome_trem'] ?? 'Todos os trens')
                                            . ' / '
                                            . ($relatorio['nome_trilho'] ?? 'Todos os trilhos')
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status = $relatorio['status_relatorio'];

                                        if ($status == 'PRONTO') {

                                            $classeStatus =
                                                'bg-success-subtle text-success-emphasis border-success-subtle';

                                        } elseif ($status == 'PROCESSANDO') {

                                            $classeStatus =
                                                'bg-warning-subtle text-warning-emphasis border-warning-subtle';

                                        } else {

                                            $classeStatus =
                                                'bg-danger-subtle text-danger-emphasis border-danger-subtle';

                                        }

                                        ?>

                                        <span class="badge border rounded-1
                                            <?php echo $classeStatus; ?>">

                                            <?php
                                            echo htmlspecialchars($status);
                                            ?>

                                        </span>

                                    </td>


                                    <!-- GERADO EM -->

                                    <td class="text-body-tertiary">

                                        <?php
                                        echo date('d/m/Y H:i', strtotime($relatorio['gerado_em']));
                                        ?>

                                    </td>


                                    <!-- EXCLUIR -->

                                    <td class="text-center">

                                        <form method="POST"
                                            style="display: inline;"
                                            onsubmit="return confirm('Tem certeza que deseja excluir este relatório?');">

                                            <input type="hidden"
                                                name="id_relatorio"
                                                value="<?php echo $relatorio['id_relatorio']; ?>">

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


                            <!-- NENHUM RELATÓRIO -->

                            <tr>

                                <td colspan="8"
                                    class="text-center text-muted py-4">

                                    Nenhum relatório gerado ainda.

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
