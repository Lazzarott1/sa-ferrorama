<?php

// funções que as listas de trilhos, trens, sensores e usuários usam igualzinho
// assim não precisa repetir o mesmo código em cada tela

// qual filtro está escolhido na url (?filtro=); o padrão é mostrar só os ativos
function filtroEscolhido()
{
    if (isset($_GET['filtro'])) {
        return $_GET['filtro'];
    }

    return 'ativos';
}

// monta o WHERE da consulta conforme o filtro
function filtrar($coluna)
{
    $filtro = filtroEscolhido();

    if ($filtro == 'ativos') {
        return "WHERE $coluna <> 'INATIVO'";
    }

    if ($filtro == 'inativos') {
        return "WHERE $coluna = 'INATIVO'";
    }

    return "";
}

// ativa ou inativa os itens marcados na lista
// $regra é uma condição a mais pra poder inativar (ex: trilho sem trem ativo)
// devolve quantos itens ficaram sem inativar
function mudarStatus($conexao, $tabela, $chave, $coluna, $regra = "1")
{
    if (!isset($_POST['ids'])) {
        return 0;
    }

    // o (int) garante que só tem número na lista de ids
    $ids = [];
    foreach ($_POST['ids'] as $id) {
        $ids[] = (int) $id;
    }
    $lista = implode(',', $ids);

    if (isset($_POST['ativar'])) {
        mysqli_query($conexao, "UPDATE $tabela
            SET $coluna = 'ATIVO'
            WHERE $chave IN ($lista) AND $coluna = 'INATIVO'");
        return 0;
    }

    // nada é apagado do banco, só muda o status
    mysqli_query($conexao, "UPDATE $tabela
        SET $coluna = 'INATIVO'
        WHERE $chave IN ($lista) AND $regra");

    // conta quantos dos marcados continuaram ativos (bloqueados pela regra)
    $resultado = mysqli_query($conexao, "SELECT COUNT(*) AS total
        FROM $tabela
        WHERE $chave IN ($lista) AND $coluna <> 'INATIVO'");
    $linha = mysqli_fetch_assoc($resultado);

    return $linha['total'];
}

// faixa azul em cima da tabela, com o título, os botões de ação em massa e o filtro
function topo($titulo, $admin)
{
    $filtro = filtroEscolhido();
    ?>
    <div class="caixa-topo">
        <?php echo $titulo; ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($admin) { ?>
                <!-- esses botões só aparecem quando marca 2 itens ou mais (listagem.js) -->
                <div id="emMassa" class="d-flex gap-2 d-none">
                    <button
                        type="submit"
                        form="formStatus"
                        name="ativar"
                        class="btn btn-sm btn-light">
                        ATIVAR SELECIONADOS
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-light"
                        data-bs-toggle="modal"
                        data-bs-target="#modalInativar"
                        onclick="inativar(0)">
                        INATIVAR SELECIONADOS
                    </button>
                </div>
            <?php } ?>
            <form method="GET">
                <select
                    name="filtro"
                    class="form-select form-select-sm"
                    onchange="enviarFiltro(this)">
                    <option value="ativos" <?php if ($filtro == 'ativos') echo 'selected'; ?>>
                        ATIVOS
                    </option>
                    <option value="inativos" <?php if ($filtro == 'inativos') echo 'selected'; ?>>
                        INATIVOS
                    </option>
                    <option value="todos" <?php if ($filtro == 'todos') echo 'selected'; ?>>
                        TODOS
                    </option>
                </select>
            </form>
        </div>
    </div>
    <?php
}

// checkbox do cabeçalho que marca ou desmarca todos
function marcarTodos()
{
    ?>
    <th><input type="checkbox" class="form-check-input" onchange="marcarTodos(this)"></th>
    <?php
}

// checkbox de cada linha; o data-bloqueio guarda o motivo de não poder inativar (vazio se pode)
function marcar($id, $nome, $bloqueio)
{
    ?>
    <td>
        <input
            type="checkbox"
            class="form-check-input marcar"
            form="formStatus"
            name="ids[]"
            value="<?php echo $id; ?>"
            data-nome="<?php echo htmlspecialchars($nome); ?>"
            data-bloqueio="<?php echo htmlspecialchars($bloqueio); ?>"
            onchange="mostrarMassa()">
    </td>
    <?php
}

// botões de cada linha: EDITAR abre o formulário embaixo do item; o outro botão é ATIVAR ou
// INATIVAR conforme o status
function acoes($id, $status)
{
    ?>
    <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        data-bs-toggle="collapse"
        data-bs-target="#editar<?php echo $id; ?>">
        EDITAR
    </button>
    <?php if ($status == 'INATIVO') { ?>
        <button
            type="submit"
            form="formStatus"
            name="ativar"
            class="btn btn-sm btn-primary"
            onclick="marcarSo(<?php echo $id; ?>)">
            ATIVAR
        </button>
    <?php } else { ?>
        <button
            type="button"
            class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal"
            data-bs-target="#modalInativar"
            onclick="inativar(<?php echo $id; ?>)">
            INATIVAR
        </button>
    <?php } ?>
    <?php
}

// formulário vazio que envia os checkboxes marcados (eles apontam pra ele com form="formStatus")
// + o modal que pergunta se quer mesmo inativar
function modalStatus()
{
    ?>
    <form id="formStatus" method="POST"></form>

    <div class="modal fade" id="modalInativar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-topo py-2">
                    <h6 class="modal-title fw-bold" id="tituloInativar"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong id="perguntaInativar"></strong>
                        <div id="textoInativar"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        CANCELAR
                    </button>
                    <button
                        type="submit"
                        form="formStatus"
                        name="inativar"
                        id="botaoInativar"
                        class="btn btn-sm btn-danger">
                        INATIVAR
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
