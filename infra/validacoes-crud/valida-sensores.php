<?php

function validarSensor($conexao, $trem_atual = 0, $trilho_atual = 0)
{
    $sensor = [
        'nome_sensor' => trim($_POST['nome'] ?? ''),
        'categoria_sensor' => $_POST['categoria'] ?? '',
        'tipo_sensor' => $_POST['tipo'] ?? '',
        'vinculo' => (int) ($_POST['trilho'] ?? 0),
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

    $ehTrem = $sensor['categoria_sensor'] == 'TREM';
    $sensor['id_trem'] = $ehTrem ? $sensor['vinculo'] : null;
    $sensor['id_trilho'] = $ehTrem ? null : $sensor['vinculo'];

    if (!in_array($sensor['categoria_sensor'], ['TREM', 'TRILHO'], true)) {
        $erros[] = "Selecione uma categoria válida.";
        return [$sensor, $erros];
    }

    $item = $ehTrem ? 'trem' : 'trilho';
    $tabela = $ehTrem ? 'trens' : 'trilhos';
    $atual = $ehTrem ? $trem_atual : $trilho_atual;

    $stmt = mysqli_prepare($conexao, "SELECT 1 FROM $tabela WHERE id_$item = ? AND (status_$item <> 'INATIVO' OR id_$item = ?)");
    mysqli_stmt_bind_param($stmt, "ii", $sensor['vinculo'], $atual);
    mysqli_stmt_execute($stmt);

    if (!mysqli_fetch_row(mysqli_stmt_get_result($stmt))) {
        $erros[] = "Selecione um $item ativo.";
    }

    return [$sensor, $erros];
}
