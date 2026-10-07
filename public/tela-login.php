<?php

require_once __DIR__ . '/../infra/seguranca.php';

iniciar_sessao_segura();
enviar_cabecalhos_seguranca();

include __DIR__ . '/../infra/conexao.php';
require_once __DIR__ . '/../infra/usuarios.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ehAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

    $resultado = autenticar_usuario(
        $conexao,
        (string) ($_POST['nome_usuario'] ?? ''),
        (string) ($_POST['senha'] ?? '')
    );

    if ($resultado['sucesso']) {
        // Novo ID de sessão a cada login (evita fixação de sessão)
        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $resultado['usuario']['id_usuario'];
        $_SESSION['usuario'] = $resultado['usuario']['nome_usuario'];
        $_SESSION['perfil_usuario'] = $resultado['usuario']['perfil_usuario'];
        $_SESSION['ultimo_acesso'] = time();

        if ($ehAjax) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => $resultado['mensagem']]);
            exit();
        }

        header("Location: tela-geral-home.php");
        exit();
    }

    $erro = $resultado['mensagem'];

    if ($ehAjax) {
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => false, 'mensagem' => $erro]);
        exit();
    }
}

$sem_administrador = contar_administradores($conexao) === 0;

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroMonitor | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <style>
        /* ---------- IDENTIDADE VISUAL ---------- */
        :root {
            --azul: #1b3f53;
            --azul-escuro: #12303f;
            --amarelo: #daa301;
            --branco: #ffffff;
        }

        body {
            font-family: Arial, Tahoma, sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at center, #245166 0%, var(--azul) 55%, var(--azul-escuro) 100%);
        }

        body.intro {
            overflow: hidden;
        }

        /* Trilho decorativo no rodapé */
        .trilho {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 40px;
            z-index: 0;
            height: 14px;
            border-top: 3px solid rgba(218, 163, 1, 0.5);
            border-bottom: 3px solid rgba(218, 163, 1, 0.5);
            background: repeating-linear-gradient(90deg,
                    transparent 0 26px,
                    rgba(218, 163, 1, 0.25) 26px 34px);
        }

        /* ---------- CARD DE LOGIN ---------- */
        .card-login {
            position: relative;
            z-index: 1;
            width: 420px;
            max-width: calc(100vw - 32px);
            background-color: var(--branco);
            border: none;
            border-top: 5px solid var(--amarelo);
            border-radius: 10px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            transition: background-color 0.6s ease, border-color 0.6s ease, box-shadow 0.6s ease;
        }

        /* ---------- LOGO + NOME ---------- */
        .marca {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transform-origin: center center;
        }

        .marca-logo {
            width: 76px;
            height: 76px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(218, 163, 1, 0.45);
        }

        .marca-nome {
            margin: 12px 0 0;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--azul);
            transition: color 0.6s ease;
        }

        .marca-nome span {
            color: var(--amarelo);
        }

        .marca-sub {
            margin: 2px 0 0;
            font-size: 0.72rem;
            letter-spacing: 3px;
            color: #6c7a86;
            transition: color 0.6s ease;
        }

        .marca-linha {
            width: 60px;
            height: 3px;
            margin-top: 12px;
            border-radius: 2px;
            background-color: var(--amarelo);
        }

        /* ---------- FORMULÁRIO ---------- */
        .form-area {
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #5b6770;
        }

        .form-control:focus {
            border-color: var(--amarelo);
            box-shadow: 0 0 0 0.2rem rgba(218, 163, 1, 0.25);
        }

        .btn-entrar {
            background-color: var(--amarelo);
            color: var(--azul);
            font-weight: 700;
            letter-spacing: 1px;
            border: none;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .btn-entrar:hover {
            background-color: var(--amarelo);
            color: var(--azul);
            filter: brightness(1.08);
            transform: translateY(-2px);
        }

        /* ---------- ESTADO INICIAL DA ANIMAÇÃO ---------- */
        /* Enquanto a tela tem a classe "intro", o card fica invisível
           e só a logo aparece, grande, no meio da tela. */
        .intro .card-login {
            background-color: transparent;
            border-color: transparent;
            box-shadow: none;
        }

        .intro .form-area {
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        .intro .marca-nome {
            color: var(--branco);
        }

        .intro .marca-sub {
            color: rgba(255, 255, 255, 0.75);
        }

        /* Etapa 1: logo surge */
        .intro .marca-logo {
            animation: logo-entra 0.9s cubic-bezier(.2, .9, .3, 1.3) both;
        }

        /* Etapa 2: nome sobe */
        .intro .marca-nome,
        .intro .marca-sub {
            animation: texto-sobe 0.6s ease 0.6s both;
        }

        /* Etapa 3: linha amarela cresce como um trilho */
        .intro .marca-linha {
            animation: linha-cresce 0.6s ease 1.1s both;
        }

        @keyframes logo-entra {
            from {
                opacity: 0;
                transform: scale(0.4) rotate(-8deg);
            }

            to {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        @keyframes texto-sobe {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes linha-cresce {
            from {
                width: 0;
            }

            to {
                width: 60px;
            }
        }

        /* Quem prefere menos movimento (configuração do sistema) vê a tela direto */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body class="intro">

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

                <div id="mensagem" class="mt-3">
                    <?php if ($erro !== '') { ?>
                        <div class="alert alert-danger"><?php echo e($erro); ?></div>
                    <?php } elseif (isset($_GET['expirada'])) { ?>
                        <div class="alert alert-warning">Sua sessão expirou por inatividade. Entre novamente.</div>
                    <?php } ?>
                </div>

                <?php if ($sem_administrador) { ?>
                    <p class="text-center mt-2 mb-0" style="font-size: 0.85rem;">
                        Nenhum administrador cadastrado.
                        <a href="usuarios/tela-cadastro-admin.php">Cadastrar o primeiro administrador</a>
                    </p>
                <?php } ?>

            </div>

        </div>
    </main>

    <div class="trilho"></div>

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
