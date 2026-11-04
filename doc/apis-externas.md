# APIs externas do FerroMonitor

Na Etapa 10 o sistema passou a usar duas APIs públicas. Esta página explica por que escolhemos cada uma, como os dados chegam e o que acontece quando algo dá errado.

## 1. Clima: Open-Meteo

- **Documentação oficial:** <https://open-meteo.com/en/docs>
- **Onde aparece:** cartão "Clima na ferrovia" da Home (`public/tela-geral-home.php`, código em `script/clima.js`).

### Por que escolhemos

| Opção | Chave de acesso | Motivo |
|-------|-----------------|--------|
| **Open-Meteo** | não precisa | escolhida: gratuita para uso não comercial |
| OpenWeatherMap | precisa de cadastro e chave | a chave ficaria exposta no JavaScript |
| WeatherAPI | precisa de cadastro e chave | mesmo problema da chave |

A Open-Meteo não pede cadastro, aceita chamadas direto do navegador (CORS liberado), devolve os dados em JSON já no fuso de São Paulo e usa os códigos de tempo da OMM, fáceis de traduzir.

### Como os dados são obtidos

O `fetch` consulta as coordenadas de Joinville - SC, cidade da ferrovia piloto:

```
GET https://api.open-meteo.com/v1/forecast?latitude=-26.3045&longitude=-48.8487
    &current=temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code,is_day
    &timezone=America/Sao_Paulo
```

Formato da resposta, só com a parte usada (valores ilustrativos; as consultas reais estão nas evidências da entrega):

```json
{
  "current": {
    "time": "2026-11-04T11:15",
    "temperature_2m": 24.6,
    "apparent_temperature": 26.1,
    "relative_humidity_2m": 78,
    "wind_speed_10m": 11.2,
    "weather_code": 61,
    "is_day": 1
  }
}
```

### Como é apresentado

| Dado da API | Na tela |
|-------------|---------|
| `temperature_2m` | temperatura grande, arredondada (ex.: 25°C) |
| `weather_code` | texto ("Chuva", "Nublado"...) e ícone; o ícone muda à noite pelo `is_day` |
| `apparent_temperature`, `relative_humidity_2m`, `wind_speed_10m` | sensação, umidade e vento |
| `time` | "Medição das 11:15", horário da última atualização da API |
| — | "consultado às 14:28", horário em que o sistema buscou |
| — | "Joinville - SC", local usado na consulta |

Se o código indica chuva, garoa ou tempestade, aparece o aviso "Pista molhada: atenção à frenagem e aos sensores da via". Com vento acima de 50 km/h o aviso é sobre a ponte. O cartão se atualiza sozinho a cada 10 minutos e pelo botão de atualizar.

### Erros tratados

| Situação | O que o sistema faz |
|----------|--------------------|
| Sem internet ou API fora do ar | mostra "Não foi possível carregar o clima agora" e "Verifique a internet e clique em atualizar" |
| API demora mais de 8 s | cancela a consulta (`AbortSignal.timeout`) e mostra a mesma mensagem |
| Resposta com erro HTTP | mesma mensagem; o resto da Home continua funcionando |

## 2. CEP: ViaCEP

- **Documentação oficial:** <https://viacep.com.br/>
- **Onde aparece:** bloco "Localização da estação" no cadastro e na edição de trilhos (`public/trilhos/campos-cep.php`, código em `script/cep.js`, validação no servidor em `infra/validacoes-crud/valida-trilho.php`).

### Por que escolhemos

| Opção | Chave | Motivo |
|-------|-------|--------|
| **ViaCEP** | não precisa | escolhida: gratuita, base dos Correios, CORS liberado, já testada na nossa pesquisa de APIs |
| BrasilAPI | não precisa | boa alternativa, mas junta várias fontes e é um pouco mais lenta |
| API dos Correios | precisa de contrato | inviável para o projeto |

### Como os dados são obtidos

Quando o usuário sai do campo CEP, o `fetch` chama:

```
GET https://viacep.com.br/ws/89201100/json/
```

Resposta (exemplo da documentação oficial, com o CEP 01001-000):

```json
{
  "cep": "01001-000",
  "logradouro": "Praça da Sé",
  "bairro": "Sé",
  "localidade": "São Paulo",
  "uf": "SP"
}
```

`logradouro`, `bairro`, `localidade` e `uf` vão para os campos Logradouro, Bairro, Cidade e UF, e o CEP ganha o hífen. Os campos continuam editáveis, porque CEPs gerais de cidade pequena vêm sem logradouro.

### Erros tratados

| Situação | O que o sistema faz |
|----------|--------------------|
| CEP com menos ou mais de 8 números | não consulta a API e mostra "CEP inválido: digite os 8 números" |
| CEP no formato certo mas inexistente (a API responde `{"erro": "true"}`) | limpa o endereço e mostra "CEP não encontrado. Confira o número digitado" |
| Sem internet, API fora do ar ou mais de 8 s | mostra "Não foi possível consultar o CEP agora. Tente de novo ou preencha o endereço à mão" |
| Alguém burla o JavaScript e envia CEP errado | o PHP recusa: "O CEP deve ter 8 números" ou "Informe a cidade e a UF do CEP" |

O endereço é opcional: um trilho sem CEP continua sendo salvo, e a lista mostra "-" na coluna LOCAL.
