<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Repositories\InventoryRepository;
use BsoutVendas\Repositories\OrderRepository;
use BsoutVendas\Repositories\WhatsappLinkRepository;
use BsoutVendas\Services\WhatsappService;

function assertTrue(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('Test failed: ' . $message);
    }
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, marketplace TEXT, external_id TEXT, customer_name TEXT, total_amount REAL, status TEXT, purchased_at TEXT, user_id INTEGER, UNIQUE(marketplace, external_id, user_id))');
$pdo->exec('CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, sku TEXT, title TEXT, quantity INTEGER, price REAL, UNIQUE(order_id, sku))');
$pdo->exec('CREATE TABLE inventory (id INTEGER PRIMARY KEY AUTOINCREMENT, marketplace TEXT, sku TEXT, product_name TEXT, quantity INTEGER, price REAL, user_id INTEGER, updated_at TEXT, UNIQUE(marketplace, sku, user_id))');
$pdo->exec('CREATE TABLE whatsapp_links (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, product_name TEXT, price REAL, phone TEXT, link TEXT, created_at TEXT)');

$orderRepository = new OrderRepository($pdo);
$orderRepository->upsert('woocommerce', [
    'id' => '1001',
    'customer' => ['name' => 'Maria'],
    'total' => 199.9,
    'status' => 'paid',
    'created_at' => '2024-05-01 12:00:00',
    'items' => [
        ['sku' => 'SKU123', 'title' => 'Produto Teste', 'quantity' => 1, 'price' => 199.9],
    ],
], 1);

$orders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
assertTrue((int) $orders === 1, 'Order was inserted');

$inventoryRepository = new InventoryRepository($pdo);
$inventoryRepository->upsert('mercado_livre', [
    'sku' => 'SKU123',
    'title' => 'Produto Teste',
    'available_quantity' => 2,
    'price' => 99.9,
], 1);

$inventoryCount = $pdo->query('SELECT quantity FROM inventory WHERE sku = "SKU123"')->fetchColumn();
assertTrue((int) $inventoryCount === 2, 'Inventory quantity stored');

putenv('WHATSAPP_PROVIDER=zapi');
putenv('ZAPI_INSTANCE=demo-instance');
putenv('WHATSAPP_TOKEN=token');

$service = new WhatsappService();
$link = $service->buildLink('Produto Teste', 99.9, '5511999999999');
assertTrue(str_contains($link, 'Produto+Teste'), 'WhatsApp link contains product');

$whatsappRepository = new WhatsappLinkRepository($pdo);
$whatsappRepository->log(1, 'Produto Teste', 99.9, '5511999999999', $link);

$logs = $pdo->query('SELECT COUNT(*) FROM whatsapp_links')->fetchColumn();
assertTrue((int) $logs === 1, 'WhatsApp link logged');

echo "All integration tests passed\n";
