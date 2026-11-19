// envio do formulário de login sem recarregar a página
let formLogin = document.getElementById('form-login');
let mensagem = document.getElementById('mensagem');
let tempoMensagem;

// mostra o aviso vermelho no canto e some sozinho depois de 5 segundos
function mostrarErro(texto) {
    mensagem.innerHTML = '<div class="alert alert-danger shadow mb-0">' + texto + '</div>';

    clearTimeout(tempoMensagem);
    tempoMensagem = setTimeout(function () {
        mensagem.innerHTML = '';
    }, 5000);
}

formLogin.addEventListener('submit', function (evento) {
    // não deixa o form recarregar a página, a gente manda pelo fetch
    evento.preventDefault();

    let usuario = document.getElementById('nome_usuario').value.trim();
    let senha = document.getElementById('senha').value.trim();

    // nem manda pro servidor se tiver campo vazio
    if (usuario == '' || senha == '') {
        mostrarErro('Preencha usuário e senha.');
        return;
    }

    // o PHP responde um JSON dizendo se deu certo
    fetch('tela-login.php', {
        method: 'POST',
        body: new FormData(formLogin)
    })
        .then(function (resposta) {
            return resposta.json();
        })
        .then(function (dados) {
            if (dados.sucesso) {
                window.location.href = 'tela-geral-home.php';
            } else {
                mostrarErro(dados.mensagem);
                formLogin.reset();
            }
        })
        // o servidor nem respondeu
        .catch(function () {
            mostrarErro('Erro ao conectar com o servidor.');
        });
});
