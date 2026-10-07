<?php

// Regras e acesso ao banco da tabela usuarios.
// TODAS as consultas usam prepared statements (mysqli_prepare + bind_param):
// o valor digitado pelo usuário nunca é concatenado no SQL, então um texto como
// ' OR '1'='1 é tratado apenas como dado e não altera o comando (proteção contra SQL Injection).

require_once __DIR__ . '/validacao-usuario.php';

const MAX_TENTATIVAS_LOGIN = 5;
const MINUTOS_BLOQUEIO_LOGIN = 15;

// bcrypt com custo 12 (mesmo custo dos dados iniciais do banco)
const OPCOES_HASH_SENHA = ['cost' => 12];

// Hash válido usado quando o usuário não existe, para o login levar o mesmo tempo
const HASH_FICTICIO = '$2y$12$LgQpVFz1SZnKxcsrssVbLeV9QSXGe1DFymQsxpTaQjzX3.qnqA4We';

function listar_usuarios(mysqli $conexao): array
{
    $sql = "SELECT id_usuario, nome_usuario, email_usuario, perfil_usuario
            FROM usuarios ORDER BY id_usuario DESC";

    $resultado = mysqli_query($conexao, $sql);

    return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}

function buscar_usuario(mysqli $conexao, int $id_usuario): ?array
{
    $stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario, email_usuario, perfil_usuario
                                      FROM usuarios WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $usuario ?: null;
}

function contar_administradores(mysqli $conexao): int
{
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) FROM usuarios WHERE perfil_usuario = 'ADMIN'");

    return (int) mysqli_fetch_row($resultado)[0];
}

// Verifica se nome ou e-mail já pertencem a outro usuário
function verificar_duplicados(mysqli $conexao, array $dados, int $ignorar_id = 0): array
{
    $erros = [];

    $stmt = mysqli_prepare($conexao, "SELECT nome_usuario, email_usuario FROM usuarios
                                      WHERE (nome_usuario = ? OR email_usuario = ?) AND id_usuario <> ?");
    mysqli_stmt_bind_param($stmt, "ssi", $dados['nome_usuario'], $dados['email_usuario'], $ignorar_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($resultado)) {
        if (strcasecmp($linha['nome_usuario'], $dados['nome_usuario']) === 0) {
            $erros['nome_usuario'] = 'Este nome de usuário já está em uso.';
        }
        if (strcasecmp($linha['email_usuario'], $dados['email_usuario']) === 0) {
            $erros['email_usuario'] = 'Este e-mail já está cadastrado.';
        }
    }

    mysqli_stmt_close($stmt);

    return $erros;
}

// CREATE: valida, verifica duplicidade, gera o hash da senha e grava
function cadastrar_usuario(mysqli $conexao, array $entrada): array
{
    $dados = normalizar_usuario($entrada);

    $erros = validar_usuario($dados, true);
    if (!$erros) {
        $erros = verificar_duplicados($conexao, $dados);
    }
    if ($erros) {
        return ['sucesso' => false, 'erros' => $erros];
    }

    // A senha nunca é gravada em texto puro: password_hash usa bcrypt com salt aleatório
    $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT, OPCOES_HASH_SENHA);

    $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (nome_usuario, email_usuario, senha, perfil_usuario)
                                      VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $dados['nome_usuario'], $dados['email_usuario'], $senha_hash, $dados['perfil_usuario']);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);

    return ['sucesso' => true, 'id_usuario' => $id, 'erros' => []];
}

// UPDATE: senha em branco mantém a atual
function atualizar_usuario(mysqli $conexao, int $id_usuario, array $entrada): array
{
    $atual = buscar_usuario($conexao, $id_usuario);
    if (!$atual) {
        return ['sucesso' => false, 'erros' => ['geral' => 'Usuário não encontrado.']];
    }

    $dados = normalizar_usuario($entrada);

    $erros = validar_usuario($dados, false);
    if (!$erros) {
        $erros = verificar_duplicados($conexao, $dados, $id_usuario);
    }

    // O sistema nunca pode ficar sem administrador
    if (!$erros && $atual['perfil_usuario'] === 'ADMIN' && $dados['perfil_usuario'] !== 'ADMIN'
        && contar_administradores($conexao) <= 1) {
        $erros['perfil_usuario'] = 'Não é possível remover o perfil do único administrador.';
    }

    if ($erros) {
        return ['sucesso' => false, 'erros' => $erros];
    }

    if ($dados['senha'] !== '') {
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT, OPCOES_HASH_SENHA);
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios
                                          SET nome_usuario = ?, email_usuario = ?, perfil_usuario = ?, senha = ?
                                          WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $dados['nome_usuario'], $dados['email_usuario'], $dados['perfil_usuario'], $senha_hash, $id_usuario);
    } else {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios
                                          SET nome_usuario = ?, email_usuario = ?, perfil_usuario = ?
                                          WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $dados['nome_usuario'], $dados['email_usuario'], $dados['perfil_usuario'], $id_usuario);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['sucesso' => true, 'erros' => []];
}

// DELETE: não deixa excluir a si mesmo nem o último administrador
function excluir_usuario(mysqli $conexao, int $id_usuario, int $id_usuario_logado): array
{
    if ($id_usuario === $id_usuario_logado) {
        return ['sucesso' => false, 'erros' => ['geral' => 'Você não pode excluir o próprio usuário.']];
    }

    $usuario = buscar_usuario($conexao, $id_usuario);
    if (!$usuario) {
        return ['sucesso' => false, 'erros' => ['geral' => 'Usuário não encontrado.']];
    }

    if ($usuario['perfil_usuario'] === 'ADMIN' && contar_administradores($conexao) <= 1) {
        return ['sucesso' => false, 'erros' => ['geral' => 'Não é possível excluir o único administrador.']];
    }

    $stmt = mysqli_prepare($conexao, "DELETE FROM usuarios WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['sucesso' => true, 'erros' => []];
}

// LOGIN: mensagem genérica, bloqueio após várias tentativas e atualização do hash
function autenticar_usuario(mysqli $conexao, string $nome_usuario, string $senha): array
{
    $falha = ['sucesso' => false, 'mensagem' => 'Usuário ou senha incorretos.'];

    if (trim($nome_usuario) === '' || $senha === '') {
        return ['sucesso' => false, 'mensagem' => 'Preencha usuário e senha.'];
    }

    $stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario, senha, perfil_usuario, tentativas_login,
                                             bloqueado_ate, bloqueado_ate > NOW() AS bloqueado
                                      FROM usuarios WHERE nome_usuario = ?");
    $nome_usuario = trim($nome_usuario);
    mysqli_stmt_bind_param($stmt, "s", $nome_usuario);
    mysqli_stmt_execute($stmt);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$usuario) {
        // Mesmo custo de tempo de um usuário real, para não revelar quais nomes existem
        password_verify($senha, HASH_FICTICIO);
        return $falha;
    }

    if ($usuario['bloqueado']) {
        return ['sucesso' => false, 'mensagem' => 'Usuário bloqueado temporariamente por excesso de tentativas. Tente novamente em ' . MINUTOS_BLOQUEIO_LOGIN . ' minutos.'];
    }

    $id = (int) $usuario['id_usuario'];

    if (!password_verify($senha, $usuario['senha'])) {
        $tentativas = $usuario['bloqueado_ate'] !== null ? 1 : (int) $usuario['tentativas_login'] + 1;

        if ($tentativas >= MAX_TENTATIVAS_LOGIN) {
            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET tentativas_login = 0,
                                              bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id_usuario = ?");
            $minutos = MINUTOS_BLOQUEIO_LOGIN;
            mysqli_stmt_bind_param($stmt, "ii", $minutos, $id);
        } else {
            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = NULL WHERE id_usuario = ?");
            mysqli_stmt_bind_param($stmt, "ii", $tentativas, $id);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $falha;
    }

    // Login certo: zera as tentativas e, se o algoritmo/custo mudou, regrava o hash
    if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT, OPCOES_HASH_SENHA)) {
        $novo_hash = password_hash($senha, PASSWORD_DEFAULT, OPCOES_HASH_SENHA);
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET senha = ?, tentativas_login = 0, bloqueado_ate = NULL WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "si", $novo_hash, $id);
    } else {
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'usuario' => [
            'id_usuario' => $id,
            'nome_usuario' => $usuario['nome_usuario'],
            'perfil_usuario' => $usuario['perfil_usuario'],
        ],
    ];
}
