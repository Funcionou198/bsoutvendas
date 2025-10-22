<?php

declare(strict_types=1);

namespace BsoutVendas\Services;

use BsoutVendas\Services\Traits\HttpClientTrait;

class AmazonApiClient implements ApiClientInterface
{
    use HttpClientTrait;

    private string $partnerId;
    private string $refreshToken;
    private string $clientId;
    private string $clientSecret;
    private string $region;

    public function __construct()
    {
        $this->partnerId = (string) env('AMAZON_SELLER_PARTNER_ID', '');
        $this->refreshToken = (string) env('AMAZON_REFRESH_TOKEN', '');
        $this->clientId = (string) env('AMAZON_CLIENT_ID', '');
        $this->clientSecret = (string) env('AMAZON_CLIENT_SECRET', '');
        $this->region = (string) env('AMAZON_REGION', 'na');
    }

    public function fetchOrders(?string $since = null): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            return [];
        }

        // Placeholder for actual SP-API call
        $query = $since ? '?createdAfter=' . urlencode($since) : '';
        $url = sprintf('https://sellingpartnerapi-%s.amazon.com/orders/v0/orders%s', $this->region, $query);

        return $this->request('GET', $url, $this->defaultHeaders());
    }

    public function fetchInventory(): array
    {
        if (!$this->clientId || !$this->clientSecret) {
            return [];
        }

        $url = sprintf('https://sellingpartnerapi-%s.amazon.com/fba/inventory/v1/summaries', $this->region);
        return $this->request('GET', $url, $this->defaultHeaders());
    }

    private function defaultHeaders(): array
    {
        return [
            'x-amz-access-token: ' . $this->refreshToken,
            'x-amz-date: ' . gmdate('Ymd\THis\Z'),
            'x-amz-security-token: ' . $this->partnerId,
            'x-amz-client-id: ' . $this->clientId,
            'x-amz-client-secret: ' . $this->clientSecret,
        ];
    }
}
