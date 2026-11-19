<?php

// confere os dados do formulário de sensor (cadastro e edição)
// $id é 0 no cadastro; devolve a lista de erros
function validarSensor($conexao, $id, $nome, $tipo, $id_trilho, $status)
{
    $erros = [];

    // nome obrigatório e no banco cabe só 100 caracteres
    if ($nome == '') {
        $erros[] = "Informe o nome do sensor.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do sensor pode ter no máximo 100 caracteres.";
    }

    // só aceita os valores que existem no select (alguém pode mexer no HTML pelo navegador)
    if ($tipo != 'Velocidade' && $tipo != 'Localização' && $tipo != 'Temperatura') {
        $erros[] = "Selecione um tipo válido.";
    }

    if ($status != 'ATIVO' && $status != 'ALERTA' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // O trilho precisa estar ativo, a não ser que o sensor já estivesse nele
    $sensor_atual = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT id_trilho FROM sensores WHERE id_sensor = $id"));

    $trilho = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT status_trilho FROM trilhos WHERE id_trilho = $id_trilho"));

    if (!$trilho) {
        $erros[] = "Selecione um trilho ativo.";
    } elseif ($trilho['status_trilho'] == 'INATIVO' && (!$sensor_atual || $sensor_atual['id_trilho'] != $id_trilho)) {
        $erros[] = "Selecione um trilho ativo.";
    }

    return $erros;
}
