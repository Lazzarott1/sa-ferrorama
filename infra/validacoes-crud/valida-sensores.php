<?php

function validarSensor($conexao, $trilho_atual = 0)
{
    $sensor = [
        'nome_sensor' => trim($_POST['nome'] ?? ''),
        'tipo_sensor' => $_POST['tipo'] ?? '',
        'id_trilho' => (int) ($_POST['trilho'] ?? 0),
        'status_sensor' => $_POST['status'] ?? ''
    ];

    $erros = [];

    if ($sensor['nome_sensor'] === '') {
        $erros[] = "Informe o nome do sensor.";
    } elseif (mb_strlen($sensor['nome_sensor']) > 100) {
        $erros[] = "O nome do sensor pode ter no máximo 100 caracteres.";
    }

    if (!in_array($sensor['tipo_sensor'], ['Velocidade', 'Localização', 'Temperatura'], true)) {
        $erros[] = "Selecione um tipo válido.";
    }

    if (!in_array($sensor['status_sensor'], ['ATIVO', 'ALERTA', 'INATIVO'], true)) {
        $erros[] = "Selecione um status válido.";
    }

    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM trilhos WHERE id_trilho = ? AND (status_trilho <> 'INATIVO' OR id_trilho = ?)");
    mysqli_stmt_bind_param($stmt, "ii", $sensor['id_trilho'], $trilho_atual);
    mysqli_stmt_execute($stmt);

    if (!mysqli_fetch_row(mysqli_stmt_get_result($stmt))) {
        $erros[] = "Selecione um trilho ativo.";
    }

    return [$sensor, $erros];
}
