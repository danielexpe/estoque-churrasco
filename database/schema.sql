CREATE DATABASE IF NOT EXISTS estoque_churrasco
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE estoque_churrasco;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  default_controls_lots TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  document VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  notes VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_suppliers_name (name)
) ENGINE=InnoDB;

CREATE TABLE products (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  sku VARCHAR(60) NULL,
  barcode VARCHAR(60) NULL,
  unit ENUM('un', 'kg', 'g', 'L', 'ml', 'pacote', 'caixa') NOT NULL DEFAULT 'un',
  sale_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_stock DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  current_stock DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  controls_lots TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_sku (sku),
  UNIQUE KEY uq_products_barcode (barcode),
  KEY idx_products_name (name),
  KEY idx_products_category (category_id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT chk_products_stock CHECK (current_stock >= 0),
  CONSTRAINT chk_products_sale_price CHECK (sale_price >= 0),
  CONSTRAINT chk_products_cost_price CHECK (cost_price >= 0),
  CONSTRAINT chk_products_min_stock CHECK (min_stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE purchases (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  supplier_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  purchased_at DATETIME NOT NULL,
  document_number VARCHAR(80) NULL,
  notes VARCHAR(500) NULL,
  total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_purchases_date (purchased_at),
  KEY idx_purchases_supplier (supplier_id),
  CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id)
    REFERENCES suppliers (id) ON DELETE SET NULL,
  CONSTRAINT fk_purchases_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_purchases_total CHECK (total_cost >= 0)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_purchase_items_purchase (purchase_id),
  KEY idx_purchase_items_product (product_id),
  CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id)
    REFERENCES purchases (id) ON DELETE RESTRICT,
  CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT chk_purchase_items_quantity CHECK (quantity > 0),
  CONSTRAINT chk_purchase_items_cost CHECK (unit_cost >= 0),
  CONSTRAINT chk_purchase_items_total CHECK (line_total >= 0)
) ENGINE=InnoDB;

CREATE TABLE product_lots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  purchase_item_id BIGINT UNSIGNED NULL,
  lot_code VARCHAR(80) NOT NULL,
  expires_at DATE NULL,
  initial_quantity DECIMAL(12,3) NOT NULL,
  current_quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_product_lots_purchase_item (purchase_item_id),
  KEY idx_product_lots_product (product_id),
  KEY idx_product_lots_expiry (expires_at),
  CONSTRAINT fk_product_lots_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT fk_product_lots_purchase_item FOREIGN KEY (purchase_item_id)
    REFERENCES purchase_items (id) ON DELETE SET NULL,
  CONSTRAINT chk_product_lots_initial CHECK (initial_quantity > 0),
  CONSTRAINT chk_product_lots_current CHECK (current_quantity >= 0),
  CONSTRAINT chk_product_lots_cost CHECK (unit_cost >= 0)
) ENGINE=InnoDB;

CREATE TABLE sales (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  sold_at DATETIME NOT NULL,
  total_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('concluida', 'cancelada') NOT NULL DEFAULT 'concluida',
  canceled_by BIGINT UNSIGNED NULL,
  canceled_at DATETIME NULL,
  cancel_reason VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sales_date (sold_at),
  KEY idx_sales_status (status),
  CONSTRAINT fk_sales_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_sales_canceled_by FOREIGN KEY (canceled_by)
    REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT chk_sales_total CHECK (total_value >= 0)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  unit_cost DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sale_items_sale (sale_id),
  KEY idx_sale_items_product (product_id),
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id)
    REFERENCES sales (id) ON DELETE RESTRICT,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT chk_sale_items_quantity CHECK (quantity > 0),
  CONSTRAINT chk_sale_items_price CHECK (unit_price >= 0),
  CONSTRAINT chk_sale_items_cost CHECK (unit_cost >= 0),
  CONSTRAINT chk_sale_items_total CHECK (line_total >= 0)
) ENGINE=InnoDB;

CREATE TABLE sale_item_lots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sale_item_id BIGINT UNSIGNED NOT NULL,
  product_lot_id BIGINT UNSIGNED NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sale_item_lots (sale_item_id, product_lot_id),
  KEY idx_sale_item_lots_lot (product_lot_id),
  CONSTRAINT fk_sale_item_lots_item FOREIGN KEY (sale_item_id)
    REFERENCES sale_items (id) ON DELETE RESTRICT,
  CONSTRAINT fk_sale_item_lots_lot FOREIGN KEY (product_lot_id)
    REFERENCES product_lots (id) ON DELETE RESTRICT,
  CONSTRAINT chk_sale_item_lots_quantity CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE stock_adjustments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  adjustment_type ENUM('ajuste_positivo', 'ajuste_negativo', 'perda') NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(10,2) NULL,
  reason VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stock_adjustments_product (product_id),
  KEY idx_stock_adjustments_date (created_at),
  CONSTRAINT fk_stock_adjustments_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT fk_stock_adjustments_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_stock_adjustments_quantity CHECK (quantity > 0),
  CONSTRAINT chk_stock_adjustments_cost CHECK (unit_cost IS NULL OR unit_cost >= 0)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id BIGINT UNSIGNED NOT NULL,
  product_lot_id BIGINT UNSIGNED NULL,
  movement_type ENUM(
    'entrada_compra',
    'saida_venda',
    'estorno_venda',
    'ajuste_positivo',
    'ajuste_negativo',
    'perda'
  ) NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  balance_after DECIMAL(12,3) NOT NULL,
  reference_type ENUM('purchase', 'sale', 'adjustment') NULL,
  reference_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  notes VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_stock_movements_product_date (product_id, created_at),
  KEY idx_stock_movements_type_date (movement_type, created_at),
  KEY idx_stock_movements_reference (reference_type, reference_id),
  CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE RESTRICT,
  CONSTRAINT fk_stock_movements_lot FOREIGN KEY (product_lot_id)
    REFERENCES product_lots (id) ON DELETE SET NULL,
  CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT chk_stock_movements_quantity CHECK (quantity <> 0),
  CONSTRAINT chk_stock_movements_balance CHECK (balance_after >= 0)
) ENGINE=InnoDB;
