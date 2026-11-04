async function buscarCep(campo) {
    const aviso = document.getElementById('avisoCep');
    const cep = campo.value.replace(/\D/g, '');

    const avisar = (texto, cor) => {
        aviso.textContent = texto;
        aviso.className = 'form-text ' + cor;
    };

    const preencher = (dados) => {
        ['logradouro', 'bairro', 'uf'].forEach(c => campo.form.elements[c].value = dados[c] || '');
        campo.form.elements.cidade.value = dados.localidade || '';
    };

    if (cep === '') return avisar('Digite o CEP para preencher o endereço.', '');
    if (cep.length !== 8) return avisar('CEP inválido: digite os 8 números.', 'text-danger');

    avisar('Consultando o CEP...', 'text-secondary');

    try {
        const resposta = await fetch('https://viacep.com.br/ws/' + cep + '/json/', { signal: AbortSignal.timeout(8000) });
        if (!resposta.ok) throw new Error();
        const dados = await resposta.json();

        if (dados.erro) {
            preencher({});
            return avisar('CEP não encontrado. Confira o número digitado.', 'text-danger');
        }

        campo.value = dados.cep;
        preencher(dados);
        avisar('Endereço encontrado: ' + dados.localidade + '/' + dados.uf + '.', 'text-success');
    } catch (erro) {
        avisar('Não foi possível consultar o CEP agora. Tente de novo ou preencha o endereço à mão.', 'text-warning-emphasis');
    }
}