<?php

declare(strict_types=1);

namespace IPTUAPI;

use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;

/**
 * Cliente para a IPTU API.
 *
 * SDK oficial para integração com a IPTU API.
 *
 * @example
 * $client = new IPTUClient('sua_api_key');
 * $resultado = $client->consultaEndereco('Avenida Paulista', '1000');
 */
class IPTUClient
{
    private const DEFAULT_BASE_URL = 'https://iptuapi.com.br/api/v1';
    private const DEFAULT_TIMEOUT = 30;

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        int $timeout = self::DEFAULT_TIMEOUT
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Busca dados de IPTU por endereço.
     *
     * @param string $logradouro Nome da rua/avenida
     * @param string|null $numero Número do imóvel (opcional)
     * @return array Dados do imóvel encontrado
     * @throws IPTUAPIException
     */
    public function consultaEndereco(string $logradouro, ?string $numero = null): array
    {
        $params = ['logradouro' => $logradouro];
        if ($numero !== null) {
            $params['numero'] = $numero;
        }

        return $this->request('GET', '/consulta/endereco', $params);
    }

    /**
     * Busca dados de IPTU por número SQL.
     * Requer plano Starter ou superior.
     *
     * @param string $sql Número SQL do imóvel
     * @return array Dados completos do imóvel
     * @throws IPTUAPIException
     */
    public function consultaSQL(string $sql): array
    {
        return $this->request('GET', '/consulta/sql', ['sql' => $sql]);
    }

    /**
     * Estima o valor de mercado do imóvel.
     * Requer plano Pro ou superior.
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
     * Busca dados de IPTU por endereço para qualquer cidade suportada.
     *
     * Cidades suportadas: "sao_paulo", "belo_horizonte", "recife"
     *
     * @param string $cidade Cidade ("sao_paulo", "belo_horizonte" ou "recife")
     * @param string $logradouro Nome da rua/avenida
     * @param int|null $numero Número do imóvel (opcional)
     * @param int $ano Ano de referência (default: 2025)
     * @param int $limit Limite de resultados (default: 20)
     * @return array Lista de imóveis encontrados (Recife inclui lat/long)
     * @throws IPTUAPIException
     *
     * @example
     * // São Paulo
     * $resultados = $client->consultaIPTU('sao_paulo', 'Paulista', 1000);
     * // Belo Horizonte
     * $resultados = $client->consultaIPTU('belo_horizonte', 'Afonso Pena');
     * // Recife (com coordenadas)
     * $resultados = $client->consultaIPTU('recife', 'Boa Viagem', null, 2025);
     */
    public function consultaIPTU(
        string $cidade,
        string $logradouro,
        ?int $numero = null,
        int $ano = 2025,
        int $limit = 20
    ): array {
        $params = [
            'logradouro' => $logradouro,
            'ano' => $ano,
            'limit' => $limit,
        ];
        if ($numero !== null) {
            $params['numero'] = $numero;
        }

        return $this->request('GET', "/dados/iptu/{$cidade}/endereco", $params);
    }

    /**
     * Busca dados de IPTU pelo identificador único do imóvel.
     *
     * Para São Paulo: use o número SQL (ex: "00904801381")
     * Para Belo Horizonte: use o Índice Cadastral (ex: "007028 005 0086")
     * Para Recife: use o número do contribuinte
     *
     * @param string $cidade Cidade ("sao_paulo", "belo_horizonte" ou "recife")
     * @param string $identificador Número SQL (SP), Índice Cadastral (BH) ou Contribuinte (Recife)
     * @param int|null $ano Ano de referência (opcional)
     * @return array Lista de dados do imóvel (Recife inclui lat/long)
     * @throws IPTUAPIException
     *
     * @example
     * // São Paulo
     * $resultados = $client->consultaIPTUSQL('sao_paulo', '00904801381');
     * // Belo Horizonte
     * $resultados = $client->consultaIPTUSQL('belo_horizonte', '007028 005 0086');
     * // Recife
     * $resultados = $client->consultaIPTUSQL('recife', '123456789');
     */
    public function consultaIPTUSQL(
        string $cidade,
        string $identificador,
        ?int $ano = null
    ): array {
        $params = [];
        if ($ano !== null) {
            $params['ano'] = $ano;
        }

        $encodedId = rawurlencode($identificador);
        return $this->request('GET', "/dados/iptu/{$cidade}/sql/{$encodedId}", $params ?: null);
    }

    /**
     * Executa uma requisição à API.
     */
    private function request(
        string $method,
        string $endpoint,
        ?array $queryParams = null,
        ?array $body = null
    ): array {
        $url = $this->baseUrl . $endpoint;

        if ($queryParams !== null) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new IPTUAPIException("Erro de conexão: $error");
        }

        $data = json_decode($response, true);

        if ($statusCode === 200) {
            return $data;
        }

        $message = $data['detail'] ?? 'Erro na API';

        switch ($statusCode) {
            case 400:
                throw new ValidationException($message);
            case 401:
                throw new AuthenticationException($message);
            case 403:
                throw new ForbiddenException($message);
            case 404:
                throw new NotFoundException($message);
            case 429:
                throw new RateLimitException($message);
            default:
                throw new IPTUAPIException($message, $statusCode);
        }
    }
}
