<?php

/**
 * Exemplo basico de uso do SDK
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IPTUAPI\IPTUClient;

// Obter API key do ambiente
$apiKey = getenv('IPTU_API_KEY');
if (!$apiKey) {
    die("IPTU_API_KEY environment variable is required\n");
}

// Criar cliente
$client = new IPTUClient($apiKey);

// Consulta por endereco
echo "=== Consulta por Endereco ===\n";
$resultado = $client->consultaEndereco(
    logradouro: 'Avenida Paulista',
    numero: '1000',
    cidade: 'sp'
);

echo "SQL: {$resultado['sql']}\n";
echo "Logradouro: {$resultado['logradouro']}, {$resultado['numero']}\n";
echo "Bairro: {$resultado['bairro']}\n";
echo sprintf("Area Terreno: %.2f m²\n", $resultado['area_terreno'] ?? 0);
echo sprintf("Area Construida: %.2f m²\n", $resultado['area_construida'] ?? 0);
echo sprintf("Valor Venal: R$ %s\n", number_format($resultado['valor_venal_total'] ?? 0, 2, ',', '.'));

// Verificar rate limit
$rateLimit = $client->getRateLimit();
if ($rateLimit) {
    echo "\nRate Limit: {$rateLimit->remaining}/{$rateLimit->limit}\n";
}

echo "Request ID: {$client->getLastRequestId()}\n";
