<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Repositories\InventoryRepository;
use BsoutVendas\Repositories\OrderRepository;
use BsoutVendas\Services\AmazonApiClient;
use BsoutVendas\Services\MercadoLivreApiClient;
use BsoutVendas\Services\WooCommerceApiClient;

if (php_sapi_name() !== 'cli') {
    $signature = $_GET['signature'] ?? '';
    if ($signature !== env('CRON_SIGNATURE')) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

$pdo = db();
$orderRepository = new OrderRepository($pdo);
$inventoryRepository = new InventoryRepository($pdo);

$clients = [
    'amazon' => new AmazonApiClient(),
    'mercado_livre' => new MercadoLivreApiClient(),
    'woocommerce' => new WooCommerceApiClient(),
];

$userId = (int) (env('DEFAULT_USER_ID', 1));
$since = date('c', strtotime('-2 hours'));

$logFile = __DIR__ . '/../storage/logs/cron.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

$logHandle = @fopen($logFile, 'a');
if (!$logHandle) {
    $logHandle = fopen('php://stderr', 'w');
}

foreach ($clients as $marketplace => $client) {
    try {
        $orders = $client->fetchOrders($since);
        if (!empty($orders['data'])) {
            $orders = $orders['data'];
        }

        if (is_array($orders)) {
            foreach ($orders as $order) {
                if (!is_array($order)) {
                    continue;
                }
                $orderRepository->upsert($marketplace, $order, $userId);
            }
        }

        $inventory = $client->fetchInventory();
        if (!empty($inventory['data'])) {
            $inventory = $inventory['data'];
        }

        if (is_array($inventory)) {
            foreach ($inventory as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $inventoryRepository->upsert($marketplace, $item, $userId);
            }
        }

        $message = sprintf("[%s] Synced %s successfully\n", date('Y-m-d H:i:s'), $marketplace);
        fwrite($logHandle, $message);
    } catch (Throwable $exception) {
        $message = sprintf("[%s] Failed syncing %s: %s\n", date('Y-m-d H:i:s'), $marketplace, $exception->getMessage());
        fwrite($logHandle, $message);
    }
}

if (is_resource($logHandle) && stream_get_meta_data($logHandle)['uri'] !== 'php://stderr') {
    fclose($logHandle);
}
