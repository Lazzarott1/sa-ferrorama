document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cadastro');
    const mensagem = document.getElementById('mensagem');
 
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // impede o envio tradicional (GET) e o recarregamento da página
 
        // Monta os dados a partir dos campos do formulário
        const dados = new FormData();
        dados.append('email_usuario', document.getElementById('email_usuario').value);
        dados.append('nome_usuario', document.getElementById('nome_usuario').value);
        dados.append('senha', document.getElementById('senha').value);
 
        try {
            const resposta = await fetch('tela-cadastro-user.php', {
                method: 'POST',
                body: dados
            });
 
            const texto = await resposta.text();
 
            if (resposta.ok) {
                mensagem.innerHTML = `<div class="alert alert-success">${texto}</div>`;
                form.reset();
            } else {
                mensagem.innerHTML = `<div class="alert alert-danger">Erro: ${texto}</div>`;
            }
 
        } catch (erro) {
            mensagem.innerHTML = `<div class="alert alert-danger">Erro de conexão: ${erro.message}</div>`;
        }
    });
});