<?php

session_start();

if (isset($_SESSION['id_usuario']) && !isset($_POST['ajax'])) {
    header("Location: tela-geral-home.php");
    exit();
}

include '../infra/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['nome_usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = mysqli_prepare($conexao, "SELECT id_usuario, nome_usuario, senha, perfil, status_usuario FROM usuarios WHERE nome_usuario = ?");
    mysqli_stmt_bind_param($stmt, 's', $usuario);
    mysqli_stmt_execute($stmt);
    $dados = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $sem_hash = $dados && !password_get_info($dados['senha'])['algo'];

    if ($sem_hash && $senha === $dados['senha']) {
        // senha gravada direto no banco sem hash: converte no primeiro login
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conexao, "UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $dados['id_usuario']);
        mysqli_stmt_execute($stmt);
        $dados['senha'] = $hash;
    }

    $erro = '';
    if (!$dados || !password_verify($senha, $dados['senha'])) {
        $erro = "Usuário ou senha incorretos.";
    } elseif ($dados['status_usuario'] !== 'ATIVO') {
        $erro = "Usuário inativo. Fale com o administrador.";
    } else {
        $_SESSION['id_usuario'] = $dados['id_usuario'];
        $_SESSION['usuario'] = $dados['nome_usuario'];
        $_SESSION['perfil'] = $dados['perfil'];
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => $erro === '', 'mensagem' => $erro]);
        exit();
    }

    if ($erro === '') {
        header("Location: tela-geral-home.php");
        exit();
    }
}

?>

<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroMonitor | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/img/style/style.css">
    <link rel="stylesheet" href="../assets/img/style/login.css">
</head>

<body class="intro">

    <div id="mensagem" class="position-fixed top-0 end-0 m-3 z-3"></div>

    <a href="../index.html" class="voltar-site">&larr; Conhecer o FerroMonitor</a>
    <div class="trilho"></div>

    <main class="d-flex justify-content-center align-items-center min-vh-100 py-4">

        <div class="card card-login p-4">

            <!-- LOGO + NOME (é esse bloco que faz a animação) -->
            <div class="marca mb-4" id="marca">
                <img src="../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo FerroMonitor" class="marca-logo">
                <h1 class="marca-nome">Ferro<span>Monitor</span></h1>
                <p class="marca-sub">SISTEMA FERROVIÁRIO</p>
                <div class="marca-linha"></div>
            </div>

            <!-- FORMULÁRIO -->
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

    <script>
        // ANIMAÇÃO DE ENTRADA
        // 1) A logo começa grande, no centro da tela.
        // 2) Depois de ~2s ela diminui e vai para o topo do card.
        // 3) O card e o formulário aparecem.
        (function () {
            const body = document.body;
            const marca = document.getElementById('marca');

            const semAnimacao = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (semAnimacao) {
                body.classList.remove('intro');
                return;
            }

            const ESCALA_INICIAL = 1.9;   // tamanho da logo em destaque
            const TEMPO_DESTAQUE = 2000;  // tempo (ms) que a logo fica no centro

            // Calcula quanto a marca precisa andar para ficar no centro da tela
            const pos = marca.getBoundingClientRect();
            const dx = window.innerWidth / 2 - (pos.left + pos.width / 2);
            const dy = window.innerHeight / 2 - (pos.top + pos.height / 2);

            marca.style.transform = `translate(${dx}px, ${dy}px) scale(${ESCALA_INICIAL})`;

            let terminou = false;

            function mostrarLogin() {
                if (terminou) return;
                terminou = true;

                // Leva a marca de volta para o lugar dela, diminuindo
                marca.style.transition = 'transform 0.9s cubic-bezier(.65, 0, .35, 1)';
                marca.style.transform = '';

                // Um pouco depois, o card e o formulário aparecem
                setTimeout(() => {
                    body.classList.remove('intro');
                    document.getElementById('nome_usuario').focus();
                }, 450);
            }

            setTimeout(mostrarLogin, TEMPO_DESTAQUE);

            // Clicar ou apertar uma tecla pula a animação
            document.addEventListener('click', mostrarLogin, { once: true });
            document.addEventListener('keydown', mostrarLogin, { once: true });
        })();
    </script>

    <script src="../script/validacao.js"></script>
</body>

</html>
