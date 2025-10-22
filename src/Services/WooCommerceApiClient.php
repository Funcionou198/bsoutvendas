<?php

declare(strict_types=1);

namespace BsoutVendas\Services;

use BsoutVendas\Services\Traits\HttpClientTrait;

class WooCommerceApiClient implements ApiClientInterface
{
    use HttpClientTrait;

    private string $site;
    private string $consumerKey;
    private string $consumerSecret;

    public function __construct()
    {
        $this->site = rtrim((string) env('WOOCOMMERCE_SITE', ''), '/');
        $this->consumerKey = (string) env('WOOCOMMERCE_CONSUMER_KEY', '');
        $this->consumerSecret = (string) env('WOOCOMMERCE_CONSUMER_SECRET', '');
    }

    public function fetchOrders(?string $since = null): array
    {
        if (!$this->site || !$this->consumerKey) {
            return [];
        }

        $query = ['per_page' => 50, 'order' => 'desc'];
        if ($since) {
            $query['after'] = $since;
        }

        $url = sprintf('%s/wp-json/wc/v3/orders?%s', $this->site, http_build_query($query));

        return $this->request('GET', $url, [
            'Authorization: Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret),
        ]);
    }

    public function fetchInventory(): array
    {
        if (!$this->site || !$this->consumerKey) {
            return [];
        }

        $url = sprintf('%s/wp-json/wc/v3/products?per_page=100', $this->site);
        return $this->request('GET', $url, [
            'Authorization: Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret),
        ]);
    }
}
