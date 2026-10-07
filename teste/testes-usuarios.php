<?php

// Testes automatizados do módulo de usuários.
//
// Como rodar (pasta do projeto, com o MySQL/MariaDB do XAMPP ligado):
//     php teste/testes-usuarios.php
//
// O script cria um banco separado (sa_ferrorama_testes) a partir de database/db_sa.sql,
// roda os testes e apaga o banco no final. O banco sa_teste não é alterado.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const BANCO_TESTES = 'sa_ferrorama_testes';

putenv('DB_NAME=' . BANCO_TESTES);

require_once __DIR__ . '/../infra/validacao-usuario.php';
require_once __DIR__ . '/../infra/usuarios.php';

$total = 0;
$falhas = 0;

function teste(string $codigo, string $descricao, bool $passou, string $detalhe = ''): void
{
    global $total, $falhas;

    $total++;
    if (!$passou) {
        $falhas++;
    }

    echo ($passou ? '[PASSOU] ' : '[FALHOU] ') . $codigo . ' - ' . $descricao;
    echo $detalhe !== '' ? '  (' . $detalhe . ')' : '';
    echo PHP_EOL;
}

function secao(string $titulo): void
{
    echo PHP_EOL . '== ' . $titulo . ' ==' . PHP_EOL;
}

function valido(array $entrada, bool $senha_obrigatoria = true): array
{
    return validar_usuario(normalizar_usuario($entrada), $senha_obrigatoria);
}

$base = ['nome_usuario' => 'joao.silva', 'email_usuario' => 'joao@ferromonitor.com', 'senha' => 'Trilho2026', 'perfil_usuario' => 'FUNCIONARIO'];

// =====================================================================
secao('1. Validações do backend (infra/validacao-usuario.php)');
// =====================================================================

teste('V01', 'Dados válidos não geram erro', valido($base) === []);
teste('V02', 'Nome de usuário vazio é rejeitado', isset(valido(['nome_usuario' => '   '] + $base)['nome_usuario']));
teste('V03', 'Nome com menos de 3 caracteres é rejeitado', isset(valido(['nome_usuario' => 'ab'] + $base)['nome_usuario']));
teste('V04', 'Nome com mais de 50 caracteres é rejeitado', isset(valido(['nome_usuario' => str_repeat('a', 51)] + $base)['nome_usuario']));
teste('V05', "Nome com aspas/SQL (admin' --) é rejeitado", isset(valido(['nome_usuario' => "admin' --"] + $base)['nome_usuario']));
teste('V06', 'Nome com HTML (<script>) é rejeitado', isset(valido(['nome_usuario' => '<script>alert(1)</script>'] + $base)['nome_usuario']));
teste('V07', 'E-mail vazio é rejeitado', isset(valido(['email_usuario' => ''] + $base)['email_usuario']));
teste('V08', 'E-mail sem formato válido é rejeitado', isset(valido(['email_usuario' => 'joao.ferromonitor.com'] + $base)['email_usuario']));
teste('V09', 'E-mail com mais de 200 caracteres é rejeitado', isset(valido(['email_usuario' => str_repeat('a', 195) . '@x.com'] + $base)['email_usuario']));
teste('V10', 'E-mail é normalizado (espaços e maiúsculas)', normalizar_usuario(['email_usuario' => '  Joao@FerroMonitor.COM '])['email_usuario'] === 'joao@ferromonitor.com');
teste('V11', 'Senha vazia é rejeitada no cadastro', isset(valido(['senha' => ''] + $base)['senha']));
teste('V12', 'Senha com menos de 8 caracteres é rejeitada', isset(valido(['senha' => 'Ab1cdef'] + $base)['senha']));
teste('V13', 'Senha com mais de 72 caracteres é rejeitada', isset(valido(['senha' => 'Ab1' . str_repeat('x', 70)] + $base)['senha']));
teste('V14', 'Senha sem letra maiúscula é rejeitada', isset(valido(['senha' => 'trilho2026'] + $base)['senha']));
teste('V15', 'Senha sem número é rejeitada', isset(valido(['senha' => 'TrilhoSeguro'] + $base)['senha']));
teste('V16', 'Senha que contém o nome de usuário é rejeitada', isset(valido(['senha' => 'Joao.Silva123'] + $base)['senha']));
teste('V17', 'Perfil fora da lista (ROOT) é rejeitado', isset(valido(['perfil_usuario' => 'ROOT'] + $base)['perfil_usuario']));
teste('V18', 'Na edição, senha em branco é aceita (mantém a atual)', valido(['senha' => ''] + $base, false) === []);
teste('V19', 'Na edição, senha preenchida continua sendo validada', isset(valido(['senha' => '123'] + $base, false)['senha']));

// =====================================================================
// Banco de testes
// =====================================================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $servidor = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '');
} catch (mysqli_sql_exception $erro) {
    echo PHP_EOL . 'Banco de dados indisponível: ' . $erro->getMessage() . PHP_EOL;
    echo 'Testes de integração não executados.' . PHP_EOL;
    exit(1);
}

$script = file_get_contents(__DIR__ . '/../database/db_sa.sql');
$script = str_replace('sa_teste', BANCO_TESTES, $script);
$script = substr($script, 0, strpos($script, 'INSERT INTO usuarios')); // só a estrutura, sem dados

$servidor->query('DROP DATABASE IF EXISTS ' . BANCO_TESTES);
$servidor->multi_query($script);
while ($servidor->more_results()) {
    $servidor->next_result();
}
$servidor->close();

include __DIR__ . '/../infra/conexao.php';

function contar_usuarios(mysqli $conexao): int
{
    return (int) mysqli_fetch_row(mysqli_query($conexao, 'SELECT COUNT(*) FROM usuarios'))[0];
}

function hash_de(mysqli $conexao, string $nome): string
{
    $stmt = mysqli_prepare($conexao, 'SELECT senha FROM usuarios WHERE nome_usuario = ?');
    mysqli_stmt_bind_param($stmt, 's', $nome);
    mysqli_stmt_execute($stmt);
    return (string) (mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] ?? '');
}

// =====================================================================
secao('2. Cadastro de Administrador');
// =====================================================================

$r = cadastrar_primeiro_administrador($conexao, ['nome_usuario' => 'admin.ferro', 'email_usuario' => 'admin@ferromonitor.com', 'senha' => 'Admin2026x', 'confirmar_senha' => 'Outra2026x']);
teste('A01', 'Primeiro admin com confirmação de senha diferente é rejeitado', !$r['sucesso'] && isset($r['erros']['confirmar_senha']) && contar_usuarios($conexao) === 0);

$r = cadastrar_primeiro_administrador($conexao, ['nome_usuario' => 'admin.ferro', 'email_usuario' => 'admin@ferromonitor.com', 'senha' => 'Admin2026x', 'confirmar_senha' => 'Admin2026x', 'perfil_usuario' => 'FUNCIONARIO']);
$id_admin = $r['id_usuario'] ?? 0;
$admin = $id_admin ? buscar_usuario($conexao, $id_admin) : null;
teste('A02', 'Primeiro admin é criado quando não há administrador', $r['sucesso'] && $admin !== null);
teste('A03', 'Primeiro admin recebe perfil ADMIN mesmo se outro perfil for enviado', ($admin['perfil_usuario'] ?? '') === 'ADMIN');

$r = cadastrar_primeiro_administrador($conexao, ['nome_usuario' => 'invasor', 'email_usuario' => 'invasor@x.com', 'senha' => 'Invasor2026', 'confirmar_senha' => 'Invasor2026']);
teste('A04', 'Tela de primeiro admin é bloqueada depois que já existe um admin', !$r['sucesso'] && contar_usuarios($conexao) === 1);

$r = cadastrar_usuario($conexao, ['nome_usuario' => 'maria.admin', 'email_usuario' => 'maria@ferromonitor.com', 'senha' => 'Maria2026x', 'perfil_usuario' => 'ADMIN']);
teste('A05', 'Administrador cadastra outro administrador pela tela de usuários', $r['sucesso'] && buscar_usuario($conexao, $r['id_usuario'])['perfil_usuario'] === 'ADMIN');
$id_maria = $r['id_usuario'] ?? 0;

// =====================================================================
secao('3. CRUD de Funcionários/Usuários (infra/usuarios.php)');
// =====================================================================

$r = cadastrar_usuario($conexao, $base);
$id_joao = $r['id_usuario'] ?? 0;
teste('C01', 'CREATE: funcionário válido é cadastrado', $r['sucesso'] && $id_joao > 0);

$nomes = array_column(listar_usuarios($conexao), 'nome_usuario');
teste('C02', 'READ: funcionário aparece na listagem', in_array('joao.silva', $nomes, true), implode(', ', $nomes));
teste('C03', 'READ: busca por ID retorna os dados certos', (buscar_usuario($conexao, $id_joao)['email_usuario'] ?? '') === 'joao@ferromonitor.com');

$antes = contar_usuarios($conexao);
$r = cadastrar_usuario($conexao, ['email_usuario' => 'outro@x.com'] + $base);
teste('C04', 'CREATE: nome de usuário repetido é rejeitado', !$r['sucesso'] && isset($r['erros']['nome_usuario']));
$r = cadastrar_usuario($conexao, ['nome_usuario' => 'joao2', 'email_usuario' => 'JOAO@FerroMonitor.com'] + $base);
teste('C05', 'CREATE: e-mail repetido (maiúsculas diferentes) é rejeitado', !$r['sucesso'] && isset($r['erros']['email_usuario']));
$r = cadastrar_usuario($conexao, ['nome_usuario' => 'x', 'email_usuario' => 'invalido', 'senha' => '1', 'perfil_usuario' => 'ROOT']);
teste('C06', 'CREATE: dados inválidos retornam erro e nada é gravado', !$r['sucesso'] && count($r['erros']) === 4 && contar_usuarios($conexao) === $antes);

$hash_antes = hash_de($conexao, 'joao.silva');
$r = atualizar_usuario($conexao, $id_joao, ['nome_usuario' => 'joao.souza', 'email_usuario' => 'joao.souza@ferromonitor.com', 'senha' => '', 'perfil_usuario' => 'FUNCIONARIO']);
$joao = buscar_usuario($conexao, $id_joao);
teste('C07', 'UPDATE: nome e e-mail são alterados', $r['sucesso'] && $joao['nome_usuario'] === 'joao.souza' && $joao['email_usuario'] === 'joao.souza@ferromonitor.com');
teste('C08', 'UPDATE: senha em branco mantém a senha atual', hash_de($conexao, 'joao.souza') === $hash_antes);

$r = atualizar_usuario($conexao, $id_joao, ['nome_usuario' => 'joao.souza', 'email_usuario' => 'joao.souza@ferromonitor.com', 'senha' => 'NovaSenha2026', 'perfil_usuario' => 'FUNCIONARIO']);
teste('C09', 'UPDATE: nova senha gera novo hash', $r['sucesso'] && hash_de($conexao, 'joao.souza') !== $hash_antes);
teste('C10', 'UPDATE: login funciona com a nova senha', autenticar_usuario($conexao, 'joao.souza', 'NovaSenha2026')['sucesso']);

$r = atualizar_usuario($conexao, $id_joao, ['nome_usuario' => 'admin.ferro', 'email_usuario' => 'joao.souza@ferromonitor.com', 'senha' => '', 'perfil_usuario' => 'FUNCIONARIO']);
teste('C11', 'UPDATE: não permite usar o nome de outro usuário', !$r['sucesso'] && isset($r['erros']['nome_usuario']));
$r = atualizar_usuario($conexao, $id_joao, ['nome_usuario' => 'joao.souza', 'email_usuario' => 'email-invalido', 'senha' => '', 'perfil_usuario' => 'FUNCIONARIO']);
teste('C12', 'UPDATE: dados inválidos são rejeitados', !$r['sucesso'] && isset($r['erros']['email_usuario']));
teste('C13', 'UPDATE: usuário inexistente retorna erro', !atualizar_usuario($conexao, 999999, $base)['sucesso']);

teste('C14', 'DELETE: usuário não pode excluir a si mesmo', !excluir_usuario($conexao, $id_admin, $id_admin)['sucesso']);
$r = excluir_usuario($conexao, $id_joao, $id_admin);
teste('C15', 'DELETE: funcionário é excluído', $r['sucesso'] && buscar_usuario($conexao, $id_joao) === null);
teste('C16', 'DELETE: usuário inexistente retorna erro', !excluir_usuario($conexao, 999999, $id_admin)['sucesso']);

teste('C17', 'DELETE: um admin pode excluir outro admin quando sobra pelo menos um', excluir_usuario($conexao, $id_maria, $id_admin)['sucesso']);
cadastrar_usuario($conexao, ['nome_usuario' => 'operador', 'email_usuario' => 'operador@ferromonitor.com', 'senha' => 'Turno2026x', 'perfil_usuario' => 'FUNCIONARIO']);
$id_operador = buscar_usuario_por_nome_teste($conexao, 'operador');
teste('C18', 'DELETE: o único administrador não pode ser excluído', !excluir_usuario($conexao, $id_admin, $id_operador)['sucesso'] && contar_administradores($conexao) === 1);
$r = atualizar_usuario($conexao, $id_admin, ['nome_usuario' => 'admin.ferro', 'email_usuario' => 'admin@ferromonitor.com', 'senha' => '', 'perfil_usuario' => 'FUNCIONARIO']);
teste('C19', 'UPDATE: o único administrador não pode perder o perfil ADMIN', !$r['sucesso'] && isset($r['erros']['perfil_usuario']));

function buscar_usuario_por_nome_teste(mysqli $conexao, string $nome): int
{
    $stmt = mysqli_prepare($conexao, 'SELECT id_usuario FROM usuarios WHERE nome_usuario = ?');
    mysqli_stmt_bind_param($stmt, 's', $nome);
    mysqli_stmt_execute($stmt);
    return (int) (mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] ?? 0);
}

// =====================================================================
secao('4. Proteção das senhas');
// =====================================================================

$hash = hash_de($conexao, 'operador');
teste('P01', 'Senha não é gravada em texto puro', $hash !== 'Turno2026x' && strpos($hash, 'Turno2026x') === false);
teste('P02', 'Senha é gravada com bcrypt custo 12 ($2y$12$)', str_starts_with($hash, '$2y$12$') && strlen($hash) === 60, substr($hash, 0, 29) . '...');
teste('P03', 'password_verify confere a senha correta', password_verify('Turno2026x', $hash));
cadastrar_usuario($conexao, ['nome_usuario' => 'tecnico', 'email_usuario' => 'tecnico@ferromonitor.com', 'senha' => 'Turno2026x', 'perfil_usuario' => 'FUNCIONARIO']);
teste('P04', 'Mesma senha em dois usuários gera hashes diferentes (salt)', hash_de($conexao, 'tecnico') !== $hash);
teste('P05', 'Listagem e busca não retornam a coluna senha', !array_key_exists('senha', listar_usuarios($conexao)[0]) && !array_key_exists('senha', buscar_usuario($conexao, $id_admin)));

$antigo = password_hash('Legado2026', PASSWORD_DEFAULT, ['cost' => 10]);
$stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (nome_usuario, email_usuario, senha) VALUES ('legado', 'legado@x.com', ?)");
mysqli_stmt_bind_param($stmt, 's', $antigo);
mysqli_stmt_execute($stmt);
autenticar_usuario($conexao, 'legado', 'Legado2026');
teste('P06', 'Hash antigo (custo 10) é atualizado para custo 12 no login', str_starts_with(hash_de($conexao, 'legado'), '$2y$12$'));

// =====================================================================
secao('5. Login e bloqueio por tentativas');
// =====================================================================

$r = autenticar_usuario($conexao, 'admin.ferro', 'Admin2026x');
teste('L01', 'Login com usuário e senha corretos', $r['sucesso'] && $r['usuario']['perfil_usuario'] === 'ADMIN');
$errada = autenticar_usuario($conexao, 'admin.ferro', 'SenhaErrada1');
$inexistente = autenticar_usuario($conexao, 'naoexiste', 'SenhaErrada1');
teste('L02', 'Senha errada é recusada', !$errada['sucesso']);
teste('L03', 'Usuário inexistente recebe a mesma mensagem (não revela quem existe)', $errada['mensagem'] === $inexistente['mensagem'], $errada['mensagem']);
teste('L04', 'Campos vazios são recusados', !autenticar_usuario($conexao, '', '')['sucesso']);

for ($i = 0; $i < MAX_TENTATIVAS_LOGIN; $i++) {
    autenticar_usuario($conexao, 'operador', 'Errada' . $i . 'X');
}
$r = autenticar_usuario($conexao, 'operador', 'Turno2026x');
teste('L05', 'Após 5 senhas erradas o usuário é bloqueado (até a senha certa é recusada)', !$r['sucesso'] && str_contains($r['mensagem'], 'bloqueado'), $r['mensagem']);
mysqli_query($conexao, "UPDATE usuarios SET bloqueado_ate = NOW() - INTERVAL 1 MINUTE WHERE nome_usuario = 'operador'");
teste('L06', 'Depois do tempo de bloqueio o login volta a funcionar', autenticar_usuario($conexao, 'operador', 'Turno2026x')['sucesso']);

// =====================================================================
secao('6. Proteção contra SQL Injection');
// =====================================================================

$usuarios_antes = contar_usuarios($conexao);

$ataques_login = ["' OR '1'='1", "' OR 1=1 -- ", "admin.ferro' -- ", "admin.ferro' #", "\" OR \"\"=\""];
foreach ($ataques_login as $n => $ataque) {
    $r = autenticar_usuario($conexao, $ataque, 'qualquer');
    teste('S0' . ($n + 1), 'Login com nome = ' . $ataque . ' é recusado', !$r['sucesso']);
}
teste('S06', "Login com senha = ' OR '1'='1 é recusado", !autenticar_usuario($conexao, 'admin.ferro', "' OR '1'='1")['sucesso']);

$r = cadastrar_usuario($conexao, ['nome_usuario' => 'hacker', 'email_usuario' => "x@y.com'); DROP TABLE usuarios; --", 'senha' => 'Hacker2026', 'perfil_usuario' => 'FUNCIONARIO']);
teste('S07', 'Cadastro com DROP TABLE no e-mail é recusado', !$r['sucesso']);

$r = verificar_duplicados($conexao, ['nome_usuario' => "' OR '1'='1", 'email_usuario' => "' OR '1'='1"]);
teste('S08', 'Consulta de duplicidade trata o ataque como texto (nenhum registro encontrado)', $r === []);

$id_recebido = '1 OR 1=1';
$usuario = buscar_usuario($conexao, (int) $id_recebido);
teste('S09', 'ID "1 OR 1=1" vira o número 1 e retorna no máximo um registro', $usuario === null || (int) $usuario['id_usuario'] === 1);

$r = excluir_usuario($conexao, (int) '0 OR 1=1', $id_admin);
teste('S10', 'Exclusão com ID "0 OR 1=1" não apaga nenhum registro', !$r['sucesso'] && contar_usuarios($conexao) === $usuarios_antes);

$existe = mysqli_query($conexao, "SHOW TABLES LIKE 'usuarios'")->num_rows === 1;
teste('S11', 'Tabela usuarios continua existindo e com os mesmos registros', $existe && contar_usuarios($conexao) === $usuarios_antes, $usuarios_antes . ' registros');

// Varredura do código: nenhum SQL de usuarios monta a consulta concatenando variáveis
$codigo = file_get_contents(__DIR__ . '/../infra/usuarios.php');
preg_match_all('/mysqli_(?:query|prepare)\(\$conexao,\s*"[^"]*\$[a-z_]/i', $codigo, $concatenados);
teste('S12', 'Nenhuma consulta em infra/usuarios.php interpola variáveis no SQL', count($concatenados[0]) === 0);

// =====================================================================

mysqli_close($conexao);
$limpeza = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '');
$limpeza->query('DROP DATABASE IF EXISTS ' . BANCO_TESTES);

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo 'Total: ' . $total . ' | Passaram: ' . ($total - $falhas) . ' | Falharam: ' . $falhas . PHP_EOL;

exit($falhas > 0 ? 1 : 0);
