<?php

// desliga as exceções do mysqli, a gente mesmo confere os erros
mysqli_report(MYSQLI_REPORT_OFF);

// dados do banco no XAMPP: servidor, usuário root, senha vazia e o nome do banco
$conexao = mysqli_connect('localhost', 'root', '', 'sa_ferrorama');

// se não conectar para tudo e mostra o erro
if (!$conexao) {
    die('Erro na conexão: ' . mysqli_connect_error());
}

// pra acentuação não vir quebrada
mysqli_set_charset($conexao, 'utf8mb4');
