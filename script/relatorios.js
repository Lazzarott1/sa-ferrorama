// quando troca o tipo no filtro já envia o formulário
function enviarFiltro(campo) {
    campo.form.submit();
}

// se a página veio com um relatório pra mostrar (?ver=), abre o modal sozinho
let modalRelatorio = document.getElementById('modalRelatorio');

if (modalRelatorio) {
    let modal = new bootstrap.Modal(modalRelatorio);
    modal.show();
}
