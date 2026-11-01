<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap/app.php';

$adminName = getenv('SEED_ADMIN_NAME') ?: 'Administrador';
$adminEmail = getenv('SEED_ADMIN_EMAIL') ?: 'admin@estoque.local';
$adminPassword = getenv('SEED_ADMIN_PASSWORD') ?: 'admin12345';
$operatorPassword = getenv('SEED_OPERATOR_PASSWORD') ?: 'operador12345';

$operators = [
    ['name' => 'Operador 1', 'email' => 'operador1@estoque.local'],
    ['name' => 'Operador 2', 'email' => 'operador2@estoque.local'],
];

$categories = [
    ['name' => 'Carnes', 'description' => 'Carnes e cortes para churrasco', 'default_controls_lots' => 1],
    ['name' => 'Carvão e fósforos', 'description' => 'Itens para acender e manter o fogo', 'default_controls_lots' => 0],
    ['name' => 'Bebidas', 'description' => 'Bebidas em geral', 'default_controls_lots' => 0],
    ['name' => 'Utensílios', 'description' => 'Utensílios e acessórios', 'default_controls_lots' => 0],
    ['name' => 'Outros', 'description' => 'Outros produtos da loja', 'default_controls_lots' => 0],
];

$products = [
    ['category' => 'Carnes', 'name' => 'Picanha', 'sku' => 'CARNE-001', 'unit' => 'kg', 'sale_price' => 89.90, 'cost_price' => 59.90, 'min_stock' => 5.000, 'controls_lots' => 1],
    ['category' => 'Carnes', 'name' => 'Linguiça', 'sku' => 'CARNE-002', 'unit' => 'kg', 'sale_price' => 34.90, 'cost_price' => 22.50, 'min_stock' => 10.000, 'controls_lots' => 1],
    ['category' => 'Carvão e fósforos', 'name' => 'Carvão', 'sku' => 'CARVAO-001', 'unit' => 'pacote', 'sale_price' => 24.90, 'cost_price' => 15.00, 'min_stock' => 20.000, 'controls_lots' => 0],
    ['category' => 'Carvão e fósforos', 'name' => 'Fósforo', 'sku' => 'FOSFORO-001', 'unit' => 'un', 'sale_price' => 2.50, 'cost_price' => 1.20, 'min_stock' => 50.000, 'controls_lots' => 0],
    ['category' => 'Bebidas', 'name' => 'Refrigerante', 'sku' => 'BEBIDA-001', 'unit' => 'un', 'sale_price' => 8.00, 'cost_price' => 4.50, 'min_stock' => 24.000, 'controls_lots' => 0],
    ['category' => 'Outros', 'name' => 'Sal grosso', 'sku' => 'OUTROS-001', 'unit' => 'kg', 'sale_price' => 6.00, 'cost_price' => 2.50, 'min_stock' => 5.000, 'controls_lots' => 0],
];

try {
    $pdo = null;
    $lastConnectionError = null;

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        try {
            $pdo = Database::connection();
            break;
        } catch (Throwable $exception) {
            $lastConnectionError = $exception;

            if ($attempt < 30) {
                sleep(2);
            }
        }
    }

    if ($pdo === null) {
        throw $lastConnectionError ?? new RuntimeException('Não foi possível conectar ao banco de dados.');
    }

    $pdo->beginTransaction();

    $findUser = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $insertUser = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');

    $ensureUser = static function (string $name, string $email, string $password, string $role) use ($findUser, $insertUser): void {
        $findUser->execute([$email]);

        if ($findUser->fetchColumn() !== false) {
            return;
        }

        $insertUser->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
        ]);
    };

    $ensureUser($adminName, $adminEmail, $adminPassword, 'admin');

    foreach ($operators as $operator) {
        $ensureUser($operator['name'], $operator['email'], $operatorPassword, 'operador');
    }

    $findCategory = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, description, default_controls_lots) VALUES (?, ?, ?)');
    $categoryIds = [];

    foreach ($categories as $category) {
        $findCategory->execute([$category['name']]);
        $id = $findCategory->fetchColumn();

        if ($id === false) {
            $insertCategory->execute([
                $category['name'],
                $category['description'],
                $category['default_controls_lots'],
            ]);
            $id = $pdo->lastInsertId();
        }

        $categoryIds[$category['name']] = (int) $id;
    }

    $findProduct = $pdo->prepare('SELECT id FROM products WHERE name = ? LIMIT 1');
    $insertProduct = $pdo->prepare(
        'INSERT INTO products (category_id, name, sku, unit, sale_price, cost_price, min_stock, current_stock, controls_lots)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)'
    );

    foreach ($products as $product) {
        $findProduct->execute([$product['name']]);

        if ($findProduct->fetchColumn() !== false) {
            continue;
        }

        $insertProduct->execute([
            $categoryIds[$product['category']],
            $product['name'],
            $product['sku'],
            $product['unit'],
            $product['sale_price'],
            $product['cost_price'],
            $product['min_stock'],
            $product['controls_lots'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Falha no seed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'Seed concluído.' . PHP_EOL);
fwrite(STDOUT, 'Admin: ' . $adminEmail . ' / ' . $adminPassword . PHP_EOL);
fwrite(STDOUT, 'Operadores: operador1@estoque.local e operador2@estoque.local / ' . $operatorPassword . PHP_EOL);
fwrite(STDOUT, 'Troque as senhas após o primeiro acesso.' . PHP_EOL);
