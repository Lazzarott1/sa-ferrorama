// avisos do login
let formLogin = document.getElementById('form-login');
let mensagem = document.getElementById('mensagem');
let tempoMensagem;

// apaga o aviso do canto depois de 5 segundos
function esconderAviso() {
    clearTimeout(tempoMensagem);
    tempoMensagem = setTimeout(function () {
        mensagem.innerHTML = '';
    }, 5000);
}

// se o PHP já mandou um erro (senha errada, usuário inativo), some sozinho também
if (mensagem.innerHTML.trim() != '') {
    esconderAviso();
}

// antes de enviar confere se não tem campo vazio (só com espaços, por exemplo)
formLogin.addEventListener('submit', function (evento) {
    let usuario = document.getElementById('nome_usuario').value.trim();
    let senha = document.getElementById('senha').value.trim();

    if (usuario == '' || senha == '') {
        // não envia o formulário
        evento.preventDefault();
        mensagem.innerHTML = '<div class="alert alert-danger shadow mb-0">'
            + 'Preencha usuário e senha.</div>';
        esconderAviso();
    }
});
