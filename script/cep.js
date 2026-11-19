// busca o endereço pelo CEP no ViaCEP e preenche os campos sozinho
// o id diz de qual formulário é (0 = cadastro, outro número = edição daquele usuário)

// escreve a mensagem embaixo do campo de CEP com a cor que a gente passar
function avisarCep(id, texto, cor) {
    let aviso = document.getElementById('avisoCep' + id);
    aviso.textContent = texto;
    aviso.className = 'form-text ' + cor;
}

// joga o endereço nos campos do formulário
function preencherEndereco(id, logradouro, bairro, cidade, uf) {
    document.getElementById('logradouro' + id).value = logradouro;
    document.getElementById('bairro' + id).value = bairro;
    document.getElementById('cidade' + id).value = cidade;
    document.getElementById('uf' + id).value = uf;
}

// chamada quando sai do campo de CEP
function buscarCep(campo, id) {
    let cep = campo.value.replace('-', '').trim();

    // vazio ou com número errado nem consulta
    if (cep == '') {
        avisarCep(id, 'Digite o CEP para preencher o endereço.', '');
        return;
    }

    if (cep.length != 8 || isNaN(cep)) {
        avisarCep(id, 'CEP inválido: digite os 8 números.', 'text-danger');
        return;
    }

    avisarCep(id, 'Consultando o CEP...', 'text-secondary');

    // pergunta pro ViaCEP (a API é de graça e não precisa de chave)
    fetch('https://viacep.com.br/ws/' + cep + '/json/')
        .then(function (resposta) {
            return resposta.json();
        })
        .then(function (dados) {
            // o ViaCEP responde { erro: true } quando o CEP não existe
            if (dados.erro) {
                preencherEndereco(id, '', '', '', '');
                avisarCep(id, 'CEP não encontrado. Confira o número digitado.', 'text-danger');
                return;
            }

            campo.value = dados.cep;
            preencherEndereco(id, dados.logradouro, dados.bairro, dados.localidade, dados.uf);
            avisarCep(id, 'Endereço encontrado: ' + dados.localidade + '/' + dados.uf + '.', 'text-success');
        })
        // sem internet ou ViaCEP fora do ar: deixa a pessoa preencher na mão
        .catch(function () {
            avisarCep(id, 'Não foi possível consultar o CEP agora. Tente de novo ou preencha o endereço à mão.', 'text-warning-emphasis');
        });
}
