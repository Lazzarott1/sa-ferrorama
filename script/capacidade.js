const modelo = document.getElementById("modeloTrem");

function atualizarCapacidade() {
    const tipos = {
        "De carga": ["CAPACIDADE (KG)", "Ex: 50000"],
        "De passageiros": ["CAPACIDADE (PASSAGEIROS)", "Ex: 300"]
    };
    [labelCapacidade.textContent, capacidadeTrem.placeholder] = tipos[modelo.value] || ["CAPACIDADE", "Selecione o modelo primeiro"];
}

if (modelo) {
    modelo.addEventListener("change", atualizarCapacidade);
    atualizarCapacidade();
}
