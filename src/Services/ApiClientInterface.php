<?php

declare(strict_types=1);

namespace BsoutVendas\Services;

interface ApiClientInterface
{
    public function fetchOrders(?string $since = null): array;

    public function fetchInventory(): array;
}
