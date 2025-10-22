<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BsoutVendas\Auth\AuthService;
use BsoutVendas\Repositories\InventoryRepository;
use BsoutVendas\Repositories\OrderRepository;
use BsoutVendas\Repositories\UserRepository;
use BsoutVendas\Repositories\WhatsappLinkRepository;

header('Content-Type: application/json');

$auth = new AuthService(new UserRepository(db()));
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autorizado']);
    exit;
}

$userId = $auth->userId();
$pdo = db();
$orderRepository = new OrderRepository($pdo);
$inventoryRepository = new InventoryRepository($pdo);
$whatsappRepository = new WhatsappLinkRepository($pdo);

$orders = $orderRepository->getRecent($userId ?? 0);
$lowStock = $inventoryRepository->lowStock($userId ?? 0);
$whatsappLinks = $whatsappRepository->recent($userId ?? 0);

$totalRevenue = array_reduce($orders, fn($carry, $order) => $carry + (float) $order['total_amount'], 0.0);
$pendingOrders = array_filter($orders, fn($order) => $order['status'] === 'pending');

$response = [
    'orders' => $orders,
    'metrics' => [
        'total_revenue' => $totalRevenue,
        'pending_orders' => count($pendingOrders),
        'low_stock_alerts' => count($lowStock),
    ],
    'low_stock' => $lowStock,
    'whatsapp_links' => $whatsappLinks,
];

echo json_encode($response);
