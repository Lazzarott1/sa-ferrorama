# Requisitos Não Funcionais — FerroMonitor

Revisão feita junto com a entrega do CRUD de Funcionários/Usuários, do Cadastro de Administrador e das estratégias de segurança.
Cada requisito tem um **critério de verificação**, para que dê para provar se ele foi atendido. Os códigos de teste (V, A, C, P, L, S, H) estão em [`validacoes-e-testes.md`](validacoes-e-testes.md).

## 1. Requisitos revisados (já existiam no README)

| Código | Requisito | Situação na revisão | Critério de verificação |
|---|---|---|---|
| RNF01 | **Desempenho**: processamento dos dados dos sensores em até 500 ms. | Mantido. Incluído limite para o módulo de usuários: login em até 1 s e telas de cadastro/listagem em até 500 ms. O login é mais lento de propósito (bcrypt custo 12). | Medido no ambiente de testes: login com média de 278 ms; listagem de usuários com 0,25 ms de consulta. |
| RNF02 | **Disponibilidade** de 99,9%. | Mantido como meta para produção. O XAMPP local não garante esse valor. | Monitoramento do servidor em produção (fora do escopo desta entrega). |
| RNF03 | **Interface responsiva** e compatível com os principais navegadores. | Revisado: os cartões das telas de usuários tinham largura fixa (600 px e 900 px). Agora usam `max-width: calc(100vw - 32px)` e não passam da largura da tela. | Abrir as telas em 360 px de largura sem rolagem horizontal no formulário. |
| RNF04 | **Acessibilidade** (WCAG) e alto contraste em alertas críticos. | Mantido e reforçado: cada erro aparece embaixo do campo certo (`invalid-feedback`) e em texto, não só em cor; a tela de login respeita `prefers-reduced-motion`. | Prints 02, 05, 07 e 09; teste manual com o modo "reduzir movimento" do sistema. |
| RNF05 | **Escalabilidade** para milhares de sensores (arquitetura modular). | Mantido. As regras de usuários passaram para um módulo próprio (`infra/usuarios.php`), que as telas reaproveitam. | Revisão de código. |
| RNF06 | **Integridade dos dados**. | Revisado e detalhado: o banco agora impede nome/e-mail repetidos (`UNIQUE`) e perfil fora da lista (`ENUM`). Toda entrada é validada no backend e o primeiro administrador é criado dentro de uma transação. | Testes C04, C05, C06, A04; `consulta-banco-senhas.txt` (erro 1062 ao forçar duplicado direto no banco). |
| RNF07 | **Segurança das sessões dos usuários**. | Revisado: o texto antigo era genérico e não dava para testar. Agora: cookie `HttpOnly` + `SameSite=Strict`, novo ID de sessão a cada login, sessão expira após 30 min sem uso e o logout destrói a sessão. | Testes H03, H05 e H19. |

## 2. Novos requisitos não funcionais

| Código | Requisito | Como foi atendido | Critério de verificação |
|---|---|---|---|
| RNF08 | **Armazenamento seguro de senhas**: senha nunca gravada nem exibida em texto puro. | `password_hash()` com bcrypt custo 12 e salt aleatório; a coluna `senha` não é lida na listagem nem na busca; hashes antigos são atualizados no login (`password_needs_rehash`). | Testes P01 a P06; `consulta-banco-senhas.txt`. |
| RNF09 | **Política de senha forte**. | De 8 a 72 caracteres, com letra maiúscula, letra minúscula e número, e sem conter o nome de usuário. | Testes V11 a V16 e V19. |
| RNF10 | **Proteção contra SQL Injection**. | 100% das consultas com dados do usuário usam *prepared statements* (`mysqli_prepare` + `bind_param`). IDs são convertidos com `(int)`. Não existe SQL montado por concatenação. | Testes S01 a S12 e H06; print 13. |
| RNF11 | **Validação obrigatória no servidor**. | `infra/validacao-usuario.php` valida tudo no backend, mesmo que o JavaScript seja desligado ou burlado (Postman, curl). Dados inválidos retornam HTTP 422 com o erro de cada campo. | Testes V01 a V19, C06 e H09. |
| RNF12 | **Controle de acesso por perfil**. | Perfis `ADMIN` e `FUNCIONARIO`. Só o administrador acessa a gestão de usuários (os outros recebem HTTP 403). O sistema nunca fica sem administrador. | Testes H17, H18, C18 e C19; print 15. |
| RNF13 | **Proteção contra força bruta no login**. | Depois de 5 senhas erradas, o usuário fica bloqueado por 15 minutos. A mensagem de erro é a mesma para usuário inexistente e para senha errada, então ninguém descobre quais usuários existem. | Testes L02, L03, L05 e L06; print 14. |
| RNF14 | **Proteção contra CSRF**. | Todo formulário que altera dados envia um token aleatório da sessão, conferido com `hash_equals`. | Testes H08, H13 e H14. |
| RNF15 | **Proteção contra XSS e clickjacking**. | Tudo o que é exibido passa por escape (`htmlspecialchars` com `ENT_QUOTES`); o JavaScript usa `textContent`; o servidor envia os cabeçalhos `X-Frame-Options: DENY` e `X-Content-Type-Options: nosniff`. | Teste V06 e H02. |
| RNF16 | **Tratamento de erros sem vazar informação**. | `display_errors` desligado: o detalhe técnico vai para o log e a tela mostra só uma mensagem amigável. Antes as telas mostravam o erro do MySQL. | Revisão de código (`infra/seguranca.php` e `infra/conexao.php`). |
| RNF17 | **Credenciais fora do código**. | `infra/conexao.php` lê `DB_HOST`, `DB_USER`, `DB_PASS` e `DB_NAME` do ambiente. Sem essas variáveis, usa o padrão do XAMPP. Em produção, recomenda-se um usuário MySQL só com SELECT/INSERT/UPDATE/DELETE. | Os testes usam bancos separados via `DB_NAME`. |
| RNF18 | **Testabilidade**. | Os testes automatizados rodam com um comando, em um banco temporário próprio, sem mexer no `sa_teste`. | `php teste/testes-usuarios.php` e `php teste/testes-http.php`. |

## 3. Pontos de atenção (fora do escopo desta entrega)

- **HTTPS em produção**: o cookie de sessão só recebe `Secure` quando a página é acessada por HTTPS. No XAMPP local o acesso é por HTTP.
- **Bloqueio por IP**: o bloqueio é por conta de usuário. Um limite por IP pode ser acrescentado se o sistema for publicado na internet.
- **`public/relatorios/relatorios.php`** já estava com erro antes desta entrega: o `require_once('../infra/conexao.php')` usa um caminho errado e a página consulta uma tabela `relatorios` que não existe no `db_sa.sql`. Fica registrado para uma tarefa própria.
