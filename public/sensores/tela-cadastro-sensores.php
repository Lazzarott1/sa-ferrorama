<?php

// tela de sensores: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-sensores.php';
include __DIR__ . '/../listagem.php';

$erros = [];
// $aberto diz qual formulário reabrir quando dá erro: 0 é o de cadastro, outro número é o id do item editado
$aberto = -1;


// ATIVAR / INATIVAR SENSORES

if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {
    // ativar/inativar os marcados (sensor não tem nada ligado nele, então não tem regra extra)
    mudarStatus($conexao, 'sensores', 'id_sensor', 'status_sensor');
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}


// CADASTRAR / EDITAR SENSOR

if ($admin && isset($_POST['salvar'])) {
    // o mesmo formulário serve pra cadastrar e editar: id 0 é cadastro, id maior que 0 é edição
    // aqui pega tudo que veio do formulário
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $id_trilho = (int) $_POST['trilho'];
    $status = $_POST['status'];

    $erros = validarSensor($conexao, $id, $nome, $tipo, $id_trilho, $status);

    if (count($erros) == 0) {
        // id 0 faz INSERT, senão faz UPDATE (os ? são preenchidos pelo bind_param, assim ninguém consegue injetar SQL)
        if ($id == 0) {
            $stmt = mysqli_prepare($conexao, "INSERT INTO sensores (nome_sensor, tipo_sensor, id_trilho, status_sensor) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", $nome, $tipo, $id_trilho, $status);
        } else {
            $stmt = mysqli_prepare($conexao, "UPDATE sensores SET nome_sensor = ?, tipo_sensor = ?, id_trilho = ?, status_sensor = ? WHERE id_sensor = ?");
            mysqli_stmt_bind_param($stmt, "ssisi", $nome, $tipo, $id_trilho, $status, $id);
        }

        // salvou: recarrega a mesma página (mantendo o filtro) pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o sensor. Tente novamente.";
    }

    // se chegou aqui deu erro: guarda o que foi digitado pra reabrir o formulário preenchido
    $aberto = $id;
    $digitado = ['id_sensor' => $id, 'nome_sensor' => $nome, 'tipo_sensor' => $tipo, 'id_trilho' => $id_trilho, 'status_sensor' => $status];
}


// BUSCAR SENSORES (com o nome do trilho)

// filtro Ativos / Inativos / Todos (função do listagem.php)
$where = filtrar('sensores.status_sensor');

$sensores = mysqli_query($conexao, "SELECT sensores.*, trilhos.nome_trilho
    FROM sensores
    LEFT JOIN trilhos ON trilhos.id_trilho = sensores.id_trilho
    $where
    ORDER BY sensores.id_sensor DESC");


// LISTA DO CAMPO TRILHO

// todos os trilhos, pro select do formulário
$trilhos = [];
$resultado = mysqli_query($conexao, "SELECT id_trilho, nome_trilho, status_trilho FROM trilhos ORDER BY nome_trilho");
while ($linha = mysqli_fetch_assoc($resultado)) {
    $trilhos[] = $linha;
}

// valores em branco pro formulário de cadastro
$novo = ['id_sensor' => 0, 'nome_sensor' => '', 'tipo_sensor' => '', 'id_trilho' => 0, 'status_sensor' => 'ATIVO'];


// desenha o formulário do sensor, usado no cadastro e também na edição dentro da lista
function formSensor($sensor, $trilhos)
{
    $id = $sensor['id_sensor'];
    ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeSensor<?php echo $id; ?>" class="form-label">NOME DO SENSOR</label>
                <input type="text" name="nome" id="nomeSensor<?php echo $id; ?>" class="form-control" placeholder="Ex: Sensor 01" value="<?php echo htmlspecialchars($sensor['nome_sensor']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="tipoSensor<?php echo $id; ?>" class="form-label">TIPO</label>
                <select name="tipo" id="tipoSensor<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione o tipo</option>
                    <option <?php if ($sensor['tipo_sensor'] == 'Velocidade') echo 'selected'; ?>>Velocidade</option>
                    <option <?php if ($sensor['tipo_sensor'] == 'Localização') echo 'selected'; ?>>Localização</option>
                    <option <?php if ($sensor['tipo_sensor'] == 'Temperatura') echo 'selected'; ?>>Temperatura</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="trilhoSensor<?php echo $id; ?>" class="form-label">TRILHO</label>
                <select name="trilho" id="trilhoSensor<?php echo $id; ?>" class="form-select" required>
                    <option value="">Selecione um trilho</option>
                    <!-- só mostra trilho ativo, a não ser que seja o que o sensor já usa -->
                    <?php foreach ($trilhos as $trilho) { ?>
                        <?php if ($trilho['status_trilho'] != 'INATIVO' || $trilho['id_trilho'] == $sensor['id_trilho']) { ?>
                            <option value="<?php echo $trilho['id_trilho']; ?>" <?php if ($trilho['id_trilho'] == $sensor['id_trilho']) echo 'selected'; ?>><?php echo htmlspecialchars($trilho['nome_trilho']); ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="statusSensor<?php echo $id; ?>" class="form-label">STATUS</label>
                <select name="status" id="statusSensor<?php echo $id; ?>" class="form-select" required>
                    <option value="ATIVO" <?php if ($sensor['status_sensor'] == 'ATIVO') echo 'selected'; ?>>Ativo</option>
                    <option value="ALERTA" <?php if ($sensor['status_sensor'] == 'ALERTA') echo 'selected'; ?>>Alerta</option>
                    <option value="INATIVO" <?php if ($sensor['status_sensor'] == 'INATIVO') echo 'selected'; ?>>Inativo</option>
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
    <title>Sensores</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Sensores</h3>
                <p>Gerencie os sensores cadastrados na ferrovia</p>
            </div>
            <?php if ($admin) { ?>
                <!-- abre e fecha o formulário de cadastro -->
                <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO SENSOR</button>
            <?php } ?>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <?php if ($admin) { ?>
            <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
            <div class="caixa collapse <?php if ($aberto === 0) echo 'show'; ?>" id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO SENSOR</div>
                <?php
                if ($aberto === 0) {
                    formSensor($digitado, $trilhos);
                } else {
                    formSensor($novo, $trilhos);
                }
                ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro (listagem.php) -->
            <?php topo('SENSORES CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <?php if ($admin) marcarTodos(); ?>
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
                            <tr>
                                <?php if ($admin) marcar($sensor['id_sensor'], 'o sensor ' . $sensor['nome_sensor'], ''); ?>
                                <td><?php echo $sensor['id_sensor']; ?></td>
                                <td><?php echo htmlspecialchars($sensor['nome_sensor']); ?></td>
                                <td><?php echo $sensor['tipo_sensor']; ?></td>
                                <td>
                                    <?php
                                    if ($sensor['nome_trilho'] == null) {
                                        echo '-';
                                    } else {
                                        echo htmlspecialchars($sensor['nome_trilho']);
                                    }
                                    ?>
                                </td>
                                <td><span class="badge status status-<?php echo $sensor['status_sensor']; ?>"><?php echo $sensor['status_sensor']; ?></span></td>
                                <td class="text-center text-nowrap">
                                    <?php
                                    if ($admin) {
                                        acoes($sensor['id_sensor'], $sensor['status_sensor']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if ($admin) { ?>
                                <!-- linha escondida embaixo de cada item com o formulário de edição, abre no botão EDITAR -->
                                <tr class="collapse <?php if ($aberto == $sensor['id_sensor']) echo 'show'; ?>" id="editar<?php echo $sensor['id_sensor']; ?>">
                                    <td colspan="7" class="p-0">
                                        <?php
                                        if ($aberto == $sensor['id_sensor']) {
                                            formSensor($digitado, $trilhos);
                                        } else {
                                            formSensor($sensor, $trilhos);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($sensores) == 0) { ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum sensor cadastrado.</td></tr>
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

</body>

</html>
