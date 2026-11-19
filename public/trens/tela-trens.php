<?php

// tela de trens: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trem.php';
include __DIR__ . '/../listagem.php';

$erros = [];
// $aberto diz qual formulário reabrir quando dá erro: 0 é o de cadastro, outro número é o id do item editado
$aberto = -1;


// ATIVAR / INATIVAR TRENS

if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {
    // ativar/inativar os marcados (trem não tem regra extra)
    mudarStatus($conexao, 'trens', 'id_trem', 'status_trem');
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}


// CADASTRAR / EDITAR TREM

if ($admin && isset($_POST['salvar'])) {
    // o mesmo formulário serve pra cadastrar e editar: id 0 é cadastro, id maior que 0 é edição
    // aqui pega tudo que veio do formulário
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $modelo = $_POST['modelo'];
    $capacidade = trim($_POST['capacidade']);
    $id_trilho = (int) $_POST['id_trilho'];
    $id_usuario = (int) $_POST['id_usuario'];
    $status = $_POST['status'];

    $erros = validarTrem($conexao, $id, $nome, $modelo, $capacidade, $id_trilho, $id_usuario, $status);

    if (count($erros) == 0) {
        // id 0 faz INSERT, senão faz UPDATE (os ? são preenchidos pelo bind_param, assim ninguém consegue injetar SQL)
        if ($id == 0) {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trens (nome_trem, modelo_trem, capacidade_trem, id_trilho, id_usuario, status_trem) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiiis", $nome, $modelo, $capacidade, $id_trilho, $id_usuario, $status);
        } else {
            $stmt = mysqli_prepare($conexao, "UPDATE trens SET nome_trem = ?, modelo_trem = ?, capacidade_trem = ?, id_trilho = ?, id_usuario = ?, status_trem = ? WHERE id_trem = ?");
            mysqli_stmt_bind_param($stmt, "ssiiisi", $nome, $modelo, $capacidade, $id_trilho, $id_usuario, $status, $id);
        }

        // salvou: recarrega a mesma página (mantendo o filtro) pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o trem. Tente novamente.";
    }

    // se chegou aqui deu erro: guarda o que foi digitado pra reabrir o formulário preenchido
    $aberto = $id;
    $digitado = ['id_trem' => $id, 'nome_trem' => $nome, 'modelo_trem' => $modelo, 'capacidade_trem' => $capacidade, 'id_trilho' => $id_trilho, 'id_usuario' => $id_usuario, 'status_trem' => $status];
}


// BUSCAR TRENS (com o nome do trilho e do responsável)

// filtro Ativos / Inativos / Todos (função do listagem.php)
$where = filtrar('trens.status_trem');

$trens = mysqli_query($conexao, "SELECT trens.*, trilhos.nome_trilho, usuarios.nome_usuario
    FROM trens
    LEFT JOIN trilhos ON trilhos.id_trilho = trens.id_trilho
    LEFT JOIN usuarios ON usuarios.id_usuario = trens.id_usuario
    $where
    ORDER BY trens.id_trem DESC");


// LISTAS DOS CAMPOS TRILHO E RESPONSÁVEL

// listas pros selects de trilho e responsável
$trilhos = [];
$resultado = mysqli_query($conexao, "SELECT id_trilho, nome_trilho, status_trilho FROM trilhos ORDER BY nome_trilho");
while ($linha = mysqli_fetch_assoc($resultado)) {
    $trilhos[] = $linha;
}

$usuarios = [];
$resultado = mysqli_query($conexao, "SELECT id_usuario, nome_usuario, status_usuario FROM usuarios ORDER BY nome_usuario");
while ($linha = mysqli_fetch_assoc($resultado)) {
    $usuarios[] = $linha;
}

// valores em branco pro formulário de cadastro
$novo = ['id_trem' => 0, 'nome_trem' => '', 'modelo_trem' => '', 'capacidade_trem' => '', 'id_trilho' => 0, 'id_usuario' => 0, 'status_trem' => 'ATIVO'];


// desenha o formulário do trem, usado no cadastro e também na edição dentro da lista
function formTrem($trem, $trilhos, $usuarios)
{
    $id = $trem['id_trem'];
    ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeTrem<?php echo $id; ?>" class="form-label">NOME DO TREM</label>
                <input type="text" name="nome" id="nomeTrem<?php echo $id; ?>" class="form-control" placeholder="Ex: Trem 01" value="<?php echo htmlspecialchars($trem['nome_trem']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="modeloTrem<?php echo $id; ?>" class="form-label">MODELO</label>
                <!-- quando troca o modelo, o capacidade.js muda o texto da capacidade (KG ou PASSAGEIROS) -->
                <select name="modelo" id="modeloTrem<?php echo $id; ?>" class="form-select modelo-trem" data-id="<?php echo $id; ?>" onchange="atualizarCapacidade(this)" required>
                    <option value="">Selecione o modelo</option>
                    <option <?php if ($trem['modelo_trem'] == 'De passageiros') echo 'selected'; ?>>De passageiros</option>
                    <option <?php if ($trem['modelo_trem'] == 'De carga') echo 'selected'; ?>>De carga</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="capacidadeTrem<?php echo $id; ?>" id="labelCapacidade<?php echo $id; ?>" class="form-label">CAPACIDADE</label>
                <input type="number" name="capacidade" id="capacidadeTrem<?php echo $id; ?>" class="form-control" value="<?php echo htmlspecialchars($trem['capacidade_trem']); ?>" min="1" required>
            </div>
            <div class="col-md-6">
                <label for="trilhoTrem<?php echo $id; ?>" class="form-label">TRILHO ATUAL</label>
                <select name="id_trilho" id="trilhoTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o trilho</option>
                    <!-- só mostra trilho ativo, a não ser que seja o que o trem já usa (mesma ideia no responsável) -->
                    <?php foreach ($trilhos as $trilho) { ?>
                        <?php if ($trilho['status_trilho'] != 'INATIVO' || $trilho['id_trilho'] == $trem['id_trilho']) { ?>
                            <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trilho['id_trilho'] == $trem['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
                <div class="form-text">O trem precisa de um trilho. <a href="../trilhos/tela-cadastro-trilhos.php?novo=1">Cadastrar trilho</a></div>
            </div>
            <div class="col-md-6">
                <label for="responsavelTrem<?php echo $id; ?>" class="form-label">RESPONSÁVEL</label>
                <select name="id_usuario" id="responsavelTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o responsável</option>
                    <?php foreach ($usuarios as $usuario) { ?>
                        <?php if ($usuario['status_usuario'] == 'ATIVO' || $usuario['id_usuario'] == $trem['id_usuario']) { ?>
                            <option value="<?php echo $usuario['id_usuario']; ?>" <?php if ($usuario['id_usuario'] == $trem['id_usuario']) echo 'selected'; ?>><?php echo htmlspecialchars($usuario['nome_usuario']); ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
                <div class="form-text">O trem precisa de um responsável. <a href="../usuarios/tela-cadastro-user.php">Cadastrar usuário</a></div>
            </div>
            <div class="col-md-6">
                <label for="statusTrem<?php echo $id; ?>" class="form-label">STATUS</label>
                <select name="status" id="statusTrem<?php echo $id; ?>" class="form-select" required>
                    <option value="ATIVO" <?php if ($trem['status_trem'] == 'ATIVO') echo 'selected'; ?>>Ativo</option>
                    <option value="MANUTENCAO" <?php if ($trem['status_trem'] == 'MANUTENCAO') echo 'selected'; ?>>Manutenção</option>
                    <option value="INATIVO" <?php if ($trem['status_trem'] == 'INATIVO') echo 'selected'; ?>>Inativo</option>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <?php if ($id == 0) { ?>
                <button type="submit" name="salvar" class="btn btn-primary">CADASTRAR</button>
            <?php } else { ?>
                <button type="submit" name="salvar" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
            <?php } ?>
            <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#editar<?php echo $id; ?>">CANCELAR</button>
        </div>
    </form>
    <?php
}

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

<body>

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Trens</h3>
                <p>Gerencie os trens cadastrados na malha ferroviária</p>
            </div>
            <?php if ($admin) { ?>
                <!-- abre e fecha o formulário de cadastro -->
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO TREM</button>
            <?php } ?>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
            <div class="caixa collapse <?php if ($aberto === 0) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TREM</div>
                <?php
                if ($aberto === 0) {
                    formTrem($digitado, $trilhos, $usuarios);
                } else {
                    formTrem($novo, $trilhos, $usuarios);
                }
                ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro (listagem.php) -->
            <?php topo('TRENS CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <?php if ($admin) marcarTodos(); ?>
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
                            <tr>
                                <?php if ($admin) marcar($trem['id_trem'], 'o trem ' . $trem['nome_trem'], ''); ?>
                                <td><?php echo $trem['id_trem']; ?></td>
                                <td><?php echo htmlspecialchars($trem['nome_trem']); ?></td>
                                <td><?php echo htmlspecialchars($trem['modelo_trem']); ?></td>
                                <td><?php echo $trem['capacidade_trem']; ?></td>
                                <td>
                                    <?php
                                    if ($trem['nome_trilho'] == null) {
                                        echo 'Sem trilho';
                                    } else {
                                        echo htmlspecialchars($trem['nome_trilho']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($trem['nome_usuario'] == null) {
                                        echo 'Sem responsável';
                                    } else {
                                        echo htmlspecialchars($trem['nome_usuario']);
                                    }
                                    ?>
                                </td>
                                <td><span class="badge status status-<?php echo $trem['status_trem']; ?>"><?php echo $trem['status_trem']; ?></span></td>
                                <td class="text-center text-nowrap">
                                    <?php
                                    if ($admin) {
                                        acoes($trem['id_trem'], $trem['status_trem']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if ($admin) { ?>
                                <!-- linha escondida embaixo de cada item com o formulário de edição, abre no botão EDITAR -->
                                <tr class="collapse <?php if ($aberto == $trem['id_trem']) echo 'show'; ?>" id="editar<?php echo $trem['id_trem']; ?>">
                                    <td colspan="9" class="p-0">
                                        <?php
                                        if ($aberto == $trem['id_trem']) {
                                            formTrem($digitado, $trilhos, $usuarios);
                                        } else {
                                            formTrem($trem, $trilhos, $usuarios);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($trens) == 0) { ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum trem cadastrado.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- modal de confirmar inativação (listagem.php) -->
    <?php modalStatus(); ?>

    <!-- marcar itens, ações em massa e o modal de inativar -->
    <script src="../../script/listagem.js"></script>
    <!-- texto da capacidade conforme o modelo -->
    <script src="../../script/capacidade.js"></script>

</body>

</html>
