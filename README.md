# IPTU API - PHP SDK

SDK oficial PHP para integracao com a IPTU API. Acesso a dados de IPTU de mais de 10 cidades brasileiras.

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue)](https://php.net)
[![Packagist Version](https://img.shields.io/packagist/v/iptuapi/iptuapi-php)](https://packagist.org/packages/iptuapi/iptuapi-php)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Instalacao

```bash
composer require iptuapi/iptuapi-php
```

## Requisitos

- PHP 8.1+
- ext-curl
- ext-json

## Uso Rapido

```php
<?php

require_once 'vendor/autoload.php';

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');

// Consulta por endereco
$resultado = $client->consultaEndereco('Avenida Paulista', '1000');
print_r($resultado);

// Consulta por SQL (Starter+)
$dados = $client->consultaSQL('100-01-001-001');
```

## Configuracao

### Cliente Basico

```php
use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');
```

### Configuracao Avancada

```php
use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Configurar logger (PSR-3)
$logger = new Logger('iptuapi');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Configuracao de retry
$retryConfig = new RetryConfig(
    maxRetries: 5,
    initialDelay: 1000,      // ms
    maxDelay: 30000,         // ms
    backoffFactor: 2.0,
    retryableStatuses: [429, 500, 502, 503, 504]
);

// Configuracao do cliente
$config = new ClientConfig(
    baseUrl: 'https://iptuapi.com.br/api/v1',
    timeout: 60,
    retryConfig: $retryConfig,
    logger: $logger
);

$client = new IPTUClient('sua_api_key', $config);
```

## Endpoints da API

### Consultas (Todos os Planos)

```php
// Consulta por endereco
$resultado = $client->consultaEndereco('Avenida Paulista', '1000', 'sp');

// Consulta por CEP
$resultado = $client->consultaCEP('01310-100', 'sp');

// Consulta por coordenadas (zoneamento)
$resultado = $client->consultaZoneamento(-23.5505, -46.6333);
```

### Consultas Avancadas (Starter+)

```php
// Consulta por numero SQL
$resultado = $client->consultaSQL('100-01-001-001', 'sp');

// Historico de valores IPTU
$historico = $client->dadosIPTUHistorico('100-01-001-001', 'sp');

// Consulta CNPJ
$empresa = $client->dadosCNPJ('12345678000100');

// Correcao monetaria IPCA
$corrigido = $client->dadosIPCACorrigir(100000.0, '2020-01', '2024-01');
```

### Valuation (Pro+)

```php
// Estimativa de valor de mercado
$avaliacao = $client->valuationEstimate([
    'area_terreno' => 250,
    'area_construida' => 180,
    'bairro' => 'Pinheiros',
    'zona' => 'ZM',
    'tipo_uso' => 'Residencial',
    'tipo_padrao' => 'Medio',
    'ano_construcao' => 2010
]);
echo "Valor estimado: R$ " . number_format($avaliacao['valor_estimado'], 2, ',', '.');

// Buscar comparaveis
$comparaveis = $client->valuationComparables(
    bairro: 'Pinheiros',
    areaMin: 150,
    areaMax: 250,
    cidade: 'sp',
    limit: 10
);
```

### Batch Operations (Enterprise)

```php
// Valuation em lote (ate 100 imoveis)
$imoveis = [
    ['area_terreno' => 250, 'area_construida' => 180, 'bairro' => 'Pinheiros'],
    ['area_terreno' => 300, 'area_construida' => 200, 'bairro' => 'Moema'],
];
$resultados = $client->valuationBatch($imoveis);
```

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
use IPTUAPI\Exception\TimeoutException;
use IPTUAPI\Exception\NetworkException;

$client = new IPTUClient('sua_api_key');

try {
    $resultado = $client->consultaEndereco('Rua Teste', '100');
} catch (AuthenticationException $e) {
    echo "API Key invalida: " . $e->getMessage();
} catch (ForbiddenException $e) {
    echo "Plano nao autorizado. Requer: " . $e->getRequiredPlan();
} catch (NotFoundException $e) {
    echo "Imovel nao encontrado: " . $e->getResource();
} catch (RateLimitException $e) {
    echo "Rate limit excedido. Retry em " . $e->getRetryAfter() . "s";
} catch (ValidationException $e) {
    echo "Parametros invalidos: ";
    foreach ($e->getErrors() as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (ServerException $e) {
    echo "Erro no servidor (retryable): " . $e->getMessage();
} catch (TimeoutException $e) {
    echo "Timeout apos " . $e->getTimeoutSeconds() . "s";
} catch (NetworkException $e) {
    echo "Erro de conexao: " . $e->getMessage();
} catch (IPTUAPIException $e) {
    echo "Erro: " . $e->getMessage();
    echo "Request ID: " . $e->getRequestId();
}
```

### Propriedades dos Erros

```php
try {
    $resultado = $client->consultaEndereco('Rua Teste', '100');
} catch (IPTUAPIException $e) {
    echo "Status Code: " . $e->getStatusCode() . "\n";
    echo "Request ID: " . $e->getRequestId() . "\n";
    echo "Retryable: " . ($e->isRetryable() ? 'Sim' : 'Nao') . "\n";

    // Converter para array
    $errorData = $e->toArray();
    print_r($errorData);
}
```

### Verificar Tipo de Erro

```php
// Verificar se erro e retryable
if ($e->isRetryable()) {
    // Tentar novamente
    sleep($e instanceof RateLimitException ? $e->getRetryAfter() : 5);
    $resultado = $client->consultaEndereco('Rua Teste', '100');
}
```

## Rate Limiting

```php
// Verificar rate limit apos requisicao
$rateLimit = $client->getRateLimit();
if ($rateLimit !== null) {
    echo "Limite: " . $rateLimit->limit . "\n";
    echo "Restantes: " . $rateLimit->remaining . "\n";
    echo "Reset em: " . $rateLimit->getResetDateTime()->format('Y-m-d H:i:s') . "\n";
}

// ID da ultima requisicao (util para suporte)
echo "Request ID: " . $client->getLastRequestId() . "\n";
```

## Hierarquia de Excecoes

```
IPTUAPIException (base)
├── AuthenticationException (401)
├── ForbiddenException (403)
├── NotFoundException (404)
├── RateLimitException (429) - retryable
├── ValidationException (400, 422)
├── ServerException (5xx) - retryable
├── TimeoutException (408) - retryable
└── NetworkException - retryable
```

## PSR Compliance

- **PSR-3**: Logger interface (compativel com Monolog, etc)
- **PSR-4**: Autoloading
- **PSR-12**: Coding style

## Testes

```bash
# Rodar testes
./vendor/bin/phpunit

# Com coverage
./vendor/bin/phpunit --coverage-html coverage

# Apenas um arquivo
./vendor/bin/phpunit tests/ExceptionTest.php
```

### Analise Estatica

```bash
# PHPStan
./vendor/bin/phpstan analyse src

# PHP CodeSniffer
./vendor/bin/phpcs src
```

## Cidades Suportadas

| Codigo | Cidade |
|--------|--------|
| sp | Sao Paulo |
| rj | Rio de Janeiro |
| bh | Belo Horizonte |
| recife | Recife |
| curitiba | Curitiba |
| poa | Porto Alegre |
| salvador | Salvador |
| fortaleza | Fortaleza |
| campinas | Campinas |
| santos | Santos |

## Exemplo Completo

```php
<?php

require_once 'vendor/autoload.php';

use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\RateLimitException;

// Configuracao
$retryConfig = new RetryConfig(maxRetries: 3);
$config = new ClientConfig(timeout: 30, retryConfig: $retryConfig);
$client = new IPTUClient($_ENV['IPTU_API_KEY'], $config);

// Consultar varios enderecos
$enderecos = [
    ['logradouro' => 'Avenida Paulista', 'numero' => '1000'],
    ['logradouro' => 'Rua Augusta', 'numero' => '500'],
    ['logradouro' => 'Avenida Faria Lima', 'numero' => '3000'],
];

foreach ($enderecos as $endereco) {
    try {
        $resultado = $client->consultaEndereco(
            $endereco['logradouro'],
            $endereco['numero'],
            'sp'
        );

        echo sprintf(
            "SQL: %s, Valor Venal: R$ %s\n",
            $resultado['sql'],
            number_format($resultado['valor_venal'], 2, ',', '.')
        );

        // Verificar rate limit
        $rateLimit = $client->getRateLimit();
        if ($rateLimit && $rateLimit->remaining < 10) {
            echo "Atencao: Apenas {$rateLimit->remaining} requisicoes restantes\n";
        }

    } catch (RateLimitException $e) {
        echo "Rate limit atingido. Aguardando {$e->getRetryAfter()}s...\n";
        sleep($e->getRetryAfter());
    } catch (IPTUAPIException $e) {
        echo "Erro ao consultar {$endereco['logradouro']}: {$e->getMessage()}\n";
    }
}
```

## Licenca

MIT License - veja [LICENSE](LICENSE) para detalhes.

## Links

- [Documentacao](https://iptuapi.com.br/docs)
- [API Reference](https://iptuapi.com.br/docs/api)
- [Portal do Desenvolvedor](https://iptuapi.com.br/dashboard)
