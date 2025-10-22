<?php

declare(strict_types=1);

namespace BsoutVendas\Repositories;

use PDO;

class InventoryRepository
{
    private bool $isSqlite;

    public function __construct(private PDO $pdo)
    {
        $this->isSqlite = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public function upsert(string $marketplace, array $item, int $userId): void
    {
        $sku = $item['sku'] ?? $item['id'] ?? uniqid('sku_', true);
        if ($this->isSqlite) {
            $stmt = $this->pdo->prepare('INSERT INTO inventory (marketplace, sku, product_name, quantity, price, user_id, updated_at)
                VALUES (:marketplace, :sku, :product_name, :quantity, :price, :user_id, CURRENT_TIMESTAMP)
                ON CONFLICT(marketplace, sku, user_id) DO UPDATE SET
                    product_name = excluded.product_name,
                    quantity = excluded.quantity,
                    price = excluded.price,
                    updated_at = CURRENT_TIMESTAMP');
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO inventory (marketplace, sku, product_name, quantity, price, user_id, updated_at)
                VALUES (:marketplace, :sku, :product_name, :quantity, :price, :user_id, NOW())
                ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), quantity = VALUES(quantity), price = VALUES(price), updated_at = NOW()');
        }

        $stmt->execute([
            'marketplace' => $marketplace,
            'sku' => $sku,
            'product_name' => $item['title'] ?? $item['name'] ?? 'Produto',
            'quantity' => $item['available_quantity'] ?? $item['quantity'] ?? 0,
            'price' => $item['price'] ?? $item['sale_price'] ?? 0,
            'user_id' => $userId,
        ]);
    }

    public function lowStock(int $userId, int $threshold = 5): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventory WHERE user_id = :user_id AND quantity <= :threshold ORDER BY quantity ASC');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
