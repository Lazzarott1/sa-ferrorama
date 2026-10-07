<?php

// Validações de usuário feitas no servidor.
// O JavaScript da tela ajuda o usuário, mas quem garante os dados é este arquivo:
// qualquer requisição (navegador, Postman, curl...) passa por aqui antes do banco.

const PERFIS_USUARIO = ['ADMIN', 'FUNCIONARIO'];

const NOME_MIN = 3;
const NOME_MAX = 50;
const EMAIL_MAX = 200;
const SENHA_MIN = 8;
const SENHA_MAX = 72; // limite do bcrypt (PASSWORD_DEFAULT)

// Limpa os dados recebidos do formulário antes de validar
function normalizar_usuario(array $entrada): array
{
    return [
        'nome_usuario' => trim((string) ($entrada['nome_usuario'] ?? '')),
        'email_usuario' => strtolower(trim((string) ($entrada['email_usuario'] ?? ''))),
        // A senha não recebe trim: espaços fazem parte dela
        'senha' => (string) ($entrada['senha'] ?? ''),
        'perfil_usuario' => strtoupper(trim((string) ($entrada['perfil_usuario'] ?? 'FUNCIONARIO'))),
    ];
}

function validar_nome_usuario(string $nome): ?string
{
    if ($nome === '') {
        return 'Informe o nome de usuário.';
    }

    $tamanho = mb_strlen($nome);

    if ($tamanho < NOME_MIN || $tamanho > NOME_MAX) {
        return 'O nome de usuário deve ter entre ' . NOME_MIN . ' e ' . NOME_MAX . ' caracteres.';
    }

    if (!preg_match('/^[A-Za-z0-9._-]+$/', $nome)) {
        return 'O nome de usuário aceita apenas letras sem acento, números, ponto, hífen e sublinhado.';
    }

    return null;
}

function validar_email_usuario(string $email): ?string
{
    if ($email === '') {
        return 'Informe o e-mail.';
    }

    if (mb_strlen($email) > EMAIL_MAX) {
        return 'O e-mail deve ter no máximo ' . EMAIL_MAX . ' caracteres.';
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'Informe um e-mail válido.';
    }

    return null;
}

function validar_senha_usuario(string $senha, string $nome = ''): ?string
{
    if ($senha === '') {
        return 'Informe a senha.';
    }

    if (strlen($senha) < SENHA_MIN) {
        return 'A senha deve ter no mínimo ' . SENHA_MIN . ' caracteres.';
    }

    if (strlen($senha) > SENHA_MAX) {
        return 'A senha deve ter no máximo ' . SENHA_MAX . ' caracteres.';
    }

    if (!preg_match('/[a-z]/', $senha) || !preg_match('/[A-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
        return 'A senha deve conter letra maiúscula, letra minúscula e número.';
    }

    if ($nome !== '' && stripos($senha, $nome) !== false) {
        return 'A senha não pode conter o nome de usuário.';
    }

    return null;
}

function validar_perfil_usuario(string $perfil): ?string
{
    if (!in_array($perfil, PERFIS_USUARIO, true)) {
        return 'Perfil inválido.';
    }

    return null;
}

// Retorna um array campo => mensagem. Array vazio = dados válidos.
// Na edição a senha é opcional: em branco mantém a senha atual.
function validar_usuario(array $dados, bool $senha_obrigatoria = true): array
{
    $erros = [];

    $erro = validar_nome_usuario($dados['nome_usuario']);
    if ($erro !== null) {
        $erros['nome_usuario'] = $erro;
    }

    $erro = validar_email_usuario($dados['email_usuario']);
    if ($erro !== null) {
        $erros['email_usuario'] = $erro;
    }

    if ($senha_obrigatoria || $dados['senha'] !== '') {
        $erro = validar_senha_usuario($dados['senha'], $dados['nome_usuario']);
        if ($erro !== null) {
            $erros['senha'] = $erro;
        }
    }

    $erro = validar_perfil_usuario($dados['perfil_usuario']);
    if ($erro !== null) {
        $erros['perfil_usuario'] = $erro;
    }

    return $erros;
}
