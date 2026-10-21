<?php

include __DIR__ . '/../../infra/verifica-admin.php';
include '../../infra/conexao.php';

if (!isset($conexao) || $conexao === false) {
    die("Erro: conexão com o banco de dados não estabelecida.");
}

$id_trem = (int) ($_POST['id_trem'] ?? $_GET['id'] ?? 0);


//    ATUALIZAR TREM

if (isset($_POST['editar'])) {

    $nome = $_POST['nome'];
    $modelo = $_POST['modelo'];
    $capacidade = (int) $_POST['capacidade'];
    $id_trilho = (int) $_POST['id_trilho'];
    $id_usuario = (int) $_POST['id_usuario'];
    $status = $_POST['status'];

    if ($status == 'INATIVO') {

        $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total FROM sensores WHERE id_trem = ? AND status_sensor <> 'INATIVO'");
        mysqli_stmt_bind_param($stmt, "i", $id_trem);
        mysqli_stmt_execute($stmt);
        $sensores = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($sensores['total'] > 0) {
            header("Location: tela-editar-trens.php?id=" . $id_trem . "&bloqueado=" . $sensores['total']);
            exit;
        }
    }

    $stmt = mysqli_prepare($conexao, "UPDATE trens SET nome_trem = ?, modelo_trem = ?, capacidade_trem = ?, id_trilho = ?, id_usuario = ?, status_trem = ? WHERE id_trem = ?");
    mysqli_stmt_bind_param($stmt, "ssiiisi", $nome, $modelo, $capacidade, $id_trilho, $id_usuario, $status, $id_trem);

    if (!mysqli_stmt_execute($stmt)) {
        die("Erro ao atualizar trem: " . mysqli_stmt_error($stmt));
    }

    header("Location: tela-trens.php");
    exit;
}


//    BUSCAR DADOS

$stmt = mysqli_prepare($conexao, "SELECT * FROM trens WHERE id_trem = ?");
mysqli_stmt_bind_param($stmt, "i", $id_trem);
mysqli_stmt_execute($stmt);
$trem = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trem) {
    header("Location: tela-trens.php");
    exit;
}

$stmt = mysqli_prepare($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' OR id_trilho = ? ORDER BY nome_trilho");
mysqli_stmt_bind_param($stmt, "i", $trem['id_trilho']);
mysqli_stmt_execute($stmt);
$resultadoTrilhos = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario FROM usuarios WHERE status_usuario = 'ATIVO' OR id_usuario = ? ORDER BY nome_usuario");
mysqli_stmt_bind_param($stmt, "i", $trem['id_usuario']);
mysqli_stmt_execute($stmt);
$resultadoUsuarios = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Trem</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modelo = document.getElementById("modeloTrem");
    const label = document.getElementById("labelCapacidade");
    const input = document.getElementById("capacidadeTrem");

    function atualizarCapacidade() {
        const tipos = {
            "De carga": ["CAPACIDADE (KG)", "Ex: 50000"],
            "De passageiros": ["CAPACIDADE (PASSAGEIROS)", "Ex: 300"]
        };
        [label.textContent, input.placeholder] = tipos[modelo.value] || ["CAPACIDADE", "Selecione o modelo primeiro"];
    }

    modelo.addEventListener("change", atualizarCapacidade);
    atualizarCapacidade();

});
</script>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="container-fluid px-4 mt-4">

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="titulo-trens">Editar trem</h3>
                <p class="subtitulo-trens">Altere os dados do trem selecionado</p>
            </div>
        </div>

        <?php if (isset($_GET['bloqueado'])) { ?>
            <div class="alert alert-danger">
                Este trem tem <?php echo (int) $_GET['bloqueado']; ?> sensor(es) ativo(s). Inative ou altere os sensores antes de inativar o trem.
            </div>
        <?php } ?>

        <!-- FORMULÁRIO -->

        <div class="card border-0 mb-4">

            <div class="cardcadastro p-3">
                <span class="spancadastrotrem">EDITAR TREM #<?php echo $trem['id_trem']; ?></span>
            </div>

            <form method="POST" class="p-4 bg-white" style="border: 1px solid #BCCCDC; border-top: none;">

                <input type="hidden" name="id_trem" value="<?php echo $trem['id_trem']; ?>">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="nomeTrem" class="form-label">NOME DO TREM</label>
                        <input type="text" name="nome" id="nomeTrem" class="form-control" value="<?php echo htmlspecialchars($trem['nome_trem']); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="modeloTrem" class="form-label">MODELO</label>
                        <select name="modelo" id="modeloTrem" class="form-select" required>
                            <option value="">Selecione o modelo</option>
                            <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>
                                <option value="<?php echo $opcao; ?>" <?php if ($trem['modelo_trem'] === $opcao) echo 'selected'; ?>><?php echo $opcao; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="capacidadeTrem" id="labelCapacidade" class="form-label">CAPACIDADE</label>
                        <input type="number" name="capacidade" id="capacidadeTrem" class="form-control" value="<?php echo (int) $trem['capacidade_trem']; ?>" min="1" required>
                    </div>

                    <div class="col-md-6">
                        <label for="trilhoTrem" class="form-label">TRILHO ATUAL</label>
                        <select name="id_trilho" id="trilhoTrem" class="form-select" required>
                            <option value="">Selecione o trilho</option>
                            <?php while ($trilho = mysqli_fetch_assoc($resultadoTrilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ((int) $trem['id_trilho'] === (int) $trilho['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="responsavelTrem" class="form-label">RESPONSÁVEL</label>
                        <select name="id_usuario" id="responsavelTrem" class="form-select" required>
                            <option value="">Selecione o responsável</option>
                            <?php while ($usuario = mysqli_fetch_assoc($resultadoUsuarios)) { ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>" <?php if ((int) $trem['id_usuario'] === (int) $usuario['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($usuario['nome_usuario']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="statusTrem" class="form-label">STATUS</label>
                        <select name="status" id="statusTrem" class="form-select" required>
                            <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($trem['status_trem'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="editar" class="btn btn-primary">SALVAR</button>
                    <a href="tela-trens.php" class="btn btn-secondary">CANCELAR</a>
                </div>

            </form>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
