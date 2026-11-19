// dashboard: busca as leituras a cada 3 segundos e redesenha a tela

// troca os caracteres especiais pra ninguém conseguir colocar HTML no nome do sensor
function protegerTexto(texto) {
    if (texto == null) {
        return '-';
    }

    let div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// unidade de medida de cada tipo de sensor
function unidade(tipo) {
    if (tipo == 'Velocidade') {
        return ' km/h';
    }
    if (tipo == 'Localização') {
        return ' km';
    }
    if (tipo == 'Temperatura') {
        return ' °C';
    }
    return '';
}

// formata o número no padrão brasileiro (12,50 km/h)
function mostrarValor(valor, tipo) {
    if (valor == null) {
        return '--';
    }

    return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + unidade(tipo);
}

// etiqueta colorida do status (as cores estão no style.css)
function mostrarStatus(status) {
    return '<span class="badge status status-' + status + '">' + status + '</span>';
}

// os 4 quadradinhos de cima; o de alertas fica vermelho se tiver algum
function mostrarResumo(resumo) {
    document.getElementById('totalLeituras').textContent = resumo.leituras;
    document.getElementById('totalHoje').textContent = resumo.leituras_hoje;
    document.getElementById('totalSensores').textContent = resumo.sensores_ativos;
    document.getElementById('totalAlertas').textContent = resumo.alertas;

    if (resumo.alertas > 0) {
        document.getElementById('totalAlertas').className = 'fs-3 fw-bold text-danger';
    } else {
        document.getElementById('totalAlertas').className = 'fs-3 fw-bold texto-azul';
    }
}

// um cartão pra cada sensor com a última leitura dele
function mostrarSensores(sensores) {
    let html = '';

    for (let i = 0; i < sensores.length; i++) {
        let s = sensores[i];

        let cor = 'texto-azul';
        if (s.status_sensor == 'ALERTA') {
            cor = 'text-danger';
        }

        let data = 'Sem leituras';
        if (s.data != null) {
            data = s.data;
        }

        html += '<div class="col-sm-6 col-lg-3"><div class="caixa mb-0 h-100">';
        html += '<div class="caixa-topo">' + protegerTexto(s.nome_sensor) + ' ' + mostrarStatus(s.status_sensor) + '</div>';
        html += '<div class="p-3">';
        html += '<div class="fs-3 fw-bold ' + cor + '">' + mostrarValor(s.valor, s.tipo_sensor) + '</div>';
        html += '<small class="text-muted">' + protegerTexto(s.tipo_sensor) + ' · ' + protegerTexto(s.nome_trilho) + '<br>' + data + '</small>';
        html += '</div></div></div>';
    }

    if (sensores.length == 0) {
        html = '<p class="text-muted">Nenhum sensor ativo cadastrado.</p>';
    }

    document.getElementById('sensores').innerHTML = html;
}

// tabela das últimas leituras
function mostrarLeituras(leituras) {
    let html = '';

    for (let i = 0; i < leituras.length; i++) {
        let l = leituras[i];

        html += '<tr>';
        html += '<td>' + l.id_leitura + '</td>';
        html += '<td>' + l.data + '</td>';
        html += '<td>' + protegerTexto(l.nome_sensor) + '</td>';
        html += '<td>' + protegerTexto(l.tipo_sensor) + '</td>';
        html += '<td>' + protegerTexto(l.nome_trilho) + '</td>';
        html += '<td class="fw-bold">' + mostrarValor(l.valor, l.tipo_sensor) + '</td>';
        html += '<td>' + mostrarStatus(l.status_sensor) + '</td>';
        html += '</tr>';
    }

    if (leituras.length == 0) {
        html = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhuma leitura registrada.</td></tr>';
    }

    document.getElementById('leituras').innerHTML = html;
}

// pede os dados pro PHP em JSON (?json=1)
function atualizar() {
    fetch('tela-dashboard.php?json=1')
        .then(function (resposta) {
            // se a sessão caiu o PHP redireciona pro login, então recarrega a página
            if (resposta.redirected) {
                location.reload();
            }
            return resposta.json();
        })
        .then(function (dados) {
            mostrarResumo(dados.resumo);
            mostrarSensores(dados.sensores);
            mostrarLeituras(dados.leituras);
            document.getElementById('atualizado').textContent = 'atualizado às ' + new Date().toLocaleTimeString('pt-BR');
        })
        // perdeu a conexão: só avisa, na próxima rodada tenta de novo
        .catch(function () {
            document.getElementById('atualizado').textContent = 'sem conexão, tentando de novo...';
        });
}

// atualiza agora e depois a cada 3 segundos
atualizar();
setInterval(atualizar, 3000);
