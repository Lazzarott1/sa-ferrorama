<?php

function validarTrilho($conexao, $trem_obrigatorio)
{
    $trilho = [
        'nome_trilho' => trim($_POST['nome'] ?? ''),
        'descricao_trilho' => trim($_POST['descricao'] ?? ''),
        'km_trilho' => str_replace(',', '.', trim($_POST['km'] ?? '')),
        'tempo_previsto_min' => trim($_POST['tempo'] ?? ''),
        'id_trem' => (int) ($_POST['id_trem'] ?? 0),
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

    if (filter_var($trilho['tempo_previsto_min'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $erros[] = "O tempo previsto deve ser um número inteiro de minutos maior que zero.";
    }

    if (!in_array($trilho['status_trilho'], ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
        $erros[] = "Selecione um status válido.";
    }

    if ($trilho['id_trem'] || $trem_obrigatorio) {
        $stmt = mysqli_prepare($conexao, "SELECT id_trem FROM trens WHERE id_trem = ? AND status_trem <> 'INATIVO'");
        mysqli_stmt_bind_param($stmt, "i", $trilho['id_trem']);
        mysqli_stmt_execute($stmt);

        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $erros[] = "Selecione um trem ativo.";
        }
    }

    return [$trilho, $erros];
}
