CREATE DATABASE sa_teste;
USE sa_teste;

CREATE TABLE trilhos (
    id_trilho INT AUTO_INCREMENT PRIMARY KEY,
    nome_trilho VARCHAR(100) NOT NULL,
    descricao_trilho VARCHAR(255) NOT NULL,
    km_trilho VARCHAR(20) NOT NULL,
    status_trilho VARCHAR(20) NOT NULL
);

CREATE TABLE usuarios(
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
nome_usuario VARCHAR(200) NOT NULL UNIQUE,
senha VARCHAR(255) NOT NULL,
email_usuario VARCHAR(200) NOT NULL,
perfil VARCHAR(20) NOT NULL DEFAULT 'OPERADOR',
status_usuario VARCHAR(20) NOT NULL DEFAULT 'ATIVO',
data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trens (
    id_trem INT AUTO_INCREMENT PRIMARY KEY,
    nome_trem VARCHAR(100) NOT NULL,
    modelo_trem VARCHAR(30) NOT NULL,
    capacidade_trem INT NOT NULL,
    id_trilho INT NULL,
    status_trem VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_trilho) REFERENCES trilhos(id_trilho) ON DELETE SET NULL
);

CREATE TABLE sensores (
    id_sensor        INT AUTO_INCREMENT PRIMARY KEY,
    nome_sensor      VARCHAR(100)    NOT NULL,
    categoria_sensor VARCHAR(50)     NOT NULL,
    tipo_sensor      VARCHAR(50)     NOT NULL,
    id_trem          INT             NULL,
    id_trilho        INT             NULL,
    status_sensor    VARCHAR(20)     NOT NULL,
    FOREIGN KEY (id_trem) REFERENCES trens(id_trem) ON DELETE SET NULL,
    FOREIGN KEY (id_trilho) REFERENCES trilhos(id_trilho) ON DELETE SET NULL
);

USE sa_teste;

INSERT INTO usuarios (nome_usuario, senha, email_usuario, perfil) VALUES
('admin',    '$2y$12$rMdLkQLkxCcq6Cbmazqc.O9mvluYpbGTNkiWy.M5fmqVeVA.S1zYi', 'admin@ferromonitor.com',    'ADMINISTRADOR'),
('operador', '$2y$12$PfDzvJ35IUzg7JEslLJhB.f4nMQxHhXVLUUpSsuIyMKQgu12tQsQq', 'operador@ferromonitor.com', 'OPERADOR');
