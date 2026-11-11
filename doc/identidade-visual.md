# Identidade visual do FerroMonitor

Registro do processo de criação da identidade visual do frontend da Ferrorama: o que pesquisamos, o que decidimos, o que mudou nas telas e como testamos. A pesquisa anterior da equipe sobre o tema está em `doc/pesquisa-id-visual.md`; este documento parte dela.

## 1. Ponto de partida: auditoria das telas

Antes de mudar qualquer coisa, abrimos todas as telas logadas como administrador, no computador (1366 × 768) e no celular (iPhone 13, 390 × 844), e tiramos prints de cada uma. Os problemas encontrados foram estes:

| # | Problema | Onde | Por que atrapalha |
|---|----------|------|-------------------|
| P1 | A navbar não mostrava em qual página o usuário estava | Todas | O usuário perde a referência ao navegar |
| P2 | No celular o cabeçalho ocupava 252 px de 844 (30% da tela) e o menu quebrava em pontos diferentes | Todas | Sobra pouco espaço para o conteúdo |
| P3 | Duas fontes diferentes: Arial no login e a fonte do sistema operacional no resto | Login × demais | As telas pareciam de sistemas diferentes |
| P4 | Status (ATIVO, MANUTENCAO...) com 10 px de altura e diferença só pela cor do fundo | Listagens, dashboard | Difícil de ler e depende só da cor |
| P5 | Borda dos campos com contraste de 1,3:1 sobre o branco | Formulários | O campo quase some (o mínimo para componentes é 3:1) |
| P6 | Campos obrigatórios sem nenhuma marcação | Formulários | O usuário só descobre ao tentar salvar |
| P7 | Foco do teclado no azul claro padrão do Bootstrap, fora da paleta e pouco visível | Todas | Quem navega pelo teclado não vê onde está |
| P8 | Ao errar a senha, o login apagava também o nome de usuário | Login | Obriga a digitar tudo de novo |
| P9 | No celular, nomes nas tabelas quebravam em até 3 linhas ("Ramal sem Saída") | Listagens | Linhas altas e desalinhadas (78 px contra 58 px das outras) |
| P10 | Botão INATIVAR desabilitado (na própria conta) parecia só um botão mais claro | Usuários | Não fica claro que a ação está bloqueada |
| P11 | CANCELAR em cinza sólido disputava atenção com a ação principal | Formulários | Duas ações com o mesmo peso visual |
| P12 | CSS do login dentro de `<style>` na página e Bootstrap 5.3.8 só nessa tela | Login | Fugia do padrão da equipe (CSS separado em `assets/img/style/`) |

![Antes: listagem de trens](img/antes-desktop-trens.png)
![Antes: trens no celular](img/antes-celular-trens.png)

## 2. Referências visuais e o que aprendemos

| Referência | O que observamos | O que levamos para o FerroMonitor |
|------------|------------------|-----------------------------------|
| Placas de estação e sinalização ferroviária (ex.: o alfabeto *Rail Alphabet* das ferrovias britânicas) | Letras sem serifa, condensadas, em caixa alta, sobre fundo escuro, feitas para serem lidas de longe e rápido | Títulos, abas e cabeçalhos de tabela em fonte condensada; topo azul escuro com texto claro |
| Sinais luminosos de ferrovia | O significado vem da posição e da forma, não só da cor (um daltônico também precisa ler o sinal) | Cada status tem uma forma própria: círculo (ativo), triângulo (atenção), losango (erro), quadrado (inativo) |
| A faixa amarela da plataforma e o trilho com dormentes | O amarelo marca o limite e chama atenção para o que importa | Uma faixa "trilho" (dois trilhos dourados e dormentes) embaixo do cabeçalho e no login; o dourado só aparece em pontos de atenção: aba atual, foco, linha das caixas |
| SoftExpert e SAP (já usados na pesquisa anterior) | Sistemas de gestão com navegação fixa no topo, conteúdo em caixas e tabelas densas | Mantivemos a estrutura de caixas com cabeçalho e tabelas, mas com identidade própria |
| Templates prontos de painel administrativo | Cantos muito arredondados, sombras grandes, gradientes e cartões todos iguais | Fizemos o contrário: cantos de 4 a 6 px, sombra quase invisível, nada de gradiente nas telas internas |

## 3. Teoria das cores aplicada

- **Harmonia complementar.** O azul petróleo `#1b3f53` (matiz ≈ 201°) e o ouro `#daa301` (matiz ≈ 45°) estão quase em lados opostos do círculo cromático. Por isso o dourado salta aos olhos sobre o azul, e usamos esse contraste só onde queremos atenção.
- **Escala monocromática do azul** para a estrutura (topo, títulos, botões, linha em foco), assim o sistema tem uma cor só e não fica carregado.
- **Regra 60-30-10.** Mais ou menos 60% neutros (fundo cinza-azulado e branco), 30% azul e 10% dourado.
- **Psicologia das cores no contexto.** Azul passa estabilidade e controle, o que combina com monitoramento. O amarelo-ouro vem da sinalização de segurança (cuidado, atenção). Verde, amarelo e vermelho dos estados seguem o mesmo significado dos sinais de ferrovia.
- **Cor nunca sozinha.** Todo estado tem texto e forma, e toda mensagem tem ícone, além da cor.

![Estudo de paleta com os contrastes](img/paleta.png)

## 4. Paleta escolhida

Todas as cores ficam como variáveis no começo de `assets/img/style/style.css`, então uma mudança vale para o sistema inteiro.

| Variável | HEX | Função |
|----------|-----|--------|
| `--azul` | `#1b3f53` | Topo, títulos, cabeçalho das caixas, botão principal |
| `--azul-escuro` | `#0f2a38` | Hover e clique do botão principal, fundo do trilho |
| `--azul-claro` | `#e6eef3` | Linha da tabela sob o mouse, hover do CANCELAR |
| `--ouro` | `#daa301` | Trilho, aba atual, linha das caixas, anel de foco, botão ENTRAR |
| `--ouro-claro` | `#fdf3d4` | Fundo dos ícones dos atalhos da Home |
| `--fundo` | `#f2f4f6` | Fundo das páginas |
| (branco) | `#ffffff` | Superfícies: caixas, tabelas, campos |
| `--borda` | `#d5dde3` | Borda das caixas e linhas das tabelas |
| `--borda-campo` | `#7f909c` | Borda dos campos de formulário |
| `--texto` | `#1c2a33` | Texto principal |
| `--texto-suave` | `#55646e` | Subtítulos, textos de ajuda, cabeçalho das tabelas |
| `--ok` / `--ok-fundo` | `#1d6b42` / `#e3f2e9` | Sucesso, ATIVO, PRONTO |
| `--atencao` / `--atencao-fundo` | `#7a4f00` / `#fff1c7` | Atenção, MANUTENCAO, ALERTA |
| `--erro` / `--erro-fundo` | `#a4231b` / `#fbe3e1` | Erro, INATIVAR, campo inválido |
| `--info` / `--info-fundo` | `#1f5a80` / `#e2edf5` | Mensagens informativas |

Decidimos **não usar o dourado como cor de texto sobre fundo claro**: ele tem só 2,3:1 sobre o branco. Ele aparece como faixa, borda ou fundo (com texto azul por cima, 4,9:1).

## 5. Tipografia e elementos gráficos

- **Barlow Condensed** (títulos, abas, rótulos, botões, cabeçalhos de tabela e status). É condensada como as placas de sinalização, então cabe mais texto nos cabeçalhos das tabelas sem quebrar linha. A Barlow foi desenhada a partir das placas de estrada, ônibus e trens públicos da Califórnia, o que combina com o tema.
- **Barlow** (textos e dados): a mesma família em largura normal, para a leitura de frases e tabelas ficar confortável. Os números usam `tabular-nums` (todos os algarismos com a mesma largura), então valores de capacidade, KM e leituras ficam alinhados na coluna.
- Fonte de reserva: Arial, a mesma que a equipe usava antes, caso o Google Fonts não carregue.
- **Elementos gráficos com função:**
  - a faixa **trilho** embaixo do topo separa a navegação do conteúdo e repete o motivo da ferrovia;
  - a **barra dourada** à esquerda do título da página (como um marco de quilômetro) mostra onde o conteúdo começa;
  - as **formas dos status** (● ▲ ◆ ■) funcionam como os sinais;
  - o **botão desabilitado listrado** lembra uma cancela fechada e deixa claro que a ação está bloqueada;
  - os ícones do Bootstrap Icons continuam só na Home, nos atalhos e no resumo, onde ajudam a achar cada área.

## 6. Decisões de layout e hierarquia

1. **Nível 1, onde estou:** título da página grande (2,1 rem, condensado, azul) com a barra dourada. A aba atual da navbar fica em negrito e sublinhada de dourado (`aria-current="page"`, que leitores de tela também anunciam).
2. **Nível 2, o que tem aqui:** caixas com cabeçalho azul e linha dourada (CADASTRAR NOVO TREM, TRENS CADASTRADOS...).
3. **Nível 3, os dados:** cabeçalho da tabela em cinza condensado e dados em texto escuro. O ID fica em azul negrito, e a linha sob o mouse ganha fundo azul claro e uma marca dourada à esquerda.
4. **Ações:** uma ação principal por formulário (azul sólido). CANCELAR passou a ser branco com borda, para não competir. INATIVAR fica em vermelho contornado.
5. **Celular:** o topo deixa de ser fixo, esconde o subtítulo e o nome do usuário (a inicial continua) e as abas vão para duas linhas. O botão "+ NOVO" ocupa a largura toda. Nas tabelas as células não quebram linha e a tabela rola para o lado.

## 7. As três principais decisões de design

**Decisão 1: status com forma, não só cor.** Antes, ATIVO e MANUTENCAO só mudavam de cor e tinham 10 px. Agora cada status tem uma forma (● ▲ ◆ ■), fonte de 13 px e borda. *Justificativa:* cerca de 8% dos homens têm algum tipo de daltonismo, e a WCAG 1.4.1 pede que a cor não seja o único meio de passar uma informação. A ideia veio dos sinais de ferrovia, então também reforça a identidade.

**Decisão 2: o dourado é reservado para atenção.** Ele só aparece no trilho, na aba atual, na linha das caixas, no foco do teclado e no ENTRAR. *Justificativa:* se tudo é dourado, nada chama atenção. Como o dourado é a cor complementar do azul, usar pouco garante que ele funcione como sinal.

**Decisão 3: menu e cabeçalho compactos no celular.** Antes, no celular, o cabeçalho ocupava 252 px; agora ocupa 155 px. *Justificativa:* na tela pequena o que importa é a lista. Escondemos o que se repete (subtítulo do logo, nome do usuário) e mantivemos todas as abas visíveis em vez de escondê-las num menu sanduíche, porque o sistema tem só 6 ou 7 páginas.

## 8. Dificuldade encontrada e como resolvemos

**O CSS da equipe não "pegava" sobre o Bootstrap.** Nas páginas, o `style.css` é carregado antes do Bootstrap, então várias regras nossas (foco, bordas dos campos, alertas) eram sobrescritas. Trocar a ordem em todas as páginas mexeria em 12 arquivos, e a equipe já tinha visto a navbar quebrar numa troca assim. A solução foi:

- mudar as **variáveis do Bootstrap** (`--bs-body-font-family`, `--bs-btn-bg`, `--bs-table-hover-bg`, `--bs-alert-bg`...), que é o jeito previsto na documentação do Bootstrap 5.3;
- usar seletores um pouco mais específicos (`html:root`, `body .btn`, `.btn.btn-primary`) só onde não havia variável.

Assim a identidade vale em todas as telas mudando só o CSS, a navbar e o login.

**Segunda dificuldade: foco visível com o dourado.** O dourado sobre o branco tem só 2,3:1, abaixo dos 3:1 pedidos para indicadores de foco. Resolvemos com um anel duplo: contorno dourado de 3 px e, por fora, um anel azul (11:1 sobre o branco). Nos campos, a borda fica azul e o brilho dourado.

![Foco do teclado e validação](img/depois-desktop-foco.png)

## 9. O que mudou em cada tela

| Tela | Mudança |
|------|---------|
| Todas | Fontes Barlow e Barlow Condensed, paleta em variáveis, trilho sob o topo, aba atual marcada, foco visível, status com forma, alertas com ícone (✕ ! ✓ i) |
| Navbar (`public/navbar.php`) | Abas geradas por uma lista (código menor), aba atual com `aria-current`, logo leva para a Home, versão compacta no celular, sem `style=""` no HTML |
| Login | CSS saiu da página para `login.css`, mesma fonte do resto, trilho no rodapé, erro com ícone e anunciado por leitores de tela (`role="alert"`), errar a senha mantém o usuário digitado e volta o cursor para a senha |
| Formulários (trens, trilhos, sensores, usuários, relatórios e telas de edição) | Asterisco nos campos obrigatórios e o aviso "* campo obrigatório", borda dos campos com 3,3:1, campo inválido com borda e fundo vermelhos depois que o usuário interage (`:user-invalid`), CANCELAR secundário |
| Listagens | Cabeçalho condensado, números alinhados, linha em foco com marca dourada, status com forma, botão bloqueado listrado, linhas sem quebra no celular |
| Home e Dashboard | Números grandes em fonte condensada, cartão de alertas com borda vermelha quando há alerta, mesmas cores das outras telas |
| Landing page | Mesmas fontes do sistema |

![Depois: listagem de trens](img/depois-desktop-trens.png)
![Depois: trens no celular](img/depois-celular-trens.png)
![Antes: erro no login](img/antes-desktop-login-erro.png)
![Depois: erro no login](img/depois-desktop-login-erro.png)
![Depois: usuários com botão bloqueado](img/depois-desktop-usuarios.png)
![Depois: Home](img/depois-desktop-home.png)

## 10. Testes realizados

### Contraste (fórmula da WCAG 2.1, conferida no WebAIM Contrast Checker)

| Combinação | Contraste | Mínimo | Resultado |
|-----------|-----------|--------|-----------|
| Texto `#1c2a33` sobre o fundo `#f2f4f6` | 13,3:1 | 4,5:1 | Passa (AAA) |
| Texto suave `#55646e` sobre o branco | 6,1:1 | 4,5:1 | Passa |
| Branco sobre o azul `#1b3f53` | 11,2:1 | 4,5:1 | Passa (AAA) |
| Azul sobre o dourado (botão ENTRAR, perfil OPERADOR) | 4,9:1 | 4,5:1 | Passa |
| Dourado sobre o azul escuro (trilho) | 6,5:1 | 3:1 | Passa |
| Sucesso `#1d6b42` sobre `#e3f2e9` | 5,6:1 | 4,5:1 | Passa |
| Atenção `#7a4f00` sobre `#fff1c7` | 6,3:1 | 4,5:1 | Passa |
| Erro `#a4231b` sobre `#fbe3e1` | 6,1:1 | 4,5:1 | Passa |
| Borda do campo `#7f909c` sobre o branco | 3,3:1 | 3:1 | Passa (antes: 1,3:1) |
| Dourado sobre o branco | 2,3:1 | — | Não usado como texto nem sozinho no foco |

### Responsividade

| Tamanho | Antes | Depois |
|---------|-------|--------|
| Computador 1366 × 768 | Navbar numa linha, sem aba atual | Navbar numa linha com aba atual |
| Celular 390 × 844 | Cabeçalho com 252 px; linhas da tabela entre 58 e 78 px | Cabeçalho com 155 px; todas as linhas com 49 px |

Os prints de antes e depois de todas as telas, nos dois tamanhos, estão na pasta de evidências da entrega.

### Legibilidade e teclado

- Navegamos com Tab pelo formulário de cadastro de trens: o foco aparece nos campos e botões com o anel dourado e azul.
- Status no tamanho de 13 px (antes 10 px), legíveis no celular.
- Testamos com "reduzir movimento" ligado: as animações do login e da Home são desligadas.

### Funcionalidades (para garantir que nada quebrou)

Cadastrar um trem, inativá-lo (sai da lista de ativos), abrir o modal de Sair e sair, entrar como operador (não aparece a aba Usuários) e abrir as telas de edição (a aba certa fica marcada). Nenhum erro de JavaScript no console.

### Validação com a equipe

| Integrante | Telas revisadas | Observações | Ajuste feito |
|-----------|-----------------|-------------|--------------|
| | | | |
| | | | |
| | | | |

*(A equipe preenche depois de testar no próprio computador e celular.)*

## 11. Ferramentas utilizadas

- VS Code e XAMPP (PHP + MySQL) para desenvolver e rodar o sistema.
- Google Fonts (Barlow e Barlow Condensed).
- Bootstrap 5.3.3 e Bootstrap Icons 1.11.3.
- DevTools do navegador (modo celular) e Playwright para os prints nos dois tamanhos.
- WebAIM Contrast Checker para conferir os contrastes.
- **IA:** usamos o Claude (Anthropic) como apoio para auditar as telas, calcular os contrastes, propor a paleta em variáveis CSS e revisar o texto deste documento. As escolhas finais (manter o azul e o dourado da equipe, o tema do trilho, as formas dos status e o que mudar no celular) foram revisadas e aceitas pela equipe, e o código foi colado e commitado pelos integrantes.

## 12. Fontes consultadas

- W3C. *Web Content Accessibility Guidelines (WCAG) 2.1*, critérios 1.4.1, 1.4.3, 1.4.11 e 2.4.7: https://www.w3.org/TR/WCAG21/
- WebAIM. *Contrast Checker*: https://webaim.org/resources/contrastchecker/
- Nielsen Norman Group. *10 Usability Heuristics for User Interface Design*: https://www.nngroup.com/articles/ten-usability-heuristics/
- Google Fonts. *Barlow*: https://fonts.google.com/specimen/Barlow
- Google Fonts. *Barlow Condensed*: https://fonts.google.com/specimen/Barlow+Condensed
- Wikipedia. *Rail Alphabet*: https://en.wikipedia.org/wiki/Rail_Alphabet
- Wikipedia. *Railway signal*: https://en.wikipedia.org/wiki/Railway_signal
- Adobe Color. *Roda de cores e harmonias*: https://color.adobe.com/create/color-wheel
- Bootstrap. *CSS variables* (5.3): https://getbootstrap.com/docs/5.3/customize/css-variables/
- MDN. *:focus-visible*: https://developer.mozilla.org/en-US/docs/Web/CSS/:focus-visible
- MDN. *:user-invalid*: https://developer.mozilla.org/en-US/docs/Web/CSS/:user-invalid
