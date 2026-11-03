# FerroMonitor

Projeto da Situação de Aprendizagem **Ferroramas**, feito por:

- Caio Marques
- Lucas Lazzarotti
- Luis Pedro Mathias
- Matheus Guesser

## O que é

O FerroMonitor é um sistema web para gerenciar uma ferrovia. A ideia é que os trens e os trilhos tenham sensores, e que a equipe que cuida da ferrovia consiga cadastrar e acompanhar tudo isso em um só lugar.

O sistema faz o gerenciamento dos cadastros (usuários, trens, trilhos e sensores) e já tem login com controle de acesso. A leitura dos sensores em tempo real e a geração dos relatórios ainda são planos para as próximas etapas.

## Funcionalidades

- **Login** com nome de usuário e senha. As senhas ficam salvas com hash no banco (`password_hash`), nunca em texto puro.
- **Dois perfis**:
- **Administrador**: pode cadastrar, editar e inativar tudo, e é o único que vê a tela de usuários.
- **Operador**: só consulta as informações.
- **Usuários**: cadastro, edição e inativação (só o administrador).
- **Trens**: cadastro, listagem, edição e inativação. Cada trem tem um usuário responsável e pode ficar ligado a um trilho.
- **Trilhos**: cadastro, listagem, edição e inativação. No nosso sistema, os trilhos fazem o papel das rotas: cada um tem nome, descrição, distância em km e data de criação.
- **Sensores**: cada sensor fica ligado a um trem ou a um trilho.
- **Logout**: pelo botão "Sair", que encerra a sessão.
- **Relatórios**: geração e visualização de relatórios contendo dados sobre trens, trilhos e sensores.

Uma decisão nossa: **nada é apagado do banco**. Em vez de excluir, o registro fica como "inativo" e pode ser visto pelo filtro Ativos / Inativos / Todos. Se você tentar inativar um trilho ou trem que ainda tem coisas ligadas nele, o sistema avisa antes.

As permissões são conferidas no PHP de cada página. Então, mesmo que um operador digite o endereço de uma página de administrador, ele é mandado de volta para a Home.

## Como rodar

Usamos o **XAMPP** (Apache + MySQL).

1. Coloque a pasta do projeto dentro de `C:\xampp\htdocs\`.
2. Abra o XAMPP e ligue o **Apache** e o **MySQL**.
3. No phpMyAdmin (`http://localhost/phpmyadmin`), importe o arquivo `database/db_sa.sql`. Ele cria o banco `sa_teste` com as tabelas e os usuários de teste.
4. Acesse `http://localhost/sa-ferrorama/` no navegador. Ele já abre a tela de login.

Se o seu MySQL tiver senha, ajuste em `infra/conexao.php`.

### Usuários de teste

| Usuário  | Senha       | Perfil        |
|----------|-------------|---------------|
| admin    | admin123    | Administrador |
| operador | operador123 | Operador      |

## Como o projeto está organizado

```
database/   script do banco
doc/        pesquisas e anotações da equipe
infra/      conexão com o banco, validações e controle de login/acesso
public/     as telas do sistema (uma pasta por assunto)
script/     JavaScript das telas
assets/     CSS e imagens
```

## Tecnologias

PHP, MySQL, HTML, CSS, JavaScript e Bootstrap 5. Para organizar o trabalho usamos Scrum e um quadro Kanban no GitHub Projects.
