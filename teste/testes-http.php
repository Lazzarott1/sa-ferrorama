<?php

// Testes de ponta a ponta (HTTP) das telas de usuários.
// Fazem requisições reais às páginas, como o navegador ou um atacante faria.
//
// Como rodar:
//   1) Suba o servidor apontando para o banco de testes:
//        DB_NAME=sa_ferrorama_http php -S localhost:8080
//   2) Em outro terminal, na pasta do projeto:
//        php teste/testes-http.php http://localhost:8080
//
// O script recria o banco sa_ferrorama_http com um administrador e um funcionário.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const BANCO_HTTP = 'sa_ferrorama_http';

$url_base = rtrim($argv[1] ?? 'http://localhost:8080', '/') . '/public';

// ---------- prepara o banco ----------

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servidor = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '');
$script = str_replace('sa_teste', BANCO_HTTP, file_get_contents(__DIR__ . '/../database/db_sa.sql'));
$script = substr($script, 0, strpos($script, 'INSERT INTO usuarios'));
$servidor->query('DROP DATABASE IF EXISTS ' . BANCO_HTTP);
$servidor->multi_query($script);
while ($servidor->more_results()) {
    $servidor->next_result();
}
$servidor->close();

putenv('DB_NAME=' . BANCO_HTTP);
include __DIR__ . '/../infra/conexao.php';
require_once __DIR__ . '/../infra/usuarios.php';

cadastrar_usuario($conexao, ['nome_usuario' => 'admin.teste', 'email_usuario' => 'admin@teste.com', 'senha' => 'Admin2026x', 'perfil_usuario' => 'ADMIN']);
cadastrar_usuario($conexao, ['nome_usuario' => 'func.teste', 'email_usuario' => 'func@teste.com', 'senha' => 'Turno2026x', 'perfil_usuario' => 'FUNCIONARIO']);

// ---------- cliente HTTP simples ----------

function requisicao(string $url, ?array $post = null, ?string $cookies = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => $cookies ?? '',
        CURLOPT_COOKIEJAR => $cookies ?? '',
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $resposta = curl_exec($ch);
    $tamanho_cabecalho = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'cabecalhos' => substr($resposta, 0, $tamanho_cabecalho),
        'corpo' => substr($resposta, $tamanho_cabecalho),
    ];
}

function token_da_pagina(string $html): string
{
    preg_match('/name="token_csrf" value="([a-f0-9]+)"/', $html, $m);
    return $m[1] ?? '';
}

function id_sessao(string $arquivo_cookies): string
{
    preg_match('/PHPSESSID\s+(\S+)/', (string) @file_get_contents($arquivo_cookies), $m);
    return $m[1] ?? '';
}

function login(string $url_base, string $nome, string $senha): string
{
    $cookies = tempnam(sys_get_temp_dir(), 'ck');
    requisicao($url_base . '/tela-login.php', null, $cookies);
    requisicao($url_base . '/tela-login.php', ['nome_usuario' => $nome, 'senha' => $senha, 'ajax' => '1'], $cookies);
    return $cookies;
}

$total = 0;
$falhas = 0;

function teste(string $codigo, string $descricao, bool $passou, string $detalhe = ''): void
{
    global $total, $falhas;
    $total++;
    if (!$passou) {
        $falhas++;
    }
    echo ($passou ? '[PASSOU] ' : '[FALHOU] ') . $codigo . ' - ' . $descricao . ($detalhe !== '' ? '  (' . $detalhe . ')' : '') . PHP_EOL;
}

echo 'Servidor testado: ' . $url_base . PHP_EOL . PHP_EOL;

// ---------- acesso e sessão ----------

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php');
teste('H01', 'Sem login, a tela de usuários redireciona para o login', $r['status'] === 302 && str_contains($r['cabecalhos'], 'tela-login.php'), 'HTTP ' . $r['status']);

$cookies = tempnam(sys_get_temp_dir(), 'ck');
$r = requisicao($url_base . '/tela-login.php', null, $cookies);
teste('H02', 'Cabeçalhos de segurança presentes (X-Frame-Options e nosniff)', stripos($r['cabecalhos'], 'X-Frame-Options: DENY') !== false && stripos($r['cabecalhos'], 'X-Content-Type-Options: nosniff') !== false);
teste('H03', 'Cookie de sessão com HttpOnly e SameSite=Strict', stripos($r['cabecalhos'], 'HttpOnly') !== false && stripos($r['cabecalhos'], 'SameSite=Strict') !== false);

$sessao_antes = id_sessao($cookies);
$r = requisicao($url_base . '/tela-login.php', ['nome_usuario' => 'admin.teste', 'senha' => 'Admin2026x', 'ajax' => '1'], $cookies);
teste('H04', 'Login do administrador pela tela', str_contains($r['corpo'], '"sucesso":true'));
teste('H05', 'ID da sessão muda após o login (fixação de sessão)', $sessao_antes !== '' && $sessao_antes !== id_sessao($cookies));

$r = requisicao($url_base . '/tela-login.php', ['nome_usuario' => "' OR '1'='1", 'senha' => "' OR '1'='1", 'ajax' => '1']);
teste('H06', "SQL Injection no login (' OR '1'='1) é recusado", str_contains($r['corpo'], '"sucesso":false'), trim($r['corpo']));

// ---------- CRUD pelo administrador ----------

$pagina = requisicao($url_base . '/usuarios/tela-cadastro-user.php', null, $cookies);
$token = token_da_pagina($pagina['corpo']);
teste('H07', 'Administrador acessa a tela de usuários', $pagina['status'] === 200 && $token !== '');

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', ['email_usuario' => 'semtoken@teste.com', 'nome_usuario' => 'sem.token', 'senha' => 'SemToken2026', 'perfil_usuario' => 'ADMIN'], $cookies);
teste('H08', 'Cadastro sem token CSRF é recusado (HTTP 403)', $r['status'] === 403, 'HTTP ' . $r['status']);

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', ['token_csrf' => $token, 'email_usuario' => 'invalido', 'nome_usuario' => 'a b', 'senha' => '123', 'perfil_usuario' => 'ROOT'], $cookies);
$json = json_decode($r['corpo'], true);
teste('H09', 'Dados inválidos enviados direto ao servidor (sem JavaScript) retornam HTTP 422', $r['status'] === 422 && count($json['erros'] ?? []) === 4, 'HTTP ' . $r['status'] . ', ' . count($json['erros'] ?? []) . ' erros');

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', ['token_csrf' => $token, 'email_usuario' => 'novo@teste.com', 'nome_usuario' => 'novo.funcionario', 'senha' => 'Novo2026x', 'perfil_usuario' => 'FUNCIONARIO'], $cookies);
teste('H10', 'Cadastro válido pela tela retorna sucesso', $r['status'] === 200 && str_contains($r['corpo'], '"sucesso":true'));

$lista = requisicao($url_base . '/usuarios/tela-cadastro-user.php', null, $cookies)['corpo'];
teste('H11', 'Novo funcionário aparece na listagem', str_contains($lista, 'novo.funcionario'));

$stmt = mysqli_prepare($conexao, "SELECT id_usuario FROM usuarios WHERE nome_usuario = 'novo.funcionario'");
mysqli_stmt_execute($stmt);
$id_novo = (int) mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];

$r = requisicao($url_base . '/usuarios/tela-editar-user.php', ['token_csrf' => $token, 'editar' => '1', 'id_usuario' => $id_novo, 'email_usuario' => 'editado@teste.com', 'nome_usuario' => 'novo.funcionario', 'senha' => '', 'perfil_usuario' => 'FUNCIONARIO'], $cookies);
teste('H12', 'Edição válida salva e volta para a listagem', $r['status'] === 302 && buscar_usuario($conexao, $id_novo)['email_usuario'] === 'editado@teste.com');

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', ['excluir' => '1', 'id_usuario' => $id_novo], $cookies);
teste('H13', 'Exclusão sem token CSRF não apaga o usuário', buscar_usuario($conexao, $id_novo) !== null);

$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', ['token_csrf' => $token, 'excluir' => '1', 'id_usuario' => $id_novo], $cookies);
teste('H14', 'Exclusão com token CSRF apaga o usuário', buscar_usuario($conexao, $id_novo) === null);

$r = requisicao($url_base . '/usuarios/tela-cadastro-admin.php', null, $cookies);
teste('H15', 'Tela de primeiro administrador fica bloqueada quando já existe admin', $r['status'] === 302);

// ---------- funcionário sem permissão ----------

$cookies_func = login($url_base, 'func.teste', 'Turno2026x');
$r = requisicao($url_base . '/tela-geral-home.php', null, $cookies_func);
teste('H16', 'Funcionário faz login e acessa a Home', $r['status'] === 200);
$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', null, $cookies_func);
teste('H17', 'Funcionário NÃO acessa a tela de usuários (HTTP 403)', $r['status'] === 403, 'HTTP ' . $r['status']);
$r = requisicao($url_base . '/usuarios/tela-editar-user.php?id=1', null, $cookies_func);
teste('H18', 'Funcionário NÃO acessa a edição de usuários (HTTP 403)', $r['status'] === 403, 'HTTP ' . $r['status']);

// ---------- logout ----------

requisicao(rtrim($argv[1] ?? 'http://localhost:8080', '/') . '/infra/logout.php', null, $cookies);
$r = requisicao($url_base . '/usuarios/tela-cadastro-user.php', null, $cookies);
teste('H19', 'Depois do logout a sessão não vale mais', $r['status'] === 302);

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo 'Total: ' . $total . ' | Passaram: ' . ($total - $falhas) . ' | Falharam: ' . $falhas . PHP_EOL;

exit($falhas > 0 ? 1 : 0);
