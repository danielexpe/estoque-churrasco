# 04 - Arquitetura

## 1. Visão geral

Aplicação monolítica em PHP puro sobre Apache, com MySQL. O Apache entrega apenas a pasta `public/`; o restante do projeto fica fora do document root. A aplicação segue um MVC simplificado com um front controller, roteamento próprio e PDO para acesso ao banco.

Objetivos da arquitetura:

- Simplicidade para estudo, sem framework e sem dependências PHP obrigatórias.
- Regras de estoque centralizadas e testáveis.
- Operações críticas transacionais e seguras contra concorrência.
- Interface renderizada no servidor com Bootstrap 5, sem build de frontend.

## 2. Estrutura de diretórios

```text
estoque-churrasco/
├── public/
│   ├── index.php              # front controller
│   ├── .htaccess              # rewrite para index.php
│   └── assets/
│       ├── css/app.css
│       └── js/app.js
├── app/
│   ├── Core/
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Database.php
│   │   ├── Money.php
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Flash.php
│   │   ├── View.php
│   │   └── Csv.php
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   ├── SupplierController.php
│   │   ├── PurchaseController.php
│   │   ├── SaleController.php
│   │   ├── AdjustmentController.php
│   │   ├── MovementController.php
│   │   ├── ReportController.php
│   │   └── UserController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Supplier.php
│   │   ├── Purchase.php
│   │   ├── ProductLot.php
│   │   ├── Sale.php
│   │   ├── StockAdjustment.php
│   │   └── StockMovement.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── UserService.php
│   │   ├── CategoryService.php
│   │   ├── SupplierService.php
│   │   ├── ProductService.php
│   │   ├── PurchaseService.php
│   │   ├── SaleService.php
│   │   ├── AdjustmentService.php
│   │   ├── StockService.php
│   │   └── ReportService.php
│   └── Views/
│       ├── layouts/
│       │   ├── app.php
│       │   └── auth.php
│       ├── partials/
│       ├── auth/
│       ├── dashboard/
│       ├── products/
│       ├── categories/
│       ├── suppliers/
│       ├── purchases/
│       ├── sales/
│       ├── adjustments/
│       ├── movements/
│       ├── reports/
│       ├── users/
│       └── errors/
├── bootstrap/
│   └── app.php                # autoload, config, sessão, helpers
├── config/
│   └── config.php
├── database/
│   ├── schema.sql
│   ├── seed.php
│   └── init-test-db.sh
├── routes/
│   └── web.php
├── storage/
│   └── logs/
├── tests/
│   └── run.php
├── docs/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── .env.example
├── .gitignore
└── README.md
```

## 3. Fluxo de uma requisição

1. Apache recebe a requisição e reescreve para `public/index.php`.
2. `public/index.php` carrega `bootstrap/app.php`.
3. O bootstrap registra o autoloader, carrega a configuração, configura o fuso horário, inicia a sessão com cookies seguros e registra o tratamento de erros.
4. `Router` casa método e caminho com uma rota de `routes/web.php`.
5. O middleware da rota verifica autenticação e perfil (`auth`, `role:admin`).
6. O controller valida a entrada, chama um service e devolve uma resposta.
7. O service abre transação quando necessário, usa os models/PDO e aplica as regras de negócio.
8. O controller renderiza uma view com layout ou redireciona com mensagem flash.
9. Erros inesperados são registrados em log e o usuário vê uma página amigável.

## 4. Camadas e responsabilidades

| Camada | Responsabilidade | Não deve fazer |
| --- | --- | --- |
| Core | Roteamento, sessão, banco, CSRF, validação, view | Regra de negócio de estoque |
| Controllers | Receber HTTP, validar formato, chamar services, responder | SQL complexo ou cálculo de estoque |
| Services | Regras de negócio, transações, cálculos, autorização contextual | HTML |
| Models | Consultas e persistência por entidade | Decidir fluxo de venda/compra |
| Views | HTML, Bootstrap, formulários, exibição escapada | Acesso direto ao banco |

Controllers devem ser finos. Toda regra que envolve saldo, lote, custo ou transação fica em `Services/`.

## 5. Banco e transações

### 5.1 Conexão PDO

Configuração recomendada:

```php
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

Uma conexão por requisição, criada de forma lazy em `Database::connection()`.

### 5.2 Fluxo transacional de venda

Pseudocódigo:

```php
$pdo->beginTransaction();
try {
    // 1. Bloquear produtos em ordem crescente de id.
    foreach ($itensOrdenadosPorProduto as $item) {
        $produto = $productModel->lockForUpdate($item->productId);
        if (!$produto || !$produto['is_active']) {
            throw new DomainException('Produto indisponível.');
        }
    }

    // 2. Validar e alocar lotes (FEFO) ou saldo agregado.
    // 3. Inserir sales, sale_items, sale_item_lots.
    // 4. Atualizar product_lots.current_quantity e products.current_stock.
    // 5. Inserir stock_movements com balance_after.
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
```

Regras de concorrência:

- Usar `SELECT ... FOR UPDATE` nos produtos e lotes envolvidos.
- Bloquear sempre na mesma ordem (crescente por `product_id`, depois `lot.id`) para reduzir deadlocks.
- Revalidar o saldo dentro da transação, mesmo que a tela já tenha validado.
- Em deadlock, permitir até 3 tentativas com pequeno backoff.

### 5.3 Alocação FEFO

Para produto com `controls_lots = 1`:

```sql
SELECT id, lot_code, expires_at, current_quantity
FROM product_lots
WHERE product_id = ?
  AND current_quantity > 0
  AND (expires_at IS NULL OR expires_at >= CURDATE())
ORDER BY expires_at IS NULL, expires_at, id
FOR UPDATE;
```

Se a soma disponível for menor que a quantidade pedida, a venda inteira é recusada com mensagem de estoque insuficiente. Cada lote consumido gera uma linha em `sale_item_lots` e uma movimentação de saída.

### 5.4 Compra e custo médio

Dentro da transação:

1. Bloquear o produto com `FOR UPDATE`.
2. Calcular o custo médio ponderado com `bcmath`.
3. Inserir `purchases` e `purchase_items`.
4. Criar `product_lots` quando `controls_lots = 1`.
5. Atualizar `products.current_stock` e `products.cost_price`.
6. Inserir `stock_movements` de entrada.

### 5.5 Cancelamento

1. Bloquear a venda e verificar `status = 'concluida'`.
2. Bloquear os produtos e lotes envolvidos na ordem padrão.
3. Para cada `sale_item_lot`, devolver a quantidade ao lote.
4. Para itens sem lote, devolver ao saldo agregado.
5. Inserir movimentações de `estorno_venda`.
6. Atualizar `products.current_stock` e marcar a venda como `cancelada`.

### 5.6 Ajustes e perdas

- Bloquear o produto.
- Para saída em produto com lote, alocar por FEFO ou por lote escolhido.
- Para entrada em produto com lote, criar lote manual com código e validade.
- Se o ajuste positivo informar custo unitário, recalcular o custo médio; caso contrário, manter o custo atual.
- Inserir `stock_adjustments`, `stock_movements` e atualizar saldos.
- Nunca permitir saldo negativo.

### 5.7 Consistência

`products.current_stock` é uma otimização de leitura. A fonte de verdade é o livro `stock_movements`. As invariantes do modelo de dados devem ser verificadas por rotina de conciliação e por testes.

## 6. Segurança

| Controle | Implementação |
| --- | --- |
| Senhas | `password_hash` com `PASSWORD_DEFAULT` (Argon2id ou bcrypt) e `password_verify` |
| Sessão | `session_regenerate_id(true)` após login; cookie `HttpOnly`, `SameSite=Lax`; `Secure` em produção |
| CSRF | Token por sessão em todo POST; validação com `hash_equals` |
| SQL injection | Sempre prepared statements; nunca concatenar entrada em SQL |
| XSS | Escapar toda saída com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; helper `e()` |
| Autorização | Middleware no servidor; esconder botão não é segurança |
| Força bruta | Limitar tentativas de login e adicionar atraso progressivo |
| Erros | Não exibir stack trace nem SQL; registrar em log |
| Segredos | Credenciais fora do repositório; `.env`/variáveis de ambiente |
| Headers | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin` |
| HTTPS | Obrigatório em produção |
| Arquivos | Document root em `public/`; `app/`, `config/`, `database/`, `storage/`, `docs/` inacessíveis via web |

## 7. Configuração

`config/config.php` lê variáveis de ambiente com valores padrão para desenvolvimento local:

```php
return [
    'app' => [
        'env' => getenv('APP_ENV') ?: 'local',
        'timezone' => 'America/Sao_Paulo',
        'expiry_alert_days' => (int) (getenv('EXPIRY_ALERT_DAYS') ?: 7),
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'estoque_churrasco',
        'user' => getenv('DB_USER') ?: '',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'session' => [
        'name' => 'estoque_churrasco',
    ],
];
```

O arquivo `.env.example` documenta as variáveis, mas o `.env` real fica no `.gitignore`. Em Apache, também é possível usar `SetEnv` no virtual host.

## 8. Sessão e autenticação

- Login por e-mail e senha.
- Usuário inativo não autentica.
- A cada requisição, o usuário da sessão é revalidado no banco; se estiver inativo, perde o acesso imediatamente.
- Sessão guarda `user_id`, `name`, `email` e `role`, atualizados a partir do banco.
- Logout remove o usuário da sessão e regenera o ID da sessão.
- Senha inicial criada pelo seed; troca de senha pelo administrador ou pelo próprio usuário (desejável).
- Após 30 minutos de inatividade, a sessão pode expirar.

## 9. Interface

- Layout único com sidebar, cabeçalho, identificação do usuário, perfil e botão de sair.
- Bootstrap 5 via CDN; CSS próprio mínimo em `public/assets/css/app.css`.
- Páginas responsivas, tabelas com rolagem horizontal no mobile.
- Mensagens flash de sucesso, erro e aviso.
- Formulários com labels, valores preservados em erro, validação HTML básica e validação no servidor.
- Confirmação explícita para cancelar venda, desativar cadastro e registrar perda.
- Listas com busca, filtros e paginação (20 itens por página).
- Badges de estoque baixo, lote vencido e venda cancelada.
- Link "Pular para o conteúdo", `aria-current` nos links ativos e navegação de relatórios por abas.
- O fluxo principal funciona sem JavaScript. JavaScript apenas melhora a adição de itens em venda/compra e a exibição de totais; o servidor recalcula tudo.

## 10. Erros e logs

- `APP_ENV=local`: mensagens detalhadas na tela, úteis para estudo.
- `APP_ENV=production`: mensagem genérica; detalhes apenas em `storage/logs/app.log`.
- Logar data, nível, URL, usuário e mensagem; nunca logar senha ou token.
- `DomainException` para erros de regra de negócio, exibidos como mensagem amigável.
- `Throwable` inesperado: log + página de erro 500.

## 11. Testes

O projeto usa um runner próprio em `tests/run.php`, sem Composer ou PHPUnit. Ele força o banco isolado `estoque_churrasco_test`, limpa e popula os dados e executa asserções dos fluxos críticos. O banco `estoque_churrasco` de desenvolvimento não é tocado.

```bash
docker compose exec web php tests/run.php
```

Resultado esperado: `OK: 49 asserções`.

Cobertura:

1. `Money`: arredondamento, multiplicação e soma.
2. `PurchaseService`: compra com múltiplos itens, lotes, custo médio ponderado e movimentações.
3. `SaleService`: consolidação de itens, FEFO, bloqueio de lote vencido, snapshot de preço/custo, venda sem saldo, cancelamento e estorno.
4. `AdjustmentService`: ajuste positivo com lote, custo médio, perda FEFO incluindo vencidos e bloqueio de saldo negativo.
5. `ReportService`: estoque, estoque baixo, vendas e conciliação.

O banco de teste é criado por `database/init-test-db.sh` na primeira inicialização do volume Docker. Em um volume existente, execute:

```bash
docker compose exec db bash /docker-entrypoint-initdb.d/02-test-db.sh
```

## 12. Ambiente local

O ambiente escolhido é Docker Compose com Apache + PHP e MySQL. Ele garante a mesma versão para todos e evita instalar serviços na máquina.

### 12.1 Serviços

| Serviço | Imagem/base | Função |
| --- | --- | --- |
| `web` | `php:8.2-apache` com PDO MySQL, `mod_rewrite` e `mod_headers` | Aplicação; porta 8080 publicada |
| `db` | `mysql:8.0` | Banco de dados com volume persistente |
| `adminer` (opcional) | `adminer` | Inspeção do banco apenas em desenvolvimento |

O `Dockerfile` ajusta o `DocumentRoot` do Apache para `public/`. O `docker-compose.yml` monta o projeto como volume, define as variáveis de ambiente do banco, aplica `database/schema.sql` e executa `database/init-test-db.sh` na primeira inicialização do volume.

Fluxo:

1. Criar o `.env` local a partir do `.env.example`.
2. `docker compose up -d --build`.
3. Aguardar o banco ficar saudável; o schema é aplicado na primeira inicialização.
4. Executar `database/seed.php` dentro do container web.
5. Acessar `http://localhost:8080/login`.

Para recriar o banco do zero, remover o volume com `docker compose down -v` (apaga os dados). Credenciais do banco e senhas iniciais ficam em `.env` local, fora do repositório.

Esboço inicial para validar na Fase 0:

```dockerfile
FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql bcmath \
    && a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf
```

```yaml
services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      APP_ENV: local
      DB_HOST: db
      DB_PORT: 3306
      DB_NAME: estoque_churrasco
      DB_USER: estoque
      DB_PASS: ${DB_PASS}
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: estoque_churrasco
      MYSQL_USER: estoque
      MYSQL_PASSWORD: ${DB_PASS}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS}
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql:ro
      - ./database/init-test-db.sh:/docker-entrypoint-initdb.d/02-test-db.sh:ro
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-p${DB_ROOT_PASS}"]
      interval: 5s
      timeout: 5s
      retries: 20

  adminer:
    image: adminer
    ports:
      - "8081:8080"
    depends_on:
      - db

volumes:
  db_data:
```

### 12.2 Alternativa sem Docker

1. Criar o banco e importar `database/schema.sql`.
2. Executar `database/seed.php` para dados iniciais.
3. Configurar o virtual host do Apache com `DocumentRoot` apontando para `public/`.
4. Habilitar `mod_rewrite` e `mod_headers`.
5. Configurar as variáveis de ambiente do banco.
6. Garantir escrita em `storage/logs/`.
7. Acessar `/login` com o usuário administrador do seed.

Exemplo de virtual host:

```apache
<VirtualHost *:80>
    ServerName estoque.local
    DocumentRoot /var/www/estoque-churrasco/public

    <Directory /var/www/estoque-churrasco/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/estoque-error.log
    CustomLog ${APACHE_LOG_DIR}/estoque-access.log combined
</VirtualHost>
```

### 12.3 `.htaccess`

`public/.htaccess`:

```apache
Options -Indexes
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set Referrer-Policy "same-origin"
</IfModule>
```

## 13. Backup e operação

Backup com `mysqldump`:

```bash
docker compose exec -T db sh -lc \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers estoque_churrasco > /tmp/estoque_backup.sql'

docker compose cp db:/tmp/estoque_backup.sql /tmp/estoque_backup.sql
```

Restauração (sobrescreve os dados atuais):

```bash
docker compose cp /tmp/estoque_backup.sql db:/tmp/estoque_backup.sql

docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" estoque_churrasco < /tmp/estoque_backup.sql'
```

Operação:

- Backup diário, retenção mínima de 7 dias e armazenamento fora do repositório.
- Testar restauração periodicamente em banco descartável.
- Antes de alterações de esquema, gerar backup.
- Monitorar espaço em disco e crescimento de `stock_movements`.
- Manter o PHP e o MySQL atualizados.

Procedimento completo e teste de restauração em `docs/08-instalacao-e-backup.md`.
