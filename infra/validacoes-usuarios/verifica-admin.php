<?php

// usado nas telas que só o administrador pode abrir
// primeiro confere o login (é de lá que vem o $admin e o $caminho)
include __DIR__ . '/verifica-login.php';

// operador tentando entrar: volta pra home com o aviso de sem permissão
if (!$admin) {
    header("Location: " . $caminho . "tela-geral-home.php?acesso=negado");
    exit();
}
