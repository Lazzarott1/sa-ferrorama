// muda o texto da capacidade conforme o modelo do trem: KG pra carga, passageiros pra passageiros
// o data-id do select diz de qual formulário ele é (0 = cadastro, outro número = edição daquele
// trem)
function atualizarCapacidade(modelo) {
    let id = modelo.dataset.id;
    let rotulo = document.getElementById('labelCapacidade' + id);
    let campo = document.getElementById('capacidadeTrem' + id);

    if (modelo.value == 'De carga') {
        rotulo.textContent = 'CAPACIDADE (KG)';
        campo.placeholder = 'Ex: 50000';
    } else if (modelo.value == 'De passageiros') {
        rotulo.textContent = 'CAPACIDADE (PASSAGEIROS)';
        campo.placeholder = 'Ex: 300';
    } else {
        rotulo.textContent = 'CAPACIDADE';
        campo.placeholder = 'Selecione o modelo primeiro';
    }
}

// quando a página abre já acerta o texto de todos os formulários
let modelos = document.getElementsByClassName('modelo-trem');

for (let i = 0; i < modelos.length; i++) {
    atualizarCapacidade(modelos[i]);
}
