// animação de entrada do login: a logo começa grande no meio da tela e depois vai pro lugar dela no
// card
let marca = document.getElementById('marca');
let terminou = false;

// tira a logo do centro e mostra o formulário (só roda uma vez)
function mostrarLogin() {
    if (terminou) {
        return;
    }
    terminou = true;

    // marca-movendo liga a transição; tirando o marca-centro a logo volta pro lugar
    marca.classList.add('marca-movendo');
    marca.classList.remove('marca-centro');

    // um pouquinho depois aparece o card e o cursor já vai pro campo de usuário
    setTimeout(function () {
        document.body.classList.remove('intro');
        document.getElementById('nome_usuario').focus();
    }, 450);
}

// calcula quanto a logo precisa andar pra ficar no meio da tela
let posicao = marca.getBoundingClientRect();
let distanciaX = window.innerWidth / 2 - (posicao.left + posicao.width / 2);
let distanciaY = window.innerHeight / 2 - (posicao.top + posicao.height / 2);

// passa essa distância pro CSS (login.css, classe marca-centro), porque ela muda com o tamanho da
// tela
marca.style.setProperty('--distancia-x', distanciaX + 'px');
marca.style.setProperty('--distancia-y', distanciaY + 'px');
marca.classList.add('marca-centro');

// espera 2 segundos, ou clicar/apertar qualquer tecla pula a animação
setTimeout(mostrarLogin, 2000);
document.addEventListener('click', mostrarLogin);
document.addEventListener('keydown', mostrarLogin);
