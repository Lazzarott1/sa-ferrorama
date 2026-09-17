## XAMPP
O XAMPP é um pacote de softwares utilizado para criar um ambiente de desenvolvimento local em computadores. Ele permite executar sites e sistemas web sem precisar contratar uma hospedagem online.

O nome XAMPP significa:
- X = multiplataforma (Windows, Linux e macOS)
- A = Apache
- M = MySQL/MariaDB
- P = PHP
- P = Perl

O XAMPP é muito utilizado por estudantes, programadores e empresas para desenvolver, testar e executar aplicações web localmente.
Principais Componentes do XAMPP

# 1. Apache
O Apache é um servidor web responsável por processar e exibir páginas na internet ou no ambiente local.
Finalidade
Hospedar sites localmente;
Processar requisições HTTP;
Exibir páginas HTML, CSS, JavaScript e arquivos PHP no navegador.
Funcionamento
Quando o usuário acessa localhost, o Apache interpreta o pedido e entrega os arquivos do projeto ao navegador.
Exemplo:
Ao criar um arquivo chamado index.php dentro da pasta htdocs, o Apache permite acessá-lo pelo navegador usando:
http://localhost/

# 2. MySQL/MariaDB
O XAMPP utiliza normalmente o MariaDB, que é compatível com o MySQL.

Finalidade:
-Armazenar dados do sistema;
-Criar bancos de dados;
-Gerenciar tabelas, usuários e informações.
-Exemplos de uso
-Cadastro de usuários;
-Sistemas de login;
-Controle de estoque;
-Armazenamento de pedidos e informações.

Recursos:
-Linguagem SQL;
-Criação de tabelas;
-Consultas e manipulação de dados.

# 3. PHP
O PHP é uma linguagem de programação voltada para desenvolvimento web.

Finalidade:
-Criar páginas dinâmicas;
-Conectar sistemas ao banco de dados;
-Processar formulários;
-Desenvolver sistemas web completos.

Exemplos:
-Login de usuários;
-Sistemas CRUD;
-Sites dinâmicos;
-APIs.

Funcionamento
O PHP é executado no servidor Apache antes da página ser enviada ao navegador.

# 4. phpMyAdmin
O phpMyAdmin é uma ferramenta gráfica utilizada para administrar bancos de dados MySQL/MariaDB.

Finalidade:
-Criar bancos de dados;
-Criar tabelas;
-Inserir, editar e excluir dados;
-Executar comandos SQL.

Vantagens:
-Interface simples;
-Não precisa usar apenas comandos SQL;
-Facilita o gerenciamento do banco de dados.

Acesso
Normalmente é acessado em:
http://localhost/phpmyadmin


## Como Realizar a Instalação do XAMPP

1. Download:
Acesse o site oficial do XAMPP:
XAMPP Official Website
Escolha a versão compatível com seu sistema operacional:
Windows;
Linux;
macOS.
Instalação Básica

# 2. Executar o instalador

Após baixar:
Abra o instalador;
Clique em “Next”;
Selecione os componentes desejados;
Escolha a pasta de instalação;
Finalize a instalação.
Configuração Básica

# 3. Abrir o Painel do XAMPP

Após instalar, abra o XAMPP Control Panel.
Os principais serviços serão exibidos:
Apache
MySQL

# 4. Iniciar os Serviços

Clique em:
Start no Apache;
Start no MySQL.
Quando estiverem funcionando, os módulos ficarão verdes.

# 5. Testar o Servidor

Abra o navegador e acesse:
http://localhost
Se a página do XAMPP aparecer, a instalação foi concluída corretamente.
Estrutura Principal do XAMPP
Pasta htdocs
A pasta htdocs é onde ficam os projetos web.
Exemplo de caminho
C:\xampp\htdocs
Tudo que estiver nessa pasta poderá ser acessado pelo navegador.
Exemplo
Se existir:
C:\xampp\htdocs\site\index.php
O acesso será:
http://localhost/site


# Importância do XAMPP no Desenvolvimento Local
O XAMPP possui grande importância no desenvolvimento web porque permite:
1. Desenvolvimento Offline
O programador consegue criar sistemas sem internet ou hospedagem.
2. Testes Seguros
É possível testar funcionalidades antes de publicar o sistema online.
3. Facilidade de Aprendizado
O XAMPP simplifica a configuração do ambiente para iniciantes.
4. Integração Completa

Ele reúne:
-Servidor web;
-Banco de dados;
-Linguagem de programação;
-Ferramentas administrativas.
-Tudo em um único pacote.

5. Agilidade no Desenvolvimento
O ambiente local permite:
-Testes rápidos;
-Correção de erros;
-Desenvolvimento mais eficiente.

## Sobre a tela inicial
Principais partes da tela
Modules

Mostra os serviços disponíveis:
-Apache → servidor web para rodar sites em PHP;
-MySQL → banco de dados;
-FileZilla → servidor FTP;
-Mercury → servidor de e-mail;
-Tomcat → servidor Java.

## Start
Inicia o serviço selecionado.
Exemplo:
iniciar Apache e MySQL para usar um site local.

## Admin
Abre o painel do serviço no navegador.
Exemplo:
phpMyAdmin do MySQL.

## Config
Permite alterar configurações do serviço.

## Logs
Mostra erros e informações do funcionamento.

## Botões da direita
Config → configurações do XAMPP;
Netstat → mostra portas em uso;
Shell → abre terminal;
Explorer → abre a pasta do XAMPP;
Help → ajuda;
Quit → fecha o programa.

## Uso básico
Para desenvolver sites localmente:
Clique em Start no Apache;
Clique em Start no MySQL;
Acesse no navegador:
 -localhost
 -localhost/phpmyadmin
O XAMPP é usado para criar um ambiente de desenvolvimento local no computador.

## Conclusão
O XAMPP é uma das ferramentas mais importantes para desenvolvimento web local. Ele oferece um ambiente completo contendo Apache, PHP, MySQL/MariaDB e phpMyAdmin, permitindo criar, testar e administrar aplicações web de forma prática e eficiente. Sua facilidade de instalação e utilização faz com que seja amplamente utilizado tanto por iniciantes quanto por desenvolvedores profissionais.

