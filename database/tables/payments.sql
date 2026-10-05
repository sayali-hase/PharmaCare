DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `payment_method` ENUM('cod','online') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,

    `payment_status` ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) NOT NULL DEFAULT 'Pending',

    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `payment_date` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_order` (`order_id`),
    UNIQUE KEY `uk_transaction_id` (`transaction_id`),

    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;