<?php

$pasta_public = realpath(__DIR__);
$pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

$caminho = ($pasta_pagina === $pasta_public) ? '' : '../';

?>

<header class="container-fluid p-2 rounded-0" style="background-color: #1b3f53; color: #ffffff;">
    <div id="header" class="hstack gap-3 px-2">
        <div class="d-flex" id="logo">
            <img src="<?php echo $caminho; ?>../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo">
            <div class="nome-sistema">
                <h2 class="mb-0 text-white">FerroMonitor</h2>
                <p>SISTEMA FERROVIÁRIO</p>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1b3f53;">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>tela-geral-home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>sensores/tela-cadastro-sensores.php">Sensores</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>trens/tela-trens.php">Trens</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>trilhos/tela-cadastro-trilhos.php">Trilhos</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>relatorios/relatorios.php">Relatórios</a></li>
                <?php if ($admin) { ?>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>usuarios/tela-cadastro-user.php">Usuários</a></li>
                <?php } ?>
            </ul>
        </nav>

        <div>
            <button class="btn-sair" onclick="window.location.href='<?php echo $caminho; ?>../infra/validacoes-usuarios/logout.php'">Sair</button>
        </div>
    </div>
</header>
