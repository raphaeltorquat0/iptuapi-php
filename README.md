# IPTU API - PHP SDK

SDK oficial para integração com a IPTU API.

## Instalação

```bash
composer require iptuapi/iptuapi-php
```

## Uso Rápido

```php
<?php

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');

// Consulta por endereço
$resultado = $client->consultaEndereco('Avenida Paulista', '1000');
print_r($resultado);

// Consulta por SQL (Starter+)
$dados = $client->consultaSQL('100-01-001-001');

// Avaliação de mercado (Pro+)
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

$client = new IPTUClient('sua_api_key');

try {
    $resultado = $client->consultaEndereco('Rua Inexistente');
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
