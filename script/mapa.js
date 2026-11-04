const trechos = {
    'principal': [[0.189, 'Reta Norte'], [0.299, 'Curva Nordeste'], [0.39, 'Curva Leste Interna'], [0.474, 'Serpentina Central'], [0.621, 'Reta Central'], [0.792, 'Curva Oeste Inferior'], [1.0, 'Reta Sul']],
    'oeste': [[0.164, 'Reta Norte (trecho oeste)'], [0.87, 'Curva Oeste Superior'], [1.0, 'Acesso à Agulha Central']],
    'diagonal-norte': [[0.165, 'Diagonal Norte'], [0.393, 'Serpentina Norte'], [0.531, 'Reta de Ligação Leste'], [0.849, 'Curva Leste'], [1.0, 'Reta Sul (trecho leste)']],
    'diagonal-sul': [[0.408, 'Diagonal Sul'], [1.0, 'Serpentina Sul']],
    'ramal': [[0.609, 'Linha do Ramal'], [1.0, 'Ramal sem Saída']]
};

const ligacoes = {
    'agulha-norte': { ponta: 'oeste:inicio', caminhos: ['principal:inicio', 'diagonal-norte:inicio'] },
    'agulha-central': { ponta: 'oeste:fim', caminhos: ['ramal:inicio', 'diagonal-sul:inicio'] },
    'agulha-sul': { ponta: 'diagonal-norte:fim', caminhos: ['principal:fim', 'diagonal-sul:fim'] }
};

const pontas = { principal: ['agulha-norte', 'agulha-sul'], oeste: ['agulha-norte', 'agulha-central'], 'diagonal-norte': ['agulha-norte', 'agulha-sul'], 'diagonal-sul': ['agulha-central', 'agulha-sul'], ramal: ['agulha-central', 'para-choque'] };

const grupoTrem = document.getElementById('trem');
const vagoes = ['#daa301', '#f0c94a', '#f0c94a', '#f0c94a'].map((cor, i) => {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.innerHTML = '<rect x="-17" y="-11" width="34" height="22" rx="8" fill="' + cor + '" stroke="#ffffff" stroke-width="3"/>' + (i == 0 ? '<circle cx="10" cy="0" r="4" fill="#ffffff"/>' : '');
    grupoTrem.appendChild(g);
    return g;
});

let trem = { seg: 'oeste', d: 200, sentido: 1 };
let rastro = [];
let anterior = null;

function entrar(saida) {
    const [seg, ponta] = saida.split(':');
    const comprimento = document.getElementById('seg-' + seg).getTotalLength();
    trem = { seg, d: ponta == 'inicio' ? 0 : comprimento, sentido: ponta == 'inicio' ? 1 : -1 };
}

function anda(tempo) {
    const passo = anterior ? Math.min(tempo - anterior, 50) * 0.12 : 0;
    anterior = tempo;
    const caminho = document.getElementById('seg-' + trem.seg);
    const comprimento = caminho.getTotalLength();
    trem.d += passo * trem.sentido;

    if (trem.d < 0 || trem.d > comprimento) {
        const chegada = trem.seg + ':' + (trem.d > comprimento ? 'fim' : 'inicio');
        const no = pontas[trem.seg][trem.d > comprimento ? 1 : 0];

        if (no == 'para-choque') {
            trem.sentido *= -1;
            trem.d = comprimento;
            rastro = [];
        } else if (ligacoes[no].ponta == chegada) {
            entrar(ligacoes[no].caminhos[Math.floor(Math.random() * 2)]);
        } else {
            entrar(ligacoes[no].ponta);
        }
    }

    const caminhoAtual = document.getElementById('seg-' + trem.seg);
    const p = caminhoAtual.getPointAtLength(trem.d);
    const q = caminhoAtual.getPointAtLength(trem.d + trem.sentido * 3);
    rastro.unshift({ x: p.x, y: p.y });

    let i = 0;
    let andado = 0;
    vagoes.forEach((vagao, n) => {
        while (i < rastro.length - 1 && andado < n * 40) {
            andado += Math.hypot(rastro[i].x - rastro[i + 1].x, rastro[i].y - rastro[i + 1].y);
            i++;
        }
        const a = rastro[i], b = i ? rastro[Math.max(i - 3, 0)] : q;
        vagao.setAttribute('transform', 'translate(' + a.x + ' ' + a.y + ') rotate(' + Math.atan2(b.y - a.y, b.x - a.x) * 180 / Math.PI + ')');
    });
    rastro.length = Math.min(rastro.length, i + 2);

    const fracao = trem.d / caminhoAtual.getTotalLength();
    document.getElementById('local-trem').textContent = 'Trem em: ' + trechos[trem.seg].find(t => fracao <= t[0])[1];

    requestAnimationFrame(anda);
}

requestAnimationFrame(anda);