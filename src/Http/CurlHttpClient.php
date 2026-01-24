<?php

declare(strict_types=1);

namespace IPTUAPI\Http;

use IPTUAPI\Exception\NetworkException;
use IPTUAPI\Exception\TimeoutException;

/**
 * Implementação do cliente HTTP usando cURL.
 */
class CurlHttpClient implements HttpClientInterface
{
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): HttpResponse {
        $ch = curl_init();

        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "{$name}: {$value}";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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
                throw new TimeoutException("Timeout após {$timeout}s", $timeout);
            }
            throw new NetworkException("Erro de conexão: {$error}");
        }

        $headerStr = substr($response, 0, $headerSize);
        $bodyStr = substr($response, $headerSize);
        $headers = $this->parseHeaders($headerStr);

        return new HttpResponse($statusCode, $bodyStr, $headers);
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
}
