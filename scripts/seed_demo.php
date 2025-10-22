<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Repositories\InventoryRepository;
use BsoutVendas\Repositories\OrderRepository;
use BsoutVendas\Repositories\WhatsappLinkRepository;
use BsoutVendas\Services\WhatsappService;

$pdo = db();
$orderRepository = new OrderRepository($pdo);
$inventoryRepository = new InventoryRepository($pdo);
$whatsappRepository = new WhatsappLinkRepository($pdo);

$userId = isset($argv[1]) ? (int) $argv[1] : (int) env('DEFAULT_USER_ID', 1);

$orders = [
    [
        'marketplace' => 'woocommerce',
        'payload' => [
            'id' => 'WC-1001',
            'customer' => ['name' => 'Maria Silva'],
            'total' => 249.90,
            'status' => 'paid',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'items' => [
                ['sku' => 'SKU-WC-001', 'title' => 'Tenis Running', 'quantity' => 1, 'price' => 249.90],
            ],
        ],
    ],
    [
        'marketplace' => 'mercado_livre',
        'payload' => [
            'id' => 'MLB-558899',
            'buyer' => 'João Pereira',
            'total_amount' => 189.90,
            'status' => 'pending',
            'dateCreated' => date('Y-m-d H:i:s', strtotime('-6 hours')),
            'items' => [
                ['sku' => 'SKU-ML-123', 'title' => 'Fone Bluetooth', 'quantity' => 1, 'price' => 189.90],
            ],
        ],
    ],
    [
        'marketplace' => 'amazon',
        'payload' => [
            'orderId' => 'AMZ-778899',
            'customer' => ['name' => 'Camila Rocha'],
            'total' => 399.00,
            'status' => 'shipped',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'items' => [
                ['sku' => 'SKU-AMZ-321', 'title' => 'Echo Dot 5ª Geração', 'quantity' => 1, 'price' => 399.00],
            ],
        ],
    ],
];

foreach ($orders as $order) {
    $orderRepository->upsert($order['marketplace'], $order['payload'], $userId);
}

$inventoryItems = [
    [
        'marketplace' => 'woocommerce',
        'item' => [
            'sku' => 'SKU-WC-001',
            'name' => 'Tenis Running',
            'quantity' => 8,
            'price' => 249.90,
        ],
    ],
    [
        'marketplace' => 'mercado_livre',
        'item' => [
            'sku' => 'SKU-ML-123',
            'title' => 'Fone Bluetooth',
            'available_quantity' => 3,
            'price' => 189.90,
        ],
    ],
    [
        'marketplace' => 'amazon',
        'item' => [
            'sku' => 'SKU-AMZ-321',
            'name' => 'Echo Dot 5ª Geração',
            'quantity' => 1,
            'price' => 399.00,
        ],
    ],
];

foreach ($inventoryItems as $inventory) {
    $inventoryRepository->upsert($inventory['marketplace'], $inventory['item'], $userId);
}

$service = new WhatsappService();
$link = $service->buildLink('Tenis Running', 249.90, '5511999999999');
$whatsappRepository->log($userId, 'Tenis Running', 249.90, '5511999999999', $link);

fwrite(STDOUT, sprintf("Dados de demonstração atualizados para o usuário #%d\n", $userId));
