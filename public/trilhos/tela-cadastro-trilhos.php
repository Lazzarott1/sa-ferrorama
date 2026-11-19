<?php

// tela de trilhos: todo mundo vê a lista, mas só o admin cadastra, edita e inativa
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-login.php';
include __DIR__ . '/../../infra/validacoes-crud/valida-trilho.php';
include __DIR__ . '/../listagem.php';

$erros = [];
// $aberto diz qual formulário reabrir quando dá erro: 0 é o de cadastro, outro número é o id do
// item editado
$aberto = -1;
// o que foi digitado no formulário que deu erro (fica vazio até dar algum erro)
$digitado = [];

// pedaços de SQL que contam quantos trens e sensores ativos estão no trilho
// ficam em variável porque são usados em vários lugares da página
$trens_ativos = "(SELECT COUNT(*)
    FROM trens
    WHERE trens.id_trilho = trilhos.id_trilho AND trens.status_trem <> 'INATIVO')";
$sensores_ativos = "(SELECT COUNT(*)
    FROM sensores
    WHERE sensores.id_trilho = trilhos.id_trilho AND sensores.status_sensor <> 'INATIVO')";


// ATIVAR / INATIVAR TRILHOS

if ($admin && (isset($_POST['ativar']) || isset($_POST['inativar']))) {
    // só inativa trilho sem trem e sem sensor ativo; se algum ficou de fora mostra o aviso
    $nao_inativados = mudarStatus(
        $conexao,
        'trilhos',
        'id_trilho',
        'status_trilho',
        "$trens_ativos = 0 AND $sensores_ativos = 0"
    );

    if ($nao_inativados == 0) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $erros[] = "Trilhos com trens ou sensores ativos não foram inativados. "
        . "Inative ou altere esses itens antes.";
}


// CADASTRAR / EDITAR TRILHO

if ($admin && isset($_POST['salvar'])) {
    // o mesmo formulário serve pra cadastrar e editar: id 0 é cadastro, id maior que 0 é edição
    // aqui pega tudo que veio do formulário
    $id = (int) $_POST['id'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $km = str_replace(',', '.', trim($_POST['km']));
    $status = $_POST['status'];

    $erros = validarTrilho($nome, $descricao, $km, $status);

    // mudou pra inativo na edição? confere se não tem trem nem sensor ativo nele
    if ($id != 0 && $status == 'INATIVO') {
        $resultado = mysqli_query($conexao, "SELECT $trens_ativos AS trens,
            $sensores_ativos AS sensores
            FROM trilhos
            WHERE id_trilho = $id");
        $total = mysqli_fetch_assoc($resultado);

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
        // id 0 faz INSERT, senão faz UPDATE (os ? são preenchidos pelo bind_param, assim ninguém
        // consegue injetar SQL)
        if ($id == 0) {
            $stmt = mysqli_prepare($conexao, "INSERT INTO trilhos (nome_trilho, descricao_trilho,
                km_trilho, status_trilho)
                VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $nome, $descricao, $km, $status);
        } else {
            $stmt = mysqli_prepare($conexao, "UPDATE trilhos
                SET nome_trilho = ?, descricao_trilho = ?, km_trilho = ?, status_trilho = ?
                WHERE id_trilho = ?");
            mysqli_stmt_bind_param($stmt, "ssssi", $nome, $descricao, $km, $status, $id);
        }

        // salvou: recarrega a mesma página (mantendo o filtro) pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o trilho. Tente novamente.";
    }

    // se chegou aqui deu erro: guarda o que foi digitado pra reabrir o formulário preenchido
    $aberto = $id;
    $digitado = [
        'id_trilho' => $id,
        'nome_trilho' => $nome,
        'descricao_trilho' => $descricao,
        'km_trilho' => $km,
        'status_trilho' => $status
    ];
}


// BUSCAR TRILHOS (com os trens associados)

// filtro Ativos / Inativos / Todos (função do listagem.php)
$where = filtrar('status_trilho');

// o GROUP_CONCAT junta o nome de todos os trens do trilho separados por vírgula
// (é o que aparece na coluna TRENS ASSOCIADOS)
$trilhos = mysqli_query($conexao, "SELECT trilhos.*,
    $trens_ativos AS total_trens,
    $sensores_ativos AS total_sensores,
    (
        SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ')
        FROM trens
        WHERE trens.id_trilho = trilhos.id_trilho
        AND trens.status_trem <> 'INATIVO'
    ) AS trens_associados
    FROM trilhos
    $where
    ORDER BY id_trilho DESC");

// valores em branco pro formulário de cadastro
$novo = [
    'id_trilho' => 0,
    'nome_trilho' => '',
    'descricao_trilho' => '',
    'km_trilho' => '',
    'status_trilho' => 'ATIVO'
];


// desenha o formulário do trilho, usado no cadastro e também na edição dentro da lista
function formTrilho(array $trilho)
{
    $id = $trilho['id_trilho'];
    ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeTrilho<?php echo $id; ?>" class="form-label">NOME DO TRILHO</label>
                <input
                    type="text"
                    name="nome"
                    id="nomeTrilho<?php echo $id; ?>"
                    class="form-control"
                    placeholder="Ex: Trilho Norte 01"
                    value="<?php echo htmlspecialchars($trilho['nome_trilho']); ?>"
                    required>
            </div>
            <div class="col-md-6">
                <label for="kmTrilho<?php echo $id; ?>" class="form-label">KM</label>
                <input
                    type="number"
                    name="km"
                    id="kmTrilho<?php echo $id; ?>"
                    class="form-control"
                    step="0.01"
                    min="0.01"
                    placeholder="Ex: 12.5"
                    value="<?php echo htmlspecialchars($trilho['km_trilho']); ?>"
                    required>
            </div>
            <div class="col-md-6">
                <label for="descricaoTrilho<?php echo $id; ?>" class="form-label">DESCRIÇÃO</label>
                <input
                    type="text"
                    name="descricao"
                    id="descricaoTrilho<?php echo $id; ?>"
                    class="form-control"
                    placeholder="Ex: Trecho entre os pátios A e B"
                    value="<?php echo htmlspecialchars($trilho['descricao_trilho']); ?>"
                    required>
            </div>
            <div class="col-md-6">
                <label for="statusTrilho<?php echo $id; ?>" class="form-label">STATUS</label>
                <select
                    name="status"
                    id="statusTrilho<?php echo $id; ?>"
                    class="form-select"
                    required>
                    <option
                        value="ATIVO"
                        <?php if ($trilho['status_trilho'] == 'ATIVO') echo 'selected'; ?>>
                        Ativo
                    </option>
                    <option
                        value="MANUTENCAO"
                        <?php if ($trilho['status_trilho'] == 'MANUTENCAO') echo 'selected'; ?>>
                        Manutenção
                    </option>
                    <option
                        value="INATIVO"
                        <?php if ($trilho['status_trilho'] == 'INATIVO') echo 'selected'; ?>>
                        Inativo
                    </option>
                </select>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <?php if ($id == 0) { ?>
                <button type="submit" name="salvar" class="btn btn-primary">CADASTRAR</button>
            <?php } else { ?>
                <button type="submit" name="salvar" class="btn btn-primary">
                    SALVAR ALTERAÇÕES
                </button>
            <?php } ?>
            <button
                type="button"
                class="btn btn-secondary"
                data-bs-toggle="collapse"
                data-bs-target="#editar<?php echo $id; ?>">
                CANCELAR
            </button>
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
    <title>Trilhos</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/img/style/style.css">
</head>

<body>

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

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
            <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
            <div
                class="caixa collapse
                    <?php if ($aberto === 0 || isset($_GET['novo'])) echo 'show'; ?>"
                id="editar0">
                <div class="caixa-topo">CADASTRAR NOVO TRILHO</div>
                <?php
                if ($aberto === 0) {
                    formTrilho($digitado);
                } else {
                    formTrilho($novo);
                }
                ?>
            </div>
        <?php } ?>

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro (listagem.php) -->
            <?php topo('TRILHOS CADASTRADOS', $admin); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <?php if ($admin) marcarTodos(); ?>
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
                            // se tiver trem ou sensor ativo, o motivo vai pro checkbox e aparece no
                            // modal de inativar
                            $bloqueio = '';
                            if ($trilho['total_trens'] > 0 || $trilho['total_sensores'] > 0) {
                                $bloqueio = 'Ele tem '
                                    . $trilho['total_trens']
                                    . ' trem(ns) e '
                                    . $trilho['total_sensores']
                                    . ' sensor(es) ativo(s). '
                                    . 'Inative ou altere esses itens antes de inativá-lo.';
                            }
                            ?>
                            <tr>
                                <?php if ($admin) marcar($trilho['id_trilho'], 'o trilho '
                                    . $trilho['nome_trilho'], $bloqueio); ?>
                                <td><?php echo $trilho['id_trilho']; ?></td>
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
                                    <?php $classe = 'status-' . $trilho['status_trilho']; ?>
                                    <span class="badge status <?php echo $classe; ?>">
                                        <?php echo $trilho['status_trilho']; ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php
                                    if ($admin) {
                                        acoes($trilho['id_trilho'], $trilho['status_trilho']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if ($admin) { ?>
                                <!-- linha escondida embaixo de cada item com o formulário de -->
                                <!-- edição, abre no botão EDITAR -->
                                <tr
                                    class="collapse
                                        <?php if ($aberto == $trilho['id_trilho']) echo 'show'; ?>"
                                    id="editar<?php echo $trilho['id_trilho']; ?>">
                                    <td colspan="9" class="p-0">
                                        <?php
                                        if ($aberto == $trilho['id_trilho']) {
                                            formTrilho($digitado);
                                        } else {
                                            formTrilho($trilho);
                                        }
                                        ?>
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

    <!-- modal de confirmar inativação (listagem.php) -->
    <?php modalStatus(); ?>

    <!-- marcar itens, ações em massa e o modal de inativar -->
    <script src="../../script/listagem.js"></script>

</body>

</html>
