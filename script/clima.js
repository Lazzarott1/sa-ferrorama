// cartão de clima da home

// API do Open-Meteo com as coordenadas de Joinville (não precisa de chave)
const CLIMA_URL = 'https://api.open-meteo.com/v1/forecast?latitude=-26.3045&longitude=-48.8487&current=temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code,is_day&timezone=America%2FSao_Paulo';

// a API manda um código de clima, aqui a gente transforma em texto
function descreverTempo(codigo) {
    if (codigo == 0) return 'Céu limpo';
    if (codigo == 1) return 'Predomínio de sol';
    if (codigo == 2) return 'Parcialmente nublado';
    if (codigo == 3) return 'Nublado';
    if (codigo <= 48) return 'Neblina';
    if (codigo <= 57) return 'Garoa';
    if (codigo <= 67) return 'Chuva';
    if (codigo <= 77) return 'Neve';
    if (codigo <= 82) return 'Pancadas de chuva';
    if (codigo <= 86) return 'Neve';
    return 'Tempestade';
}

// mesmo esquema, mas pro ícone do bootstrap-icons (de noite usa lua)
function escolherIcone(codigo, dia) {
    if (codigo <= 1) {
        if (dia) return 'sun';
        return 'moon-stars';
    }
    if (codigo == 2) {
        if (dia) return 'cloud-sun';
        return 'cloud-moon';
    }
    if (codigo == 3) return 'clouds';
    if (codigo <= 48) return 'cloud-fog';
    if (codigo <= 57) return 'cloud-drizzle';
    if (codigo <= 67) return 'cloud-rain';
    if (codigo <= 77) return 'cloud-snow';
    if (codigo <= 82) return 'cloud-rain-heavy';
    if (codigo <= 86) return 'cloud-snow';
    return 'cloud-lightning-rain';
}

// deixa a hora no formato 14:30
function horaAgora(data) {
    return data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// busca o clima e preenche o cartão (também é chamada pelo botão de atualizar)
function carregarClima() {
    document.getElementById('climaHora').textContent = 'Atualizando...';

    fetch(CLIMA_URL)
        .then(function (resposta) {
            return resposta.json();
        })
        .then(function (dados) {
            let atual = dados.current;
            let codigo = atual.weather_code;

            document.getElementById('climaTemp').textContent = Math.round(atual.temperature_2m) + '°C';
            document.getElementById('climaTexto').textContent = descreverTempo(codigo) + ' · sensação de ' + Math.round(atual.apparent_temperature) + '°C';
            document.getElementById('climaIcone').className = 'bi icone bi-' + escolherIcone(codigo, atual.is_day);
            document.getElementById('climaExtra').textContent = '· umidade ' + atual.relative_humidity_2m + '% · vento ' + Math.round(atual.wind_speed_10m) + ' km/h';
            document.getElementById('climaHora').textContent = 'Medição das ' + horaAgora(new Date(atual.time)) + ' · consultado às ' + horaAgora(new Date());

            // aviso pros operadores: chuva deixa a pista molhada e vento forte é perigoso na ponte
            let aviso = '';
            if (codigo >= 51) {
                aviso = 'Pista molhada: atenção à frenagem e aos sensores da via.';
            } else if (atual.wind_speed_10m > 50) {
                aviso = 'Vento forte: atenção redobrada na ponte.';
            }

            document.getElementById('climaAviso').textContent = aviso;
            if (aviso == '') {
                document.getElementById('climaAviso').classList.add('d-none');
            } else {
                document.getElementById('climaAviso').classList.remove('d-none');
            }
        })
        // se a API não respondeu mostra traços e pede pra atualizar
        .catch(function () {
            document.getElementById('climaTemp').textContent = '--';
            document.getElementById('climaTexto').textContent = 'Não foi possível carregar o clima agora.';
            document.getElementById('climaHora').textContent = 'Verifique a internet e clique em atualizar.';
        });
}

// carrega quando abre a página e depois a cada 10 minutos
carregarClima();
setInterval(carregarClima, 10 * 60 * 1000);
