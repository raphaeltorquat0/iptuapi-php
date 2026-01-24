# Changelog

All notable changes to the IPTU API PHP SDK will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.2] - 2026-01-24

### Fixed
- Added explicit `version` field to composer.json for proper Packagist versioning
- Added `autoload-dev` section for test classes
- Updated package description to include all supported cities

### Changed
- Updated `VERSION` constant to `2.1.2`

## [2.1.0] - 2025-12-15

### Added
- Per-request timeout parameter for all public methods
- IPTU Tools endpoints for 2026 calendar data
  - `iptuToolsCidades()` - List cities with IPTU calendar
  - `iptuToolsCalendario()` - Get IPTU calendar for a city
  - `iptuToolsSimulador()` - Simulate payment options
  - `iptuToolsIsencao()` - Check exemption eligibility
  - `iptuToolsProximoVencimento()` - Get next due date info
- Brasilia city support

### Changed
- All methods now accept optional `?int $timeout` parameter to override default config timeout
- Updated `VERSION` constant to `2.1.0`

### Example
```php
// Timeout específico para operações lentas
$result = $client->valuationBatch($imoveis, timeout: 120);

// Via options array
$result = $client->consultaEndereco("Avenida Paulista", "1000", "sp", [
    'incluirHistorico' => true,
    'timeout' => 60
]);
```

## [2.0.0] - 2025-11-01

### Added
- Complete SDK rewrite with PHP 8.1+ features
- `declare(strict_types=1)` for type safety
- PSR-3 logging support via `LoggerInterface`
- Exception hierarchy under `IPTUAPI\Exception` namespace
  - `IPTUAPIException` (base)
  - `AuthenticationException`, `ForbiddenException`, `NotFoundException`
  - `RateLimitException`, `ValidationException`, `ServerException`
  - `TimeoutException`, `NetworkException`
- `isRetryable()` method on all exceptions
- `toArray()` method for exception serialization
- Configurable retry with exponential backoff via `RetryConfig`
- Rate limit tracking via `getRateLimit()` and `getLastRequestId()`
- `ClientConfig` for centralized configuration
- Valuation endpoints (Pro+): `valuationEstimate()`, `valuationBatch()`, `valuationComparables()`
- Data endpoints: `dadosIPTUHistorico()`, `dadosCNPJ()`, `dadosIPCACorrigir()`

### Changed
- Minimum PHP version: 8.1
- Client initialization: `new IPTUClient($apiKey, $config)`
- All methods use named arguments (PHP 8.0+)

### Removed
- PHP 7.x support
- Legacy array-based error handling

## [1.0.0] - 2025-09-01

### Added
- Initial release
- Basic consultation methods: `consultaEndereco()`, `consultaSQL()`, `consultaCEP()`
- Zoning query: `consultaZoneamento()`
- Support for multiple cities (SP, BH, Recife, POA, Fortaleza, Curitiba, RJ)
