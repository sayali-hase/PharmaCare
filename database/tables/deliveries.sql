DROP TABLE IF EXISTS `deliveries`;

CREATE TABLE `deliveries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `delivery_status` ENUM(
        'Pending',
        'Assigned',
        'Out for Delivery',
        'Delivered',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    `delivery_address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(10) NOT NULL,
    `delivery_date` DATE DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_delivery_order` (`order_id`),
    KEY `idx_delivery_status` (`delivery_status`),

    CONSTRAINT `fk_deliveries_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;