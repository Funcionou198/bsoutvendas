# BsoutVendas

MVP de SaaS em PHP + JavaScript pensado para hospedagem compartilhada (HostGator cPanel). A plataforma centraliza pedidos e estoque de Amazon Seller Central, Mercado Livre e WooCommerce, e gera links dinâmicos do WhatsApp Business para acelerar o atendimento comercial.

## Visão geral das features

- **Sincronização horária** via cron job do cPanel para buscar pedidos e estoque de Amazon, Mercado Livre e WooCommerce usando APIs oficiais.
- **Dashboard web responsivo** acessível via subdomínio (ex.: `dashboard.bsoutvendas.com.br`) com pedidos unificados, alertas de estoque e histórico de links do WhatsApp.
- **Automação de WhatsApp** com geração de links dinâmicos (Z-API por padrão, Twilio opcional) e log das conversas iniciadas.
- **Base MySQL** com modelo multi-tenant pronto para expansão (tabelas com `user_id`).
- **Segurança básica**: uso de variáveis de ambiente, bloqueio de listagem de diretórios e endpoints autenticados.
- **Testes de integração** em PHP puro (`tests/run_tests.php`).

## Estrutura de diretórios

```
├── bootstrap.php           # Bootstrapping + autoloader simples
├── config/                 # Configurações de env e banco
├── cron/hourly_sync.php    # Script executado pelo cron do cPanel
├── database/schema.sql     # Script SQL para criação das tabelas
├── public/                 # Código acessível via web (login, dashboard, APIs)
├── src/                    # Serviços, repositórios e autenticação
├── storage/logs/           # Logs gerados pelo cron
└── tests/run_tests.php     # Testes de integração
```

## Instalação no HostGator (cPanel)

1. **Preparar o ambiente local**
   - Copie o arquivo `.env.example` para `.env` e preencha com suas credenciais (MySQL, APIs e tokens do WhatsApp/Z-API/Twilio).
   - Defina `DEFAULT_USER_ID` com o ID do usuário administrador que receberá os pedidos do cron.
   - Gere uma senha segura para `APP_KEY` e `CRON_SIGNATURE`.

2. **Criar banco e usuário MySQL**
   - No cPanel, abra *MySQL® Databases*.
   - Crie o banco (ex.: `user_bsoutvendas`) e o usuário (ex.: `user_bsout`).
   - Vincule o usuário ao banco com privilégios **ALL PRIVILEGES**.
   - Execute o conteúdo de `database/schema.sql` usando o *phpMyAdmin* para criar as tabelas.
   - Opcional: crie um usuário inicial executando no terminal local:
     ```bash
     php -r "echo password_hash('senhaSeguraAqui', PASSWORD_BCRYPT);"
     ```
     Copie o hash gerado e, no phpMyAdmin, execute:
     ```sql
     INSERT INTO users (email, password_hash) VALUES ('admin@empresa.com', 'HASH_GERADO_AQUI');
     ```

3. **Upload dos arquivos**
   - Gere um `.zip` atualizado executando localmente `php scripts/package_zip.php` (detalhes abaixo). Ele será salvo em `storage/releases/`.
   - No cPanel, use **File Manager** ou **FTP/SFTP** para enviar os arquivos extraídos para a pasta desejada (ex.: `public_html/bsoutvendas`).
   - Garanta que a pasta `storage/logs` tenha permissão de escrita (755 ou 775).

4. **Configurar o subdomínio para o dashboard**
   - Em *Subdomains* crie `dashboard.seudominio.com.br` apontando para `public_html/bsoutvendas/public`.
   - Ajuste o `APP_URL` no `.env` para o subdomínio configurado.

5. **Configurar variáveis de ambiente**
   - No cPanel vá em *Software > MultiPHP INI Editor* e adicione as variáveis ou carregue o `.env` diretamente no servidor.
   - Nunca faça upload de `.env` para diretórios públicos sem proteção.

6. **Cron job para sincronização**
   - Ainda no cPanel, abra *Cron Jobs* e adicione uma nova tarefa:
     ```
     0 * * * * /usr/local/bin/php -d detect_unicode=0 /home/SEU_USUARIO/public_html/bsoutvendas/cron/hourly_sync.php >> /home/SEU_USUARIO/logs/bsoutvendas-cron.log 2>&1
     ```
   - Ajuste o caminho conforme o usuário/estrutura da sua hospedagem.
   - Para executar via HTTP (fallback), cadastre a URL `https://dashboard.seudominio.com.br/cron/hourly_sync.php?signature=SEU_CRON_SIGNATURE`.

7. **Proteção adicional**
   - O `.htaccess` em `public/` bloqueia `.env` e `.sql`. Certifique-se de que o diretório raiz (fora do `public`) não seja exposto.
   - Ative HTTPS no subdomínio via *SSL/TLS*.

## Integrações com marketplaces

- **Amazon Seller Central**: utilize as credenciais da Selling Partner API (`AMAZON_CLIENT_ID`, `AMAZON_CLIENT_SECRET`, `AMAZON_REFRESH_TOKEN`, `AMAZON_SELLER_PARTNER_ID`, `AMAZON_REGION`). O cliente em `src/Services/AmazonApiClient.php` está pronto para receber os endpoints oficiais.
- **Mercado Livre**: configure `MELI_APP_ID`, `MELI_SECRET`, `MELI_ACCESS_TOKEN` e `MELI_REFRESH_TOKEN`. O script espera respostas JSON do endpoint `/orders/search` e `/users/me/inventory`.
- **WooCommerce**: informe `WOOCOMMERCE_SITE`, `WOOCOMMERCE_CONSUMER_KEY` e `WOOCOMMERCE_CONSUMER_SECRET`. Os endpoints padrão REST (`/wp-json/wc/v3/orders` e `/wp-json/wc/v3/products`) já estão configurados.

> Os clientes de API usam cURL e retornam arrays PHP. Adapte os mapeamentos conforme o payload real de cada plataforma.

## WhatsApp Business API

- Por padrão, usamos a **Z-API**. Configure `WHATSAPP_PROVIDER=zapi`, `ZAPI_INSTANCE` e `WHATSAPP_TOKEN`.
- Para **Twilio**, defina `WHATSAPP_PROVIDER=twilio` e configure `TWILIO_ACCOUNT_SID`, `WHATSAPP_TOKEN` (Auth Token) e `WHATSAPP_FROM_NUMBER`.
- O endpoint `public/api/whatsapp-link.php` gera links que podem ser incorporados no e-commerce ou enviados em fluxos de CRM.

## Testes

Execute localmente com PHP CLI:

```bash
php tests/run_tests.php
```

O script usa SQLite em memória para validar a inserção de pedidos, estoque e geração de links.

## Executar localmente para visualizar o dashboard

1. **Crie um usuário** no banco conectado:
   ```bash
   php scripts/create_user.php admin@empresa.com senhaFort3
   ```
   O comando cria o usuário (ou atualiza a senha se ele já existir) e imprime o ID. Atualize `DEFAULT_USER_ID` no `.env` para esse ID.
2. **Popule dados de demonstração** (opcional) para ver pedidos/estoque imediatamente:
   ```bash
   php scripts/seed_demo.php 1
   ```
   O argumento final é o `user_id` que receberá os dados. Se omitido, o script usa `DEFAULT_USER_ID`.
3. **Inicie o servidor embutido do PHP** apontando para a pasta `public/`:
   ```bash
   php -S 0.0.0.0:8000 -t public/
   ```
   Acesse `http://localhost:8000` e faça login com o usuário criado. O dashboard carregará os dados de demonstração.

> Os links do WhatsApp usam automaticamente a API escolhida no `.env`. Caso nenhuma credencial esteja configurada, o sistema gera um link público (`https://wa.me/`) para testes.

## Baixar o projeto em `.zip`

Para gerar rapidamente um pacote com todos os arquivos prontos para upload:

```bash
php scripts/package_zip.php
```

- O script cria um arquivo com timestamp em `storage/releases/bsoutvendas-AAAAmmdd-HHMMSS.zip`.
- Diretórios de cache/logs (`storage/logs/` e `storage/releases/`) e metadados de git não entram no pacote.
- Certifique-se de que a extensão `ZipArchive` está habilitada no PHP da sua máquina local (ativada por padrão no PHP do HostGator).

## Roadmap sugerido

- Interface multi-conta (armazenar tokens por `user_id`).
- Notificações push/e-mail quando o estoque estiver abaixo do limite.
- Mapeamento mais completo dos campos retornados pelas APIs (SP-API, MELI e WooCommerce).
- Implementar fila para webhooks em tempo real quando disponível.

## Licença

Este MVP é fornecido como ponto de partida. Ajuste conforme a necessidade da sua operação.
