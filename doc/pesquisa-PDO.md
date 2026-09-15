## Pesquisa PDO ##
PDO significa PHP Data Objects.


## O que é PDO:
PDO significa PHP Data Objects.

É uma extensão do PHP que fornece uma interface consistente para acessar bancos de dados diferentes (MySQL, PostgreSQL, SQLite, SQL Server, etc.) usando o mesmo conjunto de métodos e funções, independente do SGBD utilizado.

Principais características:

Abstração de banco de dados: você troca de banco de dados (ex: de MySQL para PostgreSQL) sem precisar reescrever toda a lógica de acesso a dados, só a string de conexão muda.
Prepared Statements (declarações preparadas): permite montar consultas SQL com parâmetros, o que ajuda a prevenir SQL Injection.
Orientação a objetos: é implementada como uma classe (PDO), diferente da antiga extensão mysql_* (já removida do PHP) que era procedural.
Tratamento de exceções: pode lançar exceções (PDOException) em caso de erro, facilitando o tratamento de falhas.


## Para que ele é utilizado no PHP:
O PDO é utilizado no PHP para permitir que a aplicação se comunique com bancos de dados de forma padronizada e seguindo boas práticas. Ele serve para conectar o sistema ao banco de dados, permitindo que o programador execute comandos como inserir, consultar, atualizar e excluir informações nas tabelas.

Além disso, o PDO é muito usado por oferecer mais segurança, já que trabalha com declarações preparadas, que evitam ataques de SQL Injection (quando alguém tenta inserir comandos maliciosos através de campos de formulário, por exemplo).

Outra vantagem é que o PDO funciona com vários tipos de banco de dados diferentes, como MySQL, PostgreSQL e SQLite, sem precisar mudar praticamente nada no código, apenas os dados de conexão. Isso torna o sistema mais flexível caso seja necessário trocar de banco de dados no futuro.

Por fim, ele também permite controlar transações, ou seja, garantir que um conjunto de operações no banco só seja efetivado se tudo ocorrer corretamente, o que é importante em sistemas que lidam com dados sensíveis, como cadastros, pedidos ou movimentações financeiras.

Em resumo: no PHP, o PDO é a ferramenta usada para fazer a ligação entre o código da aplicação e o banco de dados, de forma seráutil, segura e organizada.


## Como funciona uma conexão utilizando o PDO:
Primeiro, é necessário informar ao PDO qual banco de dados será utilizado e onde ele está localizado. Isso é feito através de uma "string de conexão" (chamada de DSN - Data Source Name), que contém informações como o tipo de banco (MySQL, PostgreSQL, etc.), o endereço do servidor (geralmente "localhost") e o nome do banco de dados que será acessado.

Junto com essa string, também são informados o usuário e a senha de acesso ao banco de dados, já que a maioria dos SGBDs exige autenticação para permitir conexões.

Com essas informações, o PDO tenta criar um objeto de conexão. Se tudo estiver correto (servidor ativo, banco existente, usuário e senha válidos), a conexão é estabelecida com sucesso e esse objeto passa a ser usado durante todo o programa para executar comandos no banco de dados.

Caso alguma informação esteja incorreta ou o banco de dados esteja indisponível, o PDO pode lançar uma exceção, ou seja, um erro que interrompe a execução e informa o motivo da falha. Por isso, é uma boa prática envolver a conexão em uma estrutura de tratamento de erros, para que o sistema consiga lidar com esse problema de forma adequada, exibindo uma mensagem amigável ao invés de quebrar completamente.

Depois que a conexão é criada, ela permanece aberta enquanto o script estiver em execução, permitindo que sejam feitas quantas operações forem necessárias (inserir, consultar, atualizar, excluir dados). Quando o script termina, o PHP encerra a conexão automaticamente.

Também é possível configurar atributos adicionais na conexão, como definir que os erros devem ser tratados como exceções, ou definir o formato padrão em que os dados serão retornados nas consultas, deixando o comportamento do PDO mais adequado às necessidades do sistema.


## Quais são as principais diferenças entre PDO e MySQLI:
Suporte a bancos de dados
O PDO consegue trabalhar com vários tipos de banco de dados diferentes, como MySQL, PostgreSQL, SQLite, Oracle, entre outros, bastando trocar a string de conexão. Já o MySQLI, como o próprio nome sugere ("MySQL Improved"), funciona exclusivamente com banco de dados MySQL, não servindo para outros sistemas.

Estilo de programação
O PDO trabalha apenas de forma orientada a objetos. O MySQLI, por sua vez, é mais flexível nesse aspecto, podendo ser usado tanto de forma orientada a objetos quanto de forma procedural, o que facilita a adaptação para quem está acostumado com um estilo mais antigo de programar em PHP.

Prepared Statements (declarações preparadas)
Ambos oferecem suporte a prepared statements, ajudando a proteger o sistema contra SQL Injection. Porém, o PDO costuma ser considerado mais simples e consistente nesse processo, enquanto no MySQLI a sintaxe pode ficar um pouco mais complexa, principalmente na hora de vincular parâmetros nas consultas.

Tratamento de erros
O PDO pode ser configurado para lançar exceções automaticamente, o que facilita capturar e tratar erros de forma organizada. No MySQLI, o tratamento de erros é um pouco mais manual, exigindo verificações específicas após cada operação.

Procedures armazenadas (stored procedures)
O MySQLI tem um suporte um pouco mais direto para chamadas de múltiplas stored procedures em algumas situações, enquanto no PDO esse processo pode exigir mais atenção dependendo do banco de dados utilizado.

Facilidade de troca de banco de dados
Como o PDO foi criado justamente para ser independente do banco de dados, migrar um sistema de MySQL para outro banco, por exemplo, se torna muito mais simples. Com o MySQLI, isso não é possível, já que ele foi feito exclusivamente para MySQL.

Em resumo: o PDO é geralmente recomendado quando existe a possibilidade de trabalhar com diferentes bancos de dados ou quando se busca um código mais padronizado e orientado a objetos. Já o MySQLI pode ser uma opção quando o projeto usa exclusivamente MySQL e há preferência por um estilo de programação mais procedural ou tradicional.


## Principais vantagens e desvantagens de utilizar o PDO:
Vantagens

Uma das principais vantagens é a compatibilidade com vários bancos de dados, como MySQL, PostgreSQL, SQLite e outros, permitindo trocar de banco de dados sem precisar reescrever grande parte do código, apenas ajustando a string de conexão.

Outra vantagem importante é a segurança, já que o PDO trabalha de forma simples e consistente com prepared statements, ajudando a proteger o sistema contra SQL Injection.

O PDO também oferece um tratamento de erros mais organizado, podendo lançar exceções automaticamente, o que facilita identificar e tratar falhas durante a conexão ou execução das consultas.

Além disso, por ser orientado a objetos, o código tende a ficar mais organizado, reutilizável e alinhado com boas práticas de programação, o que é especialmente útil em projetos maiores ou que seguem padrões como MVC.

Por fim, o PDO oferece suporte a transações, permitindo agrupar operações no banco de dados para garantir que sejam executadas por completo ou canceladas em caso de erro, mantendo a integridade dos dados.

Desvantagens

Uma desvantagem é que o PDO trabalha apenas com o estilo orientado a objetos, o que pode ser um pouco mais difícil para iniciantes ou para quem está acostumado com um estilo procedural mais simples.

Outro ponto é que, apesar de funcionar com vários bancos de dados, ele não aproveita totalmente recursos específicos de cada SGBD, já que seu objetivo é manter uma interface genérica. Isso significa que, em alguns casos, funcionalidades muito específicas de um banco podem exigir código adicional ou soluções alternativas.

Para quem trabalha exclusivamente com MySQL e busca algo mais direto, o PDO pode parecer um pouco mais complexo se comparado a extensões feitas especificamente para esse banco, como o MySQLI.

Por fim, como o PDO precisa manter uma camada de abstração para funcionar com diferentes bancos de dados, isso pode gerar uma pequena sobrecarga de desempenho em comparação a soluções mais específicas, embora essa diferença normalmente seja pouco perceptível na maioria dos sistemas.


## O que é Prepared Statements e por que são importantes:
Prepared Statements (ou "declarações preparadas") são uma forma de executar comandos SQL em que a estrutura da consulta é definida separadamente dos dados que serão utilizados nela. Ou seja, primeiro o sistema informa ao banco de dados qual é o "modelo" do comando SQL, usando marcadores no lugar dos valores reais, e depois esses valores são enviados separadamente para serem inseridos com segurança nesse modelo.

Por que são importantes:
A principal razão para utilizar prepared statements é a segurança. Quando os dados enviados pelo usuário são inseridos diretamente dentro do comando SQL, sem esse cuidado, existe o risco de ataques de SQL Injection, onde alguém pode digitar comandos maliciosos em um campo de formulário para manipular ou até mesmo destruir informações do banco de dados.
Com o uso de prepared statements, o banco de dados entende que aquele valor enviado é apenas um dado comum, e não um comando a ser executado, o que impede que códigos maliciosos sejam interpretados como parte da consulta SQL.
Além da segurança, os prepared statements também trazem outros benefícios:
Eles ajudam a evitar erros de sintaxe ao lidar com valores que contêm caracteres especiais, como aspas simples, já que o próprio sistema cuida da formatação correta desses dados.
Também podem melhorar o desempenho em determinados casos, principalmente quando a mesma consulta é executada várias vezes com valores diferentes, já que o banco de dados pode reaproveitar o mesmo plano de execução da consulta.
Por fim, deixam o código mais organizado e legível, já que separam claramente a estrutura da consulta dos dados que serão utilizados nela.


## Em quais situações o PDO pode ser uma boa escolha e porquê utilizaria ele:
Quando existe a possibilidade de trocar de banco de dados no futuro

Se um projeto pode, em algum momento, precisar migrar de um banco de dados para outro (por exemplo, de MySQL para PostgreSQL), o PDO é uma excelente escolha, já que ele permite fazer essa troca alterando praticamente apenas a string de conexão, sem precisar reescrever toda a lógica de acesso aos dados.

Quando o projeto precisa trabalhar com mais de um banco de dados ao mesmo tempo

Em sistemas que utilizam diferentes bancos de dados simultaneamente, o PDO facilita bastante, pois oferece uma interface única para lidar com todos eles, evitando a necessidade de aprender ou manter funções diferentes para cada SGBD.

Quando a segurança é uma prioridade

Como o PDO trabalha de forma simples e consistente com prepared statements, ele é uma ótima opção para projetos que lidam com dados sensíveis, como sistemas de login, cadastros de usuários, informações financeiras, entre outros, já que ajuda a prevenir ataques de SQL Injection de forma mais organizada.

Quando se busca um código mais organizado e orientado a objetos

Para quem já trabalha ou pretende seguir boas práticas de programação, como o uso de orientação a objetos e padrões como MVC, o PDO se encaixa muito bem, contribuindo para um código mais limpo, reutilizável e fácil de manter.

Quando o sistema precisa garantir integridade em operações mais complexas

Em situações que envolvem múltiplas operações no banco de dados que precisam ocorrer juntas, como transferências financeiras ou cadastros que dependem de várias tabelas, o PDO facilita o uso de transações, garantindo que tudo seja executado corretamente ou desfeito em caso de erro.

Quando o projeto é acadêmico ou de aprendizado

Por ser uma abordagem mais padronizada e amplamente utilizada no mercado, o PDO também costuma ser recomendado em ambientes de ensino, já que ajuda o estudante a compreender conceitos importantes de programação orientada a objetos, segurança e boas práticas, que serão úteis em diferentes tipos de projetos no futuro.

## EXEMPLO DE USO:
try {
    $pdo = new PDO("mysql:host=localhost;dbname=meubanco", "usuario", "senha");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => 1]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

## Fim