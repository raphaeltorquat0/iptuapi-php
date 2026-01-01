# IPTU API - PHP SDK

SDK oficial para integracao com a [IPTU API](https://iptuapi.com.br). Acesse dados de IPTU, transacoes ITBI e avaliacoes de imoveis de Sao Paulo, Belo Horizonte e Recife.

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue)](https://php.net)
[![Packagist Version](https://img.shields.io/packagist/v/iptuapi/iptuapi-php)](https://packagist.org/packages/iptuapi/iptuapi-php)
[![License](https://img.shields.io/badge/license-Proprietary-red)](LICENSE)

## Instalacao

```bash
composer require iptuapi/iptuapi-php
```

## Requisitos

- PHP 8.1 ou superior
- ext-curl
- ext-json

## Inicio Rapido

```php
<?php

require_once 'vendor/autoload.php';

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');

// Consulta por endereco
$resultado = $client->consultaEndereco('Avenida Paulista', '1000');
echo "SQL: " . $resultado['data']['sql_base'] . "\n";
echo "Valor Venal: R$ " . number_format($resultado['dados_iptu']['valor_venal'], 2, ',', '.') . "\n";
```

## Configuracao Avancada

```php
use IPTUAPI\IPTUClient;
use IPTUAPI\Config\ClientConfig;
use IPTUAPI\Config\RetryConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Logger PSR-3 (opcional)
$logger = new Logger('iptuapi');
$logger->pushHandler(new StreamHandler('logs/iptuapi.log', Logger::DEBUG));

// Configuracao de retry
$retryConfig = new RetryConfig(
    maxRetries: 3,
    initialDelayMs: 1000,
    maxDelayMs: 30000,
    backoffMultiplier: 2.0
);

// Cliente com configuracao customizada
$config = new ClientConfig(
    timeout: 30,
    retryConfig: $retryConfig,
    logger: $logger
);

$client = new IPTUClient('sua_api_key', $config);
```

---

## Endpoints Disponiveis

### Consultas IPTU

#### consultaEndereco() - Free

Busca dados de IPTU por endereco.

```php
$resultado = $client->consultaEndereco(
    logradouro: 'Avenida Paulista',
    numero: '1000',
    cidade: 'sp',                    // 'sp' | 'bh' | 'recife'
    incluirZoneamento: false         // opcional
);

// Resposta
[
    'success' => true,
    'data' => [
        'sql_base' => '00904801381',
        'logradouro' => 'AV PAULISTA',
        'numero' => '1000',
        'bairro' => 'Bela Vista',
        'cep' => '01310100',
        'area_terreno' => 3487.0,
        'tipo_uso' => 'Predio De Escritorio'
    ],
    'dados_iptu' => [
        'sql' => '00904801381',
        'ano_referencia' => 2024,
        'area_construida' => 24884.0,
        'valor_venal' => 36058.0,
        'valor_terreno' => 32242.0,
        'valor_construcao' => 3816.0
    ],
    'estatisticas_bairro' => [
        'total_imoveis' => 24673,
        'valor_venal_medio' => 12187.8,
        'valor_m2_medio' => 217.48
    ]
]
```

#### consultaSQL() - Starter+

Busca dados completos pelo numero SQL (Sao Paulo) ou Indice Cadastral (BH).

```php
$resultado = $client->consultaSQL(
    numeroContribuinte: '00904801381',
    cidade: 'sp',
    incluirHistorico: true,      // opcional - historico de valores
    incluirComparaveis: true,    // opcional - imoveis similares
    incluirZoneamento: true,     // opcional - dados de zoneamento
    ano: 2024                    // opcional - ano de referencia
);
```

#### consultaCEP() - Starter+

Busca dados de IPTU pelo CEP.

```php
$resultado = $client->consultaCEP(
    cep: '01310-100',
    numero: 1000    // opcional - filtra por numero
);
```

#### consultaZoneamento() - Pro+

Retorna o zoneamento urbano de uma coordenada (apenas Sao Paulo).

```php
$resultado = $client->consultaZoneamento(
    longitude: -46.6544,
    latitude: -23.5613
);

// Resposta
[
    'zona' => 'ZM',
    'zona_descricao' => 'Zona Mista - Uso residencial e nao residencial'
]
```

---

### Transacoes ITBI

#### itbiStatus() - Free

Retorna estatisticas do dataset ITBI.

```php
$status = $client->itbiStatus();

// Resposta
[
    'status' => 'online',
    'dataset' => [
        'fonte' => 'Prefeitura de Sao Paulo - ITBI',
        'total_transacoes' => 1002062,
        'periodo' => '1994 - 2025',
        'volume_total_bilhoes' => 644.6
    ]
]
```

#### itbiTransacoesSQL() - Starter+

Busca transacoes ITBI pelo numero SQL.

```php
$transacoes = $client->itbiTransacoesSQL(
    sql: '00401400010',
    limit: 20    // opcional, max 100
);

// Resposta: array de transacoes
[
    [
        'sql' => '00401400010',
        'ano' => 2024,
        'logradouro' => 'R AUGUSTA',
        'numero' => '1500',
        'bairro' => 'CONSOLACAO',
        'valor_transacao' => 850000.00,
        'area_construida' => 85.0,
        'tipo_transacao' => 'Compra e Venda',
        'data_transacao' => '2024-03-15'
    ]
]
```

#### itbiTransacoesEndereco() - Starter+

Busca transacoes ITBI por endereco.

```php
$transacoes = $client->itbiTransacoesEndereco(
    logradouro: 'Avenida Paulista',
    numero: '1000',      // opcional
    bairro: null,        // opcional
    limit: 20            // opcional
);
```

#### itbiHistorico() - Pro+

Retorna historico completo de transacoes de um imovel.

```php
$historico = $client->itbiHistorico(sql: '00401400010');

// Resposta: todas as transacoes do imovel ordenadas por data
[
    ['ano' => 2024, 'valor_transacao' => 850000.00, 'data_transacao' => '2024-03-15'],
    ['ano' => 2019, 'valor_transacao' => 620000.00, 'data_transacao' => '2019-07-22'],
    ['ano' => 2015, 'valor_transacao' => 480000.00, 'data_transacao' => '2015-02-10']
]
```

#### itbiEstatisticas() - Pro+

Retorna estatisticas de transacoes de um bairro.

```php
$stats = $client->itbiEstatisticas(
    bairro: 'PINHEIROS',
    ano: 2024    // opcional
);

// Resposta
[
    'bairro' => 'PINHEIROS',
    'ano' => 2024,
    'total_transacoes' => 1523,
    'valor_medio' => 1640000.00,
    'valor_mediano' => 1200000.00,
    'valor_m2_medio' => 15800.00,
    'volume_total' => 2500000000.00
]
```

#### itbiEstimativa() - Pro+

Estima valor de mercado com base em transacoes similares.

```php
$estimativa = $client->itbiEstimativa(
    bairro: 'PINHEIROS',
    areaConstruida: 85.0,
    tipoImovel: 'apartamento',    // opcional: apartamento|casa|comercial|terreno
    mesesHistorico: 24            // opcional, padrao 24
);

// Resposta
[
    'valor_estimado' => 1343000.00,
    'faixa_minima' => 1208700.00,
    'faixa_maxima' => 1477300.00,
    'valor_m2_medio' => 15800.00,
    'total_transacoes' => 156,
    'confianca' => 'alta'
]
```

#### itbiComparaveis() - Pro+

Busca transacoes de imoveis comparaveis para avaliacao.

```php
$comparaveis = $client->itbiComparaveis(
    bairro: 'PINHEIROS',
    tipoImovel: 'apartamento',    // opcional
    areaMin: 50,                  // opcional
    areaMax: 100,                 // opcional
    anos: '2023,2024',            // opcional
    limit: 20                     // opcional
);

// Resposta
[
    'encontrados' => 10,
    'transacoes' => [
        [
            'sql' => '12345678901',
            'logradouro' => 'R DOS PINHEIROS',
            'numero' => '500',
            'area_construida' => 75.0,
            'valor_transacao' => 1125000.00,
            'valor_m2' => 15000.00,
            'data_transacao' => '2024-02-10'
        ]
    ]
]
```

#### itbiBairros() - Free

Lista bairros com transacoes ordenados por volume.

```php
$bairros = $client->itbiBairros(limit: 10);

// Resposta
[
    ['bairro' => 'PINHEIROS', 'total_transacoes' => 45231, 'valor_medio' => 1640000.00],
    ['bairro' => 'MOEMA', 'total_transacoes' => 38542, 'valor_medio' => 1850000.00]
]
```

---

### Valuation (Avaliacao)

#### valuationEstimate() - Pro+

Estima valor de mercado usando Machine Learning.

```php
$avaliacao = $client->valuationEstimate([
    'area_terreno' => 250,
    'area_construida' => 180,
    'bairro' => 'Pinheiros',
    'zona' => 'ZM',
    'tipo_uso' => 'Residencial',
    'tipo_padrao' => 'Medio',
    'ano_construcao' => 2010    // opcional
]);

// Resposta
[
    'valor_estimado' => 1010464.81,
    'valor_minimo' => 909418.31,
    'valor_maximo' => 1111511.37,
    'valor_m2' => 5613.69,
    'confianca' => 0.7,
    'modelo_versao' => '1.0.0'
]
```

---

## Tratamento de Erros

```php
use IPTUAPI\IPTUClient;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;
use IPTUAPI\Exception\ServerException;

$client = new IPTUClient('sua_api_key');

try {
    $resultado = $client->consultaEndereco('Rua Teste', '100');

} catch (AuthenticationException $e) {
    // API Key invalida ou expirada
    echo "Erro de autenticacao: " . $e->getMessage();

} catch (ForbiddenException $e) {
    // Endpoint requer plano superior
    echo "Acesso negado. Plano necessario: " . $e->getRequiredPlan();

} catch (NotFoundException $e) {
    // Imovel nao encontrado
    echo "Nao encontrado: " . $e->getMessage();

} catch (RateLimitException $e) {
    // Limite de requisicoes excedido
    echo "Rate limit. Aguarde " . $e->getRetryAfter() . " segundos";
    sleep($e->getRetryAfter());

} catch (ValidationException $e) {
    // Parametros invalidos
    foreach ($e->getErrors() as $campo => $erros) {
        echo "$campo: " . implode(', ', $erros) . "\n";
    }

} catch (ServerException $e) {
    // Erro no servidor (pode tentar novamente)
    echo "Erro no servidor: " . $e->getMessage();

} catch (IPTUAPIException $e) {
    // Qualquer outro erro da API
    echo "Erro: " . $e->getMessage();
    echo "Request ID: " . $e->getRequestId();
}
```

---

## Rate Limiting

Verifique seu consumo apos cada requisicao:

```php
$resultado = $client->consultaEndereco('Avenida Paulista', '1000');

$rateLimit = $client->getRateLimit();
if ($rateLimit) {
    echo "Requisicoes restantes: " . $rateLimit->remaining . "\n";
    echo "Limite mensal: " . $rateLimit->limit . "\n";
    echo "Reset em: " . $rateLimit->resetAt->format('Y-m-d H:i:s') . "\n";
}

// ID da requisicao para suporte
echo "Request ID: " . $client->getLastRequestId() . "\n";
```

### Limites por Plano

| Plano | Requisicoes/mes | Requisicoes/min |
|-------|-----------------|-----------------|
| Free | 10 | 10 |
| Starter | 500 | 100 |
| Pro | 5.000 | 500 |
| Enterprise | 100.000 | 10.000 |

---

## Cidades Suportadas

| Codigo | Cidade | Registros | Periodo |
|--------|--------|-----------|---------|
| `sp` | Sao Paulo | 93M+ | 2009-2024 |
| `bh` | Belo Horizonte | 4M+ | 2015-2024 |
| `recife` | Recife | 427K+ | 2025 |

---

## Exemplo Completo

```php
<?php

require_once 'vendor/autoload.php';

use IPTUAPI\IPTUClient;
use IPTUAPI\Config\ClientConfig;
use IPTUAPI\Config\RetryConfig;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\RateLimitException;

// Configuracao
$config = new ClientConfig(
    timeout: 30,
    retryConfig: new RetryConfig(maxRetries: 3)
);

$client = new IPTUClient($_ENV['IPTU_API_KEY'], $config);

// Exemplo: Avaliar imovel usando dados ITBI
try {
    // 1. Buscar dados do imovel
    $imovel = $client->consultaEndereco('Avenida Paulista', '1000', 'sp');
    $bairro = $imovel['data']['bairro'];
    $area = $imovel['dados_iptu']['area_construida'];

    echo "Imovel: {$imovel['data']['logradouro']}, {$imovel['data']['numero']}\n";
    echo "Bairro: $bairro\n";
    echo "Area: {$area}m2\n";
    echo "Valor Venal IPTU: R$ " . number_format($imovel['dados_iptu']['valor_venal'], 2, ',', '.') . "\n\n";

    // 2. Buscar estimativa de mercado
    $estimativa = $client->itbiEstimativa(
        bairro: strtoupper($bairro),
        areaConstruida: $area
    );

    echo "=== Estimativa de Mercado (ITBI) ===\n";
    echo "Valor Estimado: R$ " . number_format($estimativa['valor_estimado'], 2, ',', '.') . "\n";
    echo "Faixa: R$ " . number_format($estimativa['faixa_minima'], 2, ',', '.');
    echo " - R$ " . number_format($estimativa['faixa_maxima'], 2, ',', '.') . "\n";
    echo "Baseado em {$estimativa['total_transacoes']} transacoes\n\n";

    // 3. Buscar comparaveis
    $comparaveis = $client->itbiComparaveis(
        bairro: strtoupper($bairro),
        areaMin: $area * 0.8,
        areaMax: $area * 1.2,
        limit: 5
    );

    echo "=== Imoveis Comparaveis ===\n";
    foreach ($comparaveis['transacoes'] as $comp) {
        echo "- {$comp['logradouro']}, {$comp['numero']} ";
        echo "({$comp['area_construida']}m2) - ";
        echo "R$ " . number_format($comp['valor_transacao'], 2, ',', '.') . "\n";
    }

} catch (RateLimitException $e) {
    echo "Rate limit atingido. Aguarde {$e->getRetryAfter()}s\n";
} catch (IPTUAPIException $e) {
    echo "Erro: {$e->getMessage()}\n";
    echo "Request ID: {$e->getRequestId()}\n";
}
```

---

## Compatibilidade

- **PSR-3**: Logger interface
- **PSR-4**: Autoloading
- **PSR-12**: Coding style
- **PSR-18**: HTTP Client (Guzzle)

## Testes

```bash
# Executar testes
./vendor/bin/phpunit

# Com cobertura
./vendor/bin/phpunit --coverage-html coverage

# Analise estatica
./vendor/bin/phpstan analyse src
```

---

## Suporte

- **Documentacao**: https://iptuapi.com.br/docs
- **Dashboard**: https://iptuapi.com.br/dashboard
- **Email**: contato@iptuapi.com.br

## Licenca

Copyright 2025 IPTU API. Todos os direitos reservados.
Este software e propriedade exclusiva da IPTU API. O uso esta sujeito aos [Termos de Servico](https://iptuapi.com.br/termos).
