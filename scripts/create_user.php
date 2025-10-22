<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Repositories\UserRepository;

if ($argc < 3) {
    fwrite(STDERR, "Uso: php scripts/create_user.php email senha\n");
    exit(1);
}

$email = $argv[1];
$password = $argv[2];

$repository = new UserRepository(db());
$existing = $repository->findByEmail($email);

if ($existing) {
    $repository->updatePassword((int) $existing['id'], $password);
    fwrite(STDOUT, sprintf("Senha atualizada para o usuário #%d (%s)\n", (int) $existing['id'], $email));
    exit(0);
}

$id = $repository->create($email, $password);
fwrite(STDOUT, sprintf("Usuário criado com ID #%d (%s)\n", $id, $email));
