const CLIMA_URL = 'https://api.open-meteo.com/v1/forecast?latitude=-26.3045&longitude=-48.8487&current=temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code,is_day&timezone=America%2FSao_Paulo';

const condicoes = [
    [0, 'Céu limpo', 'sun', 'moon-stars'], [1, 'Predomínio de sol', 'sun', 'moon-stars'], [2, 'Parcialmente nublado', 'cloud-sun', 'cloud-moon'],
    [3, 'Nublado', 'clouds'], [48, 'Neblina', 'cloud-fog'], [57, 'Garoa', 'cloud-drizzle'], [67, 'Chuva', 'cloud-rain'],
    [77, 'Neve', 'cloud-snow'], [82, 'Pancadas de chuva', 'cloud-rain-heavy'], [86, 'Neve', 'cloud-snow'], [99, 'Tempestade', 'cloud-lightning-rain']
];

async function carregarClima() {
    const $ = id => document.getElementById(id);
    $('climaHora').textContent = 'Atualizando...';

    try {
        const resposta = await fetch(CLIMA_URL, { signal: AbortSignal.timeout(8000) });
        if (!resposta.ok) throw new Error();
        const atual = (await resposta.json()).current;
        const [codigo, texto, iconeDia, iconeNoite] = condicoes.find(c => atual.weather_code <= c[0]);
        const medicao = new Date(atual.time);

        $('climaTemp').textContent = Math.round(atual.temperature_2m) + '°C';
        $('climaTexto').textContent = texto + ' · sensação de ' + Math.round(atual.apparent_temperature) + '°C';
        $('climaIcone').className = 'bi icone bi-' + (atual.is_day || !iconeNoite ? iconeDia : iconeNoite);
        $('climaExtra').textContent = '· umidade ' + atual.relative_humidity_2m + '% · vento ' + Math.round(atual.wind_speed_10m) + ' km/h';
        $('climaHora').textContent = 'Medição das ' + medicao.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) + ' · consultado às ' + new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

        const aviso = codigo >= 51 ? 'Pista molhada: atenção à frenagem e aos sensores da via.' : atual.wind_speed_10m > 50 ? 'Vento forte: atenção redobrada na ponte.' : '';
        $('climaAviso').textContent = aviso;
        $('climaAviso').classList.toggle('d-none', !aviso);
    } catch (erro) {
        $('climaTemp').textContent = '--';
        $('climaTexto').textContent = 'Não foi possível carregar o clima agora.';
        $('climaHora').textContent = 'Verifique a internet e clique em atualizar.';
    }
}

carregarClima();
setInterval(carregarClima, 10 * 60 * 1000);