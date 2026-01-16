<?php

declare(strict_types=1);

namespace IPTUAPI;

use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\NetworkException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ServerException;
use IPTUAPI\Exception\TimeoutException;
use IPTUAPI\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cliente para a IPTU API.
 *
 * SDK oficial para integração com a IPTU API.
 * Suporta retry automático, logging e rate limit tracking.
 *
 * @example
 * $client = new IPTUClient('sua_api_key');
 * $resultado = $client->consultaEndereco('Avenida Paulista', '1000');
 *
 * @example Com configuração customizada
 * $config = new ClientConfig(
 *     timeout: 60,
 *     retryConfig: new RetryConfig(maxRetries: 5)
 * );
 * $client = new IPTUClient('sua_api_key', $config);
 */
class IPTUClient
{
    public const VERSION = '2.0.0';

    private const DEFAULT_BASE_URL = 'https://iptuapi.com.br/api/v1';
    private const DEFAULT_TIMEOUT = 30;

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private RetryConfig $retryConfig;
    private LoggerInterface $logger;
    private string $userAgent;

    private ?RateLimitInfo $rateLimit = null;
    private ?string $lastRequestId = null;

    public function __construct(
        string $apiKey,
        ?ClientConfig $config = null
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API Key é obrigatória');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $config?->baseUrl ?? self::DEFAULT_BASE_URL;
        $this->timeout = $config?->timeout ?? self::DEFAULT_TIMEOUT;
        $this->retryConfig = $config?->retryConfig ?? new RetryConfig();
        $this->logger = $config?->logger ?? new NullLogger();
        $this->userAgent = $config?->userAgent ?? 'iptuapi-php/' . self::VERSION;
    }

    // =========================================================================
    // Properties
    // =========================================================================

    /**
     * Informações de rate limit da última requisição.
     */
    public function getRateLimit(): ?RateLimitInfo
    {
        return $this->rateLimit;
    }

    /**
     * ID da última requisição (útil para suporte).
     */
    public function getLastRequestId(): ?string
    {
        return $this->lastRequestId;
    }

    // =========================================================================
    // Consulta Endpoints
    // =========================================================================

    /**
     * Busca dados de IPTU por endereço.
     *
     * @param string $logradouro Nome da rua/avenida
     * @param string|null $numero Número do imóvel (opcional)
     * @param string $cidade Cidade (sp, bh, recife, poa, fortaleza, curitiba, rj, brasilia)
     * @param array $options Opções adicionais (incluirHistorico, incluirComparaveis, incluirZoneamento)
     * @return array Dados do imóvel encontrado
     * @throws IPTUAPIException
     */
    public function consultaEndereco(
        string $logradouro,
        ?string $numero = null,
        string $cidade = 'sp',
        array $options = []
    ): array {
        $params = [
            'logradouro' => $logradouro,
            'cidade' => $cidade,
        ];

        if ($numero !== null) {
            $params['numero'] = $numero;
        }

        if (!empty($options['incluirHistorico'])) {
            $params['incluir_historico'] = 'true';
        }
        if (!empty($options['incluirComparaveis'])) {
            $params['incluir_comparaveis'] = 'true';
        }
        if (!empty($options['incluirZoneamento'])) {
            $params['incluir_zoneamento'] = 'true';
        }

        return $this->request('GET', '/consulta/endereco', $params);
    }

    /**
     * Busca dados de IPTU por número SQL (contribuinte).
     *
     * @param string $sql Número SQL do imóvel
     * @param string $cidade Cidade (sp, bh, recife, poa, fortaleza, curitiba, rj, brasilia)
     * @param array $options Opções adicionais
     * @return array Dados completos do imóvel
     * @throws IPTUAPIException
     */
    public function consultaSQL(
        string $sql,
        string $cidade = 'sp',
        array $options = []
    ): array {
        $params = ['cidade' => $cidade];

        if (!empty($options['incluirHistorico'])) {
            $params['incluir_historico'] = 'true';
        }
        if (!empty($options['incluirComparaveis'])) {
            $params['incluir_comparaveis'] = 'true';
        }

        return $this->request('GET', "/consulta/sql/{$sql}", $params);
    }

    /**
     * Busca imóveis por CEP.
     *
     * @param string $cep CEP do imóvel
     * @param string $cidade Cidade (sp, bh, recife, poa, fortaleza, curitiba, rj, brasilia)
     * @return array Lista de imóveis no CEP
     * @throws IPTUAPIException
     */
    public function consultaCEP(string $cep, string $cidade = 'sp'): array
    {
        $cep = preg_replace('/\D/', '', $cep);
        return $this->request('GET', "/consulta/cep/{$cep}", ['cidade' => $cidade]);
    }

    /**
     * Consulta zoneamento por coordenadas.
     *
     * @param float $latitude Latitude do ponto
     * @param float $longitude Longitude do ponto
     * @return array Dados de zoneamento
     * @throws IPTUAPIException
     */
    public function consultaZoneamento(float $latitude, float $longitude): array
    {
        return $this->request('GET', '/consulta/zoneamento', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    // =========================================================================
    // Valuation Endpoints (Pro+)
    // =========================================================================

    /**
     * Estima o valor de mercado do imóvel usando ML.
     * Disponível apenas para planos Pro e Enterprise.
     *
     * @param array $params Parâmetros da avaliação
     * @return array Estimativa de valor
     * @throws IPTUAPIException
     */
    public function valuationEstimate(array $params): array
    {
        return $this->request('POST', '/valuation/estimate', null, $params);
    }

    /**
     * Valuation em lote (até 100 imóveis).
     * Disponível apenas para plano Enterprise.
     *
     * @param array $imoveis Lista de imóveis para avaliar
     * @return array Resultados de valuation
     * @throws IPTUAPIException
     */
    public function valuationBatch(array $imoveis): array
    {
        return $this->request('POST', '/valuation/estimate/batch', null, ['imoveis' => $imoveis]);
    }

    /**
     * Busca imóveis comparáveis para análise.
     *
     * @param string $bairro Nome do bairro
     * @param float $areaMin Área mínima em m²
     * @param float $areaMax Área máxima em m²
     * @param string $cidade Cidade (sp, bh, recife, poa, fortaleza, curitiba, rj, brasilia)
     * @param int $limit Número máximo de resultados
     * @return array Lista de imóveis comparáveis
     * @throws IPTUAPIException
     */
    public function valuationComparables(
        string $bairro,
        float $areaMin,
        float $areaMax,
        string $cidade = 'sp',
        int $limit = 10
    ): array {
        return $this->request('GET', '/valuation/comparables', [
            'bairro' => $bairro,
            'area_min' => $areaMin,
            'area_max' => $areaMax,
            'cidade' => $cidade,
            'limit' => $limit,
        ]);
    }

    // =========================================================================
    // Dados Endpoints
    // =========================================================================

    /**
     * Histórico de valores IPTU de um imóvel.
     *
     * @param string $sql Número SQL do imóvel
     * @param string $cidade Cidade (sp, bh, recife, poa, fortaleza, curitiba, rj, brasilia)
     * @return array Lista com histórico anual
     * @throws IPTUAPIException
     */
    public function dadosIPTUHistorico(string $sql, string $cidade = 'sp'): array
    {
        return $this->request('GET', "/dados/iptu/historico/{$sql}", ['cidade' => $cidade]);
    }

    /**
     * Consulta dados de empresa por CNPJ.
     *
     * @param string $cnpj CNPJ da empresa
     * @return array Dados cadastrais
     * @throws IPTUAPIException
     */
    public function dadosCNPJ(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        return $this->request('GET', "/dados/cnpj/{$cnpj}");
    }

    /**
     * Correção monetária pelo IPCA.
     *
     * @param float $valor Valor a corrigir
     * @param string $dataOrigem Data do valor original (YYYY-MM)
     * @param string|null $dataDestino Data destino (default: atual)
     * @return array Valor corrigido e fator de correção
     * @throws IPTUAPIException
     */
    public function dadosIPCACorrigir(
        float $valor,
        string $dataOrigem,
        ?string $dataDestino = null
    ): array {
        $params = [
            'valor' => $valor,
            'data_origem' => $dataOrigem,
        ];

        if ($dataDestino !== null) {
            $params['data_destino'] = $dataDestino;
        }

        return $this->request('GET', '/dados/ipca/corrigir', $params);
    }

    // =========================================================================
    // Internal Methods
    // =========================================================================

    private function isRetryable(int $statusCode): bool
    {
        return in_array($statusCode, $this->retryConfig->retryableStatuses, true);
    }

    private function calculateDelay(int $attempt): int
    {
        $delay = (int) ($this->retryConfig->initialDelay * pow($this->retryConfig->backoffFactor, $attempt));
        return min($delay, $this->retryConfig->maxDelay);
    }

    private function extractRateLimit(array $headers): void
    {
        $limit = $headers['x-ratelimit-limit'][0] ?? null;
        $remaining = $headers['x-ratelimit-remaining'][0] ?? null;
        $reset = $headers['x-ratelimit-reset'][0] ?? null;

        if ($limit !== null && $remaining !== null && $reset !== null) {
            $this->rateLimit = new RateLimitInfo(
                (int) $limit,
                (int) $remaining,
                (int) $reset
            );
        }

        $this->lastRequestId = $headers['x-request-id'][0] ?? null;
    }

    /**
     * Executa uma requisição à API com retry automático.
     *
     * @throws IPTUAPIException
     */
    private function request(
        string $method,
        string $endpoint,
        ?array $queryParams = null,
        ?array $body = null
    ): array {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        if ($queryParams !== null && count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        $lastException = null;

        for ($attempt = 0; $attempt <= $this->retryConfig->maxRetries; $attempt++) {
            if ($attempt > 0) {
                $delay = $this->calculateDelay($attempt - 1);
                $maxRetries = $this->retryConfig->maxRetries;
                $this->logger->warning(
                    "Request failed, retrying in {$delay}ms (attempt {$attempt}/{$maxRetries})"
                );
                usleep($delay * 1000);
            }

            $this->logger->debug("Request: {$method} {$url}");

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => [
                    'X-API-Key: ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: ' . $this->userAgent,
                ],
            ]);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                }
            }

            $response = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($errno !== 0) {
                if ($errno === CURLE_OPERATION_TIMEDOUT) {
                    $lastException = new TimeoutException("Timeout após {$this->timeout}s", $this->timeout);
                } else {
                    $lastException = new NetworkException("Erro de conexão: {$error}");
                }

                if ($attempt < $this->retryConfig->maxRetries) {
                    continue;
                }
                throw $lastException;
            }

            // Parse headers
            $headerStr = substr($response, 0, $headerSize);
            $bodyStr = substr($response, $headerSize);
            $headers = $this->parseHeaders($headerStr);

            $this->extractRateLimit($headers);
            $this->logger->debug("Response: {$statusCode} {$url}");

            if ($statusCode >= 200 && $statusCode < 300) {
                return json_decode($bodyStr, true) ?? [];
            }

            // Handle error response
            if ($this->isRetryable($statusCode) && $attempt < $this->retryConfig->maxRetries) {
                continue;
            }

            $data = json_decode($bodyStr, true) ?? [];
            throw $this->createException($statusCode, $data, $headers);
        }

        throw $lastException ?? new IPTUAPIException('Máximo de retries excedido');
    }

    private function parseHeaders(string $headerStr): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $headers[$key][] = trim($value);
            }
        }
        return $headers;
    }

    private function createException(int $statusCode, array $data, array $headers): IPTUAPIException
    {
        $message = $data['detail'] ?? 'Erro na API';

        switch ($statusCode) {
            case 400:
            case 422:
                return new ValidationException(
                    $message,
                    $data['errors'] ?? [],
                    $this->lastRequestId
                );
            case 401:
                return new AuthenticationException($message, $this->lastRequestId);
            case 403:
                return new ForbiddenException(
                    $message,
                    $data['required_plan'] ?? null,
                    $this->lastRequestId
                );
            case 404:
                return new NotFoundException($message, null, $this->lastRequestId);
            case 429:
                $retryAfter = isset($headers['retry-after'][0]) ? (int) $headers['retry-after'][0] : null;
                return new RateLimitException(
                    $message,
                    $retryAfter,
                    $this->lastRequestId
                );
            case 500:
            case 502:
            case 503:
            case 504:
                return new ServerException($message, $statusCode, $this->lastRequestId);
            default:
                return new IPTUAPIException($message, $statusCode, $this->lastRequestId);
        }
    }

    // =========================================================================
    // IPTU Tools Endpoints (Ferramentas IPTU 2026)
    // =========================================================================

    /**
     * Lista todas as cidades com calendario de IPTU disponivel.
     *
     * @return array Lista de cidades com codigo, nome, desconto e parcelas
     * @throws IPTUAPIException
     */
    public function iptuToolsCidades(): array
    {
        return $this->request('GET', '/iptu-tools/cidades');
    }

    /**
     * Retorna o calendario completo de IPTU para a cidade especificada.
     *
     * @param string $cidade Codigo da cidade (sp, bh, rj, recife, curitiba, poa, fortaleza)
     * @return array Calendario com vencimentos, descontos, alertas e novidades
     * @throws IPTUAPIException
     */
    public function iptuToolsCalendario(string $cidade = 'sp'): array
    {
        return $this->request('GET', '/iptu-tools/calendario', ['cidade' => $cidade]);
    }

    /**
     * Simula as opcoes de pagamento do IPTU (a vista vs parcelado).
     *
     * @param float $valorIptu Valor total do IPTU
     * @param string $cidade Codigo da cidade
     * @param float|null $valorVenal Valor venal do imovel (para verificar isencao)
     * @return array Comparativo entre pagamento a vista e parcelado com recomendacao
     * @throws IPTUAPIException
     */
    public function iptuToolsSimulador(
        float $valorIptu,
        string $cidade = 'sp',
        ?float $valorVenal = null
    ): array {
        $body = [
            'valor_iptu' => $valorIptu,
            'cidade' => $cidade,
        ];

        if ($valorVenal !== null) {
            $body['valor_venal'] = $valorVenal;
        }

        return $this->request('POST', '/iptu-tools/simulador', null, $body);
    }

    /**
     * Verifica se um imovel e elegivel para isencao de IPTU.
     *
     * @param float $valorVenal Valor venal do imovel
     * @param string $cidade Codigo da cidade
     * @return array Elegibilidade para isencao total ou parcial
     * @throws IPTUAPIException
     */
    public function iptuToolsIsencao(float $valorVenal, string $cidade = 'sp'): array
    {
        return $this->request('GET', '/iptu-tools/isencao', [
            'valor_venal' => $valorVenal,
            'cidade' => $cidade,
        ]);
    }

    /**
     * Retorna informacoes sobre o proximo vencimento do IPTU.
     *
     * @param string $cidade Codigo da cidade
     * @param int $parcela Numero da parcela (1-12)
     * @return array Data de vencimento, dias restantes e status
     * @throws IPTUAPIException
     */
    public function iptuToolsProximoVencimento(
        string $cidade = 'sp',
        int $parcela = 1
    ): array {
        return $this->request('GET', '/iptu-tools/proximo-vencimento', [
            'cidade' => $cidade,
            'parcela' => $parcela,
        ]);
    }
}
