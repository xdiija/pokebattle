<?php

namespace App\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class IntegrationClient
{
    protected string $baseUrl;

    protected array $headers = [];

    protected int $timeout = 10;

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders($this->headers);
    }

    protected function get(string $path, array $query = []): array
    {
        $response = $this->client()->get($path, $query);

        if ($response->failed()) {
            throw new RuntimeException(
                "Integration request failed with status {$response->status()}"
            );
        }

        return $response->json();
    }
}
