<?php

include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}


//    EXCLUIR SENSOR

if (isset($_POST['excluir'])) {

    $id_sensor = (int) $_POST['id_sensor'];

    $sql = "DELETE FROM sensores WHERE id_sensor = ?";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar exclusão: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_sensor);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao excluir sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    CADASTRAR SENSOR

if (isset($_POST['cadastrar'])) {

    $nome = $_POST['nome'];
    $categoria = $_POST['categoria'];
    $tipo = $_POST['tipo'];
    $trilho = $_POST['trilho'];
    $status = $_POST['status'];

    $sql = "INSERT INTO sensores
            (
                nome_sensor,
                categoria_sensor,
                tipo_sensor,
                trilho_sensor,
                status_sensor
            )
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        die("Erro ao preparar cadastro: " . mysqli_error($conexao));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $nome,
        $categoria,
        $tipo,
        $trilho,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao cadastrar sensor: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    header("Location: tela-cadastro-sensores.php");
    exit;
}


//    BUSCAR SENSORES

$sql = "SELECT * FROM sensores ORDER BY id_sensor DESC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    die("Erro ao buscar sensores: " . mysqli_error($conexao));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Sensores</title>

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

