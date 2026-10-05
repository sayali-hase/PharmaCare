DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',

    `status` ENUM(
        'Order Placed',
        'Confirmed',
        'Packed',
        'Shipped',
        'Out for Delivery',
        'Delivered',
        'Cancelled'
    ) NOT NULL DEFAULT 'Order Placed',

    `payment_method` ENUM('cod','online') NOT NULL DEFAULT 'cod',

    `payment_status` ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) NOT NULL DEFAULT 'Pending',

    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(10) NOT NULL,
    `landmark` VARCHAR(150) DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_number` (`order_number`),
    KEY `idx_order_user` (`user_id`),
    KEY `idx_order_status` (`status`),
    KEY `idx_order_created` (`created_at`),

    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;