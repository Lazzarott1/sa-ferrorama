<?php

require_once __DIR__ . '/seguranca.php';

iniciar_sessao_segura();
enviar_cabecalhos_seguranca();

$tela_login = caminho_public() . 'tela-login.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . $tela_login);
    exit();
}

// Encerra a sessão depois de 30 minutos sem uso
if (isset($_SESSION['ultimo_acesso']) && time() - $_SESSION['ultimo_acesso'] > TEMPO_MAXIMO_INATIVIDADE) {
    $_SESSION = [];
    session_destroy();
    header("Location: " . $tela_login . "?expirada=1");
    exit();
}

$_SESSION['ultimo_acesso'] = time();
