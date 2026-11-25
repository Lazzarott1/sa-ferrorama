<?php

// tela de usuários, só o administrador entra
// (o verifica-admin manda o operador de volta pra home)
include '../../infra/validacoes-usuarios/verifica-admin.php';

$erros = [];

// qual formulário deu erro (0 = cadastro, outro número = id do usuário editado, -1 = nenhum)
$id_com_erro = -1;


// ATIVAR / INATIVAR OS USUÁRIOS MARCADOS
// os checkboxes da lista mandam os ids em $_POST['ids']

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    // quantos não puderam ser inativados (a própria conta ou responsável por trem ativo)
    $nao_inativados = 0;

    foreach ($_POST['ids'] as $id_marcado) {
        $id_marcado = (int) $id_marcado;

        if (isset($_POST['ativar'])) {
            // só volta pra ATIVO o que estava inativo (o que está em manutenção continua)
            $sql = "UPDATE usuarios SET status_usuario = 'ATIVO'
                WHERE id_usuario = ? AND status_usuario = 'INATIVO'";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // de quantos trens ativos ele é responsável
            $sql = "SELECT COUNT(*) AS total
                FROM trens
                WHERE id_usuario = ? AND status_trem <> 'INATIVO'";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $id_marcado);
            mysqli_stmt_execute($stmt);
            $trens = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            // não inativa a própria conta nem quem tem trem ativo
            if ($id_marcado != $id_logado && $trens['total'] == 0) {
                $sql = "UPDATE usuarios SET status_usuario = 'INATIVO' WHERE id_usuario = ?";
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

    $erros[] = "Usuários responsáveis por trens ativos e a sua própria conta "
        . "não foram inativados.";
}


// CADASTRAR OU EDITAR USUÁRIO
// o mesmo formulário serve pros dois: id 0 é cadastro, id maior que 0 é edição

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome_usuario']);
    $email = trim($_POST['email_usuario']);
    $senha = $_POST['senha'];
    // o CEP é guardado só com os números
    $cep = str_replace('-', '', trim($_POST['cep']));
    $logradouro = trim($_POST['logradouro']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $uf = strtoupper(trim($_POST['uf']));

    // editando a si mesmo: perfil e status ficam travados (senão o admin poderia se trancar fora)
    // usuário novo sempre começa ATIVO
    if ($id == $id_logado) {
        $perfil = 'ADMINISTRADOR';
        $status = 'ATIVO';
    } elseif ($id == 0) {
        $perfil = $_POST['perfil'];
        $status = 'ATIVO';
    } else {
        $perfil = $_POST['perfil'];
        $status = $_POST['status_usuario'];
    }

    if ($nome == '') {
        $erros[] = "Informe o nome de usuário.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Informe um e-mail válido.";
    }

    // no cadastro a senha é obrigatória; na edição pode deixar em branco pra manter a antiga
    if ($id == 0 && $senha == '') {
        $erros[] = "Informe a senha.";
    } elseif ($senha != '' && strlen($senha) < 8) {
        $erros[] = "A senha deve ter pelo menos 8 caracteres.";
    }

    // só os valores que existem nos selects
    if ($perfil != 'ADMINISTRADOR' && $perfil != 'OPERADOR') {
        $erros[] = "Selecione um perfil válido.";
    }

    if ($status != 'ATIVO' && $status != 'INATIVO') {
        $erros[] = "Selecione um status válido.";
    }

    // não pode ter dois usuários com o mesmo nome (o "id <> ?" ignora ele mesmo na edição)
    $sql = "SELECT id_usuario FROM usuarios WHERE nome_usuario = ? AND id_usuario <> ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $nome, $id);
    mysqli_stmt_execute($stmt);
    $repetido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($repetido) {
        $erros[] = "Esse nome de usuário já existe.";
    }

    // quem é responsável por trem ativo não pode ser inativado
    if ($status == 'INATIVO') {
        $sql = "SELECT COUNT(*) AS total
            FROM trens
            WHERE id_usuario = ? AND status_trem <> 'INATIVO'";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $trens = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($trens['total'] > 0) {
            $erros[] = "Este usuário é responsável por "
                . $trens['total']
                . " trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.";
        }
    }

    // o endereço é opcional, mas se colocou CEP ele tem que estar completo
    if ($cep != '' && (strlen($cep) != 8 || !ctype_digit($cep))) {
        $erros[] = "O CEP deve ter 8 números.";
    } elseif ($cep != '' && ($cidade == '' || strlen($uf) != 2)) {
        $erros[] = "Informe a cidade e a UF do CEP.";
    }

    // tamanho máximo de cada campo no banco
    if (mb_strlen($logradouro) > 150 || mb_strlen($bairro) > 100 || mb_strlen($cidade) > 100) {
        $erros[] = "O endereço tem campos maiores que o permitido.";
    }

    if (count($erros) == 0) {
        if ($id == 0) {
            // nunca guarda a senha pura, só o hash
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
                (nome_usuario, email_usuario, senha, perfil, cep, logradouro, bairro, cidade, uf)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssss',
                $nome,
                $email,
                $senha_hash,
                $perfil,
                $cep,
                $logradouro,
                $bairro,
                $cidade,
                $uf
            );
        } elseif ($senha == '') {
            // senha em branco na edição: mantém a antiga
            $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?, cep = ?,
                    logradouro = ?, bairro = ?, cidade = ?, uf = ?
                WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssi',
                $nome,
                $email,
                $perfil,
                $status,
                $cep,
                $logradouro,
                $bairro,
                $cidade,
                $uf,
                $id
            );
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "UPDATE usuarios
                SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?, cep = ?,
                    logradouro = ?, bairro = ?, cidade = ?, uf = ?, senha = ?
                WHERE id_usuario = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssi',
                $nome,
                $email,
                $perfil,
                $status,
                $cep,
                $logradouro,
                $bairro,
                $cidade,
                $uf,
                $senha_hash,
                $id
            );
        }

        // salvou: recarrega a tela pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o usuário: " . mysqli_error($conexao);
        mysqli_stmt_close($stmt);
    }

    // deu erro: guarda qual formulário reabrir com o que foi digitado
    $id_com_erro = $id;
}


// LISTA DE USUÁRIOS (com os trens de que cada um é responsável)

// filtro Ativos / Inativos / Todos que vem na url (?filtro=)
$filtro = 'ativos';
if (isset($_GET['filtro'])) {
    $filtro = $_GET['filtro'];
}

$where = "";
if ($filtro == 'ativos') {
    $where = "WHERE status_usuario <> 'INATIVO'";
} elseif ($filtro == 'inativos') {
    $where = "WHERE status_usuario = 'INATIVO'";
}

// o GROUP_CONCAT junta o nome dos trens dele separados por vírgula (coluna TRENS)
$sql = "SELECT usuarios.*,
    (
        SELECT COUNT(*)
        FROM trens
        WHERE trens.id_usuario = usuarios.id_usuario
        AND trens.status_trem <> 'INATIVO'
    ) AS total_trens,
    (
        SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ')
        FROM trens
        WHERE trens.id_usuario = usuarios.id_usuario
        AND trens.status_trem <> 'INATIVO'
    ) AS nomes_trens
    FROM usuarios
    $where
    ORDER BY nome_usuario";
$usuarios = mysqli_query($conexao, $sql);

if ($usuarios === false) {
    die('Erro ao consultar usuários: ' . mysqli_error($conexao));
}


// VALORES DO FORMULÁRIO DE CADASTRO
// em branco, ou o que foi digitado se o cadastro deu erro

$novo_nome = '';
$novo_email = '';
$novo_perfil = 'OPERADOR';
$novo_cep = '';
$novo_logradouro = '';
$novo_bairro = '';
$novo_cidade = '';
$novo_uf = '';

if ($id_com_erro === 0) {
    $novo_nome = $nome;
    $novo_email = $email;
    $novo_perfil = $perfil;
    $novo_cep = $cep;
    $novo_logradouro = $logradouro;
    $novo_bairro = $bairro;
    $novo_cidade = $cidade;
    $novo_uf = $uf;
}

// mostra o CEP com o traço (89201-100)
if (strlen($novo_cep) == 8) {
    $novo_cep = substr($novo_cep, 0, 5) . '-' . substr($novo_cep, 5);
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
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
                <h3>Usuários</h3>
                <p>Gerencie quem tem acesso ao sistema</p>
            </div>
            <!-- abre e fecha o formulário de cadastro -->
            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="collapse"
                data-bs-target="#editar0">
                + NOVO USUÁRIO
            </button>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
        <div class="caixa collapse <?php if ($id_com_erro === 0) echo 'show'; ?>" id="editar0">
            <div class="caixa-topo">CADASTRAR NOVO USUÁRIO</div>
            <form method="POST" class="caixa-corpo">
                <input type="hidden" name="id" value="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomeUsuario0" class="form-label">NOME DE USUÁRIO</label>
                        <input
                            type="text"
                            name="nome_usuario"
                            id="nomeUsuario0"
                            class="form-control"
                            placeholder="Ex: joao.silva"
                            value="<?php echo htmlspecialchars($novo_nome); ?>"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label for="emailUsuario0" class="form-label">E-MAIL</label>
                        <input
                            type="email"
                            name="email_usuario"
                            id="emailUsuario0"
                            class="form-control"
                            placeholder="Ex: joao@ferromonitor.com"
                            value="<?php echo htmlspecialchars($novo_email); ?>"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label for="senha0" class="form-label">SENHA</label>
                        <input
                            type="password"
                            name="senha"
                            id="senha0"
                            class="form-control"
                            placeholder="Mínimo 8 caracteres"
                            minlength="8"
                            required>
                    </div>
                    <div class="col-md-3">
                        <label for="perfil0" class="form-label">PERFIL</label>
                        <select name="perfil" id="perfil0" class="form-select">
                            <option
                                value="OPERADOR"
                                <?php if ($novo_perfil == 'OPERADOR') echo 'selected'; ?>>
                                Operador
                            </option>
                            <option
                                value="ADMINISTRADOR"
                                <?php if ($novo_perfil == 'ADMINISTRADOR') echo 'selected'; ?>>
                                Administrador
                            </option>
                        </select>
                    </div>

                    <div class="col-12 border-top pt-3">
                        <span class="form-label">ENDEREÇO (OPCIONAL)</span>
                    </div>
                    <div class="col-md-3">
                        <label for="cep0" class="form-label">CEP</label>
                        <!-- ao sair do campo o cep.js preenche o endereço -->
                        <input
                            type="text"
                            name="cep"
                            id="cep0"
                            class="form-control campo-cep"
                            data-id="0"
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="Ex: 89201-100"
                            value="<?php echo htmlspecialchars($novo_cep); ?>">
                        <div id="avisoCep0" class="form-text">
                            Digite o CEP para preencher o endereço.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="logradouro0" class="form-label">LOGRADOURO</label>
                        <input
                            type="text"
                            name="logradouro"
                            id="logradouro0"
                            class="form-control"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($novo_logradouro); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="bairro0" class="form-label">BAIRRO</label>
                        <input
                            type="text"
                            name="bairro"
                            id="bairro0"
                            class="form-control"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($novo_bairro); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="cidade0" class="form-label">CIDADE</label>
                        <input
                            type="text"
                            name="cidade"
                            id="cidade0"
                            class="form-control"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($novo_cidade); ?>">
                    </div>
                    <div class="col-md-1">
                        <label for="uf0" class="form-label">UF</label>
                        <input
                            type="text"
                            name="uf"
                            id="uf0"
                            class="form-control"
                            maxlength="2"
                            value="<?php echo htmlspecialchars($novo_uf); ?>">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="salvar" class="btn btn-primary">CADASTRAR</button>
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

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro -->
            <div class="caixa-topo">
                USUÁRIOS CADASTRADOS
                <div class="d-flex flex-wrap gap-2">
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
                            <!-- marca ou desmarca todos -->
                            <th>
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="marcarTodos">
                            </th>
                            <th>ID</th>
                            <th>NOME DE USUÁRIO</th>
                            <th>E-MAIL</th>
                            <th>PERFIL</th>
                            <th>CIDADE</th>
                            <th>TRENS</th>
                            <th>STATUS</th>
                            <th>CADASTRADO EM</th>
                            <th class="text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- uma linha pra cada usuário -->
                        <?php while ($usuario = mysqli_fetch_assoc($usuarios)) { ?>
                            <?php
                            $id_usuario = $usuario['id_usuario'];

                            // motivo pra não poder inativar (vai pro pop-up)
                            $bloqueio = '';
                            if ($id_usuario == $id_logado) {
                                $bloqueio = 'Você não pode inativar a própria conta.';
                            } elseif ($usuario['total_trens'] > 0) {
                                $bloqueio = 'Ele é responsável por '
                                    . $usuario['total_trens']
                                    . ' trem(ns) ativo(s). '
                                    . 'Troque o responsável desses trens antes de inativá-lo.';
                            }

                            $cidade_uf = '-';
                            if ($usuario['cidade'] != '') {
                                $cidade_uf = htmlspecialchars(
                                    $usuario['cidade'] . '/' . $usuario['uf']
                                );
                            }

                            $trens_dele = 'Sem trem';
                            if ($usuario['nomes_trens'] != null) {
                                $trens_dele = htmlspecialchars($usuario['nomes_trens']);
                            }

                            // valores do formulário de edição: os do banco, ou o que foi
                            // digitado se a edição desse usuário deu erro
                            $form_nome = $usuario['nome_usuario'];
                            $form_email = $usuario['email_usuario'];
                            $form_perfil = $usuario['perfil'];
                            $form_status = $usuario['status_usuario'];
                            $form_cep = $usuario['cep'];
                            $form_logradouro = $usuario['logradouro'];
                            $form_bairro = $usuario['bairro'];
                            $form_cidade = $usuario['cidade'];
                            $form_uf = $usuario['uf'];

                            if ($id_com_erro == $id_usuario) {
                                $form_nome = $nome;
                                $form_email = $email;
                                $form_perfil = $perfil;
                                $form_status = $status;
                                $form_cep = $cep;
                                $form_logradouro = $logradouro;
                                $form_bairro = $bairro;
                                $form_cidade = $cidade;
                                $form_uf = $uf;
                            }

                            // mostra o CEP com o traço (89201-100)
                            if (strlen($form_cep) == 8) {
                                $form_cep = substr($form_cep, 0, 5) . '-' . substr($form_cep, 5);
                            }

                            // ninguém muda o próprio perfil nem o próprio status
                            $travado = '';
                            if ($id_usuario == $id_logado) {
                                $travado = 'disabled';
                            }
                            ?>
                            <tr>
                                <!-- checkbox da linha (usado pelo pop-up) -->
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input marcar"
                                        form="formStatus"
                                        name="ids[]"
                                        value="<?php echo $id_usuario; ?>"
                                        data-nome="o usuário <?php
                                        echo htmlspecialchars($usuario['nome_usuario']); ?>"
                                        data-bloqueio="<?php echo $bloqueio; ?>">
                                </td>
                                <td><?php echo $id_usuario; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email_usuario']); ?></td>
                                <td>
                                    <span class="badge status status-<?php
                                    echo $usuario['perfil']; ?>">
                                        <?php echo $usuario['perfil']; ?>
                                    </span>
                                </td>
                                <td><?php echo $cidade_uf; ?></td>
                                <td><?php echo $trens_dele; ?></td>
                                <td>
                                    <span class="badge status status-<?php
                                    echo $usuario['status_usuario']; ?>">
                                        <?php echo $usuario['status_usuario']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    echo date('d/m/Y H:i', strtotime($usuario['data_cadastro']));
                                    ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <!-- EDITAR abre o formulário embaixo da linha -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#editar<?php echo $id_usuario; ?>">
                                        EDITAR
                                    </button>
                                    <?php if ($usuario['status_usuario'] == 'INATIVO') { ?>
                                        <button
                                            type="submit"
                                            form="formStatus"
                                            name="ativar"
                                            class="btn btn-sm btn-primary botao-ativar"
                                            data-id="<?php echo $id_usuario; ?>">
                                            ATIVAR
                                        </button>
                                    <?php } else { ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger botao-inativar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalInativar"
                                            data-id="<?php echo $id_usuario; ?>">
                                            INATIVAR
                                        </button>
                                    <?php } ?>
                                </td>
                            </tr>
                            <!-- linha escondida com o formulário de edição -->
                            <tr
                                class="collapse
                                    <?php if ($id_com_erro == $id_usuario) echo 'show'; ?>"
                                id="editar<?php echo $id_usuario; ?>">
                                <td colspan="10" class="p-0">
                                    <form method="POST" class="caixa-corpo">
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo $id_usuario; ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label
                                                    for="nomeUsuario<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    NOME DE USUÁRIO
                                                </label>
                                                <input
                                                    type="text"
                                                    name="nome_usuario"
                                                    id="nomeUsuario<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    placeholder="Ex: joao.silva"
                                                    value="<?php
                                                    echo htmlspecialchars($form_nome); ?>"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label
                                                    for="emailUsuario<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    E-MAIL
                                                </label>
                                                <input
                                                    type="email"
                                                    name="email_usuario"
                                                    id="emailUsuario<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    placeholder="Ex: joao@ferromonitor.com"
                                                    value="<?php
                                                    echo htmlspecialchars($form_email); ?>"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label
                                                    for="senha<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    NOVA SENHA
                                                </label>
                                                <input
                                                    type="password"
                                                    name="senha"
                                                    id="senha<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    placeholder="Deixe vazio para manter a senha"
                                                    minlength="8">
                                            </div>
                                            <div class="col-md-3">
                                                <label
                                                    for="perfil<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    PERFIL
                                                </label>
                                                <select
                                                    name="perfil"
                                                    id="perfil<?php echo $id_usuario; ?>"
                                                    class="form-select"
                                                    <?php echo $travado; ?>>
                                                    <option
                                                        value="OPERADOR"
                                                        <?php
                                                        if ($form_perfil == 'OPERADOR') {
                                                            echo 'selected';
                                                        }
                                                        ?>>
                                                        Operador
                                                    </option>
                                                    <option
                                                        value="ADMINISTRADOR"
                                                        <?php
                                                        if ($form_perfil == 'ADMINISTRADOR') {
                                                            echo 'selected';
                                                        }
                                                        ?>>
                                                        Administrador
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label
                                                    for="status<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    STATUS
                                                </label>
                                                <select
                                                    name="status_usuario"
                                                    id="status<?php echo $id_usuario; ?>"
                                                    class="form-select"
                                                    <?php echo $travado; ?>>
                                                    <option
                                                        value="ATIVO"
                                                        <?php
                                                        if ($form_status == 'ATIVO') {
                                                            echo 'selected';
                                                        }
                                                        ?>>
                                                        Ativo
                                                    </option>
                                                    <option
                                                        value="INATIVO"
                                                        <?php
                                                        if ($form_status == 'INATIVO') {
                                                            echo 'selected';
                                                        }
                                                        ?>>
                                                        Inativo
                                                    </option>
                                                </select>
                                            </div>
                                            <?php if ($id_usuario == $id_logado) { ?>
                                                <div class="col-12 form-text mt-1">
                                                    Você não pode alterar o próprio perfil nem o
                                                    próprio status.
                                                </div>
                                            <?php } ?>

                                            <div class="col-12 border-top pt-3">
                                                <span class="form-label">ENDEREÇO (OPCIONAL)</span>
                                            </div>
                                            <div class="col-md-3">
                                                <label
                                                    for="cep<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    CEP
                                                </label>
                                                <!-- o cep.js preenche o endereço -->
                                                <input
                                                    type="text"
                                                    name="cep"
                                                    id="cep<?php echo $id_usuario; ?>"
                                                    class="form-control campo-cep"
                                                    data-id="<?php echo $id_usuario; ?>"
                                                    inputmode="numeric"
                                                    maxlength="9"
                                                    placeholder="Ex: 89201-100"
                                                    value="<?php
                                                    echo htmlspecialchars($form_cep); ?>">
                                                <div
                                                    id="avisoCep<?php echo $id_usuario; ?>"
                                                    class="form-text">
                                                    Digite o CEP para preencher o endereço.
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label
                                                    for="logradouro<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    LOGRADOURO
                                                </label>
                                                <input
                                                    type="text"
                                                    name="logradouro"
                                                    id="logradouro<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    maxlength="150"
                                                    value="<?php
                                                    echo htmlspecialchars($form_logradouro); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label
                                                    for="bairro<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    BAIRRO
                                                </label>
                                                <input
                                                    type="text"
                                                    name="bairro"
                                                    id="bairro<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    maxlength="100"
                                                    value="<?php
                                                    echo htmlspecialchars($form_bairro); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label
                                                    for="cidade<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    CIDADE
                                                </label>
                                                <input
                                                    type="text"
                                                    name="cidade"
                                                    id="cidade<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    maxlength="100"
                                                    value="<?php
                                                    echo htmlspecialchars($form_cidade); ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label
                                                    for="uf<?php echo $id_usuario; ?>"
                                                    class="form-label">
                                                    UF
                                                </label>
                                                <input
                                                    type="text"
                                                    name="uf"
                                                    id="uf<?php echo $id_usuario; ?>"
                                                    class="form-control"
                                                    maxlength="2"
                                                    value="<?php
                                                    echo htmlspecialchars($form_uf); ?>">
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
                                                data-bs-target="#editar<?php echo $id_usuario; ?>">
                                                CANCELAR
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($usuarios) == 0) { ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Nenhum usuário cadastrado.
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
    <!-- busca do CEP no ViaCEP -->
    <script src="../../script/cep.js"></script>

</body>

</html>
