<?php

// tela de trilhos: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include '../../infra/validacoes-usuarios/verifica-login.php';

$erros = [];

// qual formulário deu erro (0 = cadastro, outro número = id do trilho editado, -1 = nenhum)
$id_com_erro = -1;

// conta os trens e sensores ativos de um trilho (não deixa inativar trilho em uso)
$sql_contar = "SELECT
    (SELECT COUNT(*) FROM trens WHERE id_trilho = ? AND status_trem <> 'INATIVO') AS trens,
    (SELECT COUNT(*) FROM sensores WHERE id_trilho = ? AND status_sensor <> 'INATIVO') AS sensores";


// ATIVAR / INATIVAR OS TRILHOS MARCADOS

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['ids'])) {
    $nao_inativados = 0;

    foreach ($_POST['ids'] as $id_marcado) {
        $id_marcado = (int) $id_marcado;

        if (isset($_POST['ativar'])) {
            // só volta pra ATIVO o que estava inativo (manutenção continua)
            $sql = "UPDATE trilhos SET status_trilho = 'ATIVO'
                WHERE id_trilho = ? AND status_trilho = 'INATIVO'";
        } else {
            $stmt = mysqli_prepare($conexao, $sql_contar);
            mysqli_stmt_bind_param($stmt, 'ii', $id_marcado, $id_marcado);
            mysqli_stmt_execute($stmt);
            $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            // tem trem ou sensor ativo: pula esse
            if ($total['trens'] > 0 || $total['sensores'] > 0) {
                $nao_inativados++;
                continue;
            }

            $sql = "UPDATE trilhos SET status_trilho = 'INATIVO' WHERE id_trilho = ?";
        }

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // deu tudo certo: volta pra mesma tela (mantendo o filtro)
    if ($nao_inativados == 0) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $erros[] = "Trilhos com trens ou sensores ativos não foram inativados. "
        . "Inative ou altere esses itens antes.";
}


// CADASTRAR OU EDITAR TRILHO (id 0 é cadastro, id maior que 0 é edição)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $km = str_replace(',', '.', trim($_POST['km'])); // aceita 12,5 ou 12.5
    $status = $_POST['status'];

    if ($nome == '') {
        $erros[] = "Informe o nome do trilho.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do trilho pode ter no máximo 100 caracteres.";
    }

    if ($descricao == '') {
        $erros[] = "Informe a descrição do trilho.";
    } elseif (mb_strlen($descricao) > 255) {
        $erros[] = "A descrição pode ter no máximo 255 caracteres.";
    }

    if (!is_numeric($km) || $km <= 0 || strlen($km) > 20) {
        $erros[] = "O km deve ser um número maior que zero.";
    }

    if ($status != 'ATIVO' && $status != 'MANUTENCAO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // mudou pra inativo na edição? confere se não tem trem nem sensor ativo nele
    if ($id != 0 && $status == 'INATIVO') {
        $stmt = mysqli_prepare($conexao, $sql_contar);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $id);
        mysqli_stmt_execute($stmt);
        $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($total['trens'] > 0 || $total['sensores'] > 0) {
            $erros[] = "Este trilho tem " . $total['trens'] . " trem(ns) e "
                . $total['sensores'] . " sensor(es) ativo(s). "
                . "Inative ou altere esses itens antes de inativar o trilho.";
        }
    }

    if (count($erros) == 0) {
        if ($id == 0) {
            $sql = "INSERT INTO trilhos (nome_trilho, descricao_trilho, km_trilho, status_trilho)
                VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'ssss', $nome, $descricao, $km, $status);
        } else {
            $sql = "UPDATE trilhos
                SET nome_trilho = ?, descricao_trilho = ?, km_trilho = ?, status_trilho = ?
                WHERE id_trilho = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssi', $nome, $descricao, $km, $status, $id);
        }

        // salvou: recarrega a tela pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o trilho: " . mysqli_error($conexao);
        mysqli_stmt_close($stmt);
    }

    // deu erro: reabre o formulário com o que foi digitado
    $id_com_erro = $id;
}


// LISTA DE TRILHOS (com os trens e sensores ativos de cada um)

$filtro = 'ativos';
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
}

$where = "";
if ($filtro == 'ativos') {
    $where = "WHERE status_trilho <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE status_trilho = 'INATIVO'";
} elseif ($filtro == 'manutencao') {
    $where = "WHERE status_trilho = 'MANUTENCAO'";
}

// o GROUP_CONCAT junta o nome dos trens do trilho (coluna TRENS ASSOCIADOS)
$sql = "SELECT trilhos.*,
    (SELECT COUNT(*) FROM trens
        WHERE trens.id_trilho = trilhos.id_trilho AND status_trem <> 'INATIVO') AS total_trens,
    (SELECT COUNT(*) FROM sensores
        WHERE sensores.id_trilho = trilhos.id_trilho AND status_sensor <> 'INATIVO')
        AS total_sensores,
    (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens
        WHERE trens.id_trilho = trilhos.id_trilho AND status_trem <> 'INATIVO')
        AS trens_associados
    FROM trilhos $where
    ORDER BY id_trilho DESC";
$trilhos = mysqli_query($conexao, $sql);

if ($trilhos === false) {
    die('Erro ao consultar trilhos: ' . mysqli_error($conexao));
}

// formulário de cadastro: em branco, ou o que foi digitado se deu erro nele
$novo_nome = '';
$novo_descricao = '';
$novo_km = '';
$novo_status = 'ATIVO';
$abrir_cadastro = '';

if ($id_com_erro === 0) {
    $novo_nome = htmlspecialchars($nome);
    $novo_descricao = htmlspecialchars($descricao);
    $novo_km = htmlspecialchars($km);
    $novo_status = $status;
}

// o cadastro já vem aberto se deu erro nele ou se veio da home com ?novo
if ($id_com_erro === 0 || isset($_GET['novo'])) {
    $abrir_cadastro = 'show';
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trilhos</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/img/style/style.css">
</head>

<body>
    <?php include '../navbar.php'; ?>

    <main class="pagina">
        <div class="cabecalho">
            <div>
                <h3>Trilhos</h3>
                <p>Gerencie os trilhos cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">
                    + NOVO TRILHO
                </button>
            <?php } ?>
        </div>

        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <!-- CADASTRO (começa fechado) -->
            <div class="caixa collapse <?php echo $abrir_cadastro; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TRILHO</div>
                <form method="POST" class="caixa-corpo">
                    <input type="hidden" name="id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome0" class="form-label">NOME DO TRILHO</label>
                            <input type="text" name="nome" id="nome0" class="form-control"
                                placeholder="Ex: Trilho Norte 01" value="<?php echo $novo_nome; ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="km0" class="form-label">KM</label>
                            <input type="number" name="km" id="km0" class="form-control"
                                step="0.01" min="0.01" placeholder="Ex: 12.5"
                                value="<?php echo $novo_km; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="descricao0" class="form-label">DESCRIÇÃO</label>
                            <input type="text" name="descricao" id="descricao0" class="form-control"
                                placeholder="Ex: Trecho do pátio A ao B"
                                value="<?php echo $novo_descricao; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="status0" class="form-label">STATUS</label>
                            <select name="status" id="status0" class="form-select" required>
                                <option value="ATIVO">Ativo</option>
                                <option value="MANUTENCAO"
                                    <?php if ($novo_status == 'MANUTENCAO') echo 'selected'; ?>>
                                    Manutenção
                                </option>
                                <option value="INATIVO"
                                    <?php if ($novo_status == 'INATIVO') echo 'selected'; ?>>
                                    Inativo
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="salvar" class="btn btn-primary">
                            CADASTRAR
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="collapse"
                            data-bs-target="#editar0">CANCELAR</button>
                    </div>
                </form>
            </div>
        <?php } ?>

        <div class="caixa">
            <div class="caixa-topo">
                TRILHOS CADASTRADOS
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($admin) { ?>
                        <!-- só aparecem com 2 ou mais marcados (listagem.js) -->
                        <div id="emMassa" class="d-flex gap-2 d-none">
                            <button type="submit" form="formStatus" name="ativar"
                                class="btn btn-sm btn-light">ATIVAR SELECIONADOS</button>
                            <button type="button" id="inativarSelecionados"
                                class="btn btn-sm btn-outline-light" data-bs-toggle="modal"
                                data-bs-target="#modalInativar">INATIVAR SELECIONADOS</button>
                        </div>
                    <?php } ?>
                    <!-- trocar o filtro já recarrega a tela (listagem.js) -->
                    <form method="GET">
                        <select name="filtro" id="filtro" class="form-select form-select-sm">
                            <option value="ativos">ATIVOS</option>
                            <option value="inativos"
                                <?php if ($filtro == 'inativos') echo 'selected'; ?>>
                                INATIVOS
                            </option>
                            <option
                                value="manutencao"
                                <?php if ($filtro == 'manutencao') echo 'selected'; ?>>
                                EM MANUTENÇÃO
                            </option>
                            <option value="todos" <?php if ($filtro == 'todos') echo 'selected'; ?>>
                                TODOS
                            </option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <?php if ($admin) { ?>
                                <th>
                                    <input type="checkbox" id="marcarTodos"
                                        class="form-check-input">
                                </th>
                            <?php } ?>
                            <th>ID</th>
                            <th>NOME</th>
                            <th>DESCRIÇÃO</th>
                            <th>KM</th>
                            <th>TRENS ASSOCIADOS</th>
                            <th>CRIADO EM</th>
                            <th>STATUS</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trilho = mysqli_fetch_assoc($trilhos)) {
                            $id = $trilho['id_trilho'];
                            $status = $trilho['status_trilho'];
                            $nome = htmlspecialchars($trilho['nome_trilho']);
                            $descricao = htmlspecialchars($trilho['descricao_trilho']);
                            $km = htmlspecialchars($trilho['km_trilho']);
                            $trens = '';
                            if ($trilho['trens_associados'] != null) {
                                $trens = htmlspecialchars($trilho['trens_associados']);
                            }
                            $data = date('d/m/Y', strtotime($trilho['data_criacao']));

                            // trem ou sensor ativo: o motivo aparece no pop-up de inativar
                            $bloqueio = '';
                            if ($trilho['total_trens'] > 0 || $trilho['total_sensores'] > 0) {
                                $bloqueio = 'Ele tem ' . $trilho['total_trens'] . ' trem(ns) e '
                                    . $trilho['total_sensores'] . ' sensor(es) ativo(s). '
                                    . 'Inative ou altere esses itens antes de inativá-lo.';
                            }

                            // formulário de edição: dados do banco, ou o digitado se deu erro
                            $form_nome = $nome;
                            $form_descricao = $descricao;
                            $form_km = $km;
                            $form_status = $status;
                            $abrir_edicao = '';

                            if ($id_com_erro == $id) {
                                $form_nome = htmlspecialchars(trim($_POST['nome']));
                                $form_descricao = htmlspecialchars(trim($_POST['descricao']));
                                $form_km = str_replace(',', '.', trim($_POST['km']));
                                $form_km = htmlspecialchars($form_km);
                                $form_status = $_POST['status'];
                                $abrir_edicao = 'show';
                            }
                        ?>
                            <tr>
                                <?php if ($admin) { ?>
                                    <td>
                                        <input type="checkbox" class="form-check-input marcar"
                                            form="formStatus" name="ids[]"
                                            value="<?php echo $id; ?>"
                                            data-nome="o trilho <?php echo $nome; ?>"
                                            data-bloqueio="<?php echo $bloqueio; ?>">
                                    </td>
                                <?php } ?>
                                <td><?php echo $id; ?></td>
                                <td><?php echo $nome; ?></td>
                                <td><?php echo $descricao; ?></td>
                                <td class="text-nowrap"><?php echo $km; ?> km</td>
                                <td>
                                    <?php if ($trens == '') { ?>
                                        <span class="text-muted">Nenhum trem</span>
                                    <?php } else { echo $trens; } ?>
                                </td>
                                <td><?php echo $data; ?></td>
                                <td>
                                    <span class="badge status status-<?php echo $status; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if (!$admin) { ?>
                                        -
                                    <?php } else { ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editar<?php echo $id; ?>">
                                            EDITAR
                                        </button>
                                        <?php if ($status == 'INATIVO') { ?>
                                            <button type="submit" form="formStatus" name="ativar"
                                                class="btn btn-sm btn-primary botao-ativar"
                                                data-id="<?php echo $id; ?>">ATIVAR</button>
                                        <?php } else { ?>
                                            <button
                                                class="btn btn-sm btn-outline-danger botao-inativar"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInativar"
                                                data-id="<?php echo $id; ?>">INATIVAR</button>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            </tr>

                            <?php if ($admin) { ?>
                                <!-- EDIÇÃO: linha escondida embaixo do trilho -->
                                <tr class="collapse <?php echo $abrir_edicao; ?>"
                                    id="editar<?php echo $id; ?>">
                                    <td colspan="9" class="p-0">
                                        <form method="POST" class="caixa-corpo">
                                            <input type="hidden" name="id"
                                                value="<?php echo $id; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">NOME DO TRILHO</label>
                                                    <input type="text" name="nome"
                                                        class="form-control" required
                                                        value="<?php echo $form_nome; ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">KM</label>
                                                    <input type="number" name="km" step="0.01"
                                                        min="0.01" class="form-control" required
                                                        value="<?php echo $form_km; ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">DESCRIÇÃO</label>
                                                    <input type="text" name="descricao"
                                                        class="form-control" required
                                                        value="<?php echo $form_descricao; ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">STATUS</label>
                                                    <select name="status" class="form-select">
                                                        <option value="ATIVO">Ativo</option>
                                                        <option value="MANUTENCAO" <?php
                                                            if ($form_status == 'MANUTENCAO') {
                                                                echo 'selected';
                                                            } ?>>Manutenção</option>
                                                        <option value="INATIVO" <?php
                                                            if ($form_status == 'INATIVO') {
                                                                echo 'selected';
                                                            } ?>>Inativo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mt-4 d-flex gap-2">
                                                <button type="submit" name="salvar"
                                                    class="btn btn-primary">
                                                    SALVAR ALTERAÇÕES
                                                </button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#editar<?php echo $id; ?>">
                                                    CANCELAR
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>

                        <?php if (mysqli_num_rows($trilhos) == 0) { ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Nenhum trilho cadastrado.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- form vazio que envia os checkboxes marcados (cada um aponta pra ele com form=) -->
    <form id="formStatus" method="POST"></form>

    <!-- pop-up de inativar (o texto é montado no listagem.js) -->
    <div class="modal fade" id="modalInativar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold" id="tituloInativar"></h6>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong id="perguntaInativar"></strong>
                        <div id="textoInativar"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        CANCELAR
                    </button>
                    <button type="submit" form="formStatus" name="inativar" id="botaoInativar"
                        class="btn btn-sm btn-danger">INATIVAR</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../script/listagem.js"></script>
</body>

</html>
