// funções das listas de trilhos, trens, sensores e usuários (marcar, ativar e inativar)

// quando troca o filtro Ativos/Inativos/Todos já envia o form
function enviarFiltro(campo) {
    campo.form.submit();
}

// todos os checkboxes da lista
function pegarCaixas() {
    return document.getElementsByClassName('marcar');
}

// só os checkboxes que estão marcados
function pegarMarcadas() {
    let caixas = pegarCaixas();
    let marcadas = [];

    for (let i = 0; i < caixas.length; i++) {
        if (caixas[i].checked) {
            marcadas.push(caixas[i]);
        }
    }

    return marcadas;
}

// os botões de ativar/inativar selecionados só aparecem com 2 ou mais marcados
function mostrarMassa() {
    let botoes = document.getElementById('emMassa');

    if (pegarMarcadas().length >= 2) {
        botoes.classList.remove('d-none');
    } else {
        botoes.classList.add('d-none');
    }
}

// checkbox do cabeçalho: marca ou desmarca todos
function marcarTodos(caixaTopo) {
    let caixas = pegarCaixas();

    for (let i = 0; i < caixas.length; i++) {
        caixas[i].checked = caixaTopo.checked;
    }

    mostrarMassa();
}

// deixa marcado só o item do botão que foi clicado
function marcarSo(id) {
    let caixas = pegarCaixas();

    for (let i = 0; i < caixas.length; i++) {
        caixas[i].checked = (caixas[i].value == id);
    }

    mostrarMassa();
}

// prepara o texto do modal de inativar (o id 0 quer dizer que veio do botão de inativar selecionados)
function inativar(id) {
    if (id != 0) {
        marcarSo(id);
    }

    let marcadas = pegarMarcadas();
    // conta quantos marcados não podem ser inativados (o motivo fica no data-bloqueio do checkbox)
    let bloqueadas = 0;

    for (let i = 0; i < marcadas.length; i++) {
        if (marcadas[i].dataset.bloqueio != '') {
            bloqueadas++;
        }
    }

    let titulo = 'CONFIRMAR INATIVAÇÃO';
    let pergunta = '';
    let texto = '';
    let permitido = true;

    // cada caso tem um título e uma mensagem diferente
    if (marcadas.length == 0) {
        titulo = 'INATIVAÇÃO NÃO PERMITIDA';
        pergunta = 'Nenhum item selecionado.';
        texto = 'Marque os itens na lista antes de inativar.';
        permitido = false;
    } else if (marcadas.length == 1 && bloqueadas == 1) {
        titulo = 'INATIVAÇÃO NÃO PERMITIDA';
        pergunta = 'Não é possível inativar ' + marcadas[0].dataset.nome + '.';
        texto = marcadas[0].dataset.bloqueio;
        permitido = false;
    } else if (marcadas.length == 1) {
        pergunta = 'Tem certeza que deseja inativar ' + marcadas[0].dataset.nome + '?';
    } else {
        pergunta = 'Tem certeza que deseja inativar os ' + marcadas.length + ' itens selecionados?';

        if (bloqueadas > 0) {
            texto = bloqueadas + ' deles não serão inativados por terem associações.';
        }
    }

    // escreve no modal e esconde o botão vermelho quando não pode inativar
    document.getElementById('tituloInativar').textContent = titulo;
    document.getElementById('perguntaInativar').textContent = pergunta;
    document.getElementById('textoInativar').textContent = texto;
    document.getElementById('botaoInativar').hidden = !permitido;
}
