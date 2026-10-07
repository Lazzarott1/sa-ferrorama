// Validação do formulário de usuários no navegador.
// Serve só para avisar o usuário mais rápido: o servidor (infra/validacao-usuario.php)
// repete todas as regras e é ele quem decide se os dados são gravados.

function validarUsuarioNoNavegador(dados, senhaObrigatoria) {
    const erros = {};

    if (dados.nome_usuario === '') {
        erros.nome_usuario = 'Informe o nome de usuário.';
    } else if (dados.nome_usuario.length < 3 || dados.nome_usuario.length > 50) {
        erros.nome_usuario = 'O nome de usuário deve ter entre 3 e 50 caracteres.';
    } else if (!/^[A-Za-z0-9._-]+$/.test(dados.nome_usuario)) {
        erros.nome_usuario = 'O nome de usuário aceita apenas letras sem acento, números, ponto, hífen e sublinhado.';
    }

    if (dados.email_usuario === '') {
        erros.email_usuario = 'Informe o e-mail.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(dados.email_usuario)) {
        erros.email_usuario = 'Informe um e-mail válido.';
    }

    if (senhaObrigatoria || dados.senha !== '') {
        if (dados.senha.length < 8) {
            erros.senha = 'A senha deve ter no mínimo 8 caracteres.';
        } else if (!/[a-z]/.test(dados.senha) || !/[A-Z]/.test(dados.senha) || !/[0-9]/.test(dados.senha)) {
            erros.senha = 'A senha deve conter letra maiúscula, letra minúscula e número.';
        }
    }

    return erros;
}

function mostrarErros(form, erros) {
    form.querySelectorAll('.is-invalid').forEach((campo) => campo.classList.remove('is-invalid'));

    Object.entries(erros).forEach(([campo, texto]) => {
        const input = form.querySelector(`[name="${campo}"]`);
        const feedback = form.querySelector(`[data-erro="${campo}"]`);

        if (input && feedback) {
            input.classList.add('is-invalid');
            feedback.textContent = texto; // textContent: o texto nunca é interpretado como HTML
        }
    });
}

function mostrarMensagem(elemento, tipo, texto) {
    elemento.innerHTML = '';
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.textContent = texto;
    elemento.appendChild(alerta);
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cadastro');
    const mensagem = document.getElementById('mensagem');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // impede o envio tradicional e o recarregamento da página

        const dados = new FormData(form); // inclui o token CSRF e o perfil

        const erros = validarUsuarioNoNavegador({
            nome_usuario: dados.get('nome_usuario').trim(),
            email_usuario: dados.get('email_usuario').trim(),
            senha: dados.get('senha'),
        }, true);

        mostrarErros(form, erros);

        if (Object.keys(erros).length > 0) {
            mostrarMensagem(mensagem, 'danger', 'Corrija os campos destacados.');
            return;
        }

        try {
            const resposta = await fetch('tela-cadastro-user.php', {
                method: 'POST',
                body: dados
            });

            const retorno = await resposta.json();

            mostrarErros(form, retorno.erros || {});

            if (retorno.sucesso) {
                mostrarMensagem(mensagem, 'success', retorno.mensagem);
                form.reset();
                setTimeout(() => window.location.reload(), 1200); // atualiza a lista
            } else {
                mostrarMensagem(mensagem, 'danger', retorno.mensagem);
            }

        } catch (erro) {
            mostrarMensagem(mensagem, 'danger', 'Erro de conexão com o servidor.');
        }
    });
});
