<?php

/**
 * Exemplo de uso das ferramentas IPTU 2026
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IPTUAPI\IPTUClient;

$apiKey = getenv('IPTU_API_KEY');
if (!$apiKey) {
    die("IPTU_API_KEY environment variable is required\n");
}

$client = new IPTUClient($apiKey);

// Listar cidades disponiveis
echo "=== Cidades Disponiveis ===\n";
$resultado = $client->iptuToolsCidades();

foreach ($resultado['cidades'] as $c) {
    echo "  {$c['nome']} ({$c['codigo']}) - Desconto: {$c['desconto_vista']}, Parcelas: {$c['parcelas_max']}\n";
}

// Calendario de Sao Paulo
echo "\n=== Calendario SP 2026 ===\n";
$calendario = $client->iptuToolsCalendario('sp');

echo "Desconto a vista: {$calendario['desconto_vista_percentual']}%\n";
echo "Parcelas: ate {$calendario['parcelas_max']}\n";
echo "Proximo vencimento: {$calendario['proximo_vencimento']} ({$calendario['dias_para_proximo_vencimento']} dias)\n";

if (!empty($calendario['alertas'])) {
    echo "\nAlertas:\n";
    foreach ($calendario['alertas'] as $a) {
        echo "  ⚠️  {$a}\n";
    }
}

// Simulador de pagamento
echo "\n=== Simulador (IPTU R$ 2.000) ===\n";
$simulacao = $client->iptuToolsSimulador(
    valorIptu: 2000,
    cidade: 'sp',
    valorVenal: 500000
);

echo sprintf("A vista:    R$ %s (economia de R$ %s)\n",
    number_format($simulacao['valor_vista'], 2, ',', '.'),
    number_format($simulacao['economia_vista'], 2, ',', '.')
);
echo sprintf("Parcelado:  %dx de R$ %s = R$ %s\n",
    $simulacao['parcelas'],
    number_format($simulacao['valor_parcela'], 2, ',', '.'),
    number_format($simulacao['valor_total_parcelado'], 2, ',', '.')
);
echo "Recomendacao: {$simulacao['recomendacao']}\n";

// Verificar isencao
echo "\n=== Verificar Isencao ===\n";
$isencao = $client->iptuToolsIsencao(valorVenal: 250000, cidade: 'sp');

echo sprintf("Valor venal: R$ %s\n", number_format($isencao['valor_venal'], 2, ',', '.'));
echo sprintf("Limite isencao: R$ %s\n", number_format($isencao['limite_isencao'], 2, ',', '.'));
echo "Elegivel: " . ($isencao['elegivel_isencao_total'] ? 'Sim' : 'Nao') . "\n";
echo "Mensagem: {$isencao['mensagem']}\n";
