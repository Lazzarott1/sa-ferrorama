# PDO (PHP Data Objects)

## O que é o PDO

PDO, sigla para *PHP Data Objects*, é uma extensão nativa do PHP que fornece uma interface leve e consistente para acesso a bancos de dados. Diferente das extensões voltadas para um único banco (como a antiga `mysql_*` ou a `mysqli`), o PDO funciona como uma camada de abstração de acesso a dados: ele define um conjunto único de classes, métodos e funções que podem ser usados para se comunicar com diferentes sistemas de banco de dados, desde que exista um driver PDO correspondente instalado (por exemplo, `PDO_MYSQL`, `PDO_PGSQL`, `PDO_SQLITE`, entre outros).

É importante destacar que o PDO oferece abstração de *acesso* aos dados, e não abstração completa do banco de dados: ele não traduz nem reescreve comandos SQL para compensar diferenças de sintaxe entre bancos diferentes. Ou seja, trocar de banco de dados usando PDO exige apenas trocar a string de conexão (DSN) e, possivelmente, ajustar as queries SQL para a sintaxe do novo banco — mas a forma de programar (conectar, preparar, executar, buscar resultados) permanece a mesma.

## Para que ele é utilizado no PHP

O PDO é utilizado sempre que uma aplicação PHP precisa se comunicar com um banco de dados relacional. Suas principais aplicações incluem:

* Abrir e gerenciar conexões com o banco de dados;
* Executar comandos SQL (`SELECT`, `INSERT`, `UPDATE`, `DELETE`, entre outros);
* Preparar e executar consultas parametrizadas (*prepared statements*), protegendo a aplicação contra SQL Injection;
* Gerenciar transações (`beginTransaction`, `commit`, `rollBack`), garantindo integridade em operações que envolvem múltiplos comandos;
* Buscar e manipular os resultados das consultas, em diferentes formatos (arrays associativos, objetos, etc.);
* Tratar erros de forma padronizada, inclusive por meio de exceções (`PDOException`).

Por ser uma extensão nativa (embutida no PHP a partir da versão 5.1), o PDO se tornou o padrão recomendado para acesso a banco de dados em projetos modernos escritos em PHP.

## Como funciona uma conexão utilizando PDO

Uma conexão PDO é criada instanciando a classe `PDO`, que recebe como parâmetro principal uma *DSN* (Data Source Name) — uma string que informa qual driver de banco será usado e os dados de acesso (host, nome do banco, charset, etc.), além, opcionalmente, do usuário, da senha e de um array de opções.

Exemplos de conexão para diferentes bancos:

```php
// MySQL
$conexao = new PDO('mysql:host=localhost;dbname=minha_base;charset=utf8mb4', 'usuario', 'senha');

// PostgreSQL
$conexao = new PDO('pgsql:host=localhost;dbname=minha_base', 'usuario', 'senha');

// SQLite
$conexao = new PDO('sqlite:/caminho/para/banco.db');
```

Após a conexão ser estabelecida, é comum configurar o modo de tratamento de erros para lançar exceções, o que facilita a depuração:

```php
$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

A partir daí, o objeto `$conexao` (uma instância de `PDO`) é utilizado para preparar e executar comandos SQL, geralmente através de objetos da classe `PDOStatement`, retornados pelo método `prepare()`:

```php
$stmt = $conexao->prepare('SELECT nome FROM produtos WHERE preco < ?');
$stmt->execute([100]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

Como os drivers são carregados dinamicamente em tempo de execução, não é necessário recompilar ou reconfigurar o PHP para trocar de banco de dados — basta alterar a DSN e ter o driver correspondente habilitado.

## Principais características

* **Interface orientada a objetos**: o PDO é totalmente orientado a objetos, organizado em três classes principais: `PDO` (representa a conexão), `PDOStatement` (representa uma instrução preparada e seus resultados) e `PDOException` (usada para tratamento de erros);
* **Suporte a múltiplos bancos de dados**: possui drivers para MySQL, PostgreSQL, SQLite, Oracle, SQL Server, Firebird, IBM DB2, ODBC, entre outros;
* **Prepared Statements nativos**: suporte embutido a consultas preparadas com parâmetros nomeados (`:nome`) ou posicionais (`?`);
* **Suporte a transações**: métodos para iniciar, confirmar e desfazer transações, quando o driver do banco suporta essa funcionalidade;
* **Tratamento de erros flexível**: pode ser configurado para retornar códigos de erro silenciosamente, gerar *warnings* ou lançar exceções (`PDOException`);
* **Diversos modos de busca de resultados**: métodos como `fetch()`, `fetchAll()`, `fetchColumn()` e `fetchObject()`, com diferentes formatos de retorno (array associativo, array numérico, objeto, etc.).

## Diferenças entre PDO e MySQLi

| Aspecto | PDO | MySQLi |
|---|---|---|
| Bancos suportados | 12 bancos de dados diferentes (MySQL, PostgreSQL, SQLite, Oracle, SQL Server etc.) | Somente MySQL e MariaDB |
| Estilo de programação | Apenas orientado a objetos | Orientado a objetos e procedural |
| Parâmetros em prepared statements | Suporta *placeholders* nomeados (`:usuario`) e posicionais (`?`) | Suporta apenas *placeholders* posicionais (`?`), numerados |
| Desempenho | Levemente inferior ao MySQLi | Cerca de 2,5% mais rápido em consultas simples e até 6,5% em prepared statements, segundo benchmarks |
| Recursos específicos | Foco em portabilidade entre bancos | Suporta consultas assíncronas (não bloqueantes) e múltiplas instruções (*multi-query*) |
| Tratamento de erros | Suporte nativo e consistente a exceções | Suporte a exceções a partir de configuração específica |

Em resumo, a escolha entre PDO e MySQLi costuma depender do contexto: o PDO é preferível quando há necessidade de portabilidade entre diferentes bancos de dados ou quando se busca uma sintaxe mais legível com parâmetros nomeados; já o MySQLi pode ser mais adequado em projetos exclusivamente MySQL/MariaDB que exijam recursos específicos, como consultas assíncronas, ou uma pequena vantagem de desempenho.

## Vantagens e desvantagens de utilizar PDO

**Vantagens:**

* Permite trocar de banco de dados com pouca alteração no código, já que a forma de conectar, preparar e executar comandos é a mesma para qualquer driver suportado;
* Interface única e consistente, o que facilita o aprendizado e a manutenção do código;
* Suporte nativo a *prepared statements*, aumentando a segurança contra SQL Injection;
* Suporte a transações, importante para operações que precisam ser atômicas;
* Por ser escrito em C e compilado junto ao PHP, tem bom desempenho;
* Tratamento de erros mais robusto e padronizado, com possibilidade de uso de exceções.

**Desvantagens:**

* Não realiza abstração completa do banco de dados: não traduz ou adapta automaticamente comandos SQL específicos de cada SGBD, então o desenvolvedor ainda precisa se preocupar com diferenças de sintaxe entre bancos;
* Não oferece funcionalidades avançadas específicas de alguns bancos, como consultas assíncronas disponíveis no MySQLi;
* Exclusivamente orientado a objetos, o que pode ser uma barreira para quem prefere ou está mais acostumado com estilo procedural;
* Desempenho ligeiramente inferior ao MySQLi em cenários específicos com MySQL, segundo alguns benchmarks.

## O que são Prepared Statements e por que são importantes

*Prepared Statements* (instruções preparadas) são, segundo a documentação oficial do PHP, "um modelo compilado para o SQL que uma aplicação deseja executar, que pode ser personalizado usando parâmetros variáveis". Na prática, isso significa que a estrutura da consulta SQL é enviada ao banco de dados separadamente dos valores que serão utilizados nela, que são passados como parâmetros (nomeados ou posicionais) e vinculados posteriormente.

Exemplo com parâmetros nomeados:

```php
$stmt = $conexao->prepare('INSERT INTO produtos (nome, preco) VALUES (:nome, :preco)');
$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':preco', $preco);

$nome = 'Caneta';
$preco = 2.50;
$stmt->execute();
```

Exemplo com parâmetros posicionais:

```php
$stmt = $conexao->prepare('SELECT * FROM produtos WHERE nome = ?');
$stmt->execute([$_GET['nome']]);
```

As prepared statements são importantes por dois motivos principais:

1. **Segurança**: os parâmetros não precisam ser manualmente escapados pelo desenvolvedor — o próprio driver do banco cuida disso, separando claramente o comando SQL dos dados enviados pelo usuário. Isso praticamente elimina o risco de ataques de SQL Injection, desde que a aplicação utilize *exclusivamente* prepared statements para dados vindos do usuário;
2. **Desempenho**: a consulta é analisada, compilada e otimizada pelo banco de dados apenas uma vez, podendo ser executada diversas vezes com parâmetros diferentes. Isso reduz o custo de processamento em cenários com repetição da mesma consulta.

Vale destacar que o PDO é capaz de emular prepared statements mesmo em drivers cujo banco de dados não ofereça suporte nativo a esse recurso, garantindo um comportamento consistente independentemente do SGBD utilizado.

## Em quais situações o PDO pode ser uma boa escolha

O PDO é uma boa escolha, principalmente, quando:

* O projeto pode precisar trocar de banco de dados no futuro, ou já precisa dar suporte a mais de um SGBD (por exemplo, MySQL em produção e SQLite em testes);
* A equipe valoriza uma sintaxe mais legível, com uso de parâmetros nomeados nas consultas preparadas;
* Deseja-se um tratamento de erros mais robusto e padronizado, baseado em exceções;
* O projeto envolve operações que exigem controle de transações (como sistemas financeiros ou de estoque);
* Busca-se seguir uma prática atual e recomendada para acesso a dados em PHP, evitando as extensões antigas e não seguras como `mysql_*`.

Já em cenários muito específicos, como aplicações exclusivamente MySQL/MariaDB que precisem de recursos como consultas assíncronas ou que busquem o máximo desempenho possível, o MySQLi pode ser uma alternativa a ser considerada.

## Fontes

* [PHP: PDO - Manual](https://www.php.net/manual/en/book.pdo.php)
* [PHP: Prepared Statements and Stored Procedures - Manual](https://www.php.net/manual/en/pdo.prepared-statements.php)
* [PDO vs. mysqli? - PHP.earth documentation](https://docs.php.earth/faq/db/mysqli-or-pdo/)
* [PDO vs. MySQLi: The Battle of PHP Database APIs - Website Beaver](https://websitebeaver.com/php-pdo-vs-mysqli)
* [Introdução ao PDO (PHP Data Objects): Aprenda agora! - DevMedia](https://www.devmedia.com.br/introducao-ao-php-data-objects-pdo/25318)