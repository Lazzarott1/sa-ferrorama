<?php

include __DIR__ . '/../../infra/verifica-login.php';
include __DIR__ . '/../../infra/valida-trem.php';

$erros = [];
$novo = ['nome_trem' => '', 'modelo_trem' => '', 'capacidade_trem' => '', 'id_trilho' => 0, 'id_usuario' => 0, 'status_trem' => 'ATIVO'];


//    INATIVAR TREM

if ($admin && isset($_POST['inativar'])) {

    $id_trem = (int) $_POST['id_trem'];

    try {
        $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total FROM sensores WHERE id_trem = ? AND status_sensor <> 'INATIVO'");
        mysqli_stmt_bind_param($stmt, "i", $id_trem);
        mysqli_stmt_execute($stmt);
        $sensores = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($sensores['total'] > 0) {
            header("Location: tela-trens.php?bloqueado=" . $sensores['total']);
            exit;
        }

        $stmt = mysqli_prepare($conexao, "UPDATE trens SET status_trem = 'INATIVO' WHERE id_trem = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_trem);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: tela-trens.php");
            exit;
        }
    } catch (Exception $e) {
    }

    $erros[] = "Não foi possível inativar o trem. Tente novamente.";
}


//    CADASTRAR TREM

if ($admin && isset($_POST['cadastrar'])) {

    [$novo, $erros] = validarTrem($conexao);

    if (!$erros) {
        try {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trens (nome_trem, modelo_trem, capacidade_trem, id_trilho, id_usuario, status_trem) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiiis", $novo['nome_trem'], $novo['modelo_trem'], $novo['capacidade_trem'], $novo['id_trilho'], $novo['id_usuario'], $novo['status_trem']);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: tela-trens.php");
                exit;
            }
        } catch (Exception $e) {
        }

        $erros[] = "Não foi possível cadastrar o trem. Tente novamente.";
    }
}


//    BUSCAR DADOS

$filtro = $_GET['filtro'] ?? 'ativos';

$where = [
    'ativos' => "WHERE trens.status_trem <> 'INATIVO'",
    'inativos' => "WHERE trens.status_trem = 'INATIVO'"
][$filtro] ?? "";

try {
    $trens = mysqli_fetch_all(mysqli_query($conexao, "SELECT trens.*, trilhos.nome_trilho, usuarios.nome_usuario
        FROM trens
        LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
        LEFT JOIN usuarios ON usuarios.id_usuario = trens.id_usuario
        $where
        ORDER BY trens.id_trem DESC"), MYSQLI_ASSOC);

    $trilhos = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_trilho, nome_trilho FROM trilhos WHERE status_trilho <> 'INATIVO' ORDER BY nome_trilho"), MYSQLI_ASSOC);

    $usuarios = mysqli_fetch_all(mysqli_query($conexao, "SELECT id_usuario, nome_usuario FROM usuarios WHERE status_usuario = 'ATIVO' ORDER BY nome_usuario"), MYSQLI_ASSOC);
} catch (Throwable $e) {
    $trens = $trilhos = $usuarios = [];
    $erros[] = "Não foi possível carregar os trens. Tente novamente.";
}

$cores = [
    'ATIVO' => 'bg-success-subtle text-success-emphasis border-success-subtle',
    'MANUTENCAO' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle'
];

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trens</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modelo = document.getElementById("modeloTrem");
    const label = document.getElementById("labelCapacidade");
    const input = document.getElementById("capacidadeTrem");

    if (!modelo) {
        return;
    }

    modelo.addEventListener("change", function () {
        const tipos = {
            "De carga": ["CAPACIDADE (KG)", "Ex: 50000"],
            "De passageiros": ["CAPACIDADE (PASSAGEIROS)", "Ex: 300"]
        };
        [label.textContent, input.placeholder] = tipos[this.value] || ["CAPACIDADE", "Selecione o modelo primeiro"];
    });

});
</script>

<body>

    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="container-fluid px-4 mt-4">

        <!-- TÍTULO -->

        <div class="d-flex justify-content-between align-items-end mb-4">

            <div>
                <h3 class="titulo-trens">Trens</h3>
                <p class="subtitulo-trens">Gerencie os trens cadastrados na malha ferroviária</p>
            </div>

            <?php if ($admin) { ?>
                <button type="button" class="btn btn-primary text-white px-3 py-2 d-flex align-items-center gap-2" data-bs-toggle="collapse" data-bs-target="#collapseCadastroTrem" aria-expanded="false" aria-controls="collapseCadastroTrem">
                    <span class="botaonovotrem">+</span>
                    NOVO TREM
                </button>
            <?php } ?>

        </div>

        <?php if ($erros) { ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro) { ?>
                    <div><?php echo $erro; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <!-- FORMULÁRIO -->

        <?php if ($admin) { ?>

        <div class="collapse mb-4 <?php if ($erros && isset($_POST['cadastrar'])) echo 'show'; ?>" id="collapseCadastroTrem">

            <div class="card border-0">

                <div class="cardcadastro p-3">
                    <span class="spancadastrotrem">CADASTRAR NOVO TREM</span>
                </div>

                <form method="POST" class="p-4 bg-white" style="border: 1px solid #BCCCDC; border-top: none;">

                    <div class="row g-4">

                        <div class="col-md-6">
                            <label for="nomeTrem" class="form-label">NOME DO TREM</label>
                            <input type="text" name="nome" id="nomeTrem" class="form-control" placeholder="Ex: Trem 01" value="<?php echo htmlspecialchars($novo['nome_trem']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="modeloTrem" class="form-label">MODELO</label>
                            <select name="modelo" id="modeloTrem" class="form-select" required>
                                <option value="">Selecione o modelo</option>
                                <?php foreach (['De passageiros', 'De carga'] as $opcao) { ?>
                                    <option value="<?php echo $opcao; ?>" <?php if ($novo['modelo_trem'] === $opcao) echo 'selected'; ?>><?php echo $opcao; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="capacidadeTrem" id="labelCapacidade" class="form-label">CAPACIDADE</label>
                            <input type="number" name="capacidade" id="capacidadeTrem" class="form-control" placeholder="Selecione o modelo primeiro" value="<?php echo htmlspecialchars($novo['capacidade_trem']); ?>" min="1" required>
                        </div>

                        <div class="col-md-6">
                            <label for="trilhoTrem" class="form-label">TRILHO ATUAL</label>
                            <select name="id_trilho" id="trilhoTrem" class="form-select" required>
                                <option value="">Selecione o trilho</option>
                                <?php foreach ($trilhos as $trilho) { ?>
                                    <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($novo['id_trilho'] == $trilho['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="responsavelTrem" class="form-label">RESPONSÁVEL</label>
                            <select name="id_usuario" id="responsavelTrem" class="form-select" required>
                                <option value="">Selecione o responsável</option>
                                <?php foreach ($usuarios as $usuario) { ?>
                                    <option value="<?php echo $usuario['id_usuario']; ?>" <?php if ($novo['id_usuario'] == $usuario['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($usuario['nome_usuario']); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="statusTrem" class="form-label">STATUS</label>
                            <select name="status" id="statusTrem" class="form-select" required>
                                <?php foreach (['ATIVO' => 'Ativo', 'MANUTENCAO' => 'Manutenção', 'INATIVO' => 'Inativo'] as $valor => $texto) { ?>
                                    <option value="<?php echo $valor; ?>" <?php if ($novo['status_trem'] === $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="cadastrar" class="btn btn-primary">CADASTRAR</button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseCadastroTrem">CANCELAR</button>
                    </div>

                </form>

            </div>

        </div>

        <?php } ?>

        <!-- TABELA -->

        <div class="d-flex flex-column align-items-center gap-5 w-100" style="padding-top: 60px; min-height: 100vh; background-color: #f8f9fa;">

            <div class="card shadow-sm border-1 p-0" style="width: 1000px; border-radius: 4px;">

                <div class="bg-primary-subtle text-primary-emphasis p-2 border-bottom fw-bold d-flex justify-content-between align-items-center" style="font-size: 0.7rem;">

                    TRENS CADASTRADOS

                    <form method="GET">
                        <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'todos' => 'Todos'] as $valor => $texto) { ?>
                                <option value="<?php echo $valor; ?>" <?php if ($filtro == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                            <?php } ?>
                        </select>
                    </form>

                </div>

                <table class="table table-bordered table-hover mb-0 align-middle">

                    <thead class="table-light">
                        <tr class="text-secondary" style="font-size: 0.75rem;">
                            <?php foreach (['ID', 'NOME', 'MODELO', 'CAPACIDADE', 'TRILHO', 'RESPONSÁVEL', 'STATUS'] as $coluna) { ?>
                                <th class="fw-semibold"><?php echo $coluna; ?></th>
                            <?php } ?>
                            <th class="fw-semibold text-center">AÇÕES</th>
                        </tr>
                    </thead>

                    <tbody style="font-size: 0.85rem;">

                        <?php foreach ($trens as $trem) { ?>

                            <tr>
                                <td class="text-primary-emphasis fw-bold"><?php echo $trem['id_trem']; ?></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($trem['nome_trem']); ?></td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trem['modelo_trem']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($trem['capacidade_trem']); ?></td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trem['nome_trilho'] ?? 'Sem trilho'); ?></td>
                                <td class="text-body-tertiary"><?php echo htmlspecialchars($trem['nome_usuario'] ?? 'Sem responsável'); ?></td>
                                <td>
                                    <span class="badge border rounded-1 <?php echo $cores[$trem['status_trem']] ?? 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'; ?>">
                                        <?php echo htmlspecialchars($trem['status_trem']); ?>
                                    </span>
                                </td>
                                <td class="text-center">

                                    <?php if ($admin) { ?>

                                        <a href="tela-editar-trens.php?id=<?php echo $trem['id_trem']; ?>" class="btn btn-sm btn-outline-primary me-1">EDITAR</a>

                                        <?php if ($trem['status_trem'] != 'INATIVO') { ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja inativar este trem?');">
                                                <input type="hidden" name="id_trem" value="<?php echo $trem['id_trem']; ?>">
                                                <button type="submit" name="inativar" class="btn btn-sm btn-outline-danger">INATIVAR</button>
                                            </form>
                                        <?php } ?>

                                    <?php } else { ?>
                                        -
                                    <?php } ?>

                                </td>
                            </tr>

                        <?php } ?>

                        <?php if (!$trens) { ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum trem cadastrado.</td>
                            </tr>
                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <!-- AVISO -->

    <div class="modal fade" id="modalTremBloqueado" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-1 border-0">
                <div class="modal-header text-white py-2" style="background-color: #1b3f53;">
                    <h6 class="modal-title fw-bold mb-0">Não é possível inativar este trem</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Este trem tem <?php echo (int) ($_GET['bloqueado'] ?? 0); ?> sensor(es) ativo(s).
                    Inative ou altere os sensores ligados a ele antes de inativá-lo.
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Entendi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (isset($_GET['bloqueado'])) { ?>
        <script>
            new bootstrap.Modal(document.getElementById('modalTremBloqueado')).show();
        </script>
    <?php } ?>

</body>

</html>
