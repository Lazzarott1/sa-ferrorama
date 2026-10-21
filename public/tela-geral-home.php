<?php
// Só entra quem fez login
include __DIR__ . '/../infra/verifica-login.php';

$nome_usuario = $_SESSION['usuario'];

date_default_timezone_set('America/Sao_Paulo');

$meses = [
    1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
    'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
];

$data_hoje = 'Dia ' . date('d') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/navbar.php'; ?>

    <main>

        <div class="container-fluid mt-4 p-0 px-4">
            <h1 class="text-start">Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?>!</h1>
            <p class="text-start"><?php echo $data_hoje; ?></p>

            <?php if (isset($_GET['acesso'])) { ?>
                <div class="alert alert-warning rounded-1" style="border-left: 4px solid #daa301;">
                    Você não tem permissão para acessar essa tela.
                </div>
            <?php } ?>
        </div>

    </main>

</body>

</html>