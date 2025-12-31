# IPTU API - PHP SDK

SDK oficial para integração com a IPTU API - Dados de IPTU de São Paulo e Belo Horizonte.

## Instalação

```bash
composer require raphaeltorquat0/iptuapi-php
```

## Cidades Suportadas

| Cidade | Código | Identificador |
|--------|--------|---------------|
| São Paulo | `sao_paulo` | Número SQL |
| Belo Horizonte | `belo_horizonte` | Índice Cadastral |

## Uso Rápido

```php
<?php

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');

// Consulta por endereço (São Paulo - endpoint legado)
$resultado = $client->consultaEndereco('Avenida Paulista', '1000');
print_r($resultado);

// Consulta por SQL (Starter+)
$dados = $client->consultaSQL('100-01-001-001');
```

## Consulta Multi-Cidade (Novo!)

```php
<?php

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');

// São Paulo - busca por endereço
$resultados = $client->consultaIPTU('sao_paulo', 'Avenida Paulista', 1000, 2024);
foreach ($resultados as $imovel) {
    echo "SQL: {$imovel['sql']}, Valor Venal: R$ " . number_format($imovel['valor_venal'], 2, ',', '.') . "\n";
}

// Belo Horizonte - busca por endereço
$resultados = $client->consultaIPTU('belo_horizonte', 'Afonso Pena', null, 2024);
foreach ($resultados as $imovel) {
    echo "Índice: {$imovel['sql']}, Valor Venal: R$ " . number_format($imovel['valor_venal'], 2, ',', '.') . "\n";
}

// Busca por identificador único
// São Paulo (SQL)
$dados = $client->consultaIPTUSQL('sao_paulo', '00904801381');

// Belo Horizonte (Índice Cadastral)
$dados = $client->consultaIPTUSQL('belo_horizonte', '007028 005 0086');
```

## Avaliação de Mercado (Pro+)

```php
$avaliacao = $client->valuationEstimate([
    'area_terreno' => 250,
    'area_construida' => 180,
    'bairro' => 'Pinheiros',
    'zona' => 'ZM',
    'tipo_uso' => 'Residencial',
    'tipo_padrao' => 'Médio',
    'ano_construcao' => 2010,
]);
echo "Valor estimado: R$ " . number_format($avaliacao['valor_estimado'], 2, ',', '.');
```

## Tratamento de Erros

```php
<?php

use IPTUAPI\IPTUClient;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ValidationException;

$client = new IPTUClient('sua_api_key');

try {
    $resultado = $client->consultaIPTU('cidade_invalida', 'Rua Teste');
} catch (ValidationException $e) {
    echo "Cidade não suportada: " . $e->getMessage();
} catch (NotFoundException $e) {
    echo "Imóvel não encontrado";
} catch (RateLimitException $e) {
    echo "Limite de requisições excedido";
} catch (AuthenticationException $e) {
    echo "API Key inválida";
}
```

## Documentação

Acesse a documentação completa em [iptuapi.com.br/docs](https://iptuapi.com.br/docs)
