<?php

declare(strict_types=1);

namespace BsoutVendas\Repositories;

use PDO;

class OrderRepository
{
    private bool $isSqlite;

    public function __construct(private PDO $pdo)
    {
        $this->isSqlite = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public function upsert(string $marketplace, array $payload, int $userId): void
    {
        $externalId = $payload['id'] ?? $payload['orderId'] ?? uniqid('order_', true);

        if ($this->isSqlite) {
            $stmt = $this->pdo->prepare('INSERT INTO orders (marketplace, external_id, customer_name, total_amount, status, purchased_at, user_id)
                VALUES (:marketplace, :external_id, :customer_name, :total_amount, :status, :purchased_at, :user_id)
                ON CONFLICT(marketplace, external_id, user_id) DO UPDATE SET
                    customer_name = excluded.customer_name,
                    total_amount = excluded.total_amount,
                    status = excluded.status,
                    purchased_at = excluded.purchased_at');
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO orders (marketplace, external_id, customer_name, total_amount, status, purchased_at, user_id)
                VALUES (:marketplace, :external_id, :customer_name, :total_amount, :status, :purchased_at, :user_id)
                ON DUPLICATE KEY UPDATE customer_name = VALUES(customer_name), total_amount = VALUES(total_amount), status = VALUES(status), purchased_at = VALUES(purchased_at)');
        }

        $stmt->execute([
            'marketplace' => $marketplace,
            'external_id' => $externalId,
            'customer_name' => $payload['customer']['name'] ?? $payload['buyer'] ?? 'Cliente',
            'total_amount' => $payload['total'] ?? $payload['total_amount'] ?? 0,
            'status' => $payload['status'] ?? 'pending',
            'purchased_at' => $payload['created_at'] ?? $payload['dateCreated'] ?? date('Y-m-d H:i:s'),
            'user_id' => $userId,
        ]);

        $orderId = (int) $this->pdo->lastInsertId();
        if ($orderId === 0) {
            $fetchId = $this->pdo->prepare('SELECT id FROM orders WHERE marketplace = :marketplace AND external_id = :external_id AND user_id = :user_id LIMIT 1');
            $fetchId->execute([
                'marketplace' => $marketplace,
                'external_id' => $externalId,
                'user_id' => $userId,
            ]);
            $orderId = (int) $fetchId->fetchColumn();
        }

        if (!empty($payload['items']) && is_array($payload['items'])) {
            foreach ($payload['items'] as $item) {
                $this->upsertItem($orderId, $item);
            }
        }
    }

    private function upsertItem(int $orderId, array $item): void
    {
        if ($orderId <= 0) {
            return;
        }

        $sku = $item['sku'] ?? $item['id'] ?? uniqid('sku_', true);
        if ($this->isSqlite) {
            $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, sku, title, quantity, price)
                VALUES (:order_id, :sku, :title, :quantity, :price)
                ON CONFLICT(order_id, sku) DO UPDATE SET
                    title = excluded.title,
                    quantity = excluded.quantity,
                    price = excluded.price');
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, sku, title, quantity, price)
                VALUES (:order_id, :sku, :title, :quantity, :price)
                ON DUPLICATE KEY UPDATE title = VALUES(title), quantity = VALUES(quantity), price = VALUES(price)');
        }

        $stmt->execute([
            'order_id' => $orderId,
            'sku' => $sku,
            'title' => $item['title'] ?? $item['name'] ?? 'Item',
            'quantity' => $item['quantity'] ?? $item['qty'] ?? 1,
            'price' => $item['price'] ?? $item['value'] ?? 0,
        ]);
    }

    public function getRecent(int $userId, int $limit = 25): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY purchased_at DESC LIMIT :limit');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
