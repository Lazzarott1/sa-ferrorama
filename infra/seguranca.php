<?php

// Funções de segurança usadas pelas telas do sistema.

// Erros técnicos vão para o log do servidor, nunca para a tela do usuário
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

const TEMPO_MAXIMO_INATIVIDADE = 1800; // 30 minutos

function iniciar_sessao_segura(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,      // JavaScript não lê o cookie de sessão
        'samesite' => 'Strict',  // cookie não vai em requisições de outros sites
    ]);

    session_start();
}

function enviar_cabecalhos_seguranca(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Escapa texto antes de exibir no HTML (proteção contra XSS)
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// ---------- CSRF ----------

function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="token_csrf" value="' . e(token_csrf()) . '">';
}

function csrf_valido(): bool
{
    $enviado = $_POST['token_csrf'] ?? '';

    return is_string($enviado)
        && !empty($_SESSION['token_csrf'])
        && hash_equals($_SESSION['token_csrf'], $enviado);
}

// ---------- PERMISSÕES ----------

function usuario_eh_admin(): bool
{
    return ($_SESSION['perfil_usuario'] ?? '') === 'ADMIN';
}

function exigir_admin(): void
{
    if (usuario_eh_admin()) {
        return;
    }

    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Acesso negado</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
        . '<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">'
        . '<div class="card p-4 text-center shadow-sm" style="max-width:420px">'
        . '<h1 class="h4 text-danger">Acesso negado</h1>'
        . '<p class="text-secondary mb-3">Esta área é restrita a administradores.</p>'
        . '<a class="btn btn-primary" style="background-color:#1b3f53;border:none" href="' . e(caminho_public() . 'tela-geral-home.php') . '">Voltar para a Home</a>'
        . '</div></body></html>';
    exit();
}

// Caminho relativo da página atual até a pasta public/
function caminho_public(): string
{
    $pasta_public = realpath(__DIR__ . '/../public');
    $pasta_pagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

    return ($pasta_pagina === $pasta_public) ? '' : '../';
}
