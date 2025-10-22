<?php

declare(strict_types=1);

namespace BsoutVendas\Services\Traits;

use RuntimeException;

trait HttpClientTrait
{
    private function request(string $method, string $url, array $headers = [], array $payload = []): array
    {
        $ch = curl_init();
        $defaultHeaders = ['Content-Type: application/json'];
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT => 30,
        ]);

        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            throw new RuntimeException('HTTP request failed: ' . curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException("HTTP request returned status {$statusCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
