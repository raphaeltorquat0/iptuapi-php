# Exemplos - PHP SDK

## Instalacao

```bash
composer require raphaeltorquat0/iptuapi-php
```

## Executando os Exemplos

```bash
# Configurar API Key
export IPTU_API_KEY="sua_api_key"

# Instalar dependencias
composer install

# Exemplo basico
php examples/basic.php

# Exemplo avancado (retry, erros)
php examples/advanced.php

# Exemplo de valuation (requer plano Pro+)
php examples/valuation.php

# Exemplo de IPTU Tools
php examples/iptu-tools.php
```

## Exemplos Disponiveis

| Exemplo | Descricao | Plano |
|---------|-----------|-------|
| `basic.php` | Consulta simples por endereco | Free |
| `advanced.php` | Retry, timeout, tratamento de erros | Free |
| `valuation.php` | Estimativa de valor de mercado | Pro+ |
| `iptu-tools.php` | Calendario, simulador, isencao | Free |

## Uso com Laravel

```php
// config/services.php
return [
    'iptuapi' => [
        'key' => env('IPTU_API_KEY'),
    ],
];

// app/Providers/AppServiceProvider.php
use IPTUAPI\IPTUClient;

public function register()
{
    $this->app->singleton(IPTUClient::class, function ($app) {
        return new IPTUClient(config('services.iptuapi.key'));
    });
}
```
