<?php

/**
 * Exemplo de uso do endpoint de Valuation (Pro+)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IPTUAPI\IPTUClient;
use IPTUAPI\Exception\ForbiddenException;

$apiKey = getenv('IPTU_API_KEY');
if (!$apiKey) {
    die("IPTU_API_KEY environment variable is required\n");
}

$client = new IPTUClient($apiKey);

// Estimativa de valor de mercado
echo "=== Valuation Estimate ===\n";
try {
    $avaliacao = $client->valuationEstimate([
        'area_terreno' => 250,
        'area_construida' => 180,
        'bairro' => 'Pinheiros',
        'zona' => 'ZM',
        'tipo_uso' => 'Residencial',
        'tipo_padrao' => 'Medio',
        'ano_construcao' => 2010,
        'cidade' => 'sp',
    ]);

    echo sprintf("Valor Estimado: R$ %s\n", number_format($avaliacao['valor_estimado'], 2, ',', '.'));
    echo sprintf("Valor Minimo:   R$ %s\n", number_format($avaliacao['valor_minimo'] ?? 0, 2, ',', '.'));
    echo sprintf("Valor Maximo:   R$ %s\n", number_format($avaliacao['valor_maximo'] ?? 0, 2, ',', '.'));
    echo sprintf("Confianca:      %.1f%%\n", ($avaliacao['confianca'] ?? 0) * 100);
    echo "Comparaveis:    {$avaliacao['comparaveis_utilizados']}\n";

} catch (ForbiddenException $e) {
    echo "Este endpoint requer plano Pro ou superior\n";
    exit;
}

// Buscar comparaveis
echo "\n=== Comparaveis ===\n";
$comparaveis = $client->valuationComparables(
    bairro: 'Pinheiros',
    areaMin: 150,
    areaMax: 250,
    cidade: 'sp',
    limit: 5
);

foreach ($comparaveis as $i => $c) {
    $num = $i + 1;
    echo sprintf(
        "%d. %s, %s - %.0f m² - R$ %s\n",
        $num,
        $c['logradouro'],
        $c['numero'],
        $c['area_construida'] ?? 0,
        number_format($c['valor_venal_total'] ?? 0, 2, ',', '.')
    );
}
