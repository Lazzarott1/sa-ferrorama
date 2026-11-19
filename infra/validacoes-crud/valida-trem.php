<?php

// confere os dados do formulário de trem (cadastro e edição)
// $id é 0 no cadastro; devolve a lista de erros
function validarTrem($conexao, $id, $nome, $modelo, $capacidade, $id_trilho, $id_usuario, $status)
{
    $erros = [];

    // nome obrigatório, até 100 caracteres
    if ($nome == '') {
        $erros[] = "Informe o nome do trem.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do trem pode ter no máximo 100 caracteres.";
    }

    // só os dois modelos do select
    if ($modelo != 'De passageiros' && $modelo != 'De carga') {
        $erros[] = "Selecione um modelo válido.";
    }

    // capacidade só com números e maior que zero
    if (!ctype_digit($capacidade) || $capacidade < 1) {
        $erros[] = "A capacidade deve ser um número inteiro maior que zero.";
    }

    if ($status != 'ATIVO' && $status != 'MANUTENCAO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // Trilho e responsável precisam estar ativos, a não ser que o trem já estivesse com eles
    $trem_atual = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id_trilho, id_usuario FROM trens WHERE id_trem = $id"));

    // o trilho escolhido precisa existir
    $trilho = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT status_trilho FROM trilhos WHERE id_trilho = $id_trilho"));

    if (!$trilho) {
        $erros[] = "Selecione um trilho ativo.";
    } elseif ($trilho['status_trilho'] == 'INATIVO' && (!$trem_atual || $trem_atual['id_trilho'] != $id_trilho)) {
        $erros[] = "Selecione um trilho ativo.";
    }

    // e o responsável também
    $usuario = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT status_usuario FROM usuarios WHERE id_usuario = $id_usuario"));

    if (!$usuario) {
        $erros[] = "Selecione um responsável ativo.";
    } elseif ($usuario['status_usuario'] != 'ATIVO' && (!$trem_atual || $trem_atual['id_usuario'] != $id_usuario)) {
        $erros[] = "Selecione um responsável ativo.";
    }

    return $erros;
}
