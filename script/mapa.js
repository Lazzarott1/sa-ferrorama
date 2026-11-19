// animação do trem andando no mapa da home

// Nome de cada trecho: [até que parte do segmento vai (0 a 1), nome]
const trechos = {
    'principal': [
        [0.189, 'Reta Norte'],
        [0.299, 'Curva Nordeste'],
        [0.39, 'Curva Leste Interna'],
        [0.474, 'Serpentina Central'],
        [0.621, 'Reta Central'],
        [0.792, 'Curva Oeste Inferior'],
        [1.0, 'Reta Sul']
    ],
    'oeste': [
        [0.164, 'Reta Norte (trecho oeste)'],
        [0.87, 'Curva Oeste Superior'],
        [1.0, 'Acesso à Agulha Central']
    ],
    'diagonal-norte': [
        [0.165, 'Diagonal Norte'],
        [0.393, 'Serpentina Norte'],
        [0.531, 'Reta de Ligação Leste'],
        [0.849, 'Curva Leste'],
        [1.0, 'Reta Sul (trecho leste)']
    ],
    'diagonal-sul': [[0.408, 'Diagonal Sul'], [1.0, 'Serpentina Sul']],
    'ramal': [[0.609, 'Linha do Ramal'], [1.0, 'Ramal sem Saída']]
};

// Cada agulha liga uma ponta a dois caminhos
const agulhas = {
    'agulha-norte': {
        ponta: 'oeste:inicio',
        caminhos: ['principal:inicio', 'diagonal-norte:inicio']
    },
    'agulha-central': {
        ponta: 'oeste:fim',
        caminhos: ['ramal:inicio', 'diagonal-sul:inicio']
    },
    'agulha-sul': {
        ponta: 'diagonal-norte:fim',
        caminhos: ['principal:fim', 'diagonal-sul:fim']
    }
};

// O que existe no começo e no fim de cada segmento
const extremos = {
    'principal': ['agulha-norte', 'agulha-sul'],
    'oeste': ['agulha-norte', 'agulha-central'],
    'diagonal-norte': ['agulha-norte', 'agulha-sul'],
    'diagonal-sul': ['agulha-central', 'agulha-sul'],
    'ramal': ['agulha-central', 'para-choque']
};

// (as cores ficam no home.css, nas classes vagao e locomotiva)
// Desenha a locomotiva e os 3 vagões
const grupoTrem = document.getElementById('trem');
const vagoes = [];

for (let i = 0; i < 4; i++) {
    let vagao = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    vagao.innerHTML = '<rect x="-17" y="-11" width="34" height="22" rx="8"/>';

    if (i == 0) {
        vagao.setAttribute('class', 'vagao locomotiva');
        vagao.innerHTML += '<circle cx="10" cy="0" r="4"/>';
    } else {
        vagao.setAttribute('class', 'vagao');
    }

    grupoTrem.appendChild(vagao);
    vagoes.push(vagao);
}

// onde o trem está: em qual segmento, quantos pixels já andou nele e pra que lado vai (1 ou -1)
// o rastro guarda os pontos por onde a locomotiva passou, pros vagões irem atrás
let trem = { segmento: 'oeste', distancia: 200, sentido: 1 };
let rastro = [];
let tempoAnterior = null;

// pega o <path> do segmento lá no SVG
function pegarCaminho(segmento) {
    return document.getElementById('seg-' + segmento);
}

// coloca o trem no começo ou no fim de um segmento novo (ex: 'ramal:inicio')
function entrarNoSegmento(destino) {
    let partes = destino.split(':');
    let segmento = partes[0];
    let lado = partes[1];

    trem.segmento = segmento;

    if (lado == 'inicio') {
        trem.distancia = 0;
        trem.sentido = 1;
    } else {
        trem.distancia = pegarCaminho(segmento).getTotalLength();
        trem.sentido = -1;
    }
}

// chegou no fim do segmento: decide pra onde ir
function trocarDeSegmento(comprimento) {
    let lado = 'inicio';
    let extremo = extremos[trem.segmento][0];

    if (trem.distancia > comprimento) {
        lado = 'fim';
        extremo = extremos[trem.segmento][1];
    }

    // no ramal sem saída ele bate no para-choque e volta de ré
    if (extremo == 'para-choque') {
        trem.sentido = -1;
        trem.distancia = comprimento;
        rastro = [];
        return;
    }

    let agulha = agulhas[extremo];

    // chegou pela ponta da agulha: sorteia um dos dois caminhos
    // chegou por um dos caminhos: só tem como seguir pra ponta
    if (agulha.ponta == trem.segmento + ':' + lado) {
        let sorteio = Math.floor(Math.random() * 2);
        entrarNoSegmento(agulha.caminhos[sorteio]);
    } else {
        entrarNoSegmento(agulha.ponta);
    }
}

// coloca cada vagão 40 pixels atrás do anterior, seguindo o rastro e virado pro lado certo
function posicionarVagoes(caminho) {
    let atual = caminho.getPointAtLength(trem.distancia);
    let frente = caminho.getPointAtLength(trem.distancia + trem.sentido * 3);
    rastro.unshift({ x: atual.x, y: atual.y });

    let i = 0;
    let andado = 0;

    for (let n = 0; n < vagoes.length; n++) {
        while (i < rastro.length - 1 && andado < n * 40) {
            let dx = rastro[i].x - rastro[i + 1].x;
            let dy = rastro[i].y - rastro[i + 1].y;
            andado += Math.sqrt(dx * dx + dy * dy);
            i++;
        }

        let ponto = rastro[i];
        let alvo = frente;
        if (i > 0) {
            alvo = rastro[Math.max(i - 3, 0)];
        }

        let angulo = Math.atan2(alvo.y - ponto.y, alvo.x - ponto.x) * 180 / Math.PI;
        vagoes[n].setAttribute('transform', 'translate('
            + ponto.x
            + ' '
            + ponto.y
            + ') rotate('
            + angulo
            + ')');
    }

    // joga fora o pedaço do rastro que nenhum vagão usa mais
    rastro.length = Math.min(rastro.length, i + 2);
}

// escreve o nome do trecho na etiqueta "Trem em:" em cima do mapa
function mostrarTrecho(caminho) {
    let fracao = trem.distancia / caminho.getTotalLength();
    let lista = trechos[trem.segmento];

    for (let i = 0; i < lista.length; i++) {
        if (fracao <= lista[i][0]) {
            document.getElementById('local-trem').textContent = 'Trem em: ' + lista[i][1];
            return;
        }
    }
}

// roda em todo quadro da animação (uns 60 por segundo) e move o trem um pouquinho
function anda(tempo) {
    // o quanto anda depende do tempo que passou, assim a velocidade é igual em qualquer computador
    let passo = 0;
    if (tempoAnterior != null) {
        passo = Math.min(tempo - tempoAnterior, 50) * 0.12;
    }
    tempoAnterior = tempo;

    let comprimento = pegarCaminho(trem.segmento).getTotalLength();
    trem.distancia += passo * trem.sentido;

    if (trem.distancia < 0 || trem.distancia > comprimento) {
        trocarDeSegmento(comprimento);
    }

    let caminho = pegarCaminho(trem.segmento);
    posicionarVagoes(caminho);
    mostrarTrecho(caminho);

    requestAnimationFrame(anda);
}

// começa a animação
requestAnimationFrame(anda);
