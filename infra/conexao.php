<?php

// Os dados de acesso podem vir de variáveis de ambiente (servidor/testes).
// Sem elas, vale a configuração padrão do XAMPP.
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$senha = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$banco = getenv('DB_NAME') ?: "sa_teste";

// Erros do banco viram exceções (nada de falha silenciosa)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexao = new mysqli($host, $user, $senha, $banco);
    $conexao->set_charset("utf8mb4");
} catch (mysqli_sql_exception $erro) {
    // O detalhe vai para o log; a tela não mostra usuário, host ou senha do banco
    error_log("Erro na conexão com o banco: " . $erro->getMessage());
    http_response_code(500);
    die("Erro ao conectar com o banco de dados. Tente novamente mais tarde.");
}
