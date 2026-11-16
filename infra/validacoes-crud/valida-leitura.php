<?php

function registrarLeitura($conexao)
{
    $id_sensor = (int) ($_POST['sensor'] ?? 0);
    $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));

    $erros = [];

    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM sensores WHERE id_sensor = ? AND status_sensor <> 'INATIVO'");
    mysqli_stmt_bind_param($stmt, "i", $id_sensor);
    mysqli_stmt_execute($stmt);

    if (!mysqli_fetch_row(mysqli_stmt_get_result($stmt))) {
        $erros[] = "Selecione um sensor ativo.";
    }

    if (!is_numeric($valor) || abs($valor) >= 100000000) {
        $erros[] = "Informe um valor numérico válido.";
    }

    if (!$erros) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO leituras (id_sensor, valor) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "id", $id_sensor, $valor);
        mysqli_stmt_execute($stmt);
    }

    return $erros;
}
