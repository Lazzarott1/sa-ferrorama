// toda vez que a landing page abre (até pelo botão voltar) a sessão é encerrada
window.addEventListener('pageshow', function () {
    fetch('infra/validacoes-usuarios/logout.php');
});
