<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    CONTADORES (CARDS)

$totalSensores = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM sensores");
$totalTrilhos  = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM trilhos");
$totalTrens    = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM trens");

if (!$totalSensores || !$totalTrilhos || !$totalTrens) {
    die("Erro ao buscar totais: " . mysqli_error($conexao));
}

$qtdSensores = mysqli_fetch_assoc($totalSensores)['total'];
$qtdTrilhos  = mysqli_fetch_assoc($totalTrilhos)['total'];
$qtdTrens    = mysqli_fetch_assoc($totalTrens)['total'];


//    RELATÓRIO CONSOLIDADO

$sql = "SELECT 'Sensor' AS categoria, nome_sensor AS nome, status_sensor AS status
            FROM sensores
        UNION ALL
        SELECT 'Trilho', nome_trilho, status_trilho
            FROM trilhos
        UNION ALL
        SELECT 'Trem', nome_trem, status_trem
            FROM trens
        ORDER BY categoria, nome";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar relatório: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <header class="container-fluid p-2 rounded-0" style="background-color: #1b3f53; color: #ffffff;">
        <div id="header" class="hstack gap-3 px-2">

            <div class="d-flex" id="logo">
                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo">
                <div class="nome-sistema">
                    <h2 class="mb-0 text-white">FerroMonitor</h2>
                    <p>SISTEMA FERROVIÁRIO</p>
                </div>
            </div>

            <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1b3f53;">
                <div class="container-fluid">
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../tela-geral-home.php">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../sensores/tela-cadastro-sensores.php">Sensores</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../trens/tela-trens.php">Trens</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../trilhos/tela-cadastro-trilhos.php">Trilhos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" aria-current="page" href="tela-relatorios.php">Relatórios</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../usuarios/tela-cadastro-user.php">Usuários</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div>
                <button class="btn-sair">Sair</button>
            </div>

        </div>
    </header>

    <main class="container-fluid px-4 mt-4">

        <div class="mb-4">
            <h3>Relatórios</h3>
            <p class="text-secondary">Resumo geral dos sensores, trilhos e trens cadastrados</p>
        </div>

        <!-- CARDS DE RESUMO -->

        <div class="row g-4 mb-4">

            <div class="col-md-4">
                <div class="card shadow-sm border-1 p-3">
                    <span class="text-secondary" style="font-size: 0.8rem;">SENSORES CADASTRADOS</span>
                    <span class="fw-bold" style="font-size: 1.8rem;"><?php echo $qtdSensores; ?></span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-1 p-3">
                    <span class="text-secondary" style="font-size: 0.8rem;">TRILHOS CADASTRADOS</span>
                    <span class="fw-bold" style="font-size: 1.8rem;"><?php echo $qtdTrilhos; ?></span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-1 p-3">
                    <span class="text-secondary" style="font-size: 0.8rem;">TRENS CADASTRADOS</span>
                    <span class="fw-bold" style="font-size: 1.8rem;"><?php echo $qtdTrens; ?></span>
                </div>
            </div>

        </div>

        <!-- TABELA CONSOLIDADA -->

        <div class="d-flex flex-column align-items-center gap-5 w-100" style="padding-bottom: 60px;">

            <div class="card shadow-sm border-1 p-0" style="width: 1000px; border-radius: 4px;">

                <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold" style="font-size: 0.7rem;">
                    RELATÓRIO GERAL
                </div>

                <table class="table table-bordered table-hover mb-0 align-middle">

                    <thead class="table-light">
                        <tr class="text-secondary" style="font-size: 0.75rem;">
                            <th class="fw-semibold">CATEGORIA</th>
                            <th class="fw-semibold">NOME</th>
                            <th class="fw-semibold">STATUS</th>
                        </tr>
                    </thead>

                    <tbody style="font-size: 0.85rem;">

                        <?php if (mysqli_num_rows($resultado) > 0) { ?>

                            <?php while ($item = mysqli_fetch_assoc($resultado)) { ?>

                                <tr>
                                    <td class="text-primary-emphasis fw-bold">
                                        <?php echo htmlspecialchars($item['categoria']); ?>
                                    </td>
                                    <td class="text-secondary">
                                        <?php echo htmlspecialchars($item['nome']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($item['status']); ?>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
