<?php

declare(strict_types=1);

namespace BsoutVendas\Services;

use BsoutVendas\Services\Traits\HttpClientTrait;

class MercadoLivreApiClient implements ApiClientInterface
{
    use HttpClientTrait;

    private string $appId;
    private string $secret;
    private string $accessToken;

    public function __construct()
    {
        $this->appId = (string) env('MELI_APP_ID', '');
        $this->secret = (string) env('MELI_SECRET', '');
        $this->accessToken = (string) env('MELI_ACCESS_TOKEN', '');
    }

    public function fetchOrders(?string $since = null): array
    {
        if (!$this->accessToken) {
            return [];
        }

        $query = $since ? '?sort=date_desc&order.date_created.from=' . urlencode($since) : '';
        $url = 'https://api.mercadolibre.com/orders/search' . $query;

        return $this->request('GET', $url, [
            'Authorization: Bearer ' . $this->accessToken,
        ]);
    }

    public function fetchInventory(): array
    {
        if (!$this->accessToken) {
            return [];
        }

        $url = 'https://api.mercadolibre.com/users/me/inventory';
        return $this->request('GET', $url, [
            'Authorization: Bearer ' . $this->accessToken,
        ]);
    }
}
