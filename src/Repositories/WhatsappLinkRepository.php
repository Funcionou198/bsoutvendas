<?php

declare(strict_types=1);

namespace BsoutVendas\Repositories;

use PDO;

class WhatsappLinkRepository
{
    private bool $isSqlite;

    public function __construct(private PDO $pdo)
    {
        $this->isSqlite = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    public function log(int $userId, string $productName, float $price, string $phone, string $link): void
    {
        if ($this->isSqlite) {
            $stmt = $this->pdo->prepare('INSERT INTO whatsapp_links (user_id, product_name, price, phone, link, created_at)
                VALUES (:user_id, :product_name, :price, :phone, :link, CURRENT_TIMESTAMP)');
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO whatsapp_links (user_id, product_name, price, phone, link, created_at)
                VALUES (:user_id, :product_name, :price, :phone, :link, NOW())');
        }

        $stmt->execute([
            'user_id' => $userId,
            'product_name' => $productName,
            'price' => $price,
            'phone' => $phone,
            'link' => $link,
        ]);
    }

    public function recent(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM whatsapp_links WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
