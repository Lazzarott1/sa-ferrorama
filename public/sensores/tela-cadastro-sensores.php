<?php

// tela de sensores: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include '../../infra/validacoes-usuarios/verifica-login.php';

$erros = [];

// qual formulário deu erro (0 = cadastro, outro número = id do sensor editado, -1 = nenhum)
$id_com_erro = -1;


// ATIVAR / INATIVAR OS SENSORES MARCADOS
// os checkboxes da lista mandam os ids em $_POST['ids']

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['ids'])) {
    // o sensor não tem nada ligado nele, então pode inativar sem conferir nada
    if (isset($_POST['ativar'])) {
        // só volta pra ATIVO o que estava inativo (o que está em manutenção continua)
        $sql = "UPDATE sensores SET status_sensor = 'ATIVO'
            WHERE id_sensor = ? AND status_sensor = 'INATIVO'";
    } else {
        $sql = "UPDATE sensores SET status_sensor = 'INATIVO' WHERE id_sensor = ?";
    }

    // um UPDATE pra cada id marcado
    foreach ($_POST['ids'] as $id_marcado) {
        $id_marcado = (int) $id_marcado;

        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // volta pra mesma tela (mantendo o filtro)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}


// CADASTRAR OU EDITAR SENSOR
// o mesmo formulário serve pros dois: id 0 é cadastro, id maior que 0 é edição

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $id_trilho = (int) $_POST['trilho'];
    $status = $_POST['status'];

    // nome obrigatório e no banco cabe só 100 caracteres
    if ($nome == '') {
        $erros[] = "Informe o nome do sensor.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do sensor pode ter no máximo 100 caracteres.";
    }

    // só aceita o que existe no select (dá pra mexer no HTML pelo navegador)
    if ($tipo != 'Velocidade' && $tipo != 'Localização' && $tipo != 'Temperatura') {
        $erros[] = "Selecione um tipo válido.";
    }

    if ($status != 'ATIVO' && $status != 'ALERTA' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // o trilho precisa existir e estar ativo
    // (se o sensor já estava num trilho que foi inativado, pode continuar nele)
    $sql = "SELECT status_trilho FROM trilhos WHERE id_trilho = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_trilho);
    mysqli_stmt_execute($stmt);
    $trilho = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $sql = "SELECT id_trilho FROM sensores WHERE id_sensor = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $sensor_antes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$trilho) {
        $erros[] = "Selecione um trilho ativo.";
    } elseif ($trilho['status_trilho'] == 'INATIVO') {
        if (!$sensor_antes || $sensor_antes['id_trilho'] != $id_trilho) {
            $erros[] = "Selecione um trilho ativo.";
        }
    }

    if (count($erros) == 0) {
        if ($id == 0) {
            $sql = "INSERT INTO sensores (nome_sensor, tipo_sensor, id_trilho, status_sensor)
                VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'ssis', $nome, $tipo, $id_trilho, $status);
        } else {
            $sql = "UPDATE sensores
                SET nome_sensor = ?, tipo_sensor = ?, id_trilho = ?, status_sensor = ?
                WHERE id_sensor = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'ssisi', $nome, $tipo, $id_trilho, $status, $id);
        }

        // salvou: recarrega a tela pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o sensor: " . mysqli_error($conexao);
        mysqli_stmt_close($stmt);
    }

    // deu erro: guarda qual formulário reabrir com o que foi digitado
    $id_com_erro = $id;
}


// LISTA DE SENSORES (com o nome do trilho)

// filtro Ativos / Inativos / Em alerta / Todos que vem na url (?filtro=)
$filtro = 'ativos';
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
}

$where = "";
if ($filtro == 'ativos') {
    $where = "WHERE sensores.status_sensor <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE sensores.status_sensor = 'INATIVO'";
} elseif ($filtro == 'alerta') {
    $where = "WHERE sensores.status_sensor = 'ALERTA'";
}

$sql = "SELECT sensores.*, trilhos.nome_trilho
    FROM sensores
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    $where
    ORDER BY sensores.id_sensor DESC";
$sensores = mysqli_query($conexao, $sql);

if ($sensores === false) {
    die('Erro ao consultar sensores: ' . mysqli_error($conexao));
}


// TRILHOS PRO SELECT DO FORMULÁRIO
// guardados num array porque o select aparece várias vezes (cadastro + edição de cada sensor)

$trilhos = [];
$sql = "SELECT id_trilho, nome_trilho, status_trilho FROM trilhos ORDER BY nome_trilho";
$resultado = mysqli_query($conexao, $sql);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $trilhos[] = $linha;
}


// VALORES DO FORMULÁRIO DE CADASTRO
// em branco, ou o que foi digitado se o cadastro deu erro

$novo_nome = '';
$novo_tipo = '';
$novo_trilho = 0;
$novo_status = 'ATIVO';

if ($id_com_erro === 0) {
    $novo_nome = $nome;
    $novo_tipo = $tipo;
    $novo_trilho = $id_trilho;
    $novo_status = $status;
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensores</title>
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
                <h3>Sensores</h3>
                <p>Gerencie os sensores cadastrados na ferrovia</p>
            </div>
            <?php if ($admin) { ?>
                <!-- abre e fecha o formulário de cadastro -->
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="collapse"
                    data-bs-target="#editar0">
                    + NOVO SENSOR
                </button>
            <?php } ?>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
            <div class="caixa collapse <?php if ($id_com_erro === 0) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO SENSOR</div>
                <form method="POST" class="caixa-corpo">
                    <input type="hidden" name="id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nomeSensor0" class="form-label">NOME DO SENSOR</label>
                            <input
                                type="text"
                                name="nome"
                                id="nomeSensor0"
                                class="form-control"
                                placeholder="Ex: Sensor 01"
                                value="<?php echo htmlspecialchars($novo_nome); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="tipoSensor0" class="form-label">TIPO</label>
                            <select name="tipo" id="tipoSensor0" class="form-select" required>
                                <option value="">Selecione o tipo</option>
                                <option
                                    <?php if ($novo_tipo == 'Velocidade') echo 'selected'; ?>>
                                    Velocidade
                                </option>
                                <option
                                    <?php if ($novo_tipo == 'Localização') echo 'selected'; ?>>
                                    Localização
                                </option>
                                <option
                                    <?php if ($novo_tipo == 'Temperatura') echo 'selected'; ?>>
                                    Temperatura
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="trilhoSensor0" class="form-label">TRILHO</label>
                            <select name="trilho" id="trilhoSensor0" class="form-select" required>
                                <option value="">Selecione um trilho</option>
                                <!-- no cadastro só aparecem os trilhos ativos -->
                                <?php
                                foreach ($trilhos as $trilho) {
                                    if ($trilho['status_trilho'] != 'INATIVO') {
                                        $selecionado = '';
                                        if ($trilho['id_trilho'] == $novo_trilho) {
                                            $selecionado = 'selected';
                                        }
                                        echo "<option value='" . $trilho['id_trilho'] . "' "
                                            . $selecionado . ">"
                                            . htmlspecialchars($trilho['nome_trilho'])
                                            . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="statusSensor0" class="form-label">STATUS</label>
                            <select name="status" id="statusSensor0" class="form-select" required>
                                <option
                                    value="ATIVO"
                                    <?php if ($novo_status == 'ATIVO') echo 'selected'; ?>>
                                    Ativo
                                </option>
                                <option
                                    value="ALERTA"
                                    <?php if ($novo_status == 'ALERTA') echo 'selected'; ?>>
                                    Alerta
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
                SENSORES CADASTRADOS
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
                            <option
                                value="alerta"
                                <?php if ($filtro == 'alerta') echo 'selected'; ?>>
                                EM ALERTA
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
                            <th>TIPO</th>
                            <th>TRILHO</th>
                            <th>STATUS</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- uma linha pra cada sensor -->
                        <?php while ($sensor = mysqli_fetch_assoc($sensores)) { ?>
                            <?php
                            $id_sensor = $sensor['id_sensor'];

                            // valores do formulário de edição: os do banco, ou o que foi
                            // digitado se a edição desse sensor deu erro
                            $form_nome = $sensor['nome_sensor'];
                            $form_tipo = $sensor['tipo_sensor'];
                            $form_trilho = $sensor['id_trilho'];
                            $form_status = $sensor['status_sensor'];

                            if ($id_com_erro == $id_sensor) {
                                $form_nome = $nome;
                                $form_tipo = $tipo;
                                $form_trilho = $id_trilho;
                                $form_status = $status;
                            }

                            $nome_trilho = '-';
                            if ($sensor['nome_trilho'] != null) {
                                $nome_trilho = htmlspecialchars($sensor['nome_trilho']);
                            }
                            ?>
                            <tr>
                                <?php if ($admin) { ?>
                                    <!-- checkbox da linha (o pop-up usa o data-nome) -->
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input marcar"
                                            form="formStatus"
                                            name="ids[]"
                                            value="<?php echo $id_sensor; ?>"
                                            data-nome="o sensor <?php
                                            echo htmlspecialchars($sensor['nome_sensor']); ?>"
                                            data-bloqueio="">
                                    </td>
                                <?php } ?>
                                <td><?php echo $id_sensor; ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_sensor']); ?></td>
                                <td><?php echo $sensor['tipo_sensor']; ?></td>
                                <td><?php echo $nome_trilho; ?></td>
                                <td>
                                    <span class="badge status status-<?php
                                    echo $sensor['status_sensor']; ?>">
                                        <?php echo $sensor['status_sensor']; ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <!-- EDITAR abre o formulário embaixo da linha -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editar<?php echo $id_sensor; ?>">
                                            EDITAR
                                        </button>
                                        <?php if ($sensor['status_sensor'] == 'INATIVO') { ?>
                                            <button
                                                type="submit"
                                                form="formStatus"
                                                name="ativar"
                                                class="btn btn-sm btn-primary botao-ativar"
                                                data-id="<?php echo $id_sensor; ?>">
                                                ATIVAR
                                            </button>
                                        <?php } else { ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger botao-inativar"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInativar"
                                                data-id="<?php echo $id_sensor; ?>">
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
                                        <?php if ($id_com_erro == $id_sensor) echo 'show'; ?>"
                                    id="editar<?php echo $id_sensor; ?>">
                                    <td colspan="7" class="p-0">
                                        <form method="POST" class="caixa-corpo">
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo $id_sensor; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label
                                                        for="nomeSensor<?php echo $id_sensor; ?>"
                                                        class="form-label">
                                                        NOME DO SENSOR
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="nome"
                                                        id="nomeSensor<?php echo $id_sensor; ?>"
                                                        class="form-control"
                                                        placeholder="Ex: Sensor 01"
                                                        value="<?php
                                                        echo htmlspecialchars($form_nome);
                                                        ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="tipoSensor<?php echo $id_sensor; ?>"
                                                        class="form-label">
                                                        TIPO
                                                    </label>
                                                    <select
                                                        name="tipo"
                                                        id="tipoSensor<?php echo $id_sensor; ?>"
                                                        class="form-select"
                                                        required>
                                                        <option value="">Selecione o tipo</option>
                                                        <?php
                                                        $tipos = [
                                                            'Velocidade',
                                                            'Localização',
                                                            'Temperatura'
                                                        ];
                                                        foreach ($tipos as $um_tipo) {
                                                            $selecionado = '';
                                                            if ($form_tipo == $um_tipo) {
                                                                $selecionado = 'selected';
                                                            }
                                                            echo "<option " . $selecionado . ">"
                                                                . $um_tipo . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="trilhoSensor<?php echo $id_sensor; ?>"
                                                        class="form-label">
                                                        TRILHO
                                                    </label>
                                                    <select
                                                        name="trilho"
                                                        id="trilhoSensor<?php echo $id_sensor; ?>"
                                                        class="form-select"
                                                        required>
                                                        <option value="">
                                                            Selecione um trilho
                                                        </option>
                                                        <!-- trilhos ativos + o atual -->
                                                        <?php
                                                        foreach ($trilhos as $trilho) {
                                                            $e_dele = $trilho['id_trilho']
                                                                == $form_trilho;

                                                            $pode = $trilho['status_trilho']

                                                                != 'INATIVO';
                                                            if ($pode || $e_dele) {
                                                                $selecionado = '';
                                                                if ($e_dele) {
                                                                    $selecionado = 'selected';
                                                                }
                                                                echo "<option value='"
                                                                    . $trilho['id_trilho'] . "' "
                                                                    . $selecionado . ">"
                                                                    . htmlspecialchars(
                                                                        $trilho['nome_trilho']
                                                                    )
                                                                    . "</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="statusSensor<?php echo $id_sensor; ?>"
                                                        class="form-label">
                                                        STATUS
                                                    </label>
                                                    <select
                                                        name="status"
                                                        id="statusSensor<?php echo $id_sensor; ?>"
                                                        class="form-select"
                                                        required>
                                                        <?php
                                                        $opcoes = [
                                                            'ATIVO' => 'Ativo',
                                                            'ALERTA' => 'Alerta',
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
                                                    echo $id_sensor; ?>">
                                                    CANCELAR
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($sensores) == 0) { ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhum sensor cadastrado.
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
