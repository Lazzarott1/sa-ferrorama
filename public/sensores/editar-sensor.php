<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    VALIDAR ID RECEBIDO

if (!isset($_GET['id']) && !isset($_POST['id_sensor'])) {
    header("Location: tela-cadastro-sensores.php");
    exit;
}

$id_sensor = isset($_POST['id_sensor'])
    ? (int) $_POST['id_sensor']
    : (int) $_GET['id'];


//    ATUALIZAR SENSOR

if (isset($_POST['editar'])) {

    $nome = $_POST['nome'];
    $categoria = $_POST['categoria'];
    $tipo = $_POST['tipo'];
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "UPDATE sensores
            SET
                nome_sensor = ?,
                categoria_sensor = ?,
                tipo_sensor = ?,
                trilho_sensor = ?,
                status_sensor = ?
            WHERE id_sensor = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar atualização: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssi",
        $nome,
        $categoria,
        $tipo,
        $trilho,
        $status,
        $id_sensor
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    BUSCAR SENSOR PARA PREENCHER O FORMULÁRIO

$sql = "SELECT * FROM sensores WHERE id_sensor = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar busca: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id_sensor);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao buscar sensor: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    header("Location: tela-cadastro-sensores.php");
    exit;
}

$sensor = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Editar Sensor</title>

    <link rel="stylesheet"
        href="../../assets/img/style/style.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body>


         <!-- HEADER -->

    <header class="container-fluid p-2 rounded-0"
        style="background-color: #1b3f53; color: #ffffff;">

        <div id="header"
            class="hstack gap-3 px-2">


            <!-- LOGO -->

            <div class="d-flex"
                id="logo">

                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png"
                    alt="Logo">

                <div class="nome-sistema">

                    <h2 class="mb-0 text-white">
                        FerroMonitor
                    </h2>

                    <p>
                        SISTEMA FERROVIÁRIO
                    </p>

                </div>

            </div>