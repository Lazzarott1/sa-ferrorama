<?php

function validarTrem($conexao, $trilho_atual = 0, $usuario_atual = 0)
{
    $trem = [
        'nome_trem' => trim($_POST['nome'] ?? ''),
        'modelo_trem' => $_POST['modelo'] ?? '',
        'capacidade_trem' => trim($_POST['capacidade'] ?? ''),
        'id_trilho' => (int) ($_POST['id_trilho'] ?? 0),
        'id_usuario' => (int) ($_POST['id_usuario'] ?? 0),
        'status_trem' => $_POST['status'] ?? ''
    ];

    $erros = [];

    if ($trem['nome_trem'] === '') {
        $erros[] = "Informe o nome do trem.";
    } elseif (mb_strlen($trem['nome_trem']) > 100) {
        $erros[] = "O nome do trem pode ter no máximo 100 caracteres.";
    }

    if (!in_array($trem['modelo_trem'], ['De passageiros', 'De carga'], true)) {
        $erros[] = "Selecione um modelo válido.";
    }

    if (filter_var($trem['capacidade_trem'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $erros[] = "A capacidade deve ser um número inteiro maior que zero.";
    }

    if (!in_array($trem['status_trem'], ['ATIVO', 'MANUTENCAO', 'INATIVO'], true)) {
        $erros[] = "Selecione um status válido.";
    }

    $stmt = mysqli_prepare($conexao, "SELECT id_trilho FROM trilhos WHERE id_trilho = ? AND (status_trilho <> 'INATIVO' OR id_trilho = ?)");
    mysqli_stmt_bind_param($stmt, "ii", $trem['id_trilho'], $trilho_atual);
    mysqli_stmt_execute($stmt);

    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        $erros[] = "Selecione um trilho ativo.";
    }

    $stmt = mysqli_prepare($conexao, "SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND (status_usuario = 'ATIVO' OR id_usuario = ?)");
    mysqli_stmt_bind_param($stmt, "ii", $trem['id_usuario'], $usuario_atual);
    mysqli_stmt_execute($stmt);

    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        $erros[] = "Selecione um responsável ativo.";
    }

    return [$trem, $erros];
}
