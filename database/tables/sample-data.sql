USE `pharmacare`;

-- =========================================
-- 1. ADMIN
-- =========================================

INSERT IGNORE INTO `admins`
(`full_name`, `email`, `password`, `phone`, `status`)
VALUES
(
    'MediCare Admin',
    'admin@pharmacare.com',
    '$2y$10$voLdE4HVbP.8tr/q2gJmEeR/4N0MvhK/1cG2PHBUe3.huZgXWz0gu',
    '9876543210',
    'active'
);


-- =========================================
-- 2. CATEGORIES
-- =========================================

INSERT IGNORE INTO `categories`
(`name`)
VALUES
('Pain Relief'),
('Cold and Cough'),
('Vitamins'),
('Digestive Health'),
('First Aid');


-- =========================================
-- 3. SUPPLIER
-- =========================================

INSERT IGNORE INTO `suppliers`
(`supplier_name`, `email`, `phone`, `address`, `city`, `state`, `pincode`, `status`)
VALUES
(
    'ABC Pharma Suppliers',
    'supplier@abcpharma.com',
    '9876543211',
    'Main Market Road',
    'Nashik',
    'Maharashtra',
    '422001',
    'active'
);


-- =========================================
-- 4. DEMO CUSTOMER
-- =========================================

INSERT IGNORE INTO `users`
(`full_name`, `email`, `phone`, `password`, `status`)
VALUES
(
    'Demo Customer',
    'customer@example.com',
    '9876543212',
    '$2y$10$voLdE4HVbP.8tr/q2gJmEeR/4N0MvhK/1cG2PHBUe3.huZgXWz0gu',
    'active'
);


-- =========================================
-- 5. MEDICINES
-- =========================================

INSERT IGNORE INTO `medicines`
(`supplier_id`, `category_id`, `name`, `description`, `price`, `stock`, `image`, `status`)
VALUES
(
    1,
    1,
    'Paracetamol',
    'Pain and fever relief medicine',
    25.00,
    100,
    NULL,
    'active'
),
(
    1,
    2,
    'Cough Syrup',
    'Medicine for cough relief',
    85.00,
    50,
    NULL,
    'active'
),
(
    1,
    3,
    'Vitamin Tablets',
    'Daily vitamin supplement',
    120.00,
    75,
    NULL,
    'active'
);