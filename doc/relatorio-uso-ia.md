# Relatório de uso de Inteligência Artificial – Etapa 10

Projeto **FerroMonitor** (SA Ferroramas) · Equipe: Caio Marques, Lucas Lazzarotti, Luis Pedro Mathias e Matheus Guesser

## 1. Ferramenta usada

| Item | Detalhe |
|------|---------|
| Ferramenta | **Claude**, assistente de IA da Anthropic, usado pelo claude.ai (Projetos com o Claude Code na nuvem) |
| Acesso ao projeto | Leitura do repositório `Lazzarott1/sa-ferrorama` |
| Forma de trabalho | A IA **não faz commit**. Ela entrega os arquivos, a equipe lê, cola no VS Code, testa e faz o commit |
| Coautoria | `Co-authored-by: Claude <noreply@anthropic.com>` no fim da mensagem de cada commit com código da IA |

## 2. Objetivo de cada uso

| Uso | Objetivo |
|-----|----------|
| 1 | Pesquisar e integrar uma API de clima na Home |
| 2 | Pesquisar e integrar uma API de CEP, com tratamento de erros |
| 3 | Refazer a Home com resumo da operação, clima e atalhos |
| 4 | Aplicar na Home o esquema do circuito do projeto [trem-visual-controle](https://github.com/icrcode/trem-visual-controle) |
| 5 | Criar a Landing Page |
| 6 | Corrigir a ligação entre a Landing Page e o login |
| 7 | Atualizar o README e documentar as APIs |

## 3. Principais prompts

Instruções fixas do projeto:

> Nunca dê commits nem pull requests. Dê os códigos. Sempre que você der um código, diga o nome do commit ideal para isso e nos lembre de dar commit.
>
> Tem como diminuir o tamanho do código delas com elas fazendo exatamente a mesma coisa? Se tiver, faça. Os códigos precisam ser simples e funcionais.

Pedidos da Etapa 10:

1. *"Com base nas outras entregas desenvolva a entrega 10"*, seguido do enunciado completo.
2. *"aplique o esquema do mapa que tem nesse repositório, na nossa tela home"*, com o link do trem-visual-controle.
3. *"manda separado por arquivos, pra eu copiar e colar no VS Code"*.
4. *"a landing page não está se comunicando corretamente com a tela de login"*.
5. *"sobre o relatório de uso da IA, peço que faça referente apenas à entrega 10"*.

## 4. Arquivos produzidos com a IA

| Arquivo | O que faz | Commit |
|---------|-----------|--------|
| `script/clima.js` | Consulta a Open-Meteo, traduz o código do tempo, mostra a hora da medição e da consulta, avisa sobre chuva ou vento e trata falhas | `add - api de clima e nova home` |
| `script/mapa.js` | Circuito da Home no esquema do trem-visual-controle. Os traçados dos trilhos e os nomes dos trechos vieram de lá; a IA reescreveu o movimento de forma mais curta, com agulhas de escolha automática, para-choque que inverte o sentido e o trecho atual do trem | `add - api de clima e nova home` |
| `public/tela-geral-home.php`, `assets/img/style/home.css`, `assets/img/style/style.css` | Home nova e navbar que quebra em linhas no celular | `add - api de clima e nova home` |
| `script/cep.js`, `public/trilhos/campos-cep.php` | Busca no ViaCEP e preenche o endereço, com mensagens de erro | `add - api de cep no cadastro de trilhos` |
| `public/trilhos/tela-cadastro-trilhos.php`, `tela-editar-trilhos.php`, `infra/validacoes-crud/valida-trilho.php`, `database/db_sa.sql` | Salvam e validam o endereço; coluna LOCAL na lista | `add - api de cep no cadastro de trilhos` |
| `index.html`, `assets/img/style/landing.css` | Landing Page | `add - landing page` |
| `public/tela-login.php`, `infra/validacoes-usuarios/logout.php` | Link do login para a Landing, usuário já logado vai direto para a Home e o "Sair" volta para a Landing | `add - ligacao entre landing page e login` |
| `README.md`, `doc/apis-externas.md`, `doc/relatorio-uso-ia.md` | Documentação | `add - readme, documentacao das apis e relatorio de ia` |

## 5. Alterações feitas pela equipe

| Arquivo | Quem revisou | O que mudou no código da IA |
|---------|--------------|-----------------------------|
| `script/clima.js` | | |
| `script/cep.js` | | |
| `script/mapa.js` | | |
| `public/tela-geral-home.php` | | |
| `index.html` | | |
| `public/tela-login.php` | | |

Também foram decisões da equipe: pedir o mapa no esquema do trem-visual-controle no lugar do traçado antigo, receber os arquivos separados para colar no VS Code e apontar que a Landing e o login não estavam bem ligados.

## 6. Testes

Feitos pela IA antes de entregar (PHP 8.3, MariaDB 10.11 e navegador automatizado Playwright):

| Teste | Resultado |
|-------|-----------|
| `php -l` em todos os PHP alterados | sem erro de sintaxe |
| CEP `123` | "CEP inválido: digite os 8 números", sem chamar a API |
| CEP `99999999` (API responde `erro`) | "CEP não encontrado", campos limpos |
| CEP `89201100` | campos preenchidos e trilho salvo com cidade e UF; a coluna LOCAL mostra Joinville/SC |
| APIs do CEP e do clima bloqueadas (sem internet) | as mensagens de falha aparecem e a página continua funcionando |
| CEP `123` enviado direto para o PHP, sem JavaScript | recusado: "O CEP deve ter 8 números" |
| Editar trilho e salvar o bairro | gravado no banco |
| Home e Landing em 1280 px e em celular (iPhone 13) | sem rolagem lateral; a navbar quebra em duas linhas |
| Home como operador | o atalho Usuários não aparece |
| Circuito da Home observado por 60 s | o trem passou pelas três agulhas, voltou no para-choque e o selo mostrou 13 trechos diferentes, sem erro no console |
| Landing → login → Landing → login → Home → Sair | todos os links levam à página certa; logado, "Acessar o sistema" vai direto para a Home; "Sair" volta para a Landing |

Feitos pela equipe com internet de verdade. O ambiente da IA não acessava as APIs, então nos testes dela as respostas foram simuladas, e as consultas reais são as da equipe:

- [ ] A Home mostra a temperatura atual de Joinville e o horário da medição
- [ ] O botão de atualizar muda o "consultado às"
- [ ] Um CEP real preenche logradouro, bairro, cidade e UF
- [ ] Um CEP inexistente mostra "CEP não encontrado"
- [ ] Com o Wi-Fi desligado, o clima e o CEP mostram a mensagem de falha
- [ ] Prints e aba Rede (F12) das consultas guardados para a entrega

## 7. Problemas encontrados e correções

| Problema | Como apareceu | Correção |
|----------|---------------|----------|
| A animação do trem da Home parou | O teste no navegador mostrou o erro "Unexpected end of input": ao reorganizar a Home, a IA cortou o script no lugar errado | O script foi copiado inteiro de novo e testado |
| A navbar saía da tela no celular | Print em tela de celular | Regra `@media` no `style.css` |
| O cartão do clima invadia o "Acesso rápido" no celular | Print em tela de celular | Retirada a altura fixa do cartão |
| O desenho da Landing ficava cortado nas bordas | Print da Landing | Ajuste do `viewBox` do SVG |
| A Landing não se ligava bem ao login: o login não tinha volta para a Landing, quem já estava logado caía no login de novo e o "Sair" não voltava para a Landing | Teste da equipe depois do commit | Link "Conhecer o FerroMonitor" no login, redirecionamento de quem já está logado e logout para a Landing |
| O código do trem-visual-controle era grande demais (8 arquivos JS) | Leitura do repositório | Só os traçados foram reaproveitados, e o movimento foi reescrito num único `mapa.js` |

## 8. Reflexão

**Onde ajudou:** a IA comparou as APIs de clima e de CEP, explicou as respostas em JSON e escreveu o tratamento de erros, que a gente provavelmente faria só para o caso de sucesso. Também adaptou o circuito do trem-visual-controle ao visual do FerroMonitor e montou a Landing com a mesma identidade das outras telas.

**Limitações:** ela supôs que a ferrovia fica em Joinville para consultar o clima, e isso teve de ser conferido. Errou ao reorganizar o script da Home e não percebeu sozinha que a Landing e o login estavam mal ligados; foi a equipe que achou esse problema. O ambiente dela não acessava as APIs da internet, então a prova de que as consultas funcionam de verdade foi feita por nós.

**Conclusão:** a IA acelerou bastante a entrega, mas todo o código passou por leitura, teste e commit da equipe, que continua responsável pelo que foi entregue.
