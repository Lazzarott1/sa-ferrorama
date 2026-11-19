<?php

// confere os dados do formulário de usuário (cadastro e edição)
// $id é 0 no cadastro; devolve a lista de erros
function validarUsuario($conexao, $id, $nome, $email, $senha, $perfil, $status, $cep, $logradouro, $bairro, $cidade, $uf)
{
    $erros = [];

    if ($nome == '') {
        $erros[] = "Informe o nome de usuário.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Informe um e-mail válido.";
    }

    // no cadastro a senha é obrigatória; na edição pode deixar em branco pra manter a antiga
    if ($id == 0 && $senha == '') {
        $erros[] = "Informe a senha.";
    } elseif ($senha != '' && strlen($senha) < 8) {
        $erros[] = "A senha deve ter pelo menos 8 caracteres.";
    }

    // só os valores que existem nos selects
    if ($perfil != 'ADMINISTRADOR' && $perfil != 'OPERADOR') {
        $erros[] = "Selecione um perfil válido.";
    }

    if ($status != 'ATIVO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // não pode ter dois usuários com o mesmo nome (o "id <> ?" ignora o próprio usuário na edição)
    $stmt = mysqli_prepare($conexao, "SELECT id_usuario FROM usuarios WHERE nome_usuario = ? AND id_usuario <> ?");
    mysqli_stmt_bind_param($stmt, "si", $nome, $id);
    mysqli_stmt_execute($stmt);
    $repetido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($repetido) {
        $erros[] = "Esse nome de usuário já existe.";
    }

    // quem é responsável por trem ativo não pode ser inativado
    if ($status == 'INATIVO') {
        $resultado = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM trens WHERE id_usuario = $id AND status_trem <> 'INATIVO'");
        $trens = mysqli_fetch_assoc($resultado);

        if ($trens['total'] > 0) {
            $erros[] = "Este usuário é responsável por " . $trens['total'] . " trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.";
        }
    }

    // o endereço é opcional, mas se colocou CEP ele tem que estar completo
    if ($cep != '' && (strlen($cep) != 8 || !ctype_digit($cep))) {
        $erros[] = "O CEP deve ter 8 números.";
    } elseif ($cep != '' && ($cidade == '' || strlen($uf) != 2)) {
        $erros[] = "Informe a cidade e a UF do CEP.";
    }

    // tamanho máximo de cada campo no banco
    if (mb_strlen($logradouro) > 150 || mb_strlen($bairro) > 100 || mb_strlen($cidade) > 100) {
        $erros[] = "O endereço tem campos maiores que o permitido.";
    }

    return $erros;
}
