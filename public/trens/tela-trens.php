<?php

// tela de trens: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include '../../infra/validacoes-usuarios/verifica-login.php';

// links do "Cadastrar trilho" e "Cadastrar usuário" embaixo dos selects
$link_trilho = '../trilhos/tela-cadastro-trilhos.php?novo=1';
$link_usuario = '../usuarios/tela-cadastro-user.php';

$erros = [];

// qual formulário deu erro (0 = cadastro, outro número = id do trem editado, -1 = nenhum)
$id_com_erro = -1;


// ATIVAR / INATIVAR OS TRENS MARCADOS
// os checkboxes da lista mandam os ids em $_POST['ids']

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['ids'])) {
    // trem pode inativar direto, não tem nada que dependa dele
    if (isset($_POST['ativar'])) {
        // só volta pra ATIVO o que estava inativo (o que está em manutenção continua)
        $sql = "UPDATE trens SET status_trem = 'ATIVO'
            WHERE id_trem = ? AND status_trem = 'INATIVO'";
    } else {
        $sql = "UPDATE trens SET status_trem = 'INATIVO' WHERE id_trem = ?";
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


// CADASTRAR OU EDITAR TREM
// o mesmo formulário serve pros dois: id 0 é cadastro, id maior que 0 é edição

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $modelo = $_POST['modelo'];
    $capacidade = trim($_POST['capacidade']);
    $id_trilho = (int) $_POST['id_trilho'];
    $id_usuario = (int) $_POST['id_usuario'];
    $status = $_POST['status'];

    // nome obrigatório, até 100 caracteres
    if ($nome == '') {
        $erros[] = "Informe o nome do trem.";
    } elseif (mb_strlen($nome) > 100) {
        $erros[] = "O nome do trem pode ter no máximo 100 caracteres.";
    }

    // só os dois modelos do select
    if ($modelo != 'De passageiros' && $modelo != 'De carga') {
        $erros[] = "Selecione um modelo válido.";
    }

    // capacidade só com números e maior que zero
    if (!ctype_digit($capacidade) || $capacidade < 1) {
        $erros[] = "A capacidade deve ser um número inteiro maior que zero.";
    }

    if ($status != 'ATIVO' && $status != 'MANUTENCAO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // como o trem estava antes (no cadastro não acha nada)
    $sql = "SELECT id_trilho, id_usuario FROM trens WHERE id_trem = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $trem_antes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // o trilho precisa existir e estar ativo (ou ser o mesmo que o trem já tinha)
    $sql = "SELECT status_trilho FROM trilhos WHERE id_trilho = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_trilho);
    mysqli_stmt_execute($stmt);
    $trilho = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$trilho) {
        $erros[] = "Selecione um trilho ativo.";
    } elseif ($trilho['status_trilho'] == 'INATIVO') {
        if (!$trem_antes || $trem_antes['id_trilho'] != $id_trilho) {
            $erros[] = "Selecione um trilho ativo.";
        }
    }

    // com o responsável é a mesma ideia
    $sql = "SELECT status_usuario FROM usuarios WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    mysqli_stmt_execute($stmt);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$usuario) {
        $erros[] = "Selecione um responsável ativo.";
    } elseif ($usuario['status_usuario'] != 'ATIVO') {
        if (!$trem_antes || $trem_antes['id_usuario'] != $id_usuario) {
            $erros[] = "Selecione um responsável ativo.";
        }
    }

    if (count($erros) == 0) {
        if ($id == 0) {
            $sql = "INSERT INTO trens
                (nome_trem, modelo_trem, capacidade_trem, id_trilho, id_usuario, status_trem)
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssiiis',
                $nome,
                $modelo,
                $capacidade,
                $id_trilho,
                $id_usuario,
                $status
            );
        } else {
            $sql = "UPDATE trens
                SET nome_trem = ?, modelo_trem = ?, capacidade_trem = ?, id_trilho = ?,
                    id_usuario = ?, status_trem = ?
                WHERE id_trem = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssiiisi',
                $nome,
                $modelo,
                $capacidade,
                $id_trilho,
                $id_usuario,
                $status,
                $id
            );
        }

        // salvou: recarrega a tela pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o trem: " . mysqli_error($conexao);
        mysqli_stmt_close($stmt);
    }

    // deu erro: guarda qual formulário reabrir com o que foi digitado
    $id_com_erro = $id;
}


// LISTA DE TRENS (com o nome do trilho e do responsável)

// filtro Ativos / Inativos / Todos que vem na url (?filtro=)
$filtro = 'ativos';
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
}

$where = "";
if ($filtro == 'ativos') {
    $where = "WHERE trens.status_trem <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE trens.status_trem = 'INATIVO'";
}

$sql = "SELECT trens.*, trilhos.nome_trilho, usuarios.nome_usuario
    FROM trens
    LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
    LEFT JOIN usuarios ON usuarios.id_usuario = trens.id_usuario
    $where
    ORDER BY trens.id_trem DESC";
$trens = mysqli_query($conexao, $sql);

if ($trens === false) {
    die('Erro ao consultar trens: ' . mysqli_error($conexao));
}


// TRILHOS E USUÁRIOS PROS SELECTS DO FORMULÁRIO
// guardados em array porque os selects aparecem várias vezes (cadastro + edição de cada trem)

$trilhos = [];
$sql = "SELECT id_trilho, nome_trilho, status_trilho FROM trilhos ORDER BY nome_trilho";
$resultado = mysqli_query($conexao, $sql);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $trilhos[] = $linha;
}

$usuarios = [];
$sql = "SELECT id_usuario, nome_usuario, status_usuario FROM usuarios ORDER BY nome_usuario";
$resultado = mysqli_query($conexao, $sql);
while ($linha = mysqli_fetch_assoc($resultado)) {
    $usuarios[] = $linha;
}


// VALORES DO FORMULÁRIO DE CADASTRO
// em branco, ou o que foi digitado se o cadastro deu erro

$novo_nome = '';
$novo_modelo = '';
$novo_capacidade = '';
$novo_trilho = 0;
$novo_usuario = 0;
$novo_status = 'ATIVO';

if ($id_com_erro === 0) {
    $novo_nome = $nome;
    $novo_modelo = $modelo;
    $novo_capacidade = $capacidade;
    $novo_trilho = $id_trilho;
    $novo_usuario = $id_usuario;
    $novo_status = $status;
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trens</title>
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
                <h3>Trens</h3>
                <p>Gerencie os trens cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <!-- abre e fecha o formulário de cadastro -->
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="collapse"
                    data-bs-target="#editar0">
                    + NOVO TREM
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
                <div class="caixa-topo">CADASTRAR NOVO TREM</div>
                <form method="POST" class="caixa-corpo">
                    <input type="hidden" name="id" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nomeTrem0" class="form-label">NOME DO TREM</label>
                            <input
                                type="text"
                                name="nome"
                                id="nomeTrem0"
                                class="form-control"
                                placeholder="Ex: Trem 01"
                                value="<?php echo htmlspecialchars($novo_nome); ?>"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="modeloTrem0" class="form-label">MODELO</label>
                            <!-- quando troca o modelo, o capacidade.js muda o texto da -->
                            <!-- capacidade (KG ou PASSAGEIROS) -->
                            <select
                                name="modelo"
                                id="modeloTrem0"
                                class="form-select modelo-trem"
                                data-id="0"
                                required>
                                <option value="">Selecione o modelo</option>
                                <option
                                    <?php if ($novo_modelo == 'De passageiros') echo 'selected'; ?>>
                                    De passageiros
                                </option>
                                <option <?php if ($novo_modelo == 'De carga') echo 'selected'; ?>>
                                    De carga
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label
                                for="capacidadeTrem0"
                                id="labelCapacidade0"
                                class="form-label">
                                CAPACIDADE
                            </label>
                            <input
                                type="number"
                                name="capacidade"
                                id="capacidadeTrem0"
                                class="form-control"
                                value="<?php echo htmlspecialchars($novo_capacidade); ?>"
                                min="1"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="trilhoTrem0" class="form-label">TRILHO ATUAL</label>
                            <select name="id_trilho" id="trilhoTrem0" class="form-select" required>
                                <option value="">Selecione o trilho</option>
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
                            <div class="form-text">
                                O trem precisa de um trilho.
                                <a href="<?php echo $link_trilho; ?>">
                                    Cadastrar trilho
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="responsavelTrem0" class="form-label">RESPONSÁVEL</label>
                            <select
                                name="id_usuario"
                                id="responsavelTrem0"
                                class="form-select"
                                required>
                                <option value="">Selecione o responsável</option>
                                <!-- só usuários ativos -->
                                <?php
                                foreach ($usuarios as $usuario) {
                                    if ($usuario['status_usuario'] == 'ATIVO') {
                                        $selecionado = '';
                                        if ($usuario['id_usuario'] == $novo_usuario) {
                                            $selecionado = 'selected';
                                        }
                                        echo "<option value='" . $usuario['id_usuario'] . "' "
                                            . $selecionado . ">"
                                            . htmlspecialchars($usuario['nome_usuario'])
                                            . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <div class="form-text">
                                O trem precisa de um responsável.
                                <a href="<?php echo $link_usuario; ?>">Cadastrar usuário</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="statusTrem0" class="form-label">STATUS</label>
                            <select name="status" id="statusTrem0" class="form-select" required>
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
                TRENS CADASTRADOS
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
                            <th>MODELO</th>
                            <th>CAPACIDADE</th>
                            <th>TRILHO</th>
                            <th>RESPONSÁVEL</th>
                            <th>STATUS</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- uma linha pra cada trem -->
                        <?php while ($trem = mysqli_fetch_assoc($trens)) { ?>
                            <?php
                            $id_trem = $trem['id_trem'];

                            $nome_trilho = 'Sem trilho';
                            if ($trem['nome_trilho'] != null) {
                                $nome_trilho = htmlspecialchars($trem['nome_trilho']);
                            }

                            $nome_responsavel = 'Sem responsável';
                            if ($trem['nome_usuario'] != null) {
                                $nome_responsavel = htmlspecialchars($trem['nome_usuario']);
                            }

                            // valores do formulário de edição: os do banco, ou o que foi
                            // digitado se a edição desse trem deu erro
                            $form_nome = $trem['nome_trem'];
                            $form_modelo = $trem['modelo_trem'];
                            $form_capacidade = $trem['capacidade_trem'];
                            $form_trilho = $trem['id_trilho'];
                            $form_usuario = $trem['id_usuario'];
                            $form_status = $trem['status_trem'];

                            if ($id_com_erro == $id_trem) {
                                $form_nome = $nome;
                                $form_modelo = $modelo;
                                $form_capacidade = $capacidade;
                                $form_trilho = $id_trilho;
                                $form_usuario = $id_usuario;
                                $form_status = $status;
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
                                            value="<?php echo $id_trem; ?>"
                                            data-nome="o trem <?php
                                            echo htmlspecialchars($trem['nome_trem']); ?>"
                                            data-bloqueio="">
                                    </td>
                                <?php } ?>
                                <td><?php echo $id_trem; ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['modelo_trem']); ?></td>
                                <td><?php echo $trem['capacidade_trem']; ?></td>
                                <td><?php echo $nome_trilho; ?></td>
                                <td><?php echo $nome_responsavel; ?></td>
                                <td>
                                    <span class="badge status status-<?php
                                    echo $trem['status_trem']; ?>">
                                        <?php echo $trem['status_trem']; ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($admin) { ?>
                                        <!-- EDITAR abre o formulário embaixo da linha -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editar<?php echo $id_trem; ?>">
                                            EDITAR
                                        </button>
                                        <?php if ($trem['status_trem'] == 'INATIVO') { ?>
                                            <button
                                                type="submit"
                                                form="formStatus"
                                                name="ativar"
                                                class="btn btn-sm btn-primary botao-ativar"
                                                data-id="<?php echo $id_trem; ?>">
                                                ATIVAR
                                            </button>
                                        <?php } else { ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger botao-inativar"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalInativar"
                                                data-id="<?php echo $id_trem; ?>">
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
                                        <?php if ($id_com_erro == $id_trem) echo 'show'; ?>"
                                    id="editar<?php echo $id_trem; ?>">
                                    <td colspan="9" class="p-0">
                                        <form method="POST" class="caixa-corpo">
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo $id_trem; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label
                                                        for="nomeTrem<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        NOME DO TREM
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="nome"
                                                        id="nomeTrem<?php echo $id_trem; ?>"
                                                        class="form-control"
                                                        placeholder="Ex: Trem 01"
                                                        value="<?php
                                                        echo htmlspecialchars($form_nome); ?>"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="modeloTrem<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        MODELO
                                                    </label>
                                                    <select
                                                        name="modelo"
                                                        id="modeloTrem<?php echo $id_trem; ?>"
                                                        class="form-select modelo-trem"
                                                        data-id="<?php echo $id_trem; ?>"
                                                        required>
                                                        <option value="">Selecione o modelo</option>
                                                        <?php
                                                        $modelos = ['De passageiros', 'De carga'];
                                                        foreach ($modelos as $um_modelo) {
                                                            $selecionado = '';
                                                            if ($form_modelo == $um_modelo) {
                                                                $selecionado = 'selected';
                                                            }
                                                            echo "<option " . $selecionado . ">"
                                                                . $um_modelo . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="capacidadeTrem<?php echo $id_trem; ?>"
                                                        id="labelCapacidade<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        CAPACIDADE
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="capacidade"
                                                        id="capacidadeTrem<?php echo $id_trem; ?>"
                                                        class="form-control"
                                                        value="<?php
                                                        echo htmlspecialchars($form_capacidade);
                                                        ?>"
                                                        min="1"
                                                        required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="trilhoTrem<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        TRILHO ATUAL
                                                    </label>
                                                    <select
                                                        name="id_trilho"
                                                        id="trilhoTrem<?php echo $id_trem; ?>"
                                                        class="form-select"
                                                        required>
                                                        <option value="">Selecione o trilho</option>
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
                                                    <div class="form-text">
                                                        O trem precisa de um trilho.
                                                        <a href="<?php echo $link_trilho; ?>">
                                                            Cadastrar trilho
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="responsavelTrem<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        RESPONSÁVEL
                                                    </label>
                                                    <select
                                                        name="id_usuario"
                                                        id="responsavelTrem<?php echo $id_trem; ?>"
                                                        class="form-select"
                                                        required>
                                                        <option value="">
                                                            Selecione o responsável
                                                        </option>
                                                        <!-- usuários ativos + o atual -->
                                                        <?php
                                                        foreach ($usuarios as $usuario) {
                                                            $e_dele = $usuario['id_usuario']
                                                                == $form_usuario;

                                                            $pode = $usuario['status_usuario']

                                                                == 'ATIVO';
                                                            if ($pode || $e_dele) {
                                                                $selecionado = '';
                                                                if ($e_dele) {
                                                                    $selecionado = 'selected';
                                                                }
                                                                echo "<option value='"
                                                                    . $usuario['id_usuario'] . "' "
                                                                    . $selecionado . ">"
                                                                    . htmlspecialchars(
                                                                        $usuario['nome_usuario']
                                                                    )
                                                                    . "</option>";
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="form-text">
                                                        O trem precisa de um responsável.
                                                        <a href="<?php echo $link_usuario; ?>">
                                                            Cadastrar usuário
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label
                                                        for="statusTrem<?php echo $id_trem; ?>"
                                                        class="form-label">
                                                        STATUS
                                                    </label>
                                                    <select
                                                        name="status"
                                                        id="statusTrem<?php echo $id_trem; ?>"
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
                                                    echo $id_trem; ?>">
                                                    CANCELAR
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($trens) == 0) { ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Nenhum trem cadastrado.
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
    <!-- texto da capacidade conforme o modelo -->
    <script src="../../script/capacidade.js"></script>

</body>

</html>
