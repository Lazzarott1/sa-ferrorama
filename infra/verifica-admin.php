<?php

include __DIR__ . '/verifica-login.php';

if (!$admin) {
    $pasta_public = realpath(__DIR__ . '/../public');
    $pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

    $tela_home = ($pasta_pagina === $pasta_public) ? 'tela-geral-home.php' : '../tela-geral-home.php';

    header("Location: " . $tela_home . "?acesso=negado");
    exit();
}
