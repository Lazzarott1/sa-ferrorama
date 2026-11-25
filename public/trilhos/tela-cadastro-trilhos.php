<?php

// tela de trilhos: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include '../../infra/validacoes-usuarios/verifica-login.php';

$erros = [];

// qual formulário deu erro (0 = cadastro, outro número = id do trilho editado, -1 = nenhum)
$id_com_erro = -1;


// ATIVAR / INATIVAR OS TRILHOS MARCADOS
// os checkboxes da lista mandam os ids em $_POST['ids']

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['ids'])) {
    // quantos não puderam ser inativados (por ter trem ou sensor ativo)
    $nao_inativados = 0;

    foreach ($_POST['ids'] as $id_marcado) {
        $id_marcado = (int) $id_marcado;

        if (isset($_POST['ativar'])) {
            $sql = "UPDATE trilhos SET status_trilho = 'ATIVO' WHERE id_trilho = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // conta os trens e sensores ativos desse trilho
            $sql = "SELECT
                (SELECT COUNT(*) FROM trens
                    WHERE id_trilho = ? AND status_trem <> 'INATIVO') AS trens,
                (SELECT COUNT(*) FROM sensores
                    WHERE id_trilho = ? AND status_sensor <> 'INATIVO') AS sensores";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'ii', $id_marcado, $id_marcado);
            mysqli_stmt_execute($stmt);
            $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            // só inativa se não tiver nada ativo nele
            if ($total['trens'] == 0 && $total['sensores'] == 0) {
                $sql = "UPDATE trilhos SET status_trilho = 'INATIVO' WHERE id_trilho = ?";
                $stmt = mysqli_prepare($conexao, $sql);
                mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                $nao_inativados++;
            }
        }
    }

    // deu tudo certo: volta pra mesma tela (mantendo o filtro)
    if ($nao_inativados == 0) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $erros[] = "Trilhos com trens ou sensores ativos não foram inativados. "
        . "Inative ou altere esses itens antes.";
}


// CADASTRAR OU EDITAR TRILHO
// o mesmo formulário serve pros dois: id 0 é cadastro, id maior que 0 é edição

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    // aceita 12,5 ou 12.5
    $km = str_replace(',', '.', trim($_POST['km']));
    $status = $_POST['status'];

    // nome e descrição obrigatórios, com o limite de tamanho do banco
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

    // km tem que ser número maior que zero
    if (!is_numeric($km) || $km <= 0 || strlen($km) > 20) {
        $erros[] = "O km deve ser um número maior que zero.";
    }

    // só os status que existem no select
    if ($status != 'ATIVO' && $status != 'MANUTENCAO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // mudou pra inativo na edição? confere se não tem trem nem sensor ativo nele
    if ($id != 0 && $status == 'INATIVO') {
        $sql = "SELECT
            (SELECT COUNT(*) FROM trens
                WHERE id_trilho = ? AND status_trem <> 'INATIVO') AS trens,
            (SELECT COUNT(*) FROM sensores
                WHERE id_trilho = ? AND status_sensor <> 'INATIVO') AS sensores";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $id);
        mysqli_stmt_execute($stmt);
        $total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($total['trens'] > 0 || $total['sensores'] > 0) {
            $erros[] = "Este trilho tem "
                . $total['trens']
                . " trem(ns) e "
                . $total['sensores']
                . " sensor(es) ativo(s). "
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

    // deu erro: guarda qual formulário reabrir com o que foi digitado
    $id_com_erro = $id;
}


// LISTA DE TRILHOS
// junto vem quantos trens e sensores ativos cada um tem e o nome dos trens dele

// filtro Ativos / Inativos / Todos que vem na url (?filtro=)
$filtro = 'ativos';
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
}

$where = "";
if ($filtro == 'ativos') {
    $where = "WHERE status_trilho <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE status_trilho = 'INATIVO'";
}

// o GROUP_CONCAT junta o nome dos trens do trilho separados por vírgula
// (é o que aparece na coluna TRENS ASSOCIADOS)
$sql = "SELECT trilhos.*,
    (
        SELECT COUNT(*)
        FROM trens
        WHERE trens.id_trilho = trilhos.id_trilho
        AND trens.status_trem <> 'INATIVO'
    ) AS total_trens,
    (
        SELECT COUNT(*)
        FROM sensores
        WHERE sensores.id_trilho = trilhos.id_trilho
        AND sensores.status_sensor <> 'INATIVO'
    ) AS total_sensores,
    (
        SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ')
        FROM trens
        WHERE trens.id_trilho = trilhos.id_trilho
        AND trens.status_trem <> 'INATIVO'
    ) AS trens_associados
    FROM trilhos
    $where
    ORDER BY id_trilho DESC";
$trilhos = mysqli_query($conexao, $sql);

if ($trilhos === false) {
    die('Erro ao consultar trilhos: ' . mysqli_error($conexao));
}


// VALORES DO FORMULÁRIO DE CADASTRO
// em branco, ou o que foi digitado se o cadastro deu erro

$novo_nome = '';
$novo_descricao = '';
$novo_km = '';
$novo_status = 'ATIVO';

if ($id_com_erro === 0) {
    $novo_nome = $nome;
    $novo_descricao = $descricao;
    $novo_km = $km;
    $novo_status = $status;
}

// o cadastro já vem aberto se deu erro nele ou se veio da home com ?novo
$abrir_cadastro = '';
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

    <!-- menu de cima -->
    <?php include '../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Trilhos</h3>
                <p>Gerencie os trilhos cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <!-- abre e fecha o formulário de cadastro -->
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="collapse"
                    data-bs-target="#editar0">
                    + NOVO TRILHO
                </button>
            <?php } ?>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <!-- formulário de cadastro, começa fechado -->
            <div class="caixa collapse <?php echo $abrir_cadastro; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TRILHO</div>
                <form method="POST" class="caixa-corpo">
                    <input type="hidden" name="id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nomeTrilho0" class="form-label">NOME DO TRILHO</label>
                            <input
                                type="text"
                                name="nome"
                                id="nomeTrilho0"
                                class="form-control"
                                placeholder="Ex: Trilho Norte 01"
                                value="<?php echo htmlspecialchars($novo_nome); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="kmTrilho0" class="form-label">KM</label>
                            <input
                                type="number"
                                name="km"
                                id="kmTrilho0"
                                class="form-control"
                                step="0.01"
                                min="0.01"
                                placeholder="Ex: 12.5"
                                value="<?php echo htmlspecialchars($novo_km); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="descricaoTrilho0" class="form-label">DESCRIÇÃO</label>
                            <input
                                type="text"
                                name="descricao"
                                id="descricaoTrilho0"
                                class="form-control"
                                placeholder="Ex: Trecho do pátio A ao B"
                                value="<?php echo htmlspecialchars($novo_descricao); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="statusTrilho0" class="form-label">STATUS</label>
                            <select name="status" id="statusTrilho0" class="form-select" required>
                                <option
                                    value="ATIVO"
                                    <?php if ($novo_status == 'ATIVO') echo 'selected'; ?>>
                                    Ativo
                                </option>
                                <option
                                    value="MANUTENCAO"
                                    <?php if ($novo_status == 'MANUTENCAO') echo 'selected'; ?>>
                                    Manutenção
                                </option>
                                <option
                                    value="INATIVO"
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
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-toggle="collapse"
                            data-bs-target="#editar0">
                            CANCELAR
                        </button>
                    </div>
                </form>
            </div>
        <?php } ?>

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro -->
            <div class="caixa-topo">
                TRILHOS CADASTRADOS
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($admin) { ?>
                        <!-- só aparecem quando marca 2 itens ou mais (listagem.js) -->
                        <div id="emMassa" class="d-flex gap-2 d-none">
                            <button
                                type="submit"
                                form="formStatus"
                                name="ativar"
                                class="btn btn-sm btn-light">
                                ATIVAR SELECIONADOS
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-light"
                                data-bs-toggle="modal"
                                data-bs-target="#modalInativar"
                                id="inativarSelecionados">
                                INATIVAR SELECIONADOS
                            </button>
                        </div>
                    <?php } ?>
                    <!-- trocar o filtro já recarrega a tela com ?filtro= -->
                    <form method="GET">
                        <select
                            name="filtro"
                            class="form-select form-select-sm"
                            id="filtro">
                            <option
                                value="ativos"
                                <?php if ($filtro == 'ativos') echo 'selected'; ?>>
                                ATIVOS
                            </option>
                            <option
                                value="inativos"
                                <?php if ($filtro == 'inativos') echo 'selected'; ?>>
                                INATIVOS
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
                                <!-- marca ou desmarca todos -->
                                <th>
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="marcarTodos">
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
                        <!-- uma linha pra cada trilho -->
                        <?php while ($trilho = mysqli_fetch_assoc($trilhos)) { ?>
                            <?php
                            $id_trilho = $trilho['id_trilho'];

                            // se tiver trem ou sensor ativo, o motivo vai pro checkbox e aparece
                            // no pop-up de inativar
                            $bloqueio = '';
                            if ($trilho['total_trens'] > 0 || $trilho['total_sensores'] > 0) {
                                $bloqueio = 'Ele tem '
                                    . $trilho['total_trens']
                                    . ' trem(ns) e '
                                    . $trilho['total_sensores']
                                    . ' sensor(es) ativo(s). '
                                    . 'Inative ou altere esses itens antes de inativá-lo.';
                            }

                            // valores do formulário de edição: os do banco, ou o que foi
                            // digitado se a edição desse trilho deu erro
                            $form_nome = $trilho['nome_trilho'];
                            $form_descricao = $trilho['descricao_trilho'];
                            $form_km = $trilho['km_trilho'];
                            $form_status = $trilho['status_trilho'];

                            if ($id_com_erro == $id_trilho) {
                                $form_nome = $nome;
                                $form_descricao = $descricao;
                                $form_km = $km;
                                $form_status = $status;
                            }
                            ?>
                            <tr>
                                <?php if ($admin) { ?>
                                    <!-- checkbox da linha (o pop-up usa o data-nome e o -->
                                    <!-- data-bloqueio) -->
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input marcar"
                                            form="formStatus"
                                            name="ids[]"
                                            value="<?php echo $id_trilho; ?>"
                                            data-nome="o trilho <?php
                                            echo htmlspecialchars($trilho['nome_trilho']); ?>"
                                            data-bloqueio="<?php echo $bloqueio; ?>">
                                    </td>
                                <?php } ?>
                                <td><?php echo $id_trilho; ?></td>
                                <td><?php echo htmlspecialchars($trilho['nome_trilho']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($trilho['descricao_trilho']); ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php echo htmlspecialchars($trilho['km_trilho']); ?> km
                                </td>
                                <td>
                                    <!-- nome dos trens que estão nesse trilho -->
                                    <?php if ($trilho['trens_associados'] == null) { ?>
                                        <span class="text-muted">Nenhum trem</span>
                                    <?php } else { ?>
                                        <?php echo htmlspecialchars($trilho['trens_associados']); ?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($trilho['data_criacao'])); ?>
                                </td>
                                <td>
                                    <span class="badge status status-<?php
                                    echo $trilho['status_trilho']; ?>">
                                        <?php echo $trilho['status_trilho']; ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <!-- EDITAR abre o formulário embaixo da linha -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editar<?php echo $id_trilho; ?>">
                                            EDITAR
                                        </button>
                                        <?php if ($trilho['status_trilho'] == 'INATIVO') { ?>
                                            <button
                                                type="submit"
                                                form="formStatus"
                                                name="ativar"
                                                class="btn btn-sm btn-primary botao-ativar"
                                                data-id="<?php echo $id_trilho; ?>">
                                                ATIVAR
                                            </button>
                                        <?php } else { ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger botao-inativar"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInativar"
                                                data-id="<?php echo $id_trilho; ?>">
                                                INATIVAR
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php if ($admin) { ?>
                                <!-- linha escondida com o formulário de edição -->
                                <tr
                                    class="collapse
                                        <?php if ($id_com_erro == $id_trilho) echo 'show'; ?>"
                                    id="editar<?php echo $id_trilho; ?>">
                                    <td colspan="9" class="p-0">
                                        <form method="POST" class="caixa-corpo">
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo $id_trilho; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label
                                                        for="nomeTrilho<?php echo $id_trilho; ?>"
                                                        class="form-label">
                                                        NOME DO TRILHO
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="nome"
                                                        id="nomeTrilho<?php echo $id_trilho; ?>"
                                                        class="form-control"
                                                        placeholder="Ex: Trilho Norte 01"
                                                        value="<?php
                                                        echo htmlspecialchars($form_nome); ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="kmTrilho<?php echo $id_trilho; ?>"
                                                        class="form-label">
                                                        KM
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="km"
                                                        id="kmTrilho<?php echo $id_trilho; ?>"
                                                        class="form-control"
                                                        step="0.01"
                                                        min="0.01"
                                                        placeholder="Ex: 12.5"
                                                        value="<?php
                                                        echo htmlspecialchars($form_km); ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="descricaoTrilho<?php
                                                        echo $id_trilho; ?>"
                                                        class="form-label">
                                                        DESCRIÇÃO
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="descricao"
                                                        id="descricaoTrilho<?php
                                                        echo $id_trilho; ?>"
                                                        class="form-control"
                                                        placeholder="Ex: Trecho do pátio A ao B"
                                                        value="<?php
                                                        echo htmlspecialchars($form_descricao); ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="statusTrilho<?php echo $id_trilho; ?>"
                                                        class="form-label">
                                                        STATUS
                                                    </label>
                                                    <select
                                                        name="status"
                                                        id="statusTrilho<?php echo $id_trilho; ?>"
                                                        class="form-select"
                                                        required>
                                                        <?php
                                                        $opcoes = [
                                                            'ATIVO' => 'Ativo',
                                                            'MANUTENCAO' => 'Manutenção',
                                                            'INATIVO' => 'Inativo'
                                                        ];
                                                        foreach ($opcoes as $valor => $texto) {
                                                            $selecionado = '';
                                                            if ($form_status == $valor) {
                                                                $selecionado = 'selected';
                                                            }
                                                            echo "<option value='" . $valor . "' "
                                                                . $selecionado . ">"
                                                                . $texto . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mt-4 d-flex gap-2">
                                                <button
                                                    type="submit"
                                                    name="salvar"
                                                    class="btn btn-primary">
                                                    SALVAR ALTERAÇÕES
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#editar<?php
                                                    echo $id_trilho; ?>">
                                                    CANCELAR
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <!-- lista vazia -->
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

    <!-- form vazio que envia os checkboxes marcados -->
    <!-- (cada checkbox aponta pra ele com form="formStatus") -->
    <form id="formStatus" method="POST"></form>

    <!-- pop-up que pergunta se quer mesmo inativar (o texto é montado no listagem.js) -->
    <div class="modal fade" id="modalInativar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold" id="tituloInativar"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong id="perguntaInativar"></strong>
                        <div id="textoInativar"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        CANCELAR
                    </button>
                    <button
                        type="submit"
                        form="formStatus"
                        name="inativar"
                        id="botaoInativar"
                        class="btn btn-sm btn-danger">
                        INATIVAR
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- marcar itens, ações em massa e o texto do pop-up -->
    <script src="../../script/listagem.js"></script>

</body>

</html>
