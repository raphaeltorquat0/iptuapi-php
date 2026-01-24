# PHP SDK Status

**Última atualização:** 2026-01-24
**Versão:** 2.1.2
**Status:** 🟢 FUNCIONAL

---

## Informações

| Item | Valor |
|------|-------|
| **Versão** | 2.1.2 |
| **Registry** | Packagist (`composer require raphaeltorquat0/iptuapi-php`) |
| **Status** | 🟢 FUNCIONAL |
| **Mínimo** | PHP 8.1+ |

## Instalação

```bash
composer require raphaeltorquat0/iptuapi-php
```

## Exemplo Rápido

```php
<?php
require_once 'vendor/autoload.php';

use IPTUAPI\IPTUClient;

$client = new IPTUClient('sua_api_key');
$cidades = $client->iptuToolsCidades();
echo "{$cidades['total']} cidades disponíveis";
```

## Validação Automática

Este SDK é validado automaticamente:
- ✅ Instalação limpa via Composer
- ✅ Autoload do pacote
- ✅ Teste contra API real (`iptuToolsCidades`)
- ✅ Teste autenticado (`consultaEndereco`)

---

*Atualizado automaticamente pelo CI/CD*
