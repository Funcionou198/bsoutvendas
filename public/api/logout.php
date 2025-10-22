<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

session_destroy();

header('Content-Type: application/json');
echo json_encode(['message' => 'Sessão encerrada.']);
