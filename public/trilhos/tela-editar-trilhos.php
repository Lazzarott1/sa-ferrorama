<?php

include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trilho.php';

$id_trilho = (int) ($_POST['id_trilho'] ?? $_GET['id'] ?? 0);
$erros = [];


//    BUSCAR TRILHO

$stmt = mysqli_prepare($conexao, "SELECT trilhos.*, (SELECT id_trem FROM trens WHERE trens.id_trilho = trilhos.id_trilho AND status_trem <> 'INATIVO' LIMIT 1) AS id_trem FROM trilhos WHERE id_trilho = ?");
mysqli_stmt_bind_param($stmt, "i", $id_trilho);
mysqli_stmt_execute($stmt);
$trilho = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$trilho) {
    header("Location: tela-cadastro-trilhos.php");
    exit;
}


//    ATUALIZAR TRILHO

if (isset($_POST['editar'])) {

    [$dados, $erros] = validarTrilho($conexao, false);
    $trilho = array_merge($trilho, $dados);

    if (!$erros && $trilho['status_trilho'] == 'INATIVO') {

        $stmt = mysqli_prepare($conexao, "SELECT
            (SELECT COUNT(*) FROM trens WHERE id_trilho = ? AND status_trem <> 'INATIVO') AS total_trens,
            (SELECT COUNT(*) FROM sensores WHERE id_trilho = ? AND status_sensor <> 'INATIVO') AS total_sensores");
        mysqli_stmt_bind_param($stmt, "ii", $id_trilho, $id_trilho);
        mysqli_stmt_execute($stmt);
        $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($total['total_trens'] > 0 || $total['total_sensores'] > 0) {
            $erros[] = "Este trilho tem " . $total['total_trens'] . " trem(ns) e " . $total['total_sensores'] . " sensor(es) ativo(s). Inative ou altere esses itens antes de inativar o trilho.";
        }
    }

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "UPDATE trilhos SET nome_trilho = ?, descricao_trilho = ?, km_trilho = ?, tempo_previsto_min = ?, status_trilho = ? WHERE id_trilho = ?");
            mysqli_stmt_bind_param($stmt, "sssisi", $trilho['nome_trilho'], $trilho['descricao_trilho'], $trilho['km_trilho'], $trilho['tempo_previsto_min'], $trilho['status_trilho'], $id_trilho);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conexao, "UPDATE trens SET id_trilho = IF(id_trem = ?, ?, NULL) WHERE id_trem = ? OR (id_trilho = ? AND status_trem <> 'INATIVO')");
            mysqli_stmt_bind_param($stmt, "iiii", $trilho['id_trem'], $id_trilho, $trilho['id_trem'], $id_trilho);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: tela-cadastro-trilhos.php");
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível salvar o trilho. Tente novamente.";
    }
}

$trens = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trem, nome_trem FROM trens WHERE status_trem <> 'INATIVO' ORDER BY nome_trem"), MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Trilho</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="container-fluid px-4 mt-4">

        <!-- TÍTULO -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>
                <h3 class="titulo-sensores">Editar Trilho</h3>
                <p class="subtitulo-sensores">Atualize as informações do trilho selecionado</p>
            </div>

            <a href="tela-cadastro-trilhos.php" class="btn btn-secondary text-white px-3 py-2">VOLTAR</a>

        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro) { ?>
                    <div><?php echo $erro; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- FORMULÁRIO -->

        <div class="d-flex flex-column align-items-center gap-5 w-100" style="padding-top: 20px; min-height: 60vh; background-color: #f8f9fa;">

            <div class="card border-0" style="width: 1000px;">

                <div class="cardcadastro p-3">
                    <span class="spancadastrosensor">EDITAR TRILHO #<?php echo $trilho['id_trilho']; ?></span>
                </div>

                <form method="POST" class="p-4 bg-white" style="border: 1px solid #BCCCDC; border-top: none;">

                    <input type="hidden" name="id_trilho" value="<?php echo $trilho['id_trilho']; ?>">

                    <div class="row g-4">

                        <div class="col-md-6">
                            <label for="nomeTrilho" class="form-label">NOME DO TRILHO</label>
                            <input type="text" name="nome" id="nomeTrilho" class="form-control" value="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>" placeholder="Ex: Trilho Norte 01" required>
                        </div>

                        <div class="col-md-6">
                            <label for="kmTrilho" class="form-label">KM</label>
                            <input type="number" name="km" id="kmTrilho" class="form-control" step="0.01" min="0.01" value="<?php echo htmlspecialchars($trilho['km_trilho']); ?>" placeholder="Ex: 12.5" required>
                        </div>

                        <div class="col-md-8">
                            <label for="descricaoTrilho" class="form-label">DESCRIÇÃO</label>
                            <input type="text" name="descricao" id="descricaoTrilho" class="form-control" value="<?php echo htmlspecialchars($trilho['descricao_trilho']); ?>" placeholder="Ex: Trecho entre os pátios A e B" required>
                        </div>

                        <div class="col-md-4">
                            <label for="statusTrilho" class="form-label">STATUS</label>
                            <select name="status" id="statusTrilho" class="form-select" required>
                                <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                    <option value="<?php echo $valor; ?>" <?php if ($trilho['status_trilho'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="tremTrilho" class="form-label">TREM VINCULADO</label>
                            <select name="id_trem" id="tremTrilho" class="form-select">
                                <option value="">Nenhum trem</option>
                                <?php foreach ($trens as $trem) { ?>
                                    <option value="<?php echo $trem['id_trem']; ?>" <?php if ($trem['id_trem'] == $trilho['id_trem']) echo 'selected'; ?>><?php echo htmlspecialchars($trem['nome_trem']); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="tempoTrilho" class="form-label">TEMPO PREVISTO (MIN)</label>
                            <input type="number" name="tempo" id="tempoTrilho" class="form-control" min="1" value="<?php echo htmlspecialchars($trilho['tempo_previsto_min']); ?>" placeholder="Ex: 15" required>
                        </div>

                        <div class="col-md-4">
                            <label for="dataTrilho" class="form-label">DATA DE CRIAÇÃO</label>
                            <input type="text" id="dataTrilho" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($trilho['data_criacao'])); ?>" disabled>
                        </div>

                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="editar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                        <a href="tela-cadastro-trilhos.php" class="btn btn-secondary">CANCELAR</a>
                    </div>

                </form>

            </div>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
