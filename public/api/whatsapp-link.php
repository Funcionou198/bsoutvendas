<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BsoutVendas\Auth\AuthService;
use BsoutVendas\Repositories\UserRepository;
use BsoutVendas\Repositories\WhatsappLinkRepository;
use BsoutVendas\Services\WhatsappService;

header('Content-Type: application/json');

$auth = new AuthService(new UserRepository(db()));
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$product = trim((string) ($input['product'] ?? 'Produto'));
$price = (float) ($input['price'] ?? 0);
$phone = preg_replace('/\D+/', '', (string) ($input['phone'] ?? ''));

if (!$product || $price <= 0 || strlen($phone) < 10) {
    http_response_code(422);
    echo json_encode(['message' => 'Informe produto, preço válido e telefone com DDD.']);
    exit;
}

$service = new WhatsappService();
$link = $service->buildLink($product, $price, $phone);

$repository = new WhatsappLinkRepository(db());
$repository->log($auth->userId() ?? 0, $product, $price, $phone, $link);

echo json_encode([
    'link' => $link,
]);
