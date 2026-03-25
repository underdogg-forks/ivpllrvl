<?php

namespace Modules\Core\Testing;

/**
 * TestResponse - Wrapper for HTTP response in tests
 */
class TestResponse
{
    public int $statusCode = 200;
    public string $content = '';
    public array $headers = [];
    public ?string $redirectUrl = null;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function json(): array
    {
        return json_decode($this->content, true) ?? [];
    }
}
