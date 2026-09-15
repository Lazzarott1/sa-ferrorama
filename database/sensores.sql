CREATE DATABASE sa_teste;
USE sa_teste;

CREATE TABLE sensores (
    id_sensor INT AUTO_INCREMENT PRIMARY KEY,
    nome_sensor VARCHAR(100) NOT NULL,
    categoria_sensor VARCHAR(50) NOT NULL,
    tipo_sensor VARCHAR(50) NOT NULL,
    trilho_sensor VARCHAR(75) NOT NULL,
    status_sensor VARCHAR(20) NOT NULL
);

CREATE TABLE trilhos (
    id_trilho INT AUTO_INCREMENT PRIMARY KEY,
    nome_trilho VARCHAR(100) NOT NULL,
    descricao_trilho VARCHAR(255) NOT NULL,
    km_trilho VARCHAR(20) NOT NULL,
    status_trilho VARCHAR(20) NOT NULL
);

CREATE TABLE usuarios(
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
nome_usuario VARCHAR(200) NOT NULL,
senha VARCHAR(255) NOT NULL,
email_usuario VARCHAR(200) NOT NULL
);

CREATE TABLE trens (
    id_trem         INT AUTO_INCREMENT PRIMARY KEY,
    nome_trem       VARCHAR(100)    NOT NULL,
    carga_trem     VARCHAR(100)    NOT NULL,
    status_trem     VARCHAR(20)     NOT NULL   
) 
