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
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>dashboard/tela-dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>sensores/tela-cadastro-sensores.php">Sensores</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>trens/tela-trens.php">Trens</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>trilhos/tela-cadastro-trilhos.php">Trilhos</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>relatorios/relatorios.php">Relatórios</a></li>
                <?php if ($admin) { ?>
                    <li class="nav-item"><a class="nav-link text-white" href="<?php echo $caminho; ?>usuarios/tela-cadastro-user.php">Usuários</a></li>
                <?php } ?>
            </ul>
        </nav>

        <div class="d-flex align-items-center gap-3">
            <div class="tag-perfil <?php echo $admin ? '' : 'tag-operador'; ?>">
                <span class="tag-avatar"><?php echo strtoupper(substr($_SESSION['usuario'] ?? '?', 0, 1)); ?></span>
                <div class="tag-texto">
                    <strong><?php echo htmlspecialchars($_SESSION['usuario'] ?? ''); ?></strong>
                    <small><?php echo $admin ? 'ADMINISTRADOR' : 'OPERADOR'; ?></small>
                </div>
            </div>
            <button class="btn-sair" data-bs-toggle="modal" data-bs-target="#modalSair">Sair</button>
        </div>
    </div>
</header>

<div class="modal fade" id="modalSair" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-topo py-2">
                <h6 class="modal-title fw-bold">SAIR DO SISTEMA</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><strong>Tem certeza que deseja sair?</strong></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <a href="<?php echo $caminho; ?>../infra/validacoes-usuarios/logout.php" class="btn btn-sm btn-danger">SAIR</a>
            </div>
        </div>
    </div>
</div>
