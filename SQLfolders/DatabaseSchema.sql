-- Create Database
CREATE DATABASE sari_sari_store;
USE sari_sari_store;

-- =========================
-- TABLE CREATION
-- =========================

-- 1. Create Products Table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    product_description TEXT,
    category VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    cost_price DECIMAL(10,2),
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 2. Create Customers Table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Create Sales Transactions Table
CREATE TABLE sales_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit', 'gcash', 'paymaya') DEFAULT 'cash',
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    cashier_name VARCHAR(100),
    receipt_number VARCHAR(50) UNIQUE,
    status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
);

-- 4. Create Sales Transaction Items Table
CREATE TABLE sales_transaction_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES sales_transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

-- 5. Create Categories Table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Create Inventory Movements Table
CREATE TABLE inventory_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- =========================
-- INSERT SAMPLE DATA
-- =========================

-- Insert Sample Categories
INSERT INTO categories (category_name, description) VALUES
('Beverages', 'Soft drinks, juices, water'),
('Snacks', 'Chips, crackers, candies'),
('Personal Care', 'Soap, shampoo, toothpaste'),
('Household', 'Detergent, cleaning supplies'),
('Food Items', 'Canned goods, instant noodles'),
('Tobacco', 'Cigarettes, tobacco products'),
('School/Office Supplies', 'Pens, notebooks, paper');

-- Insert Sample Products
INSERT INTO products (product_name, product_description, category, price, stock_quantity, cost_price, expiry_date) VALUES
('Coca Cola 350ml', 'Coca Cola soft drink', 'Beverages', 25.00, 50, 20.00, '2024-12-31'),
('Lucky Me Instant Noodles', 'Chicken flavor instant noodles', 'Food Items', 15.00, 100, 12.00, '2024-10-15'),
('Surf Powder 35g', 'Laundry detergent powder', 'Household', 8.00, 75, 6.50, NULL),
('Kopiko Coffee', 'Instant coffee mix', 'Beverages', 12.00, 80, 10.00, '2024-11-30'),
('Rebisco Crackers', 'Cheese flavored crackers', 'Snacks', 18.00, 60, 15.00, '2024-09-20');

-- Insert Sample Customer Data
INSERT INTO customers (customer_name) VALUES
('Walk-in Customer'),
('Regular Customer 1'),
('Regular Customer 2');

-- Create Indexes
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_transactions_date ON sales_transactions(transaction_date);
CREATE INDEX idx_transaction_items_product ON sales_transaction_items(product_id);
CREATE INDEX idx_inventory_product ON inventory_movements(product_id);

-- =========================
-- VIEWS FOR REPORTING
-- =========================

-- 7. Monthly Sales Summary View
CREATE VIEW monthly_sales_summary AS
SELECT
    YEAR(transaction_date) as year,
    MONTH(transaction_date) as month,
    MONTHNAME(transaction_date) as month_name,
    COUNT(transaction_id) as total_transactions,
    SUM(total_amount) as total_sales,
    AVG(total_amount) as average_sale
FROM sales_transactions
WHERE status = 'completed'
GROUP BY YEAR(transaction_date), MONTH(transaction_date)
ORDER BY year DESC, month DESC;

CREATE VIEW product_sales_summary AS
SELECT
    p.product_id,
    p.product_name,
    p.category,
    SUM(sti.quantity) as total_sold,
    SUM(sti.total_price) as total_revenue,
    p.stock_quantity as current_stock
FROM products p
LEFT JOIN sales_transaction_items sti ON p.product_id = sti.product_id
LEFT JOIN sales_transactions st ON sti.transaction_id = st.transaction_id AND st.status = 'completed'
GROUP BY p.product_id, p.product_name, p.category, p.stock_quantity
ORDER BY total_revenue DESC;


