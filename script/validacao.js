document.getElementById("form-login").onsubmit = (e) => {
    e.preventDefault();
 
    let nome_usuario = document.getElementById("nome_usuario").value.trim();
    let senha = document.getElementById("senha").value; // senha não leva trim: espaços fazem parte dela
    let mensagem = document.getElementById("mensagem");
    mensagem.innerHTML = "";
 
    if (nome_usuario === "" || senha.trim() === "") {
        mensagem.innerHTML = "<div class='alert alert-danger'>Preencha usuário e senha.</div>";
        return;
    }
 
    fetch("tela-login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "nome_usuario=" + encodeURIComponent(nome_usuario) + "&senha=" + encodeURIComponent(senha) + "&ajax=1"
    })
    .then(response => response.json())
    .then(data => {
        console.log("resposta do servidor:", data);
 
        if (data.sucesso) {
            mensagem.innerHTML = "<div class='alert alert-success'></div>";
            mensagem.firstChild.textContent = data.mensagem;
            window.location.href = "../public/tela-geral-home.php";
        } else {
            mensagem.innerHTML = "<div class='alert alert-danger'></div>";
            mensagem.firstChild.textContent = data.mensagem;
        }
 
        document.getElementById("form-login").reset();
    })
    .catch(error => {
        mensagem.innerHTML = "<div class='alert alert-danger'>Erro ao conectar com o servidor.</div>";
        console.error("Erro na requisição:", error);
    });
};