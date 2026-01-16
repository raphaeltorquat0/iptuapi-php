# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.x.x   | :white_check_mark: |
| 1.x.x   | :x:                |

## Reporting a Vulnerability

A seguranca dos nossos usuarios e prioridade. Se voce descobrir uma vulnerabilidade de seguranca neste SDK, por favor reporte de forma responsavel.

### Como Reportar

1. **NAO** abra uma issue publica no GitHub
2. Envie um email para **security@iptuapi.com.br** com:
   - Descricao detalhada da vulnerabilidade
   - Passos para reproduzir o problema
   - Impacto potencial
   - Sugestao de correcao (se tiver)

### O Que Esperar

- **Confirmacao**: Responderemos em ate 48 horas confirmando o recebimento
- **Avaliacao**: Avaliaremos a vulnerabilidade em ate 7 dias
- **Correcao**: Vulnerabilidades criticas serao corrigidas em ate 30 dias
- **Credito**: Voce sera creditado no changelog (se desejar)

### Escopo

Este policy cobre:
- Codigo fonte do SDK
- Dependencias diretas
- Configuracoes de seguranca

Fora do escopo:
- A API em si (reporte para security@iptuapi.com.br separadamente)
- Aplicacoes de terceiros que usam o SDK

## Boas Praticas de Seguranca

### Proteja sua API Key

```php
// NUNCA faca isso
$client = new IPTUClient("sk_live_abc123"); // API key hardcoded

// Faca isso
$client = new IPTUClient($_ENV['IPTU_API_KEY']);

// Ou com dotenv
$client = new IPTUClient(getenv('IPTU_API_KEY'));
```

### Use HTTPS

O SDK sempre usa HTTPS por padrao. Nunca desabilite a verificacao SSL em producao.

### Mantenha Atualizado

Sempre use a versao mais recente do SDK para ter as ultimas correcoes de seguranca.

```bash
composer update raphaeltorquat0/iptuapi-php
```

### Verificar Dependencias

```bash
# Verificar vulnerabilidades conhecidas
composer audit
```

## Vulnerabilidades Conhecidas

Nenhuma vulnerabilidade conhecida na versao atual.

Consulte o [PHP Security Advisories Database](https://github.com/FriendsOfPHP/security-advisories) para verificar dependencias.
