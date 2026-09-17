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
);

CREATE TABLE relatorios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    titulo       VARCHAR(150)    NOT NULL,
    tipo         VARCHAR(50)     NOT NULL,
    data_inicio  DATE            NOT NULL,
    data_fim     DATE            NOT NULL,
    trem_id      INT             NULL,
    trilho_id    INT             NULL,
    tipo_dado    VARCHAR(50)     NULL,
    status       VARCHAR(20)     NOT NULL DEFAULT 'PROCESSANDO',
    gerado_em    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_relatorios_trem   FOREIGN KEY (trem_id)   REFERENCES trens (id_trem)     ON DELETE SET NULL,
    CONSTRAINT fk_relatorios_trilho FOREIGN KEY (trilho_id) REFERENCES trilhos (id_trilho) ON DELETE SET NULL
);
