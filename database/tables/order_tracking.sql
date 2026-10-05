DROP TABLE IF EXISTS `order_tracking`;

CREATE TABLE `order_tracking` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `status` ENUM(
        'Order Placed',
        'Confirmed',
        'Packed',
        'Shipped',
        'Out for Delivery',
        'Delivered',
        'Cancelled'
    ) NOT NULL DEFAULT 'Order Placed',
    `status_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `location` VARCHAR(150) DEFAULT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_tracking_order` (`order_id`),
    KEY `idx_tracking_status` (`status`),

    CONSTRAINT `fk_tracking_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;