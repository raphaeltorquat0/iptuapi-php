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
