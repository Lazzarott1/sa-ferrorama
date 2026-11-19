<?php

session_start();

// tela de login (não usa o verifica-login porque aqui a pessoa ainda não entrou)
include '../infra/conexao.php';


// LOGIN (o script/validacao.js envia o formulário e recebe a resposta em JSON)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['nome_usuario']);
    $senha = $_POST['senha'];

    // procura o usuário pelo nome
    $stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario, senha, perfil, status_usuario FROM usuarios WHERE nome_usuario = ?");
    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Senha cadastrada direto no banco, sem hash: troca pelo hash no primeiro login
    if ($dados && $senha === $dados['senha']) {
        $dados['senha'] = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "si", $dados['senha'], $dados['id_usuario']);
        mysqli_stmt_execute($stmt);
    }

    // monta a resposta: senha errada, usuário inativo ou tudo certo (aí guarda os dados na sessão)
    if (!$dados || !password_verify($senha, $dados['senha'])) {
        $resposta = ['sucesso' => false, 'mensagem' => 'Usuário ou senha incorretos.'];
    } elseif ($dados['status_usuario'] != 'ATIVO') {
        $resposta = ['sucesso' => false, 'mensagem' => 'Usuário inativo. Fale com o administrador.'];
    } else {
        $_SESSION['id_usuario'] = $dados['id_usuario'];
        $_SESSION['usuario'] = $dados['nome_usuario'];
        $_SESSION['perfil'] = $dados['perfil'];
        $resposta = ['sucesso' => true, 'mensagem' => ''];
    }

    // devolve em JSON pro validacao.js, que mostra o erro ou vai pra home
    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit();
}


// QUEM JÁ ESTÁ LOGADO VAI DIRETO PARA A HOME

if (isset($_SESSION['id_usuario'])) {
    header("Location: tela-geral-home.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroMonitor | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- todo o visual do login (cores, card e animação) fica no login.css -->
    <link rel="stylesheet" href="../assets/img/style/login.css">
</head>

<body class="intro">

    <!-- aviso de erro no canto da tela, o validacao.js escreve aqui -->
    <div id="mensagem" class="position-fixed top-0 end-0 m-3 z-3"></div>

    <!-- volta pra landing page -->
    <a href="../index.html" class="voltar-site">&larr; Conhecer o FerroMonitor</a>

    <main class="d-flex justify-content-center align-items-center min-vh-100 py-4">

        <div class="card card-login p-4">

            <!-- logo + nome: é esse bloco que se mexe na animação de entrada -->
            <div class="marca mb-4" id="marca">
                <img src="../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo FerroMonitor" class="marca-logo">
                <h1 class="marca-nome">Ferro<span>Monitor</span></h1>
                <p class="marca-sub">SISTEMA FERROVIÁRIO</p>
                <div class="marca-linha"></div>
            </div>

            <!-- formulário, fica escondido até a animação terminar -->
            <div class="form-area">

                <form id="form-login">

                    <div class="mb-3 conjunto">
                        <label for="nome_usuario" class="form-label">NOME DE USUÁRIO</label>
                        <input type="text" id="nome_usuario" name="nome_usuario" class="form-control" placeholder="Digite seu Usuário" required>
                    </div>
                    <div class="mb-4 conjunto">
                        <label for="senha" class="form-label">SENHA</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite sua Senha" required>
                    </div>

                    <button class="btn btn-entrar w-100 py-2 fs-5" type="submit">ENTRAR</button>

                </form>

                <p class="aviso-suporte">Precisa de ajuda? Fale com o administrador do sistema.</p>

            </div>

        </div>
    </main>

    <!-- animação da logo e envio do formulário -->
    <script src="../script/animacao-login.js"></script>
    <script src="../script/validacao.js"></script>
</body>

</html>
