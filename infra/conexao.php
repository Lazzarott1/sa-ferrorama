<?php
// desliga as exceções do mysqli, a gente mesmo confere os erros
mysqli_report(MYSQLI_REPORT_OFF);

// dados do banco no XAMPP (root sem senha é o padrão)
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'sa_ferrorama';

$conexao = new mysqli($host, $usuario, $senha, $banco);

// se não conectar para tudo e mostra o erro
if ($conexao->connect_error) {
    die('Erro na conexão: ' . $conexao->connect_error);
}

// pra acentuação não vir quebrada
$conexao->set_charset('utf8mb4');

// algumas telas antigas usam $conn, então deixamos os dois nomes
$conn = $conexao;