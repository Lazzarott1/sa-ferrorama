<?php

echo "<h2>Teste de Conexão - FerroMonitor</h2>";


include '../infra/conexao.php';

// 1) A conexão foi criada e não retornou erro?
if (isset($conexao) && $conexao instanceof mysqli && !$conexao->connect_error) {
    echo "<p style='color:green;'>Conexão estabelecida com sucesso.</p>";
    echo "<p>Servidor: " . $conexao->host_info . "</p>";
} else {
    echo "<p style='color:red;'>Falha na conexão: " . ($conexao->connect_error ?? 'variável $conexao não definida') . "</p>";
    exit;
}

// 2) A conexão realmente executa comandos no banco certo?
$db = $conexao->query("SELECT DATABASE()")->fetch_row()[0];
echo "<p>Banco atual: <strong>" . htmlspecialchars($db) . "</strong></p>";

// 3) Conseguimos listar as tabelas (prova que o schema foi criado e está acessível)
$resultado = mysqli_query($conexao, "SHOW TABLES");

if ($resultado) {
    echo "<p style='color:green;'>Consulta de teste executada com sucesso. Tabelas encontradas no banco:</p><ul>";
    while ($linha = mysqli_fetch_row($resultado)) {
        echo "<li>" . htmlspecialchars($linha[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>Erro ao executar consulta: " . mysqli_error($conexao) . "</p>";
}

mysqli_close($conexao);