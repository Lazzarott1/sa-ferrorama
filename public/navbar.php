<?php

$pasta_public = realpath(__DIR__);
$pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

$caminho = ($pasta_pagina === $pasta_public) ? '' : '../';
$pasta = $caminho ? basename($pasta_pagina) . '/' : 'tela-geral-home.php';

$abas = ['tela-geral-home.php' => 'Home', 'dashboard/tela-dashboard.php' => 'Dashboard', 'sensores/tela-cadastro-sensores.php' => 'Sensores', 'trens/tela-trens.php' => 'Trens', 'trilhos/tela-cadastro-trilhos.php' => 'Trilhos', 'relatorios/relatorios.php' => 'Relatórios'];
if ($admin) $abas['usuarios/tela-cadastro-user.php'] = 'Usuários';

?>

<header class="topo">
    <div class="topo-linha">
        <a href="<?php echo $caminho; ?>tela-geral-home.php" class="topo-logo">
            <img src="<?php echo $caminho; ?>../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="">
            <span><strong>FerroMonitor</strong><small>SISTEMA FERROVIÁRIO</small></span>
        </a>

        <nav class="abas" aria-label="Menu principal">
            <?php foreach ($abas as $link => $nome) { ?>
                <a href="<?php echo $caminho . $link; ?>" <?php if (str_starts_with($link, $pasta)) echo 'aria-current="page"'; ?>><?php echo $nome; ?></a>
            <?php } ?>
        </nav>

        <div class="topo-usuario">
            <div class="tag-perfil <?php echo $admin ? '' : 'tag-operador'; ?>" title="<?php echo $admin ? 'Administrador' : 'Operador'; ?>">
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

<div class="modal fade" id="modalSair" tabindex="-1" aria-labelledby="tituloSair">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-topo py-2">
                <h6 class="modal-title fw-bold" id="tituloSair">SAIR DO SISTEMA</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body"><strong>Tem certeza que deseja sair?</strong></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <a href="<?php echo $caminho; ?>../infra/validacoes-usuarios/logout.php" class="btn btn-sm btn-danger">SAIR</a>
            </div>
        </div>
    </div>
</div>
