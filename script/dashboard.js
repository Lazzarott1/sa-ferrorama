const esc = texto => String(texto ?? '-').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
const status = s => `<span class="badge status status-${esc(s)}">${esc(s)}</span>`;

async function atualizar() {
    try {
        const resposta = await fetch('tela-dashboard.php?json=1', { cache: 'no-store' });
        if (!resposta.headers.get('content-type').includes('json')) return location.reload();
        const d = await resposta.json();
        const valor = (v, tipo) => v === null ? '--' : Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + ' ' + (d.unidades[tipo] ?? '');

        resumo.innerHTML = Object.entries(d.resumo).map(([nome, qtd]) => `
            <div class="col-6 col-md-3"><div class="caixa py-2 mb-0">
                <div class="fs-3 fw-bold ${nome == 'Alertas' && qtd > 0 ? 'text-danger' : 'texto-azul'}">${qtd}</div>
                <div class="form-label text-uppercase mb-0">${nome}</div>
            </div></div>`).join('');

        sensores.innerHTML = d.sensores.map(s => `
            <div class="col-sm-6 col-lg-3"><div class="caixa mb-0 h-100">
                <div class="caixa-topo">${esc(s.nome_sensor)} ${status(s.status_sensor)}</div>
                <div class="p-3">
                    <div class="fs-3 fw-bold ${s.status_sensor == 'ALERTA' ? 'text-danger' : 'texto-azul'}">${valor(s.valor, s.tipo_sensor)}</div>
                    <small class="text-muted">${esc(s.tipo_sensor)} · ${esc(s.nome_trilho)}<br>${s.data ?? 'Sem leituras'}</small>
                </div>
            </div></div>`).join('') || '<p class="text-muted">Nenhum sensor ativo cadastrado.</p>';

        leituras.innerHTML = d.leituras.map(l => `
            <tr><td>${l.id_leitura}</td><td>${l.data}</td><td>${esc(l.nome_sensor)}</td><td>${esc(l.tipo_sensor)}</td><td>${esc(l.nome_trilho)}</td>
            <td class="fw-bold">${valor(l.valor, l.tipo_sensor)}</td><td>${status(l.status_sensor)}</td></tr>`).join('')
            || '<tr><td colspan="7" class="text-center text-muted py-4">Nenhuma leitura registrada.</td></tr>';

        atualizado.textContent = 'atualizado às ' + new Date().toLocaleTimeString('pt-BR');
    } catch (e) {
        atualizado.textContent = 'sem conexão, tentando de novo...';
    }
}

atualizar();
setInterval(atualizar, 3000);
