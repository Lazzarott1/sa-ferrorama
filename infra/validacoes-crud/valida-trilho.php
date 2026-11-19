<?php

// confere os dados do formulário de trilho (cadastro e edição)
// devolve a lista de erros, se vier vazia pode salvar
function validarTrilho($nome, $descricao, $km, $status)
{
    $erros = [];

    // nome e descrição são obrigatórios e têm limite de tamanho por causa do banco
    if ($nome == '') {
        $erros[] = "Informe o nome do trilho.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do trilho pode ter no máximo 100 caracteres.";
    }

    if ($descricao == '') {
        $erros[] = "Informe a descrição do trilho.";
    } elseif (mb_strlen($descricao) > 255) {
        $erros[] = "A descrição pode ter no máximo 255 caracteres.";
    }

    // km tem que ser número positivo
    if (!is_numeric($km) || $km <= 0 || strlen($km) > 20) {
        $erros[] = "O km deve ser um número maior que zero.";
    }

    // só os status que existem no select
    if ($status != 'ATIVO' && $status != 'MANUTENCAO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    return $erros;
}
