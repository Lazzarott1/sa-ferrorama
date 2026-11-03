# Pesquisa sobre APIs

Projeto: **FerroMonitor** (SA Ferrorama)

Nesta pesquisa a equipe estudou o que são APIs, como elas funcionam e como podem ser usadas no desenvolvimento de sistemas. Sempre que deu, tentamos trazer os exemplos para o nosso próprio projeto, usando as tabelas de trens, sensores e trilhos que já temos no banco.

---

## 1. O que é uma API e para que serve

API é a sigla de *Application Programming Interface*, ou Interface de Programação de Aplicações. De forma simples, é o jeito combinado que um programa usa para pedir alguma coisa a outro programa e receber a resposta.

A comparação que mais nos ajudou a entender foi a de um restaurante. O cliente não entra na cozinha para fazer a própria comida: ele olha o cardápio, faz o pedido para o garçom, e o garçom traz o prato. Na API acontece a mesma coisa. O sistema que precisa da informação é o cliente, a documentação da API é o cardápio, a API é o garçom e o servidor com o banco de dados é a cozinha. Quem pede não precisa saber como as coisas funcionam lá dentro, só precisa saber pedir do jeito certo.

Percebemos que a API serve principalmente para:

- fazer sistemas diferentes conversarem, mesmo que sejam feitos em linguagens diferentes;
- reaproveitar serviços que já existem, como consulta de CEP ou pagamento, em vez de programar tudo do zero;
- proteger o banco de dados, já que o outro sistema só tem acesso ao que a API libera;
- permitir que o mesmo back-end atenda um site, um aplicativo de celular e até sensores.

Vale dizer que "API" é um termo bem amplo (as funções do `mysqli` no PHP, por exemplo, também são uma API). Aqui nós focamos nas **APIs Web**, que funcionam pela internet usando HTTP.

---

## 2. Como os sistemas se comunicam por meio de APIs

A comunicação segue o modelo **cliente-servidor**. O cliente é quem pede (um navegador, um aplicativo, outro sistema PHP ou até um sensor) e o servidor é quem recebe o pedido, processa e responde.

Um exemplo do caminho que uma consulta faria no FerroMonitor:

```
Painel do FerroMonitor  --->  GET /api/trens/3  --->  API (PHP)
                                                        |
                                                        v
                                         SELECT ... FROM trens WHERE id_trem = 3
                                                        |
Painel do FerroMonitor  <---  200 OK + JSON do trem  <--+
```

Ou seja: o cliente monta o pedido, o pedido vai pela rede, o servidor confere se o cliente tem permissão, busca ou altera os dados no banco e devolve uma resposta com um código (dizendo se deu certo ou não) e os dados. Depois o cliente usa esses dados como quiser.

Um detalhe importante que aprendemos é que o HTTP é **stateless** (sem estado). O servidor não "lembra" das requisições anteriores, então cada pedido precisa levar tudo que o servidor precisa saber, inclusive a identificação de quem está pedindo.

---

## 3. O que são APIs REST

REST (*Representational State Transfer*) é um estilo de arquitetura para montar APIs. Ele foi descrito por Roy Fielding na tese de doutorado dele, em 2000. Não é uma linguagem nem uma ferramenta, é um conjunto de regras de organização. Quando uma API segue essas regras, ela é chamada de API REST ou RESTful.

As ideias principais do REST que encontramos foram:

- **cliente e servidor separados**, cada um podendo mudar sem quebrar o outro;
- **sem estado**, como explicamos acima;
- **cache**, ou seja, a resposta pode avisar se pode ser guardada para não precisar pedir de novo;
- **interface uniforme**, onde todo recurso é acessado do mesmo jeito;
- **sistema em camadas**, onde o cliente não precisa saber se existe algum servidor intermediário no caminho.

No REST, tudo é tratado como um **recurso**: trens, sensores, trilhos, usuários. Cada recurso tem um endereço próprio, por exemplo `/api/trens` (todos os trens) ou `/api/trens/3` (o trem de id 3).

Uma regra que achamos interessante é que a URL deve ter **substantivos**, e não verbos. Quem diz a ação é o método HTTP. Então o certo é `DELETE /api/trens/3`, e não algo como `/api/deletarTrem?id=3`.

---

## 4. Os métodos HTTP

Os métodos HTTP indicam o que o cliente quer fazer com o recurso. Eles combinam direitinho com o CRUD que já fazemos no sistema:

| Método | O que faz | No CRUD |
|---|---|---|
| GET | Busca dados | Read |
| POST | Cria um registro novo | Create |
| PUT | Substitui o registro inteiro | Update |
| PATCH | Altera só uma parte do registro | Update |
| DELETE | Apaga um registro | Delete |

Dois conceitos apareceram bastante nas fontes. Um método é **seguro** quando só lê e não altera nada (é o caso do GET). E um método é **idempotente** quando repetir a mesma requisição várias vezes dá o mesmo resultado de fazer uma vez só. Por exemplo, apagar o trem 3 uma vez ou cinco vezes termina igual: o trem 3 não existe mais. Já mandar o mesmo POST cinco vezes cria cinco trens. GET, PUT e DELETE são idempotentes; POST não é, e o PATCH depende do que ele faz.

Alguns exemplos que montamos:

**GET** – listar os trens:

```http
GET /api/trens HTTP/1.1
Host: ferromonitor.local
Authorization: Bearer abc123
```

**POST** – cadastrar um trem:

```http
POST /api/trens HTTP/1.1
Content-Type: application/json
Authorization: Bearer abc123

{
  "nome_trem": "Expresso Joinville",
  "carga_trem": "Contêineres",
  "status_trem": "ativo"
}
```

**PUT** – trocar todos os dados do trem 3 (aqui precisa mandar o registro completo):

```http
PUT /api/trens/3 HTTP/1.1
Content-Type: application/json

{
  "nome_trem": "Expresso Joinville",
  "carga_trem": "Grãos",
  "status_trem": "manutencao"
}
```

**PATCH** – mudar só o status do trem 3 (o resto continua como está):

```http
PATCH /api/trens/3 HTTP/1.1
Content-Type: application/json

{
  "status_trem": "parado"
}
```

**DELETE** – apagar o trem 3:

```http
DELETE /api/trens/3 HTTP/1.1
Authorization: Bearer abc123
```

A diferença entre PUT e PATCH foi a que mais gerou dúvida na equipe. O jeito que encontramos de lembrar é: PUT troca tudo, PATCH só remenda.

---

## 5. Requisições e respostas HTTP

Toda conversa HTTP é feita de pares: o cliente manda uma **requisição** e o servidor devolve uma **resposta**.

A requisição tem o método, o endereço, os cabeçalhos (informações extras, como o tipo de dado enviado e o token de acesso) e, às vezes, um corpo com os dados:

```http
POST /api/sensores HTTP/1.1
Host: ferromonitor.local
Content-Type: application/json
Authorization: Bearer abc123

{
  "nome_sensor": "Temperatura KM 12",
  "categoria_sensor": "Temperatura",
  "tipo_sensor": "Analógico",
  "trilho_sensor": 2,
  "status_sensor": "ativo"
}
```

A resposta começa com um **código de status**, depois vêm os cabeçalhos e, normalmente, o corpo em JSON:

```http
HTTP/1.1 201 Created
Content-Type: application/json; charset=utf-8

{
  "id_sensor": 15,
  "nome_sensor": "Temperatura KM 12",
  "status_sensor": "ativo"
}
```

O primeiro número do código já diz o tipo de resultado: os **2xx** são sucesso, os **4xx** são erro de quem pediu e os **5xx** são erro do servidor. Os que mais vamos usar são:

| Código | Significado |
|---|---|
| 200 OK | Deu certo |
| 201 Created | Registro criado |
| 204 No Content | Deu certo, mas não tem nada para devolver (comum no DELETE) |
| 400 Bad Request | O pedido veio errado, por exemplo um JSON quebrado |
| 401 Unauthorized | Não mandou o token ou ele é inválido |
| 403 Forbidden | Está identificado, mas não tem permissão |
| 404 Not Found | O registro não existe |
| 405 Method Not Allowed | Esse método não é aceito ali |
| 500 Internal Server Error | Erro no servidor |

Quando dá erro, o ideal é a API explicar o que aconteceu, por exemplo:

```json
{
  "erro": "Trem não encontrado"
}
```

---

## 6. O que são endpoints

Endpoint é cada "ponto de acesso" da API, ou seja, a junção de um método com um endereço. Voltando ao restaurante, cada endpoint seria um item do cardápio.

Pensando no FerroMonitor, a API poderia ter endpoints assim:

| Método e endereço | O que faz |
|---|---|
| `GET /api/trens` | Lista os trens |
| `GET /api/trens/3` | Mostra o trem 3 |
| `POST /api/trens` | Cadastra um trem |
| `PATCH /api/trens/3` | Altera parte do trem 3 |
| `DELETE /api/trens/3` | Apaga o trem 3 |
| `GET /api/sensores?status=ativo` | Lista só os sensores ativos |
| `GET /api/trilhos/2/sensores` | Lista os sensores do trilho 2 |

Algumas boas práticas que vimos: usar nomes no plural, letras minúsculas, usar o `?` para filtros e colocar a versão no endereço (`/api/v1/trens`) para não quebrar quem já usa a API quando ela mudar.

No XAMPP, endereços "bonitos" como `/api/trens/3` precisam de configuração no `.htaccess`. Para começar, dá para usar algo mais simples como `/api/trens.php?id=3`, que funciona do mesmo jeito.

---

## 7. Troca de dados em JSON

JSON (*JavaScript Object Notation*) é o formato de texto mais usado para mandar dados entre sistemas hoje em dia. Apesar do nome ter JavaScript, praticamente toda linguagem lê e escreve JSON, inclusive o PHP.

As regras básicas são poucas: objetos ficam entre chaves `{ }`, listas ficam entre colchetes `[ ]`, textos sempre usam aspas duplas, e também existem números, `true`, `false` e `null`. Não pode ter vírgula sobrando no final e não existe comentário em JSON.

Um exemplo de como um trilho com seus sensores poderia vir da API:

```json
{
  "id_trilho": 2,
  "nome_trilho": "Trecho Norte",
  "km_trilho": 48.5,
  "status_trilho": "operacional",
  "sensores": [
    { "id_sensor": 7, "nome_sensor": "Temperatura KM 12", "status_sensor": "ativo" },
    { "id_sensor": 9, "nome_sensor": "Presença KM 30", "status_sensor": "inativo" }
  ]
}
```

No PHP usamos duas funções: `json_encode` transforma um array em JSON (para mandar) e `json_decode` faz o caminho contrário (para receber).

```php
<?php
// array do PHP virando JSON
$trem = ['id_trem' => 3, 'nome_trem' => 'Expresso Joinville', 'status_trem' => 'ativo'];
echo json_encode($trem, JSON_UNESCAPED_UNICODE);

// JSON virando array do PHP
$texto = '{"id_trem":3,"nome_trem":"Expresso Joinville"}';
$dados = json_decode($texto, true); // o true faz voltar um array
echo $dados['nome_trem'];
```

O `JSON_UNESCAPED_UNICODE` serve para os acentos aparecerem normais, senão "Contêineres" vira `Cont\u00eaineres`.

---

## 8. Diferença entre uma API e um sistema web tradicional

As telas que fizemos até agora no FerroMonitor são um sistema web tradicional: o PHP monta a página HTML inteira, com Bootstrap, menu e tabela, e o navegador só mostra. Quem usa é uma pessoa.

Na API é diferente. O servidor devolve só os dados, em JSON, e quem recebe decide o que fazer com eles. Quem usa normalmente é outro programa.

Resumindo as diferenças que encontramos:

- o sistema tradicional devolve **página HTML**; a API devolve **dados**;
- no tradicional os dados chegam por formulário (`$_POST`); na API chegam como JSON no corpo da requisição;
- o tradicional usa quase só GET e POST; a API usa todos os métodos;
- o tradicional costuma usar sessão (`$_SESSION`); a API normalmente usa um token em cada requisição;
- no tradicional o erro aparece na tela; na API ele vem como código de status e JSON.

Exemplo: `listatrens.php` devolve uma página com a tabela de trens pronta, enquanto `GET /api/trens` devolveria só algo como `[{"id_trem":1,...},{"id_trem":2,...}]`. Os dois podem existir juntos no mesmo projeto.

---

## 9. Como uma aplicação PHP pode consumir uma API

"Consumir" uma API é fazer pedidos para ela e usar a resposta. No PHP o jeito mais comum é com o **cURL**. (No XAMPP ele normalmente já vem ativado; se não vier, é só tirar o `;` da linha `extension=curl` no `php.ini`.)

Testamos com o ViaCEP, uma API pública e gratuita de consulta de CEP:

```php
<?php
$cep = '01001000';

$ch = curl_init("https://viacep.com.br/ws/{$cep}/json/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // devolve a resposta numa variável
curl_setopt($ch, CURLOPT_TIMEOUT, 10);          // desiste depois de 10 segundos

$resposta = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($resposta === false) {
    die('Erro na conexão: ' . curl_error($ch));
}

$endereco = json_decode($resposta, true);

if ($status == 200 && !isset($endereco['erro'])) {
    echo "Rua: {$endereco['logradouro']}<br>";
    echo "Cidade: {$endereco['localidade']} - {$endereco['uf']}";
} else {
    echo 'CEP não encontrado';
}
```

O que acontece aqui: o `curl_init` prepara o pedido, o `curl_setopt` configura, o `curl_exec` envia e recebe a resposta, o `curl_getinfo` pega o código de status e o `json_decode` transforma o JSON num array para conseguirmos usar.

A resposta que veio foi parecida com esta (cortamos alguns campos):

```json
{
  "cep": "01001-000",
  "logradouro": "Praça da Sé",
  "bairro": "Sé",
  "localidade": "São Paulo",
  "uf": "SP"
}
```

Para **enviar** dados (POST), é preciso avisar o método, mandar o JSON e o cabeçalho dizendo que é JSON:

```php
<?php
$novoSensor = [
    'nome_sensor'      => 'Vibração KM 22',
    'categoria_sensor' => 'Vibração',
    'tipo_sensor'      => 'Analógico',
    'trilho_sensor'    => 2,
    'status_sensor'    => 'ativo',
];

$ch = curl_init('http://localhost/ferromonitor/api/sensores.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($novoSensor));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . getenv('FERRO_API_TOKEN'),
]);

$resposta = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo $status == 201 ? 'Sensor cadastrado!' : 'Deu erro: ' . $resposta;
```

Para PUT, PATCH ou DELETE, troca-se o método com `curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');`.

Também dá para fazer um GET simples com `file_get_contents('https://viacep.com.br/ws/01001000/json/')`, mas com ele fica mais difícil tratar erros e definir tempo limite, por isso preferimos o cURL.

Uma coisa que pode acontecer no XAMPP no Windows é erro de certificado SSL. A solução certa é configurar o `curl.cainfo` no `php.ini`, e não desligar a verificação de SSL, porque isso deixa a conexão insegura.

---

## 10. Como uma API pode disponibilizar informações para outros sistemas

Aqui é o caminho inverso: em vez de usar a API de alguém, nós criamos a nossa para outros sistemas usarem. No PHP isso não é tão diferente de uma tela normal. A diferença é que o arquivo:

1. avisa que vai responder em JSON (`header('Content-Type: application/json')`);
2. olha qual método foi usado (`$_SERVER['REQUEST_METHOD']`);
3. lê os dados que chegaram, com `$_GET` para o que vem na URL e `file_get_contents('php://input')` para o JSON do corpo (o `$_POST` **não** funciona com JSON, esse foi um erro que nos pegou);
4. responde com o código certo (`http_response_code()`) e o JSON (`echo json_encode(...)`).

Montamos um exemplo de como ficaria uma API de sensores no padrão do nosso projeto (PHP procedural com mysqli):

```php
<?php
// api/sensores.php
header('Content-Type: application/json; charset=utf-8');
require_once '../infra/conexao.php'; // usar a variável de conexão do projeto

function responder($status, $dados = null) {
    http_response_code($status);
    if ($dados !== null) {
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// confere o token antes de qualquer coisa
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($token !== 'Bearer ' . getenv('FERRO_API_TOKEN')) {
    responder(401, ['erro' => 'Token inválido']);
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo == 'GET') {
    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $stmt = $conexao->prepare('SELECT * FROM sensores WHERE id_sensor = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $sensor = $stmt->get_result()->fetch_assoc();

        if (!$sensor) {
            responder(404, ['erro' => 'Sensor não encontrado']);
        }
        responder(200, $sensor);
    }

    $resultado = $conexao->query('SELECT * FROM sensores');
    responder(200, $resultado->fetch_all(MYSQLI_ASSOC));
}

if ($metodo == 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (empty($dados['nome_sensor']) || empty($dados['trilho_sensor'])) {
        responder(400, ['erro' => 'Preencha nome_sensor e trilho_sensor']);
    }

    $stmt = $conexao->prepare(
        'INSERT INTO sensores (nome_sensor, categoria_sensor, tipo_sensor, trilho_sensor, status_sensor)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssis',
        $dados['nome_sensor'],
        $dados['categoria_sensor'],
        $dados['tipo_sensor'],
        $dados['trilho_sensor'],
        $dados['status_sensor']
    );
    $stmt->execute();

    responder(201, ['id_sensor' => $conexao->insert_id]);
}

if ($metodo == 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conexao->prepare('DELETE FROM sensores WHERE id_sensor = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows == 0) {
        responder(404, ['erro' => 'Sensor não encontrado']);
    }
    responder(204);
}

responder(405, ['erro' => 'Método não permitido']);
```

Para testar sem precisar fazer outra tela, dá para usar programas como o Postman, o Insomnia ou a extensão Thunder Client do VS Code. Pelo terminal também funciona:

```bash
curl -H "Authorization: Bearer SEU_TOKEN" http://localhost/ferromonitor/api/sensores.php

curl -X POST http://localhost/ferromonitor/api/sensores.php \
     -H "Authorization: Bearer SEU_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"nome_sensor":"Vibração KM 22","categoria_sensor":"Vibração","tipo_sensor":"Analógico","trilho_sensor":2,"status_sensor":"ativo"}'
```

Também aprendemos que uma API precisa de **documentação**, explicando os endpoints, o que cada um recebe e devolve e quais erros podem acontecer. Sem isso ninguém consegue usar. O padrão mais usado para isso no mercado é o OpenAPI (antigo Swagger).

---

## 11. Autenticação, autorização e segurança

Como a API fica aberta na rede, qualquer um pode tentar mandar requisições para ela. Por isso segurança não pode ficar para depois.

Dois conceitos que parecem iguais mas são diferentes:

- **Autenticação** é saber **quem** está pedindo. Se falhar, o erro é 401.
- **Autorização** é saber **o que** essa pessoa pode fazer. Por exemplo, um operador pode ver os trens, mas só o administrador pode apagar. Se falhar, o erro é 403.

As formas de autenticação mais comuns que encontramos foram:

- **API Key:** uma chave fixa enviada no cabeçalho, mais usada para identificar sistemas;
- **Bearer Token:** a pessoa faz login, recebe um token e manda ele em toda requisição (`Authorization: Bearer ...`). É o mais usado em APIs REST;
- **JWT:** um tipo de token assinado que já carrega informações, como o id do usuário e até quando ele vale;
- **OAuth 2.0:** é o que está por trás do "Entrar com Google", onde um sistema acessa dados de outro sem saber a sua senha.

Os cuidados de segurança que achamos mais importantes para o nosso projeto:

- usar **HTTPS**, senão o token e as senhas passam pela rede sem proteção;
- **nunca deixar senha ou token no código** que vai para o GitHub;
- **validar tudo** que chega, porque não dá para confiar no que o cliente manda;
- usar **prepared statements** (`prepare` e `bind_param`) para evitar SQL Injection;
- salvar senhas com `password_hash()`, nunca em texto puro;
- **não devolver dados sensíveis**, como o campo `senha` da tabela de usuários;
- conferir a permissão em cada pedido, para ninguém ver dados de outro só trocando o id na URL;
- limitar a quantidade de requisições, para evitar abuso;
- não mostrar o erro do MySQL para o usuário, só uma mensagem genérica, e guardar o detalhe num log.

A OWASP, uma organização focada em segurança, publica uma lista dos dez maiores riscos em APIs. Na edição de 2023, os dois primeiros são justamente falhas de autorização (acessar objetos de outros usuários trocando o id) e falhas de autenticação. A lista também cita a falta de limite de requisições, configurações inseguras, versões antigas da API esquecidas no ar e confiar demais em dados vindos de APIs de terceiros.

---

## 12. Exemplos de APIs em sistemas empresariais

Pesquisando, vimos que quase todo sistema de empresa hoje usa APIs, mesmo que o usuário nem perceba. Alguns exemplos:

- **Pagamentos:** lojas virtuais usam APIs de pagamento e do Pix para cobrar e saber na hora quando o cliente pagou.
- **CEP e entregas:** o endereço que se preenche sozinho no cadastro vem de APIs como o ViaCEP; frete e rastreio vêm de APIs das transportadoras e dos Correios.
- **Nota fiscal:** a emissão de NF-e é feita pelos serviços das Secretarias da Fazenda.
- **Integração interna:** um pedido feito no sistema de vendas atualiza o estoque e o financeiro automaticamente através de APIs entre os sistemas (ERP, CRM).
- **Login:** "Entrar com Google" ou "Entrar com Microsoft".
- **Mensagens:** envio automático de e-mail, SMS e WhatsApp com avisos e códigos de verificação.
- **Mapas:** mostrar rotas e a posição de veículos.
- **Indústria e IoT:** máquinas e sensores mandando leituras para um servidor central.

No caso do FerroMonitor, conseguimos imaginar várias aplicações: os sensores dos trilhos poderiam mandar as leituras direto para a API (por exemplo, `POST /api/sensores/7/leituras`), o painel poderia atualizar o status dos trens sozinho sem recarregar a página, a equipe de manutenção poderia usar um aplicativo de celular ligado na mesma API e o sistema poderia consultar a previsão do tempo para avisar sobre chuva forte nos trechos.

---

## 13. Conclusão

Antes da pesquisa, a equipe via a API como algo distante, mas percebemos que ela é basicamente um contrato para que sistemas conversem de forma organizada. No estilo REST, cada coisa é um recurso com seu endereço (o endpoint), e o método HTTP diz o que fazer com ele: GET para consultar, POST para criar, PUT e PATCH para alterar e DELETE para apagar. A conversa sempre acontece em pares de requisição e resposta, com códigos de status, e os dados viajam em JSON.

Também entendemos a diferença para as telas que já fizemos: em vez de devolver uma página pronta, a API devolve só os dados, que podem ser usados por qualquer outro sistema. O PHP consegue tanto usar APIs de fora com o cURL quanto oferecer a sua própria. E, como a API fica exposta, cuidar da autenticação, da autorização e da segurança é obrigatório. Acreditamos que esses conceitos podem ser usados no futuro do FerroMonitor, principalmente para receber os dados dos sensores e integrar o sistema com outras aplicações.

---

## Fontes consultadas

**Documentação oficial e especificações**

- IETF. RFC 9110 – HTTP Semantics. <https://www.rfc-editor.org/rfc/rfc9110.html>
- IETF. RFC 5789 – PATCH Method for HTTP. <https://www.rfc-editor.org/rfc/rfc5789.html>
- IETF. RFC 8259 – The JavaScript Object Notation (JSON) Data Interchange Format. <https://www.rfc-editor.org/rfc/rfc8259.html>
- IETF. RFC 6750 – OAuth 2.0 Bearer Token Usage. <https://www.rfc-editor.org/rfc/rfc6750.html>
- IETF. RFC 7519 – JSON Web Token (JWT). <https://www.rfc-editor.org/rfc/rfc7519.html>
- FIELDING, Roy T. Architectural Styles and the Design of Network-based Software Architectures, cap. 5 (REST), 2000. <https://ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm>
- JSON.org. Introdução ao JSON. <https://www.json.org/json-pt.html>

**MDN Web Docs**

- API (glossário). <https://developer.mozilla.org/pt-BR/docs/Glossary/API>
- Uma visão geral do HTTP. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Overview>
- Métodos de requisição HTTP. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Methods>
- Códigos de status de respostas HTTP. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status>
- Trabalhando com JSON. <https://developer.mozilla.org/pt-BR/docs/Learn/JavaScript/Objects/JSON>

**Manual do PHP**

- cURL. <https://www.php.net/manual/pt_BR/book.curl.php>
- json_decode. <https://www.php.net/manual/pt_BR/function.json-decode.php>
- json_encode. <https://www.php.net/manual/pt_BR/function.json-encode.php>
- php://input. <https://www.php.net/manual/pt_BR/wrappers.php.php>
- MySQLi – Prepared Statements. <https://www.php.net/manual/pt_BR/mysqli.quickstart.prepared-statements.php>
- password_hash. <https://www.php.net/manual/pt_BR/function.password-hash.php>

**Segurança**

- OWASP. API Security Top 10 – 2023. <https://owasp.org/API-Security/editions/2023/en/0x11-t10/>

**API usada nos testes**

- ViaCEP. <https://viacep.com.br/>