<?php

function validarTrilho()
{
    $trilho = [
        'nome_trilho' => trim($_POST['nome'] ?? ''),
        'descricao_trilho' => trim($_POST['descricao'] ?? ''),
        'km_trilho' => str_replace(',', '.', trim($_POST['km'] ?? '')),
        'status_trilho' => $_POST['status'] ?? ''
    ];

    $erros = [];

    if ($trilho['nome_trilho'] === '') {
        $erros[] = "Informe o nome do trilho.";
    } elseif (mb_strlen($trilho['nome_trilho']) > 100) {
        $erros[] = "O nome do trilho pode ter no máximo 100 caracteres.";
    }

    if ($trilho['descricao_trilho'] === '') {
        $erros[] = "Informe a descrição do trilho.";
    } elseif (mb_strlen($trilho['descricao_trilho']) > 255) {
        $erros[] = "A descrição pode ter no máximo 255 caracteres.";
    }

    if (!is_numeric($trilho['km_trilho']) || $trilho['km_trilho'] <= 0 || strlen($trilho['km_trilho']) > 20) {
        $erros[] = "O km deve ser um número maior que zero.";
    }

    if (!in_array($trilho['status_trilho'], ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
        $erros[] = "Selecione um status válido.";
    }

    return [$trilho, $erros];
}
