# Validações: listagem e inativação de usuários (Etapa 5)

Todas foram executadas num MySQL (MariaDB 10.11) com PHP 8.3 e deram o resultado esperado. 

| Nº | Teste | Como fazer | Resultado esperado | Requisito |
|---|---|---|---|---|
| V1 | Usuários aparecem na lista | Entrar como admin e abrir Usuários | Todos os usuários do banco aparecem | RF28 |
| V2 | Dados batem com o banco | Comparar a tela com `SELECT id_usuario, nome_usuario, email_usuario, perfil, status_usuario, data_cadastro FROM usuarios;` | Os valores são os mesmos | RF28, RF45 |
| V3 | Senha não aparece | Olhar a tela e o código-fonte da página (Ctrl+U) | Nenhuma senha nem hash `$2y$` | RNF11, RNF12 |
| V4 | Inativar usuário existente | Clicar em INATIVAR num operador e confirmar | Aparece "Usuário inativado com sucesso." e o status muda para INATIVO | RF29, RN29 |
| V5 | Registro continua no banco, inativo | `SELECT nome_usuario, status_usuario FROM usuarios WHERE id_usuario = <id>;` | A linha existe, com status INATIVO | RN29 |
| V6 | Cancelar | Clicar em INATIVAR e depois em CANCELAR | Nada muda | RF14 |
| V7 | Usuário inexistente | No F12, trocar o valor do campo `id_usuario` da janela para 999 e confirmar | "Usuário não encontrado ou já inativo." | RF29 |
| V8 | ID inválido | Trocar `id_usuario` para `abc` ou `-1` | "Solicitação inválida." | RF29 |
| V9 | Inativar a própria conta | O botão do admin logado fica cinza; trocar o ID no F12 para o próprio ID | "Você não pode inativar a própria conta." | RN45 |
| V10 | Erro no banco ao inativar | Criar `CREATE TRIGGER teste BEFORE UPDATE ON usuarios FOR EACH ROW SIGNAL SQLSTATE '45000';`, inativar alguém e depois rodar `DROP TRIGGER teste;` | A página não quebra e mostra "Erro ao inativar usuário. Tente novamente." | RF29 |
| V11 | Erro na consulta da lista | Renomear a tabela `usuarios` e abrir a tela (depois voltar o nome) | Aparece "Não foi possível carregar os usuários." | RF28 |
| V12 | Filtro por status | Escolher Ativo, Inativo e Todos | Só aparecem os usuários daquele status | RF34 |
| V13 | Usuário inativo tenta entrar | Fazer login com o usuário inativado | "Usuário inativo. Fale com o administrador." | RF38, RN36 |
| V14 | Reativar | EDITAR o usuário inativo, mudar STATUS para Ativo e salvar | Volta como ATIVO e consegue fazer login | RF35 |
| V15 | Operador abre a tela de usuários | Entrar como operador e abrir `public/usuarios/tela-cadastro-user.php` | Volta para a Home com "Você não tem permissão..." e o menu não mostra Usuários | RF37, RN39 |
| V16 | Operador tenta inativar | Logado como operador, enviar um POST para `inativar-user.php` | Volta para a Home e nada muda | RN45 |
| V17 | Sem login | Sair e abrir `inativar-user.php` | Vai para a tela de login | RF1 |
| V18 | Link direto (GET) | Abrir `inativar-user.php?id_usuario=2` logado como admin | "Solicitação inválida." e nada muda | RN45 |
| V19 | Admin edita o próprio cadastro | Abrir EDITAR na própria conta | PERFIL e STATUS travados; mesmo forçando pelo F12, continua ADMINISTRADOR e ATIVO | RN45 |
| V20 | Nome de usuário repetido | Cadastrar ou editar com um nome que já existe | "Esse nome de usuário já existe." | RF27 |
| V21 | Sistema continua funcionando | Depois das inativações, cadastrar um usuário novo e navegar pelas telas | Tudo funciona | RF27 |
