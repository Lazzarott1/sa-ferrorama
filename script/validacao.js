const form = document.getElementById("form-login");
const mensagem = document.getElementById("mensagem");

function mostrarErro(texto) {
    mensagem.innerHTML = "<div class='alert alert-danger shadow mb-0'>" + texto + "</div>";
    clearTimeout(mensagem.tempo);
    mensagem.tempo = setTimeout(() => mensagem.innerHTML = "", 5000);
}

form.onsubmit = (e) => {
    e.preventDefault();

    let nome_usuario = document.getElementById("nome_usuario").value.trim();
    let senha = document.getElementById("senha").value.trim();

    if (nome_usuario === "" || senha === "") {
        return mostrarErro("Preencha usuário e senha.");
    }

    fetch("tela-login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "nome_usuario=" + encodeURIComponent(nome_usuario) + "&senha=" + encodeURIComponent(senha) + "&ajax=1"
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            window.location.href = "tela-geral-home.php";
        } else {
            mostrarErro(data.mensagem);
            form.reset();
        }
    })
    .catch(() => mostrarErro("Erro ao conectar com o servidor."));
};
