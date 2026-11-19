<?php

// tela de usuários, só o administrador entra (o verifica-admin manda o operador de volta pra home)
include __DIR__ . '/../../infra/validacoes-usuarios/verifica-admin.php';
include __DIR__ . '/../../infra/validacoes-usuarios/valida-usuario.php';
include __DIR__ . '/../listagem.php';

$erros = [];
// $aberto diz qual formulário reabrir quando dá erro: 0 é o de cadastro, outro número é o id do item editado
$aberto = -1;

// SQL que conta de quantos trens ativos o usuário é responsável
$trens_ativos = "(SELECT COUNT(*) FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND trens.status_trem <> 'INATIVO')";


// ATIVAR / INATIVAR USUÁRIOS

if (isset($_POST['ativar']) || isset($_POST['inativar'])) {
    // não inativa a própria conta nem quem é responsável por trem ativo
    $nao_inativados = mudarStatus($conexao, 'usuarios', 'id_usuario', 'status_usuario', "id_usuario <> $id_logado AND $trens_ativos = 0");

    if ($nao_inativados == 0) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $erros[] = "Usuários responsáveis por trens ativos e a sua própria conta não foram inativados.";
}


// CADASTRAR / EDITAR USUÁRIO

if (isset($_POST['salvar'])) {
    // o mesmo formulário serve pra cadastrar e editar: id 0 é cadastro, id maior que 0 é edição
    // aqui pega tudo que veio do formulário
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

    $erros = validarUsuario($conexao, $id, $nome, $email, $senha, $perfil, $status, $cep, $logradouro, $bairro, $cidade, $uf);

    if (count($erros) == 0) {
        // id 0 faz INSERT, senão faz UPDATE (os ? são preenchidos pelo bind_param, assim ninguém consegue injetar SQL)
        if ($id == 0) {
            // nunca guarda a senha pura, só o hash
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (nome_usuario, email_usuario, senha, perfil, cep, logradouro, bairro, cidade, uf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssssss", $nome, $email, $senha_hash, $perfil, $cep, $logradouro, $bairro, $cidade, $uf);
        // senha em branco na edição: mantém a antiga
        } elseif ($senha == '') {
            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?, cep = ?, logradouro = ?, bairro = ?, cidade = ?, uf = ? WHERE id_usuario = ?");
            mysqli_stmt_bind_param($stmt, "sssssssssi", $nome, $email, $perfil, $status, $cep, $logradouro, $bairro, $cidade, $uf, $id);
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET nome_usuario = ?, email_usuario = ?, perfil = ?, status_usuario = ?, cep = ?, logradouro = ?, bairro = ?, cidade = ?, uf = ?, senha = ? WHERE id_usuario = ?");
            mysqli_stmt_bind_param($stmt, "ssssssssssi", $nome, $email, $perfil, $status, $cep, $logradouro, $bairro, $cidade, $uf, $senha_hash, $id);
        }

        // salvou: recarrega a mesma página (mantendo o filtro) pra limpar o formulário
        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }

        $erros[] = "Não foi possível salvar o usuário. Tente novamente.";
    }

    // se chegou aqui deu erro: guarda o que foi digitado pra reabrir o formulário preenchido
    $aberto = $id;
    $digitado = ['id_usuario' => $id, 'nome_usuario' => $nome, 'email_usuario' => $email, 'perfil' => $perfil, 'status_usuario' => $status, 'cep' => $cep, 'logradouro' => $logradouro, 'bairro' => $bairro, 'cidade' => $cidade, 'uf' => $uf];
}


// BUSCAR USUÁRIOS (com os trens de que são responsáveis)

// filtro Ativos / Inativos / Todos (função do listagem.php)
$where = filtrar('status_usuario');

// junta o nome dos trens que cada um é responsável (coluna TRENS)
$usuarios = mysqli_query($conexao, "SELECT usuarios.*,
    $trens_ativos AS total_trens,
    (SELECT GROUP_CONCAT(nome_trem SEPARATOR ', ') FROM trens WHERE trens.id_usuario = usuarios.id_usuario AND trens.status_trem <> 'INATIVO') AS nomes_trens
    FROM usuarios
    $where
    ORDER BY nome_usuario");

// valores em branco pro formulário de cadastro
$novo = ['id_usuario' => 0, 'nome_usuario' => '', 'email_usuario' => '', 'perfil' => 'OPERADOR', 'status_usuario' => 'ATIVO', 'cep' => '', 'logradouro' => '', 'bairro' => '', 'cidade' => '', 'uf' => ''];


// desenha o formulário do usuário, usado no cadastro e também na edição dentro da lista
function formUsuario($usuario, $id_logado)
{
    $id = $usuario['id_usuario'];

    // mostra o CEP com o traço (89201-100)
    $cep = $usuario['cep'];
    if (strlen($cep) == 8) {
        $cep = substr($cep, 0, 5) . '-' . substr($cep, 5);
    }
    ?>
    <form method="POST" class="caixa-corpo">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nomeUsuario<?php echo $id; ?>" class="form-label">NOME DE USUÁRIO</label>
                <input type="text" name="nome_usuario" id="nomeUsuario<?php echo $id; ?>" class="form-control" placeholder="Ex: joao.silva" value="<?php echo htmlspecialchars($usuario['nome_usuario']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="emailUsuario<?php echo $id; ?>" class="form-label">E-MAIL</label>
                <input type="email" name="email_usuario" id="emailUsuario<?php echo $id; ?>" class="form-control" placeholder="Ex: joao@ferromonitor.com" value="<?php echo htmlspecialchars($usuario['email_usuario']); ?>" required>
            </div>
            <div class="col-md-6">
                <?php if ($id == 0) { ?>
                    <label for="senha0" class="form-label">SENHA</label>
                    <input type="password" name="senha" id="senha0" class="form-control" placeholder="Mínimo 8 caracteres" minlength="8" required>
                <?php } else { ?>
                    <label for="senha<?php echo $id; ?>" class="form-label">NOVA SENHA</label>
                    <input type="password" name="senha" id="senha<?php echo $id; ?>" class="form-control" placeholder="Deixe em branco para manter a senha atual" minlength="8">
                <?php } ?>
            </div>
            <div class="col-md-3">
                <label for="perfil<?php echo $id; ?>" class="form-label">PERFIL</label>
                <select name="perfil" id="perfil<?php echo $id; ?>" class="form-select" <?php if ($id == $id_logado) echo 'disabled'; ?>>
                    <option value="OPERADOR" <?php if ($usuario['perfil'] == 'OPERADOR') echo 'selected'; ?>>Operador</option>
                    <option value="ADMINISTRADOR" <?php if ($usuario['perfil'] == 'ADMINISTRADOR') echo 'selected'; ?>>Administrador</option>
                </select>
            </div>
            <?php if ($id != 0) { ?>
                <div class="col-md-3">
                    <label for="status<?php echo $id; ?>" class="form-label">STATUS</label>
                    <select name="status_usuario" id="status<?php echo $id; ?>" class="form-select" <?php if ($id == $id_logado) echo 'disabled'; ?>>
                        <option value="ATIVO" <?php if ($usuario['status_usuario'] == 'ATIVO') echo 'selected'; ?>>Ativo</option>
                        <option value="INATIVO" <?php if ($usuario['status_usuario'] == 'INATIVO') echo 'selected'; ?>>Inativo</option>
                    </select>
                </div>
            <?php } ?>
            <?php if ($id == $id_logado) { ?>
                <div class="col-12 form-text mt-1">Você não pode alterar o próprio perfil nem o próprio status.</div>
            <?php } ?>

            <div class="col-12 border-top pt-3">
                <span class="form-label">ENDEREÇO (OPCIONAL)</span>
            </div>
            <div class="col-md-3">
                <label for="cep<?php echo $id; ?>" class="form-label">CEP</label>
                <!-- quando sai do campo o cep.js busca o endereço e preenche os campos de baixo -->
                <input type="text" name="cep" id="cep<?php echo $id; ?>" class="form-control" inputmode="numeric" maxlength="9" placeholder="Ex: 89201-100" value="<?php echo htmlspecialchars($cep); ?>" onblur="buscarCep(this, <?php echo $id; ?>)">
                <div id="avisoCep<?php echo $id; ?>" class="form-text">Digite o CEP para preencher o endereço.</div>
            </div>
            <div class="col-md-4">
                <label for="logradouro<?php echo $id; ?>" class="form-label">LOGRADOURO</label>
                <input type="text" name="logradouro" id="logradouro<?php echo $id; ?>" class="form-control" maxlength="150" value="<?php echo htmlspecialchars($usuario['logradouro']); ?>">
            </div>
            <div class="col-md-2">
                <label for="bairro<?php echo $id; ?>" class="form-label">BAIRRO</label>
                <input type="text" name="bairro" id="bairro<?php echo $id; ?>" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($usuario['bairro']); ?>">
            </div>
            <div class="col-md-2">
                <label for="cidade<?php echo $id; ?>" class="form-label">CIDADE</label>
                <input type="text" name="cidade" id="cidade<?php echo $id; ?>" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($usuario['cidade']); ?>">
            </div>
            <div class="col-md-1">
                <label for="uf<?php echo $id; ?>" class="form-label">UF</label>
                <input type="text" name="uf" id="uf<?php echo $id; ?>" class="form-control" maxlength="2" value="<?php echo htmlspecialchars($usuario['uf']); ?>">
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
    <title>Usuários</title>
    <link rel="stylesheet" href="../../assets/img/style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <!-- menu de cima -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <main class="pagina">

        <div class="cabecalho">
            <div>
                <h3>Usuários</h3>
                <p>Gerencie quem tem acesso ao sistema</p>
            </div>
            <!-- abre e fecha o formulário de cadastro -->
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#editar0">+ NOVO USUÁRIO</button>
        </div>

        <!-- erros da validação, um por linha -->
        <?php if (count($erros) > 0) { ?>
            <div class="alert alert-danger"><?php echo implode('<br>', $erros); ?></div>
        <?php } ?>

        <!-- formulário de cadastro, começa fechado (abre sozinho se deu erro no cadastro) -->
        <div class="caixa collapse <?php if ($aberto === 0) echo 'show'; ?>" id="editar0">
            <div class="caixa-topo">CADASTRAR NOVO USUÁRIO</div>
            <?php
            if ($aberto === 0) {
                formUsuario($digitado, $id_logado);
            } else {
                formUsuario($novo, $id_logado);
            }
            ?>
        </div>

        <div class="caixa">
            <!-- faixa azul com o título, os botões de ação em massa e o filtro (listagem.php) -->
            <?php topo('USUÁRIOS CADASTRADOS', true); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabela">
                    <thead>
                        <tr>
                            <?php marcarTodos(); ?>
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
                            $id = $usuario['id_usuario'];

                            // motivo pra não poder inativar (vai pro modal)
                            $bloqueio = '';
                            if ($id == $id_logado) {
                                $bloqueio = 'Você não pode inativar a própria conta.';
                            } elseif ($usuario['total_trens'] > 0) {
                                $bloqueio = 'Ele é responsável por ' . $usuario['total_trens'] . ' trem(ns) ativo(s). Troque o responsável desses trens antes de inativá-lo.';
                            }
                            ?>
                            <tr>
                                <?php marcar($id, 'o usuário ' . $usuario['nome_usuario'], $bloqueio); ?>
                                <td><?php echo $id; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email_usuario']); ?></td>
                                <td><span class="badge status status-<?php echo $usuario['perfil']; ?>"><?php echo $usuario['perfil']; ?></span></td>
                                <td>
                                    <?php
                                    if ($usuario['cidade'] == '') {
                                        echo '-';
                                    } else {
                                        echo htmlspecialchars($usuario['cidade'] . '/' . $usuario['uf']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($usuario['nomes_trens'] == null) {
                                        echo 'Sem trem';
                                    } else {
                                        echo htmlspecialchars($usuario['nomes_trens']);
                                    }
                                    ?>
                                </td>
                                <td><span class="badge status status-<?php echo $usuario['status_usuario']; ?>"><?php echo $usuario['status_usuario']; ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                                <td class="text-center text-nowrap"><?php acoes($id, $usuario['status_usuario']); ?></td>
                            </tr>
                            <!-- linha escondida embaixo de cada item com o formulário de edição, abre no botão EDITAR -->
                            <tr class="collapse <?php if ($aberto == $id) echo 'show'; ?>" id="editar<?php echo $id; ?>">
                                <td colspan="10" class="p-0">
                                    <?php
                                    if ($aberto == $id) {
                                        formUsuario($digitado, $id_logado);
                                    } else {
                                        formUsuario($usuario, $id_logado);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <!-- lista vazia -->
                        <?php if (mysqli_num_rows($usuarios) == 0) { ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
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
    <!-- busca do CEP no ViaCEP -->
    <script src="../../script/cep.js"></script>

</body>

</html>
