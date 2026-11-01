<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Money;
use App\Services\AdjustmentService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\SaleService;

putenv('DB_NAME=estoque_churrasco_test');

require dirname(__DIR__) . '/bootstrap/app.php';

$assertions = 0;
$today = new DateTimeImmutable('today');

function assertTrue(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;

    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertDecimal(string $expected, mixed $actual, string $message): void
{
    assertTrue(
        bccomp($expected, (string) $actual, 3) === 0,
        $message . ' (esperado ' . $expected . ', obtido ' . $actual . ')'
    );
}

function dbValue(string $sql, array $parameters = []): mixed
{
    $statement = Database::connection()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchColumn();
}

try {
    $pdo = Database::connection();

    $tables = [
        'stock_movements',
        'sale_item_lots',
        'sale_items',
        'sales',
        'product_lots',
        'purchase_items',
        'purchases',
        'stock_adjustments',
        'products',
        'categories',
        'suppliers',
        'users',
    ];

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tables as $table) {
        $pdo->exec('TRUNCATE TABLE ' . $table);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $insert = static function (string $sql, array $parameters) use ($pdo): int {
        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $pdo->lastInsertId();
    };

    $passwordHash = password_hash('teste12345', PASSWORD_DEFAULT);

    $adminId = $insert(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)',
        ['Admin Teste', 'admin@teste.local', $passwordHash, 'admin']
    );
    $operatorId = $insert(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)',
        ['Operador Teste', 'operador@teste.local', $passwordHash, 'operador']
    );

    $meatCategoryId = $insert(
        'INSERT INTO categories (name, description, default_controls_lots) VALUES (?, ?, ?)',
        ['Carnes', 'Carnes', 1]
    );
    $charcoalCategoryId = $insert(
        'INSERT INTO categories (name, description, default_controls_lots) VALUES (?, ?, ?)',
        ['Carvão', 'Carvão', 0]
    );

    $picanhaId = $insert(
        'INSERT INTO products (
            category_id, name, unit, sale_price, cost_price, min_stock, current_stock, controls_lots
         ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)',
        [$meatCategoryId, 'Picanha', 'kg', '89.90', '50.00', '5.000', 1]
    );
    $charcoalId = $insert(
        'INSERT INTO products (
            category_id, name, unit, sale_price, cost_price, min_stock, current_stock, controls_lots
         ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)',
        [$charcoalCategoryId, 'Carvão', 'pacote', '24.90', '15.00', '20.000', 0]
    );

    $expiryA = $today->modify('+60 days')->format('Y-m-d');
    $expiryB = $today->modify('+30 days')->format('Y-m-d');
    $expired = $today->modify('-1 day')->format('Y-m-d');
    $expiryD = $today->modify('+90 days')->format('Y-m-d');

    assertTrue(Money::round('2.675') === '2.68', 'arredondamento para cima');
    assertTrue(Money::round('2.674') === '2.67', 'arredondamento para baixo');
    assertTrue(Money::multiply('2.500', '30.00') === '75.00', 'multiplicação monetária');

    $purchaseService = new PurchaseService();
    $purchaseId = $purchaseService->create(
        [
            'supplier_id' => null,
            'purchased_at' => $today->format('Y-m-d') . ' 00:00:00',
            'document_number' => 'TEST-COMPRA',
            'notes' => null,
        ],
        [
            ['product_id' => $picanhaId, 'quantity' => '2.000', 'unit_cost' => '60.00', 'lot_code' => 'A', 'expires_at' => $expiryA],
            ['product_id' => $picanhaId, 'quantity' => '3.000', 'unit_cost' => '70.00', 'lot_code' => 'B', 'expires_at' => $expiryB],
            ['product_id' => $picanhaId, 'quantity' => '2.000', 'unit_cost' => '65.00', 'lot_code' => 'C', 'expires_at' => $expired],
            ['product_id' => $charcoalId, 'quantity' => '5.000', 'unit_cost' => '16.00', 'lot_code' => null, 'expires_at' => null],
        ],
        $adminId
    );

    assertDecimal('540.00', dbValue('SELECT total_cost FROM purchases WHERE id = ?', [$purchaseId]), 'total da compra');
    assertDecimal('7.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$picanhaId]), 'saldo da picanha após compra');
    assertDecimal('65.71', dbValue('SELECT cost_price FROM products WHERE id = ?', [$picanhaId]), 'custo médio da picanha');
    assertDecimal('5.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$charcoalId]), 'saldo do carvão após compra');
    assertDecimal('16.00', dbValue('SELECT cost_price FROM products WHERE id = ?', [$charcoalId]), 'custo do carvão');
    assertDecimal('2.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'A']), 'lote A');
    assertDecimal('3.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'B']), 'lote B');
    assertDecimal('2.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'C']), 'lote C');
    assertTrue(
        (int) dbValue('SELECT COUNT(*) FROM stock_movements WHERE reference_type = ? AND reference_id = ?', ['purchase', $purchaseId]) === 4,
        'movimentações da compra'
    );

    $saleService = new SaleService();
    $saleId = $saleService->create(
        [
            ['product_id' => $picanhaId, 'quantity' => '2.000'],
            ['product_id' => $picanhaId, 'quantity' => '2.000'],
            ['product_id' => $charcoalId, 'quantity' => '2.000'],
        ],
        $operatorId,
        date('Y-m-d H:i:s')
    );

    assertDecimal('409.40', dbValue('SELECT total_value FROM sales WHERE id = ?', [$saleId]), 'total da venda');
    assertTrue(
        (int) dbValue('SELECT COUNT(*) FROM sale_items WHERE sale_id = ?', [$saleId]) === 2,
        'itens repetidos devem ser consolidados'
    );
    assertDecimal('4.000', dbValue('SELECT quantity FROM sale_items WHERE sale_id = ? AND product_id = ?', [$saleId, $picanhaId]), 'quantidade consolidada');
    assertDecimal('89.90', dbValue('SELECT unit_price FROM sale_items WHERE sale_id = ? AND product_id = ?', [$saleId, $picanhaId]), 'preço congelado');
    assertDecimal('65.71', dbValue('SELECT unit_cost FROM sale_items WHERE sale_id = ? AND product_id = ?', [$saleId, $picanhaId]), 'custo congelado');
    assertDecimal('3.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$picanhaId]), 'saldo da picanha após venda');
    assertDecimal('3.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$charcoalId]), 'saldo do carvão após venda');
    assertDecimal('1.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'A']), 'lote A após FEFO');
    assertDecimal('0.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'B']), 'lote B consumido');
    assertDecimal('2.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'C']), 'lote vencido preservado');
    assertDecimal(
        '3.000',
        dbValue(
            'SELECT sil.quantity
             FROM sale_item_lots sil
             JOIN sale_items si ON si.id = sil.sale_item_id
             JOIN product_lots l ON l.id = sil.product_lot_id
             WHERE si.sale_id = ? AND l.lot_code = ?',
            [$saleId, 'B']
        ),
        'alocação FEFO no lote B'
    );
    assertDecimal(
        '1.000',
        dbValue(
            'SELECT sil.quantity
             FROM sale_item_lots sil
             JOIN sale_items si ON si.id = sil.sale_item_id
             JOIN product_lots l ON l.id = sil.product_lot_id
             WHERE si.sale_id = ? AND l.lot_code = ?',
            [$saleId, 'A']
        ),
        'alocação FEFO no lote A'
    );

    $oversellRejected = false;
    try {
        $saleService->create(
            [['product_id' => $picanhaId, 'quantity' => '2.000']],
            $operatorId,
            date('Y-m-d H:i:s')
        );
    } catch (DomainException) {
        $oversellRejected = true;
    }

    assertTrue($oversellRejected, 'venda sem saldo deve ser recusada');

    $saleService->cancel($saleId, $adminId, 'Cancelamento de teste', date('Y-m-d H:i:s'));

    assertTrue(dbValue('SELECT status FROM sales WHERE id = ?', [$saleId]) === 'cancelada', 'status da venda cancelada');
    assertDecimal('7.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$picanhaId]), 'saldo restaurado da picanha');
    assertDecimal('5.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$charcoalId]), 'saldo restaurado do carvão');
    assertDecimal('2.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'A']), 'lote A restaurado');
    assertDecimal('3.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'B']), 'lote B restaurado');
    assertDecimal('2.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'C']), 'lote C restaurado');

    $doubleCancelRejected = false;
    try {
        $saleService->cancel($saleId, $adminId, 'Tentativa duplicada', date('Y-m-d H:i:s'));
    } catch (DomainException) {
        $doubleCancelRejected = true;
    }

    assertTrue($doubleCancelRejected, 'cancelamento duplicado deve ser recusado');

    $adjustmentService = new AdjustmentService();
    $adjustmentService->create(
        'ajuste_positivo',
        $picanhaId,
        '5.000',
        '50.00',
        'D',
        $expiryD,
        'Carga inicial de teste',
        $adminId
    );

    assertDecimal('12.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$picanhaId]), 'saldo após ajuste positivo');
    assertDecimal('59.16', dbValue('SELECT cost_price FROM products WHERE id = ?', [$picanhaId]), 'custo médio após ajuste positivo');
    assertDecimal('5.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'D']), 'lote D');

    $adjustmentService->create(
        'perda',
        $picanhaId,
        '6.000',
        null,
        null,
        null,
        'Perda de teste',
        $adminId
    );

    assertDecimal('6.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$picanhaId]), 'saldo após perda');
    assertDecimal('0.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'C']), 'lote vencido consumido na perda');
    assertDecimal('0.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'B']), 'lote B consumido na perda');
    assertDecimal('1.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'A']), 'lote A restante após perda');
    assertDecimal('5.000', dbValue('SELECT current_quantity FROM product_lots WHERE product_id = ? AND lot_code = ?', [$picanhaId, 'D']), 'lote D preservado');
    assertDecimal('59.16', dbValue('SELECT cost_price FROM products WHERE id = ?', [$picanhaId]), 'perda não altera custo');

    $adjustmentService->create(
        'ajuste_negativo',
        $charcoalId,
        '3.000',
        null,
        null,
        null,
        'Correção de teste',
        $adminId
    );

    assertDecimal('2.000', dbValue('SELECT current_stock FROM products WHERE id = ?', [$charcoalId]), 'saldo após ajuste negativo');

    $insufficientRejected = false;
    try {
        $adjustmentService->create(
            'perda',
            $picanhaId,
            '7.000',
            null,
            null,
            null,
            'Teste de saldo insuficiente',
            $adminId
        );
    } catch (DomainException) {
        $insufficientRejected = true;
    }

    assertTrue($insufficientRejected, 'ajuste sem saldo deve ser recusado');

    $reportService = new ReportService();
    assertTrue(count($reportService->stock()) === 2, 'relatório de estoque');
    assertTrue(count($reportService->lowStock()) === 1, 'relatório de estoque baixo');
    $salesReport = $reportService->sales($today->format('Y-m-d'), $today->format('Y-m-d'));
    assertTrue($salesReport['concluded_count'] === 0 && $salesReport['canceled_count'] === 1, 'relatório de vendas');
    assertTrue($reportService->reconciliation() === [], 'conciliação deve estar limpa');

    $pdo->exec('UPDATE products SET current_stock = current_stock + 1 WHERE id = ' . $picanhaId);
    assertTrue(count($reportService->reconciliation()) === 1, 'conciliação deve detectar divergência');
    $pdo->exec('UPDATE products SET current_stock = current_stock - 1 WHERE id = ' . $picanhaId);
    assertTrue($reportService->reconciliation() === [], 'conciliação deve voltar a ficar limpa');

    fwrite(STDOUT, 'OK: ' . $assertions . ' asserções' . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
