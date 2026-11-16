<?php

function filtrar($coluna)
{
    global $filtro;
    $filtro = $_GET['filtro'] ?? 'ativos';
    return ['ativos' => "WHERE $coluna <> 'INATIVO'", 'inativos' => "WHERE $coluna = 'INATIVO'"][$filtro] ?? "";
}

function mudarStatus($conexao, $tabela, $chave, $coluna, $permitido = "1")
{
    $ids = implode(',', array_map('intval', $_POST['ids'] ?? [])) ?: '0';

    if (isset($_POST['ativar'])) {
        mysqli_query($conexao, "UPDATE $tabela SET $coluna = 'ATIVO' WHERE $chave IN ($ids) AND $coluna = 'INATIVO'");
        return 0;
    }

    mysqli_query($conexao, "UPDATE $tabela SET $coluna = 'INATIVO' WHERE $chave IN ($ids) AND $permitido");
    return mysqli_fetch_row(mysqli_query($conexao, "SELECT COUNT(*) FROM $tabela WHERE $chave IN ($ids) AND $coluna <> 'INATIVO'"))[0];
}

function topo($titulo, $admin)
{
    global $filtro; ?>
    <div class="caixa-topo">
        <?php echo $titulo; ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($admin) { ?>
                <div id="emMassa" class="d-flex gap-2 d-none">
                    <button type="submit" form="formStatus" name="ativar" class="btn btn-sm btn-light">ATIVAR SELECIONADOS</button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="inativar()">INATIVAR SELECIONADOS</button>
                </div>
            <?php } ?>
            <form method="GET">
                <select name="filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach (['ativos' => 'ATIVOS', 'inativos' => 'INATIVOS', 'todos' => 'TODOS'] as $valor => $texto) { ?>
                        <option value="<?php echo $valor; ?>" <?php if ($filtro == $valor) echo 'selected'; ?>><?php echo $texto; ?></option>
                    <?php } ?>
                </select>
            </form>
        </div>
    </div>
<?php }

function marcarTodos()
{ ?>
    <th><input type="checkbox" class="form-check-input" onchange="document.querySelectorAll('.marcar').forEach(c => c.checked = this.checked)"></th>
<?php }

function marcar($id, $nome, $bloqueio = '')
{ ?>
    <td><input type="checkbox" class="form-check-input marcar" form="formStatus" name="ids[]" value="<?php echo $id; ?>" data-nome="<?php echo htmlspecialchars($nome); ?>" data-bloqueio="<?php echo htmlspecialchars($bloqueio); ?>"></td>
<?php }

function acoes($id, $inativo)
{ ?>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editar<?php echo $id; ?>">EDITAR</button>
    <?php if ($inativo) { ?>
        <button type="submit" form="formStatus" name="ativar" class="btn btn-sm btn-primary" onclick="marcarSo(<?php echo $id; ?>)">ATIVAR</button>
    <?php } else { ?>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="inativar(<?php echo $id; ?>)">INATIVAR</button>
    <?php }
}

function modalStatus()
{ ?>
    <form id="formStatus" method="POST">
        <input type="hidden" name="token" value="<?php echo $_SESSION['token'] ?? ''; ?>">
    </form>

    <div class="modal fade" id="modalInativar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold" id="tituloInativar"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0"><strong id="perguntaInativar"></strong><div id="textoInativar"></div></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" form="formStatus" name="inativar" id="botaoInativar" class="btn btn-sm btn-danger">INATIVAR</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarMassa() {
            document.getElementById('emMassa')?.classList.toggle('d-none', document.querySelectorAll('.marcar:checked').length < 2);
        }

        document.addEventListener('change', mostrarMassa);

        function marcarSo(id) {
            document.querySelectorAll('.marcar').forEach(c => c.checked = c.value == id);
            mostrarMassa();
        }

        function inativar(id) {
            if (id) marcarSo(id);

            const lista = [...document.querySelectorAll('.marcar:checked')];
            const bloqueados = lista.filter(c => c.dataset.bloqueio).length;
            const um = lista.length == 1;
            const negado = !lista.length || (um && bloqueados);

            tituloInativar.textContent = negado ? 'INATIVAÇÃO NÃO PERMITIDA' : 'CONFIRMAR INATIVAÇÃO';
            perguntaInativar.textContent = !lista.length ? 'Nenhum item selecionado.'
                : um ? (negado ? 'Não é possível inativar ' : 'Tem certeza que deseja inativar ') + lista[0].dataset.nome + (negado ? '.' : '?')
                : 'Tem certeza que deseja inativar os ' + lista.length + ' itens selecionados?';
            textoInativar.textContent = !lista.length ? 'Marque os itens na lista antes de inativar.'
                : um ? lista[0].dataset.bloqueio
                : bloqueados ? bloqueados + ' deles não serão inativados por terem associações.' : '';
            botaoInativar.hidden = negado;

            bootstrap.Modal.getOrCreateInstance(modalInativar).show();
        }
    </script>
<?php }