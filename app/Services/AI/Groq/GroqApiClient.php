<?php

namespace App\Services\AI\Groq;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

readonly class GroqApiClient
{
    public function __construct(
        private Client $http,
        private string $url,
        private string $apiKey,
        private string $model,
        private int $timeout = 10,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function chat(string $system, string $user): array
    {
        $response = $this->http->post($this->url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ],
            'timeout' => $this->timeout,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}
