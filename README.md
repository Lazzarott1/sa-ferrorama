# SA Ferrorama

### Proposta do Sistema

O FerroMonitor é um sistema desenvolvido para gerenciar ferrovias de forma inteligente e moderna. Ele utiliza sensores instalados nos trens e nos trilhos para coletar informações continuamente. Esses dados são enviados a um computador central, onde são organizados para que os gestores possam acompanhar em tempo real tudo o que acontece na malha ferroviária.

### Objetivos do Projeto

O principal objetivo deste projeto é oferecer uma ferramenta que apoie a tomada de decisões com base em dados reais. O sistema concentra-se em três frentes: melhorar a velocidade e o uso de energia, antecipar a necessidade de manutenção dos equipamentos e aumentar a segurança das operações. Para isso, registra velocidade, localização e possíveis defeitos, transformando essas informações em gráficos e relatórios de fácil compreensão.

### Equipe

O projeto é desenvolvido por uma equipe formada por quatro integrantes: Caio Marques, Lucas Lazzarotti, Luis Pedro Mathias e Matheus Guesser.

### Funcionalidades Previstas

O funcionamento do sistema começa pelo recebimento e processamento das informações enviadas pelos sensores instalados nos trens e trilhos. Para uso administrativo, o software conta com uma tela de login segura, responsável por identificar o usuário e liberar o acesso às páginas de gestão. As ferramentas de gerenciamento permitem cadastrar, listar e excluir sensores e locomotivas, além de acompanhar em um mapa a localização e a velocidade de cada trem em tempo real. O sistema também foi projetado para evitar perda de dados: caso a conexão com a internet seja interrompida, os sensores armazenam as informações localmente e as sincronizam automaticamente com o banco de dados assim que a conexão for restabelecida.

### Especificações Técnicas

A qualidade e a confiabilidade do FerroMonitor são garantidas por requisitos não funcionais rigorosos, como latência máxima de 500ms no processamento de dados e disponibilidade de 99,9%. A interface foi projetada para ser responsiva e compatível com os principais navegadores do mercado, priorizando a acessibilidade por meio das diretrizes WCAG e do uso de alto contraste em alertas críticos. Além disso, a arquitetura modular do sistema permite escalabilidade para milhares de sensores, garantindo a integridade dos dados e a segurança das sessões dos usuários.

### Tecnologias Utilizadas

Além do HTML, o projeto emprega diversas linguagens e tecnologias para garantir seu pleno funcionamento, entre elas JavaScript, PHP, CSS e Bootstrap para o desenvolvimento da interface; MySQL, gerenciado via phpMyAdmin, para o armazenamento dos dados; e XAMPP como ambiente de desenvolvimento local. A operação CRUD (Create, Read, Update, Delete) estrutura as ações de cadastro e manipulação de dados, enquanto Scrum e Kanban orientam a organização e o acompanhamento do trabalho da equipe.