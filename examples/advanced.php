<?php

/**
 * Exemplo avancado com configuracao, retry e tratamento de erros
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;
use IPTUAPI\Exception\ServerException;
use IPTUAPI\Exception\TimeoutException;
use IPTUAPI\Exception\NetworkException;
use IPTUAPI\Exception\IPTUAPIException;
use Psr\Log\AbstractLogger;

// Logger simples para debug
class ConsoleLogger extends AbstractLogger
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        echo "[" . strtoupper($level) . "] $message\n";
    }
}

$apiKey = getenv('IPTU_API_KEY');
if (!$apiKey) {
    die("IPTU_API_KEY environment variable is required\n");
}

// Configuracao avancada
$retryConfig = new RetryConfig(
    maxRetries: 5,
    initialDelay: 1000,
    maxDelay: 30000,
    backoffFactor: 2.0
);

$config = new ClientConfig(
    timeout: 60,
    retryConfig: $retryConfig,
    logger: new ConsoleLogger()
);

$client = new IPTUClient($apiKey, $config);

// Consulta com tratamento de erros
echo "=== Consulta com Tratamento de Erros ===\n";
try {
    $resultado = $client->consultaEndereco(
        logradouro: 'Avenida Paulista',
        numero: '1000',
        cidade: 'sp',
        options: [
            'incluirHistorico' => true,
            'incluirZoneamento' => true,
        ]
    );

    echo "SQL: {$resultado['sql']}\n";
    echo sprintf("Valor Venal: R$ %s\n", number_format($resultado['valor_venal_total'] ?? 0, 2, ',', '.'));

    // Historico
    if (!empty($resultado['historico'])) {
        echo "\n=== Historico ===\n";
        foreach ($resultado['historico'] as $h) {
            echo sprintf("  %d: R$ %s\n", $h['ano'], number_format($h['valor_venal_total'] ?? 0, 2, ',', '.'));
        }
    }

    // Zoneamento
    if (!empty($resultado['zoneamento'])) {
        $z = $resultado['zoneamento'];
        echo "\n=== Zoneamento ===\n";
        echo "  Zona: {$z['zona']} ({$z['zona_descricao']})\n";
        echo sprintf("  CA Basico: %.2f\n", $z['coeficiente_aproveitamento_basico'] ?? 0);
        echo sprintf("  CA Maximo: %.2f\n", $z['coeficiente_aproveitamento_maximo'] ?? 0);
    }

} catch (AuthenticationException $e) {
    echo "Erro: API Key invalida\n";
} catch (ForbiddenException $e) {
    echo "Erro: Plano nao autorizado. Requer: {$e->getRequiredPlan()}\n";
} catch (NotFoundException $e) {
    echo "Erro: Imovel nao encontrado\n";
} catch (RateLimitException $e) {
    echo "Erro: Rate limit excedido. Retry em {$e->getRetryAfter()}s\n";
} catch (ValidationException $e) {
    echo "Erro: Parametros invalidos\n";
    foreach ($e->getErrors() as $error) {
        echo "  - {$error['field']}: {$error['message']}\n";
    }
} catch (ServerException $e) {
    echo "Erro: Servidor (status {$e->getCode()})\n";
} catch (TimeoutException $e) {
    echo "Erro: Timeout\n";
} catch (NetworkException $e) {
    echo "Erro: Falha de conexao\n";
} catch (IPTUAPIException $e) {
    echo "Erro: {$e->getMessage()} (Request ID: {$e->getRequestId()})\n";
}
