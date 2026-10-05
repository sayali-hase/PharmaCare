DROP TABLE IF EXISTS `medicines`;

CREATE TABLE `medicines` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` INT UNSIGNED DEFAULT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
    `stock` INT UNSIGNED NOT NULL DEFAULT '0',
    `image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_medicine_supplier` (`supplier_id`),
    KEY `idx_medicine_category` (`category_id`),
    KEY `idx_medicine_name` (`name`),
    KEY `idx_medicine_stock` (`stock`),

    CONSTRAINT `fk_medicines_supplier`
        FOREIGN KEY (`supplier_id`)
        REFERENCES `suppliers` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT `fk_medicines_category`
        FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;