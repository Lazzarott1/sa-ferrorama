<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trem.php';

$id_trem = (int) ($_POST['id_trem'] ?? $_GET['id'] ?? 0);
$erros = [];


//    BUSCAR TREM

$stmt = mysqli_prepare($conexao, "SELECT * FROM trens WHERE id_trem = ?");
mysqli_stmt_bind_param($stmt, "i", $id_trem);
mysqli_stmt_execute($stmt);
$trem = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trem) {
    header("Location: tela-trens.php");
    exit;
}

$trilho_atual = (int) $trem['id_trilho'];
$usuario_atual = (int) $trem['id_usuario'];


//    ATUALIZAR TREM

if (isset($_POST['editar'])) {

    [$dados, $erros] = validarTrem($conexao, $trilho_atual, $usuario_atual);
    $trem = array_merge($trem, $dados);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "UPDATE trens SET nome_trem = ?, modelo_trem = ?, capacidade_trem = ?, id_trilho = ?, id_usuario = ?, status_trem = ? WHERE id_trem = ?");
            mysqli_stmt_bind_param($stmt, "ssiiisi", $trem['nome_trem'], $trem['modelo_trem'], $trem['capacidade_trem'], $trem['id_trilho'], $trem['id_usuario'], $trem['status_trem'], $id_trem);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: tela-trens.php");
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível salvar o trem. Tente novamente.";
    }
}


//    BUSCAR OPÇÕES

$stmt = mysqli_prepare($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' OR id_trilho = ? ORDER BY nome_trilho");
mysqli_stmt_bind_param($stmt, "i", $trilho_atual);
mysqli_stmt_execute($stmt);
$resultadoTrilhos = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario FROM usuarios WHERE status_usuario = 'ATIVO' OR id_usuario = ? ORDER BY nome_usuario");
mysqli_stmt_bind_param($stmt, "i", $usuario_atual);
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

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Editar trem</h3>
                <p>Atualize as informações do trem selecionado</p>
            </div>
        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">EDITAR TREM #<?php echo $trem['id_trem']; ?></div>
            <form method="POST" class="caixa-corpo">
                <input type="hidden" name="id_trem" value="<?php echo $trem['id_trem']; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeTrem" class="form-label">NOME DO TREM</label>
                        <input type="text" name="nome" id="nomeTrem" class="form-control" value="<?php echo htmlspecialchars($trem['nome_trem']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="modeloTrem" class="form-label">MODELO</label>
                        <select name="modelo" id="modeloTrem" class="form-select" required>
                            <option value="">Selecione o modelo</option>
                            <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>
                                <option <?php if ($trem['modelo_trem'] === $opcao) echo 'selected'; ?>><?php echo $opcao; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="capacidadeTrem" id="labelCapacidade" class="form-label">CAPACIDADE</label>
                        <input type="number" name="capacidade" id="capacidadeTrem" class="form-control" value="<?php echo htmlspecialchars($trem['capacidade_trem']); ?>" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label for="trilhoTrem" class="form-label">TRILHO ATUAL</label>
                        <select name="id_trilho" id="trilhoTrem" class="form-select" required>
                            <option value="">Selecione o trilho</option>
                            <?php while ($trilho = mysqli_fetch_assoc($resultadoTrilhos)) { ?>
                                <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trem['id_trilho'] == $trilho['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="responsavelTrem" class="form-label">RESPONSÁVEL</label>
                        <select name="id_usuario" id="responsavelTrem" class="form-select" required>
                            <option value="">Selecione o responsável</option>
                            <?php while ($usuario = mysqli_fetch_assoc($resultadoUsuarios)) { ?>
                                <option value="<?php echo $usuario['id_usuario']; ?>" <?php if ($trem['id_usuario'] == $usuario['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($usuario['nome_usuario']); ?></option>
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
                    <button type="submit" name="editar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    <a href="tela-trens.php" class="btn btn-secondary">CANCELAR</a>
                </div>
            </form>
        </div>

    </main>

    <script src="../../script/capacidade.js"></script>

</body>

</html>
