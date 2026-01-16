.PHONY: all test lint build clean install dev help

# Default target
all: lint test

# Install dependencies
install:
	composer install --no-dev

# Install with dev dependencies
dev:
	composer install

# Run tests
test:
	./vendor/bin/phpunit

# Run tests with coverage
test-coverage:
	XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/
	@echo "Coverage report: coverage/index.html"

# Run linter
lint:
	./vendor/bin/phpcs --standard=PSR12 src/ || true
	@which phpstan > /dev/null && ./vendor/bin/phpstan analyse src --level=5 || true

# Fix lint issues
lint-fix:
	./vendor/bin/phpcbf --standard=PSR12 src/ || true

# Security scan
security:
	composer audit

# Validate composer.json
validate:
	composer validate --strict

# Clean build artifacts
clean:
	rm -rf vendor/ coverage/
	rm -f composer.lock

# Run examples (requires IPTU_API_KEY)
examples:
	@if [ -z "$(IPTU_API_KEY)" ]; then echo "IPTU_API_KEY is required"; exit 1; fi
	php examples/basic.php

# Help
help:
	@echo "Available targets:"
	@echo "  make install       - Install dependencies (production)"
	@echo "  make dev           - Install with dev dependencies"
	@echo "  make test          - Run tests"
	@echo "  make test-coverage - Run tests with coverage"
	@echo "  make lint          - Run linter"
	@echo "  make lint-fix      - Fix lint issues"
	@echo "  make security      - Run security scan"
	@echo "  make validate      - Validate composer.json"
	@echo "  make clean         - Clean build artifacts"
	@echo "  make examples      - Run examples (requires IPTU_API_KEY)"
	@echo "  make help          - Show this help"
