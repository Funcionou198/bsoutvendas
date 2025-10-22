<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BsoutVendas\Auth\AuthService;
use BsoutVendas\Repositories\UserRepository;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
    http_response_code(422);
    echo json_encode(['message' => 'Credenciais obrigatórias.']);
    exit;
}

$auth = new AuthService(new UserRepository(db()));

if ($auth->attempt($email, $password)) {
    echo json_encode(['message' => 'Login realizado com sucesso.']);
    exit;
}

http_response_code(401);
echo json_encode(['message' => 'Email ou senha inválidos.']);
