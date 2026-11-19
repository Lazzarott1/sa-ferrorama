<?php

// confere uma leitura antes de salvar (usado no dashboard)
// devolve a lista de erros, se vier vazia pode salvar
function validarLeitura($conexao, $id_sensor, $valor)
{
    $erros = [];

    // o sensor tem que existir e não pode estar inativo
    $sensor = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT status_sensor FROM sensores WHERE id_sensor = $id_sensor"));

    if (!$sensor || $sensor['status_sensor'] == 'INATIVO') {
        $erros[] = "Selecione um sensor ativo.";
    }

    // o valor tem que ser número e não pode ser gigante (não cabe no banco)
    if (!is_numeric($valor) || abs($valor) >= 100000000) {
        $erros[] = "Informe um valor numérico válido.";
    }

    return $erros;
}
