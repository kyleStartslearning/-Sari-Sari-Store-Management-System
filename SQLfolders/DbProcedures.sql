-- =========================
-- STORED PROCEDURES FOR PRODUCT MANAGEMENT
-- =========================

-- 9. Add New Product

DELIMITER //
CREATE PROCEDURE AddProduct(
    IN p_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_price DECIMAL(10,2),
    IN p_stock INT,
    IN p_cost_price DECIMAL(10,2),
    IN p_expiry_date DATE
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    INSERT INTO products (
        product_name, product_description, category, price,
        stock_quantity, cost_price, expiry_date
    ) VALUES (
        p_name, p_description, p_category, p_price,
        p_stock, p_cost_price, p_expiry_date
    );
    -- Log inventory movement for initial stock
    INSERT INTO inventory_movements (
        product_id, movement_type, quantity, reference_type, notes
    ) VALUES (
        LAST_INSERT_ID(), 'in', p_stock, 'adjustment', 'Initial stock'
    );
    COMMIT;
    SELECT LAST_INSERT_ID() as product_id;
END //
DELIMITER ;

-- 10. Update Existing Product
DELIMITER //
CREATE PROCEDURE UpdateProduct(
    IN p_id INT,
    IN p_name VARCHAR(100),
    IN p_description TEXT,
    IN p_category VARCHAR(50),
    IN p_price DECIMAL(10,2),
    IN p_stock INT,
    IN p_cost_price DECIMAL(10,2),
    IN p_expiry_date DATE
)
BEGIN
    DECLARE old_stock INT DEFAULT 0;
    DECLARE stock_diff INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    -- Get current stock level
    SELECT stock_quantity INTO old_stock FROM products WHERE product_id = p_id;
    SET stock_diff = p_stock - old_stock;
    UPDATE products SET
        product_name = p_name,
        product_description = p_description,
        category = p_category,
        price = p_price,
        stock_quantity = p_stock,
        cost_price = p_cost_price,
        expiry_date = p_expiry_date,
        updated_at = CURRENT_TIMESTAMP
    WHERE product_id = p_id;
    -- Log stock adjustment if stock changed
    IF stock_diff != 0 THEN
        INSERT INTO inventory_movements (
            product_id, movement_type, quantity, reference_type, notes
        ) VALUES (
            p_id,
            IF(stock_diff > 0, 'in', 'out'),
            ABS(stock_diff),
            'adjustment',
            'Stock adjustment via product update'
        );
    END IF;
    COMMIT;
    SELECT ROW_COUNT() as affected_rows;
END //
DELIMITER ;

-- 11. Deactivate (Delete) Product
DELIMITER //
CREATE PROCEDURE DeleteProduct(IN p_id INT)
BEGIN
    DECLARE product_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO product_exists FROM products WHERE product_id = p_id;
    IF product_exists > 0 THEN
        UPDATE products SET status = 'inactive' WHERE product_id = p_id;
        SELECT 1 as success, 'Product deactivated successfully' as message;
    ELSE
        SELECT 0 as success, 'Product not found' as message;
    END IF;
END //
DELIMITER ;

-- 12. Retrieve All Active Products
DELIMITER //
CREATE PROCEDURE GetAllProducts()
BEGIN
    SELECT
        product_id, product_name, product_description, category,
        price, stock_quantity, cost_price,
        expiry_date, created_at, updated_at, status
    FROM products
    WHERE status = 'active'
    ORDER BY product_name;
END //
DELIMITER ;

-- 13. Retrieve Product by ID
DELIMITER //
CREATE PROCEDURE GetProductById(IN p_id INT)
BEGIN
    SELECT
        product_id, product_name, product_description, category,
        price, stock_quantity, cost_price,
        expiry_date, created_at, updated_at, status
    FROM products
    WHERE product_id = p_id AND status = 'active';
END //
DELIMITER ;

-- =========================
-- STORED PROCEDURES FOR SALES TRANSACTIONS
-- =========================

-- 14. Process a Sale Transaction
DELIMITER //
CREATE PROCEDURE ProcessSaleTransaction(
    IN p_customer_id INT,
    IN p_total_amount DECIMAL(10,2),
    IN p_payment_method VARCHAR(20),
    IN p_discount_amount DECIMAL(10,2),
    IN p_tax_amount DECIMAL(10,2),
    IN p_cashier_name VARCHAR(100),
    IN p_items JSON
)
BEGIN
    DECLARE v_transaction_id INT;
    DECLARE v_receipt_number VARCHAR(50);
    DECLARE v_item_count INT DEFAULT 0;
    DECLARE v_counter INT DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_total_price DECIMAL(10,2);
    DECLARE v_current_stock INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    -- Generate a unique receipt number
    SET v_receipt_number = CONCAT('REC-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    -- Insert main transaction record
    INSERT INTO sales_transactions (
        customer_id, total_amount, payment_method, discount_amount,
        tax_amount, cashier_name, receipt_number
    ) VALUES (
        p_customer_id, p_total_amount, p_payment_method, p_discount_amount,
        p_tax_amount, p_cashier_name, v_receipt_number
    );
    SET v_transaction_id = LAST_INSERT_ID();
    -- Process each item in JSON array
    SET v_item_count = JSON_LENGTH(p_items);
    WHILE v_counter < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_counter, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_counter, '].quantity')));
        SET v_unit_price = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_counter, '].unit_price')));
        SET v_total_price = v_quantity * v_unit_price;
        -- Check stock availability
        SELECT stock_quantity INTO v_current_stock FROM products WHERE product_id = v_product_id;
        IF v_current_stock < v_quantity THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for one or more products';
        END IF;
        -- Insert transaction item
        INSERT INTO sales_transaction_items (
            transaction_id, product_id, quantity, unit_price, total_price
        ) VALUES (
            v_transaction_id, v_product_id, v_quantity, v_unit_price, v_total_price
        );
        -- Reduce stock quantity
        UPDATE products
        SET stock_quantity = stock_quantity - v_quantity
        WHERE product_id = v_product_id;
        -- Log inventory outflow
        INSERT INTO inventory_movements (
            product_id, movement_type, quantity, reference_type, reference_id, notes
        ) VALUES (
            v_product_id, 'out', v_quantity, 'sale', v_transaction_id, 'Sale transaction'
        );
        SET v_counter = v_counter + 1;
    END WHILE;
    COMMIT;
    -- Return transaction details
    SELECT v_transaction_id as transaction_id, v_receipt_number as receipt_number;
END //
DELIMITER ;

-- 15. Get Details of a Specific Transaction
DELIMITER //
CREATE PROCEDURE GetTransactionDetails(IN p_transaction_id INT)
BEGIN
    -- Transaction header info
    SELECT
        st.transaction_id, st.customer_id, c.customer_name,
        st.transaction_date, st.total_amount, st.payment_method,
        st.discount_amount, st.tax_amount, st.cashier_name,
        st.receipt_number, st.status
    FROM sales_transactions st
    LEFT JOIN customers c ON st.customer_id = c.customer_id
    WHERE st.transaction_id = p_transaction_id;
    -- Transaction items
    SELECT
        sti.item_id, sti.product_id, p.product_name,
        sti.quantity, sti.unit_price, sti.total_price
    FROM sales_transaction_items sti
    JOIN products p ON sti.product_id = p.product_id
    WHERE sti.transaction_id = p_transaction_id;
END //
DELIMITER ;

-- =========================
-- REPORTING PROCEDURES
-- =========================

-- 16. Get Monthly Sales Report
DELIMITER //
CREATE PROCEDURE GetMonthlySalesReport(
    IN p_year INT,
    IN p_month INT
)
BEGIN
    SELECT
        DATE(st.transaction_date) as sale_date,
        COUNT(st.transaction_id) as total_transactions,
        SUM(st.total_amount) as daily_sales,
        AVG(st.total_amount) as average_transaction
    FROM sales_transactions st
    WHERE YEAR(st.transaction_date) = p_year
      AND MONTH(st.transaction_date) = p_month
      AND st.status = 'completed'
    GROUP BY DATE(st.transaction_date)
    ORDER BY sale_date;
    -- Summary totals
    SELECT
        COUNT(st.transaction_id) as total_transactions,
        SUM(st.total_amount) as total_sales,
        AVG(st.total_amount) as average_transaction,
        MAX(st.total_amount) as highest_sale,
        MIN(st.total_amount) as lowest_sale
    FROM sales_transactions st
    WHERE YEAR(st.transaction_date) = p_year
      AND MONTH(st.transaction_date) = p_month
      AND st.status = 'completed';
END //
DELIMITER ;

-- 17. Get Product Sales Report within Date Range
DELIMITER //
CREATE PROCEDURE GetProductSalesReport(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT
        p.product_id,
        p.product_name,
        p.category,
        COALESCE(SUM(sti.quantity), 0) as total_sold,
        COALESCE(SUM(sti.total_price), 0) as total_revenue,
        p.stock_quantity as current_stock,
        p.price as current_price
    FROM products p
    LEFT JOIN sales_transaction_items sti ON p.product_id = sti.product_id
    LEFT JOIN sales_transactions st ON sti.transaction_id = st.transaction_id
        AND st.transaction_date BETWEEN p_start_date AND p_end_date
        AND st.status = 'completed'
    WHERE p.status = 'active'
    GROUP BY p.product_id, p.product_name, p.category, p.stock_quantity, p.price
    ORDER BY total_revenue DESC;
END //
DELIMITER ;

-- 19. Get All Customer Transactions
DELIMITER //
CREATE PROCEDURE GetAllCustomerTransactions(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        DATE(st.transaction_date) as transaction_date,
        st.customer_id,
        CASE 
            WHEN c.customer_id IS NOT NULL THEN CONCAT('#', st.customer_id, ' - ', c.customer_name)
            WHEN st.customer_id IS NOT NULL THEN CONCAT('#', st.customer_id, ' (Deleted)')
            ELSE 'Walk-in'
        END as customer_info,
        GROUP_CONCAT(CONCAT(p.product_name, ' (', sti.quantity, ')') SEPARATOR ', ') as items,
        st.payment_method,
        st.total_amount,
        st.transaction_id
    FROM sales_transactions st
    LEFT JOIN customers c ON st.customer_id = c.customer_id
    LEFT JOIN sales_transaction_items sti ON st.transaction_id = sti.transaction_id
    LEFT JOIN products p ON sti.product_id = p.product_id
    WHERE (p_start_date IS NULL OR DATE(st.transaction_date) >= p_start_date)
    AND (p_end_date IS NULL OR DATE(st.transaction_date) <= p_end_date)
    GROUP BY st.transaction_id, st.transaction_date, st.customer_id, c.customer_id, c.customer_name, st.payment_method, st.total_amount
    ORDER BY st.transaction_date DESC;
END //
DELIMITER ;

-- 20. Authenticate Admin
DELIMITER //
CREATE PROCEDURE AuthenticateAdmin(
    IN p_username VARCHAR(50)
)
BEGIN
    SELECT admin_id, username, password
    FROM admin_users
    WHERE username = p_username;
END //
DELIMITER ;

-- 21. Get Dashboard Statistics
DELIMITER //
CREATE PROCEDURE GetDashboardStats()
BEGIN
    -- Get total active products
    SELECT COUNT(*) as total_products
    FROM products 
    WHERE status = 'active';
    
    -- Get total completed transactions
    SELECT COUNT(*) as total_transactions
    FROM sales_transactions 
    WHERE status = 'completed';
    
    -- Get low stock products count (threshold of 10)
    SELECT COUNT(*) as low_stock_count
    FROM products 
    WHERE stock_quantity <= 10 AND status = 'active';
END //
DELIMITER ;