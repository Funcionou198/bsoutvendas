<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Auth\AuthService;
use BsoutVendas\Repositories\UserRepository;

$auth = new AuthService(new UserRepository(db()));
if ($auth->check()) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>BsoutVendas - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
    <article>
        <h1>BsoutVendas</h1>
        <p>Centralize seus pedidos e estoque dos principais marketplaces brasileiros.</p>
        <form id="login-form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Entrar</button>
        </form>
        <p id="login-message"></p>
    </article>
</main>
<script>
const form = document.getElementById('login-form');
const message = document.getElementById('login-message');
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    message.textContent = 'Autenticando...';
    const payload = {
        email: form.email.value,
        password: form.password.value,
    };
    const response = await fetch('/api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await response.json();
    if (response.ok) {
        window.location.href = '/dashboard.php';
    } else {
        message.textContent = data.message || 'Falha no login';
    }
});
</script>
</body>
</html>
