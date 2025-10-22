<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BsoutVendas\Auth\AuthService;
use BsoutVendas\Repositories\UserRepository;

$auth = new AuthService(new UserRepository(db()));
if (!$auth->check()) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BsoutVendas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        .grid {
            display: grid;
            gap: 1rem;
        }
        @media(min-width: 768px) {
            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        table {
            width: 100%;
        }
        tbody tr:nth-child(even) {
            background: rgba(0,0,0,0.03);
        }
    </style>
</head>
<body>
<header class="container">
    <nav>
        <ul>
            <li><strong>BsoutVendas</strong></li>
        </ul>
        <ul>
            <li><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></li>
            <li><a href="/api/logout.php">Sair</a></li>
        </ul>
    </nav>
</header>
<main class="container">
    <section>
        <h2>Visão geral</h2>
        <div id="metrics" class="grid grid-3">
            <article>
                <h3>Receita (30 dias)</h3>
                <p id="metric-revenue">R$ 0,00</p>
            </article>
            <article>
                <h3>Pedidos pendentes</h3>
                <p id="metric-pending">0</p>
            </article>
            <article>
                <h3>Alertas de estoque</h3>
                <p id="metric-stock">0</p>
            </article>
        </div>
    </section>
    <section>
        <h2>Pedidos recentes</h2>
        <table>
            <thead>
                <tr>
                    <th>Marketplace</th>
                    <th>Cliente</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody id="orders"></tbody>
        </table>
    </section>
    <section>
        <h2>Estoque crítico</h2>
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Produto</th>
                    <th>Marketplace</th>
                    <th>Qtd.</th>
                </tr>
            </thead>
            <tbody id="low-stock"></tbody>
        </table>
    </section>
    <section>
        <h2>Link dinâmico do WhatsApp</h2>
        <form id="whatsapp-form">
            <div class="grid">
                <label>Produto
                    <input type="text" name="product" required>
                </label>
                <label>Preço (R$)
                    <input type="number" name="price" min="0" step="0.01" required>
                </label>
                <label>Telefone do cliente (com DDD)
                    <input type="tel" name="phone" required>
                </label>
            </div>
            <button type="submit">Gerar link</button>
        </form>
        <p id="whatsapp-link"></p>
    </section>
    <section>
        <h2>Histórico recente de links</h2>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Cliente</th>
                    <th>Link</th>
                    <th>Gerado em</th>
                </tr>
            </thead>
            <tbody id="whatsapp-history"></tbody>
        </table>
    </section>
</main>
<script>
async function loadDashboard() {
    const response = await fetch('/api/dashboard-data.php');
    if (!response.ok) {
        return;
    }
    const data = await response.json();
    const currencyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('metric-revenue').textContent = currencyFormatter.format(data.metrics.total_revenue);
    document.getElementById('metric-pending').textContent = data.metrics.pending_orders;
    document.getElementById('metric-stock').textContent = data.metrics.low_stock_alerts;

    const ordersBody = document.getElementById('orders');
    ordersBody.innerHTML = '';
    data.orders.forEach(order => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${order.marketplace}</td>
            <td>${order.customer_name}</td>
            <td>${order.status}</td>
            <td>${currencyFormatter.format(order.total_amount)}</td>
            <td>${new Date(order.purchased_at).toLocaleString('pt-BR')}</td>
        `;
        ordersBody.appendChild(row);
    });

    const stockBody = document.getElementById('low-stock');
    stockBody.innerHTML = '';
    data.low_stock.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.sku}</td>
            <td>${item.product_name}</td>
            <td>${item.marketplace}</td>
            <td>${item.quantity}</td>
        `;
        stockBody.appendChild(row);
    });

    const historyBody = document.getElementById('whatsapp-history');
    historyBody.innerHTML = '';
    data.whatsapp_links.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.product_name}</td>
            <td>${currencyFormatter.format(item.price)}</td>
            <td>${item.phone}</td>
            <td><a href="${item.link}" target="_blank">Abrir</a></td>
            <td>${new Date(item.created_at).toLocaleString('pt-BR')}</td>
        `;
        historyBody.appendChild(row);
    });
}

loadDashboard();
setInterval(loadDashboard, 60000);

const whatsappForm = document.getElementById('whatsapp-form');
const whatsappLink = document.getElementById('whatsapp-link');
whatsappForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(whatsappForm);
    const payload = Object.fromEntries(formData.entries());
    payload.price = parseFloat(payload.price);

    const response = await fetch('/api/whatsapp-link.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const data = await response.json();
    if (response.ok) {
        whatsappLink.innerHTML = `<a href="${data.link}" target="_blank" rel="noopener noreferrer">Abrir conversa</a>`;
        loadDashboard();
    } else {
        whatsappLink.textContent = data.message || 'Erro ao gerar link.';
    }
});
</script>
</body>
</html>
