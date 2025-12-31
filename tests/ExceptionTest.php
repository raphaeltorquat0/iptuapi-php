<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\Exception\IPTUAPIException;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;
use IPTUAPI\Exception\ServerException;
use IPTUAPI\Exception\TimeoutException;
use IPTUAPI\Exception\NetworkException;

class ExceptionTest extends TestCase
{
    public function testIPTUAPIExceptionDefaults(): void
    {
        $exception = new IPTUAPIException();

        $this->assertEquals('Erro na API', $exception->getMessage());
        $this->assertEquals(0, $exception->getStatusCode());
        $this->assertNull($exception->getRequestId());
        $this->assertFalse($exception->isRetryable());
    }

    public function testIPTUAPIExceptionCustomValues(): void
    {
        $exception = new IPTUAPIException('Custom error', 500, 'req-123');

        $this->assertEquals('Custom error', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('req-123', $exception->getRequestId());
    }

    public function testIPTUAPIExceptionToArray(): void
    {
        $exception = new IPTUAPIException('Test error', 400, 'req-456');
        $array = $exception->toArray();

        $this->assertEquals(IPTUAPIException::class, $array['type']);
        $this->assertEquals('Test error', $array['message']);
        $this->assertEquals(400, $array['status_code']);
        $this->assertEquals('req-456', $array['request_id']);
        $this->assertFalse($array['retryable']);
    }

    public function testAuthenticationException(): void
    {
        $exception = new AuthenticationException();

        $this->assertEquals('API Key inválida ou expirada', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertFalse($exception->isRetryable());
    }

    public function testAuthenticationExceptionWithRequestId(): void
    {
        $exception = new AuthenticationException('Custom auth error', 'req-auth');

        $this->assertEquals('Custom auth error', $exception->getMessage());
        $this->assertEquals('req-auth', $exception->getRequestId());
    }

    public function testForbiddenException(): void
    {
        $exception = new ForbiddenException();

        $this->assertEquals('Plano não autorizado para este recurso', $exception->getMessage());
        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertNull($exception->getRequiredPlan());
        $this->assertFalse($exception->isRetryable());
    }

    public function testForbiddenExceptionWithRequiredPlan(): void
    {
        $exception = new ForbiddenException('Upgrade required', 'enterprise', 'req-403');

        $this->assertEquals('enterprise', $exception->getRequiredPlan());
        $this->assertEquals('req-403', $exception->getRequestId());

        $array = $exception->toArray();
        $this->assertEquals('enterprise', $array['required_plan']);
    }

    public function testNotFoundException(): void
    {
        $exception = new NotFoundException();

        $this->assertEquals('Recurso não encontrado', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertNull($exception->getResource());
        $this->assertFalse($exception->isRetryable());
    }

    public function testNotFoundExceptionWithResource(): void
    {
        $exception = new NotFoundException('Property not found', 'imovel/123', 'req-404');

        $this->assertEquals('imovel/123', $exception->getResource());

        $array = $exception->toArray();
        $this->assertEquals('imovel/123', $array['resource']);
    }

    public function testRateLimitException(): void
    {
        $exception = new RateLimitException();

        $this->assertEquals('Limite de requisições excedido', $exception->getMessage());
        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertNull($exception->getRetryAfter());
        $this->assertTrue($exception->isRetryable());
    }

    public function testRateLimitExceptionWithRetryAfter(): void
    {
        $exception = new RateLimitException('Too many requests', 60, 'req-429');

        $this->assertEquals(60, $exception->getRetryAfter());

        $array = $exception->toArray();
        $this->assertEquals(60, $array['retry_after']);
        $this->assertTrue($array['retryable']);
    }

    public function testValidationException(): void
    {
        $exception = new ValidationException();

        $this->assertEquals('Parâmetros inválidos', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
        $this->assertEmpty($exception->getErrors());
        $this->assertFalse($exception->isRetryable());
    }

    public function testValidationExceptionWithErrors(): void
    {
        $errors = [
            'cidade' => ['Campo obrigatório'],
            'logradouro' => ['Deve ter no mínimo 3 caracteres', 'Caracteres inválidos'],
        ];
        $exception = new ValidationException('Validation failed', $errors, 'req-400');

        $this->assertEquals($errors, $exception->getErrors());
        $this->assertTrue($exception->hasFieldError('cidade'));
        $this->assertTrue($exception->hasFieldError('logradouro'));
        $this->assertFalse($exception->hasFieldError('numero'));

        $this->assertEquals(['Campo obrigatório'], $exception->getFieldErrors('cidade'));
        $this->assertEquals([], $exception->getFieldErrors('numero'));

        $array = $exception->toArray();
        $this->assertEquals($errors, $array['errors']);
    }

    public function testServerException(): void
    {
        $exception = new ServerException();

        $this->assertEquals('Erro interno do servidor', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertTrue($exception->isRetryable());
    }

    public function testServerExceptionCustomCode(): void
    {
        $exception = new ServerException('Bad Gateway', 502, 'req-502');

        $this->assertEquals(502, $exception->getStatusCode());
        $this->assertEquals('req-502', $exception->getRequestId());
    }

    public function testTimeoutException(): void
    {
        $exception = new TimeoutException();

        $this->assertEquals('Timeout na requisição', $exception->getMessage());
        $this->assertEquals(408, $exception->getStatusCode());
        $this->assertEquals(30, $exception->getTimeoutSeconds());
        $this->assertTrue($exception->isRetryable());
    }

    public function testTimeoutExceptionCustomTimeout(): void
    {
        $exception = new TimeoutException('Request timed out', 60);

        $this->assertEquals(60, $exception->getTimeoutSeconds());
    }

    public function testNetworkException(): void
    {
        $exception = new NetworkException();

        $this->assertEquals('Erro de conexão com a API', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
    }

    public function testNetworkExceptionCustomMessage(): void
    {
        $exception = new NetworkException('DNS resolution failed');

        $this->assertEquals('DNS resolution failed', $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $this->assertInstanceOf(IPTUAPIException::class, new AuthenticationException());
        $this->assertInstanceOf(IPTUAPIException::class, new ForbiddenException());
        $this->assertInstanceOf(IPTUAPIException::class, new NotFoundException());
        $this->assertInstanceOf(IPTUAPIException::class, new RateLimitException());
        $this->assertInstanceOf(IPTUAPIException::class, new ValidationException());
        $this->assertInstanceOf(IPTUAPIException::class, new ServerException());
        $this->assertInstanceOf(IPTUAPIException::class, new TimeoutException());
        $this->assertInstanceOf(IPTUAPIException::class, new NetworkException());
        $this->assertInstanceOf(\Exception::class, new IPTUAPIException());
    }
}
