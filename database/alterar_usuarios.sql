-- Migração da tabela usuarios para bancos criados com a versão anterior do db_sa.sql
-- Adiciona perfil (ADMIN / FUNCIONARIO), controle de tentativas de login e
-- impede nome de usuário ou e-mail repetidos.

USE sa_teste;

ALTER TABLE usuarios
    ADD COLUMN perfil_usuario ENUM('ADMIN', 'FUNCIONARIO') NOT NULL DEFAULT 'FUNCIONARIO' AFTER email_usuario,
    ADD COLUMN tentativas_login INT NOT NULL DEFAULT 0 AFTER perfil_usuario,
    ADD COLUMN bloqueado_ate DATETIME NULL AFTER tentativas_login,
    ADD COLUMN criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER bloqueado_ate,
    ADD UNIQUE KEY uk_usuarios_nome (nome_usuario),
    ADD UNIQUE KEY uk_usuarios_email (email_usuario);

-- O usuário "admin" dos dados iniciais passa a ser Administrador
UPDATE usuarios SET perfil_usuario = 'ADMIN' WHERE nome_usuario = 'admin';
