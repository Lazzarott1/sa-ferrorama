<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'sa_ferrorama';
$porta = 3307;

$conexao = new mysqli($host, $usuario, $senha, $banco, $porta);

if ($conexao->connect_error) {
    die('Erro na conexão: ' . $conexao->connect_error);
}

$conexao->set_charset('utf8mb4');
$conn = $conexao;