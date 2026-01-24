<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use IPTUAPI\Http\MockHttpClient;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;
use IPTUAPI\Exception\ServerException;
use IPTUAPI\Exception\TimeoutException;
use IPTUAPI\Exception\NetworkException;

/**
 * Integration tests for IPTUClient using MockHttpClient.
 */
class IntegrationTest extends TestCase
{
    private MockHttpClient $mockHttp;
    private IPTUClient $client;

    protected function setUp(): void
    {
        $this->mockHttp = new MockHttpClient();
        $config = new ClientConfig(
            baseUrl: 'https://api.test.iptuapi.com.br/api/v1',
            timeout: 30,
            retryConfig: new RetryConfig(maxRetries: 0) // Disable retries for most tests
        );
        $this->client = new IPTUClient('test-api-key', $config, $this->mockHttp);
    }

    // =========================================================================
    // Consulta Endereco Tests
    // =========================================================================

    public function testConsultaEnderecoSuccess(): void
    {
        $expectedData = [
            'sql' => '000.000.0000-0',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cep' => '01310-100',
            'area_terreno' => 500.0,
            'area_construida' => 1200.0,
            'valor_venal_total' => 4300000.0,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->consultaEndereco('Avenida Paulista', '1000');

        $this->assertEquals('000.000.0000-0', $result['sql']);
        $this->assertEquals('Avenida Paulista', $result['logradouro']);
        $this->assertEquals('Bela Vista', $result['bairro']);

        // Verify rate limit tracking
        $rateLimit = $this->client->getRateLimit();
        $this->assertNotNull($rateLimit);
        $this->assertEquals(1000, $rateLimit->limit);
        $this->assertEquals(999, $rateLimit->remaining);

        // Verify request ID tracking
        $this->assertEquals('req_test123', $this->client->getLastRequestId());

        // Verify request was made correctly
        $request = $this->mockHttp->getLastRequest();
        $this->assertNotNull($request);
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/consulta/endereco', $request['url']);
        $this->assertStringContainsString('logradouro=Avenida+Paulista', $request['url']);
        $this->assertStringContainsString('numero=1000', $request['url']);
    }

    public function testConsultaEnderecoWithOptions(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::successResponse(['sql' => '000.000.0000-0'])
        );

        $this->client->consultaEndereco('Avenida Paulista', '1000', 'sp', [
            'incluirHistorico' => true,
            'incluirComparaveis' => true,
            'incluirZoneamento' => true,
        ]);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('incluir_historico=true', $request['url']);
        $this->assertStringContainsString('incluir_comparaveis=true', $request['url']);
        $this->assertStringContainsString('incluir_zoneamento=true', $request['url']);
    }

    public function testConsultaEnderecoNotFound(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(404, 'Imóvel não encontrado')
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Imóvel não encontrado');

        $this->client->consultaEndereco('Rua Inexistente', '999');
    }

    public function testConsultaEnderecoWithCustomTimeout(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::successResponse(['sql' => '000.000.0000-0'])
        );

        $this->client->consultaEndereco('Avenida Paulista', null, 'sp', [
            'timeout' => 60,
        ]);

        $request = $this->mockHttp->getLastRequest();
        $this->assertEquals(60, $request['timeout']);
    }

    // =========================================================================
    // Consulta SQL Tests
    // =========================================================================

    public function testConsultaSQLSuccess(): void
    {
        $expectedData = [
            'sql' => '000.000.0000-0',
            'logradouro' => 'Avenida Paulista',
            'valor_venal_total' => 4300000.0,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->consultaSQL('000.000.0000-0');

        $this->assertEquals('000.000.0000-0', $result['sql']);
        $this->assertEquals(4300000.0, $result['valor_venal_total']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('/consulta/sql/000.000.0000-0', $request['url']);
    }

    // =========================================================================
    // Consulta CEP Tests
    // =========================================================================

    public function testConsultaCEPSuccess(): void
    {
        $expectedData = [
            ['sql' => '000.000.0000-0', 'numero' => '100'],
            ['sql' => '000.000.0000-1', 'numero' => '200'],
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->consultaCEP('01310-100');

        $this->assertCount(2, $result);
        $this->assertEquals('100', $result[0]['numero']);

        // Verify CEP was cleaned
        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('/consulta/cep/01310100', $request['url']);
    }

    // =========================================================================
    // Consulta Zoneamento Tests
    // =========================================================================

    public function testConsultaZoneamentoSuccess(): void
    {
        $expectedData = [
            'zona' => 'ZM',
            'zona_descricao' => 'Zona Mista',
            'coeficiente_aproveitamento_basico' => 1.0,
            'coeficiente_aproveitamento_maximo' => 2.5,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->consultaZoneamento(-23.5505, -46.6333);

        $this->assertEquals('ZM', $result['zona']);
        $this->assertEquals('Zona Mista', $result['zona_descricao']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('latitude=-23.5505', $request['url']);
        $this->assertStringContainsString('longitude=-46.6333', $request['url']);
    }

    // =========================================================================
    // Valuation Tests
    // =========================================================================

    public function testValuationEstimateSuccess(): void
    {
        $expectedData = [
            'valor_estimado' => 5000000.0,
            'valor_minimo' => 4500000.0,
            'valor_maximo' => 5500000.0,
            'confianca' => 0.85,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->valuationEstimate([
            'area_terreno' => 500,
            'area_construida' => 1200,
            'bairro' => 'Bela Vista',
            'zona' => 'ZC',
            'tipo_uso' => 'Comercial',
            'tipo_padrao' => 'Alto',
        ]);

        $this->assertEquals(5000000.0, $result['valor_estimado']);
        $this->assertEquals(0.85, $result['confianca']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/valuation/estimate', $request['url']);
        $this->assertNotNull($request['body']);
        $body = json_decode($request['body'], true);
        $this->assertEquals(500, $body['area_terreno']);
    }

    public function testValuationBatchSuccess(): void
    {
        $expectedData = [
            'resultados' => [
                ['valor_estimado' => 5000000.0],
                ['valor_estimado' => 6000000.0],
            ],
            'total_processados' => 2,
            'total_erros' => 0,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $imoveis = [
            [
                'area_terreno' => 500,
                'area_construida' => 1200,
                'bairro' => 'Bela Vista',
                'zona' => 'ZC',
                'tipo_uso' => 'Comercial',
                'tipo_padrao' => 'Alto',
            ],
            [
                'area_terreno' => 600,
                'area_construida' => 1400,
                'bairro' => 'Jardins',
                'zona' => 'ZM',
                'tipo_uso' => 'Residencial',
                'tipo_padrao' => 'Alto',
            ],
        ];

        $result = $this->client->valuationBatch($imoveis);

        $this->assertCount(2, $result['resultados']);
        $this->assertEquals(2, $result['total_processados']);
    }

    public function testValuationForbiddenForBasicPlan(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(403, 'Plano Pro ou superior necessário', [
                'required_plan' => 'Pro',
            ])
        );

        $this->expectException(ForbiddenException::class);

        $this->client->valuationEstimate([
            'area_terreno' => 500,
            'area_construida' => 1200,
            'bairro' => 'Bela Vista',
            'zona' => 'ZC',
            'tipo_uso' => 'Comercial',
            'tipo_padrao' => 'Alto',
        ]);
    }

    public function testValuationComparablesSuccess(): void
    {
        $expectedData = [
            ['sql' => '001.001.0001-0', 'valor_venal_total' => 4200000],
            ['sql' => '001.001.0002-0', 'valor_venal_total' => 4600000],
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->valuationComparables('Bela Vista', 400, 600);

        $this->assertCount(2, $result);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('bairro=Bela+Vista', $request['url']);
        $this->assertStringContainsString('area_min=400', $request['url']);
        $this->assertStringContainsString('area_max=600', $request['url']);
    }

    // =========================================================================
    // Dados Endpoints Tests
    // =========================================================================

    public function testDadosIPTUHistoricoSuccess(): void
    {
        $expectedData = [
            ['ano' => 2024, 'valor_venal_total' => 4300000, 'iptu_valor' => 12500],
            ['ano' => 2023, 'valor_venal_total' => 4100000, 'iptu_valor' => 12000],
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->dadosIPTUHistorico('000.000.0000-0');

        $this->assertCount(2, $result);
        $this->assertEquals(2024, $result[0]['ano']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('/dados/iptu/historico/000.000.0000-0', $request['url']);
    }

    public function testDadosCNPJSuccess(): void
    {
        $expectedData = [
            'cnpj' => '00000000000191',
            'razao_social' => 'EMPRESA TESTE LTDA',
            'situacao_cadastral' => 'ATIVA',
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->dadosCNPJ('00.000.000/0001-91');

        $this->assertEquals('EMPRESA TESTE LTDA', $result['razao_social']);

        // Verify CNPJ was cleaned
        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('/dados/cnpj/00000000000191', $request['url']);
    }

    public function testDadosIPCACorrigirSuccess(): void
    {
        $expectedData = [
            'valor_corrigido' => 115000.0,
            'fator' => 1.15,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->dadosIPCACorrigir(100000, '2020-01', '2024-01');

        $this->assertEquals(115000.0, $result['valor_corrigido']);
        $this->assertEquals(1.15, $result['fator']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('valor=100000', $request['url']);
        $this->assertStringContainsString('data_origem=2020-01', $request['url']);
        $this->assertStringContainsString('data_destino=2024-01', $request['url']);
    }

    // =========================================================================
    // IPTU Tools Tests
    // =========================================================================

    public function testIptuToolsCidadesSuccess(): void
    {
        $expectedData = [
            'cidades' => [
                ['codigo' => 'sp', 'nome' => 'São Paulo', 'desconto_vista' => '3%'],
                ['codigo' => 'bh', 'nome' => 'Belo Horizonte', 'desconto_vista' => '4%'],
            ],
            'total' => 2,
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->iptuToolsCidades();

        $this->assertCount(2, $result['cidades']);
        $this->assertEquals('sp', $result['cidades'][0]['codigo']);
    }

    public function testIptuToolsCalendarioSuccess(): void
    {
        $expectedData = [
            'cidade' => 'sp',
            'ano' => 2026,
            'desconto_vista_percentual' => 3,
            'parcelas_max' => 10,
            'vencimentos_parcelado' => [
                '2026-02-15', '2026-03-15', '2026-04-15',
            ],
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->iptuToolsCalendario('sp');

        $this->assertEquals(2026, $result['ano']);
        $this->assertEquals(3, $result['desconto_vista_percentual']);
        $this->assertCount(3, $result['vencimentos_parcelado']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('cidade=sp', $request['url']);
    }

    public function testIptuToolsSimuladorSuccess(): void
    {
        $expectedData = [
            'valor_original' => 5000,
            'valor_vista' => 4850,
            'desconto_vista' => 150,
            'economia_vista' => 150,
            'recomendacao' => 'Recomendamos pagamento à vista',
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->iptuToolsSimulador(5000, 'sp', 200000);

        $this->assertEquals(5000, $result['valor_original']);
        $this->assertEquals(150, $result['economia_vista']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $body = json_decode($request['body'], true);
        $this->assertEquals(5000, $body['valor_iptu']);
        $this->assertEquals(200000, $body['valor_venal']);
    }

    public function testIptuToolsIsencaoSuccess(): void
    {
        $expectedData = [
            'cidade' => 'sp',
            'valor_venal' => 200000,
            'limite_isencao' => 230000,
            'elegivel_isencao_total' => true,
            'mensagem' => 'Imóvel elegível para isenção total de IPTU',
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->iptuToolsIsencao(200000, 'sp');

        $this->assertTrue($result['elegivel_isencao_total']);
        $this->assertEquals(230000, $result['limite_isencao']);
    }

    public function testIptuToolsProximoVencimentoSuccess(): void
    {
        $expectedData = [
            'cidade' => 'sp',
            'data_vencimento' => '2026-02-15',
            'dias_restantes' => 30,
            'status' => 'em_dia',
            'mensagem' => 'Próximo vencimento em 30 dias',
        ];

        $this->mockHttp->addResponse(
            MockHttpClient::successResponse($expectedData)
        );

        $result = $this->client->iptuToolsProximoVencimento('sp', 1);

        $this->assertEquals('em_dia', $result['status']);
        $this->assertEquals(30, $result['dias_restantes']);

        $request = $this->mockHttp->getLastRequest();
        $this->assertStringContainsString('parcela=1', $request['url']);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    public function testAuthenticationError(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(401, 'API Key inválida')
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('API Key inválida');

        $this->client->consultaEndereco('Avenida Paulista');
    }

    public function testValidationError(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(400, 'Parâmetros inválidos', [
                'errors' => [
                    ['field' => 'logradouro', 'message' => 'campo obrigatório'],
                ],
            ])
        );

        try {
            $this->client->consultaEndereco('');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertNotEmpty($e->getErrors());
        }
    }

    public function testRateLimitError(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(429, 'Rate limit exceeded', [], [
                'retry-after' => ['60'],
            ])
        );

        try {
            $this->client->consultaEndereco('Avenida Paulista');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(60, $e->getRetryAfter());
            $this->assertTrue($e->isRetryable());
        }
    }

    public function testServerError(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::errorResponse(500, 'Internal server error')
        );

        try {
            $this->client->consultaEndereco('Avenida Paulista');
            $this->fail('Expected ServerException');
        } catch (ServerException $e) {
            $this->assertEquals(500, $e->getStatusCode());
            $this->assertTrue($e->isRetryable());
        }
    }

    public function testTimeoutError(): void
    {
        $this->mockHttp->addTimeout(30);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Timeout');

        $this->client->consultaEndereco('Avenida Paulista');
    }

    public function testNetworkError(): void
    {
        $this->mockHttp->addNetworkError('Connection refused');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->client->consultaEndereco('Avenida Paulista');
    }

    // =========================================================================
    // Retry Tests
    // =========================================================================

    public function testRetryOnServerError(): void
    {
        // Create client with retries enabled
        $config = new ClientConfig(
            baseUrl: 'https://api.test.iptuapi.com.br/api/v1',
            retryConfig: new RetryConfig(maxRetries: 2, initialDelay: 1)
        );
        $mockHttp = new MockHttpClient();
        $client = new IPTUClient('test-api-key', $config, $mockHttp);

        // First two requests fail, third succeeds
        $mockHttp->addResponse(MockHttpClient::errorResponse(500, 'Server error'));
        $mockHttp->addResponse(MockHttpClient::errorResponse(500, 'Server error'));
        $mockHttp->addResponse(MockHttpClient::successResponse(['sql' => '000.000.0000-0']));

        $result = $client->consultaEndereco('Avenida Paulista');

        $this->assertEquals('000.000.0000-0', $result['sql']);
        $this->assertCount(3, $mockHttp->getHistory());
    }

    public function testRetryOnRateLimit(): void
    {
        $config = new ClientConfig(
            baseUrl: 'https://api.test.iptuapi.com.br/api/v1',
            retryConfig: new RetryConfig(maxRetries: 1, initialDelay: 1)
        );
        $mockHttp = new MockHttpClient();
        $client = new IPTUClient('test-api-key', $config, $mockHttp);

        // First request rate limited, second succeeds
        $mockHttp->addResponse(MockHttpClient::errorResponse(429, 'Rate limit'));
        $mockHttp->addResponse(MockHttpClient::successResponse(['sql' => '000.000.0000-0']));

        $result = $client->consultaEndereco('Avenida Paulista');

        $this->assertEquals('000.000.0000-0', $result['sql']);
        $this->assertCount(2, $mockHttp->getHistory());
    }

    public function testNoRetryOnValidationError(): void
    {
        $config = new ClientConfig(
            baseUrl: 'https://api.test.iptuapi.com.br/api/v1',
            retryConfig: new RetryConfig(maxRetries: 3, initialDelay: 1)
        );
        $mockHttp = new MockHttpClient();
        $client = new IPTUClient('test-api-key', $config, $mockHttp);

        // Only queue one response - 400 errors should not retry
        $mockHttp->addResponse(MockHttpClient::errorResponse(400, 'Invalid params'));

        $this->expectException(ValidationException::class);

        try {
            $client->consultaEndereco('');
        } finally {
            // Should only have made 1 request (no retries for 400)
            $this->assertCount(1, $mockHttp->getHistory());
        }
    }

    // =========================================================================
    // Header Verification Tests
    // =========================================================================

    public function testCorrectHeadersSent(): void
    {
        $this->mockHttp->addResponse(
            MockHttpClient::successResponse(['sql' => '000.000.0000-0'])
        );

        $this->client->consultaEndereco('Avenida Paulista');

        $request = $this->mockHttp->getLastRequest();

        $this->assertArrayHasKey('X-API-Key', $request['headers']);
        $this->assertEquals('test-api-key', $request['headers']['X-API-Key']);
        $this->assertEquals('application/json', $request['headers']['Content-Type']);
        $this->assertEquals('application/json', $request['headers']['Accept']);
        $this->assertStringContainsString('iptuapi-php/', $request['headers']['User-Agent']);
    }
}
