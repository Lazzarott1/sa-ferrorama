<?php

// Cadastro do primeiro administrador do sistema.
// Fica disponível sem login apenas enquanto não existir nenhum administrador.
// Depois disso, novos administradores são criados pela tela de usuários,
// por um administrador logado (campo PERFIL = Administrador).

require_once __DIR__ . '/../../infra/seguranca.php';

iniciar_sessao_segura();
enviar_cabecalhos_seguranca();

include __DIR__ . '/../../infra/conexao.php';
require_once __DIR__ . '/../../infra/usuarios.php';

if (contar_administradores($conexao) > 0) {
    header("Location: " . (usuario_eh_admin() ? "tela-cadastro-user.php" : "../tela-login.php"));
    exit;
}

$erros = [];
$dados = ['nome_usuario' => '', 'email_usuario' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valido()) {
        $erros['geral'] = 'Requisição inválida. Atualize a página e tente novamente.';
    } else {
        $resultado = cadastrar_primeiro_administrador($conexao, $_POST);

        if ($resultado['sucesso']) {
            header("Location: ../tela-login.php?admin_criado=1");
            exit;
        }

        $erros = $resultado['erros'];
    }

    $dados['nome_usuario'] = trim((string) ($_POST['nome_usuario'] ?? ''));
    $dados['email_usuario'] = trim((string) ($_POST['email_usuario'] ?? ''));
}

function classe_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function mensagem_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? '<div class="invalid-feedback">' . e($erros[$campo]) . '</div>' : '';
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerroMonitor | Cadastro de Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, Tahoma, sans-serif;
            min-height: 100vh;
            background: radial-gradient(circle at center, #245166 0%, #1b3f53 55%, #12303f 100%);
        }

        .card-admin {
            width: 460px;
            max-width: calc(100vw - 32px);
            border: none;
            border-top: 5px solid #daa301;
            border-radius: 10px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #5b6770;
        }

        .btn-cadastrar {
            background-color: #daa301;
            color: #1b3f53;
            font-weight: 700;
            letter-spacing: 1px;
            border: none;
        }

        .btn-cadastrar:hover {
            background-color: #daa301;
            color: #1b3f53;
            filter: brightness(1.08);
        }
    </style>
</head>

<body>

    <main class="d-flex justify-content-center align-items-center min-vh-100 py-4">

        <div class="card card-admin p-4">

            <div class="text-center mb-4">
                <img src="../../assets/img/Gemini_Generated_Image_z2d26bz2d26bz2d2.png" alt="Logo FerroMonitor"
                    style="width: 64px; height: 64px; border-radius: 12px;">
                <h1 class="h4 mt-2 mb-0" style="color: #1b3f53; font-weight: 700;">Cadastro de Administrador</h1>
                <p class="text-secondary mb-0" style="font-size: 0.85rem;">
                    Primeiro acesso: crie o administrador responsável pelo sistema.
                </p>
            </div>

            <?php if (isset($erros['geral'])) { ?>
                <div class="alert alert-danger"><?php echo e($erros['geral']); ?></div>
            <?php } elseif ($erros) { ?>
                <div class="alert alert-danger">Corrija os campos destacados.</div>
            <?php } ?>

            <form method="POST" novalidate>

                <?php echo campo_csrf(); ?>

                <div class="mb-3">
                    <label for="email_usuario" class="form-label">EMAIL</label>
                    <input type="email" id="email_usuario" name="email_usuario"
                        class="form-control<?php echo classe_erro($erros, 'email_usuario'); ?>"
                        value="<?php echo e($dados['email_usuario']); ?>" maxlength="200" required>
                    <?php echo mensagem_erro($erros, 'email_usuario'); ?>
                </div>

                <div class="mb-3">
                    <label for="nome_usuario" class="form-label">NOME DE USUÁRIO</label>
                    <input type="text" id="nome_usuario" name="nome_usuario"
                        class="form-control<?php echo classe_erro($erros, 'nome_usuario'); ?>"
                        value="<?php echo e($dados['nome_usuario']); ?>" minlength="3" maxlength="50" required>
                    <?php echo mensagem_erro($erros, 'nome_usuario'); ?>
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">SENHA</label>
                    <input type="password" id="senha" name="senha"
                        class="form-control<?php echo classe_erro($erros, 'senha'); ?>"
                        minlength="8" maxlength="72" autocomplete="new-password" required>
                    <div class="form-text" style="font-size: 0.75rem;">
                        Mínimo de 8 caracteres, com letra maiúscula, letra minúscula e número.
                    </div>
                    <?php echo mensagem_erro($erros, 'senha'); ?>
                </div>

                <div class="mb-4">
                    <label for="confirmar_senha" class="form-label">CONFIRMAR SENHA</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha"
                        class="form-control<?php echo classe_erro($erros, 'confirmar_senha'); ?>"
                        maxlength="72" autocomplete="new-password" required>
                    <?php echo mensagem_erro($erros, 'confirmar_senha'); ?>
                </div>

                <button type="submit" class="btn btn-cadastrar w-100 py-2">CADASTRAR ADMINISTRADOR</button>

            </form>

        </div>
    </main>

</body>

</html>
