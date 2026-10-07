# Validações e Testes — Módulo de Funcionários/Usuários

## 1. Onde cada validação acontece

A validação no navegador (`script/validacao_cadastro_user.js`) só serve para avisar o usuário mais rápido.
**Quem decide se o dado é gravado é o servidor** (`infra/validacao-usuario.php` e `infra/usuarios.php`). Por último, o banco ainda tem restrições próprias (`database/db_sa.sql`).

| Campo | Regra | Navegador | Backend (PHP) | Banco (MySQL) | Mensagem | Testes |
|---|---|:-:|:-:|:-:|---|---|
| Nome de usuário | Obrigatório | ✔ | ✔ | `NOT NULL` | Informe o nome de usuário. | V02 |
| Nome de usuário | De 3 a 50 caracteres | ✔ | ✔ | — | O nome de usuário deve ter entre 3 e 50 caracteres. | V03, V04 |
| Nome de usuário | Só letras sem acento, números, `.`, `-` e `_` (bloqueia aspas, espaços e HTML) | ✔ | ✔ | — | O nome de usuário aceita apenas letras sem acento, números, ponto, hífen e sublinhado. | V05, V06 |
| Nome de usuário | Não pode repetir | — | ✔ | `UNIQUE` | Este nome de usuário já está em uso. | C04, C11 |
| E-mail | Obrigatório e em formato válido (`FILTER_VALIDATE_EMAIL`) | ✔ | ✔ | `NOT NULL` | Informe um e-mail válido. | V07, V08 |
| E-mail | Até 200 caracteres | `maxlength` | ✔ | `VARCHAR(200)` | O e-mail deve ter no máximo 200 caracteres. | V09 |
| E-mail | Gravado em minúsculas e sem espaços nas pontas | — | ✔ | — | — | V10 |
| E-mail | Não pode repetir (sem diferenciar maiúsculas) | — | ✔ | `UNIQUE` | Este e-mail já está cadastrado. | C05 |
| Senha | Obrigatória no cadastro; opcional na edição (em branco mantém a atual) | ✔ | ✔ | `NOT NULL` | Informe a senha. | V11, V18, C08 |
| Senha | De 8 a 72 caracteres (72 é o limite do bcrypt) | ✔ | ✔ | — | A senha deve ter no mínimo 8 / no máximo 72 caracteres. | V12, V13 |
| Senha | Letra maiúscula, letra minúscula e número | ✔ | ✔ | — | A senha deve conter letra maiúscula, letra minúscula e número. | V14, V15 |
| Senha | Não pode conter o nome de usuário | — | ✔ | — | A senha não pode conter o nome de usuário. | V16 |
| Confirmar senha (1º admin) | Igual à senha | — | ✔ | — | As senhas não conferem. | A01 |
| Perfil | Somente `ADMIN` ou `FUNCIONARIO` | `select` | ✔ | `ENUM` | Perfil inválido. | V17 |
| Perfil | O último administrador não pode virar funcionário | — | ✔ | — | Não é possível remover o perfil do único administrador. | C19 |
| Exclusão | Não pode excluir o próprio usuário | — | ✔ | — | Você não pode excluir o próprio usuário. | C14, print 11 |
| Exclusão | Não pode excluir o único administrador | — | ✔ | — | Não é possível excluir o único administrador. | C18 |
| Todos os formulários | Token CSRF válido | — | ✔ | — | Requisição inválida. Atualize a página e tente novamente. | H08, H13 |

Respostas do cadastro (fetch/JSON): **200** = sucesso, **422** = dados inválidos (com o erro de cada campo), **403** = token CSRF inválido.

## 2. Estratégias de segurança aplicadas

| Ameaça | Estratégia | Arquivo |
|---|---|---|
| Senha vazada do banco | `password_hash` (bcrypt, custo 12, salt aleatório), `password_verify` e `password_needs_rehash`; a senha nunca volta nas consultas | `infra/usuarios.php` |
| SQL Injection | *Prepared statements* em todas as consultas com dados do usuário; IDs convertidos com `(int)` | `infra/usuarios.php` |
| Dados inválidos enviados sem passar pela tela | Validação completa no backend + restrições do banco (`UNIQUE`, `ENUM`, `NOT NULL`) | `infra/validacao-usuario.php`, `database/db_sa.sql` |
| Força bruta no login | Bloqueio por 15 min depois de 5 erros; mensagem genérica; mesmo tempo de resposta para usuário inexistente | `infra/usuarios.php` |
| Roubo ou fixação de sessão | Cookie `HttpOnly` + `SameSite=Strict`, `session.use_strict_mode`, `session_regenerate_id` no login, expiração após 30 min sem uso | `infra/seguranca.php`, `infra/verifica-login.php`, `public/tela-login.php` |
| CSRF | Token por sessão em todo formulário que altera dados, conferido com `hash_equals` | `infra/seguranca.php` |
| XSS | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` em toda saída; `textContent` no JavaScript; `X-Content-Type-Options: nosniff` | telas de usuários, `script/*.js` |
| Clickjacking | `X-Frame-Options: DENY` | `infra/seguranca.php` |
| Acesso indevido | Perfil `ADMIN`/`FUNCIONARIO`; `exigir_admin()` nas telas de usuários (HTTP 403) | `infra/seguranca.php` |
| Vazamento de detalhes técnicos | `display_errors = 0`; erro de conexão genérico na tela, detalhe no log | `infra/seguranca.php`, `infra/conexao.php` |

## 3. Como executar os testes

Pré-requisito: MySQL/MariaDB ligado (no XAMPP, iniciar o **MySQL** no painel).

```bash
# Testes de unidade e integração (cria e apaga o banco sa_ferrorama_testes)
php teste/testes-usuarios.php

# Testes HTTP de ponta a ponta
DB_NAME=sa_ferrorama_http php -S localhost:8080      # terminal 1
php teste/testes-http.php http://localhost:8080      # terminal 2
```

> No Windows (PowerShell) o servidor do terminal 1 sobe com: `$env:DB_NAME="sa_ferrorama_http"; php -S localhost:8080`

## 4. Resultado

| Suíte | Testes | Passaram | Falharam | Saída completa |
|---|---|---|---|---|
| Validações do backend (V) | 19 | 19 | 0 | [`resultado-testes-usuarios.txt`](evidencias/resultado-testes-usuarios.txt) |
| Cadastro de Administrador (A) | 5 | 5 | 0 | idem |
| CRUD de usuários (C) | 19 | 19 | 0 | idem |
| Proteção das senhas (P) | 6 | 6 | 0 | idem |
| Login e bloqueio (L) | 6 | 6 | 0 | idem |
| SQL Injection (S) | 12 | 12 | 0 | idem |
| HTTP ponta a ponta (H) | 19 | 19 | 0 | [`resultado-testes-http.txt`](evidencias/resultado-testes-http.txt) |
| **Total** | **86** | **86** | **0** | |

Ambiente: PHP 8.3.6 e MariaDB 10.11 (compatível com o MySQL do XAMPP).

## 5. Evidências visuais (pasta `evidencias/img`)

| Print | O que mostra |
|---|---|
| 01 | Login sem nenhum administrador: aparece o link de primeiro acesso |
| 02 | Cadastro de administrador recusado pelo servidor (e-mail, senha fraca e confirmação) |
| 03 | Administrador criado; mensagem na tela de login |
| 04 | Tela de usuários acessada pelo administrador |
| 05 | Validação no navegador |
| 06 | Funcionário cadastrado com sucesso |
| 07 | Nome e e-mail repetidos recusados pelo servidor |
| 08 | Listagem com perfis Administrador e Funcionário |
| 09 | Edição recusada pelo servidor (e-mail de outro usuário e senha curta) |
| 10 | Edição salva com sucesso |
| 11 | Tentativa de excluir o próprio usuário, bloqueada |
| 12 | Exclusão realizada |
| 13 | SQL Injection (`' OR '1'='1`) no login, recusado |
| 14 | Usuário bloqueado após 5 senhas erradas |
| 15 | Funcionário tentando abrir a tela de usuários (acesso negado) |

Consulta no banco mostrando apenas hashes bcrypt e as restrições `UNIQUE`: [`consulta-banco-senhas.txt`](evidencias/consulta-banco-senhas.txt).
