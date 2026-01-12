-- 09/01/2026 --
CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `units` varchar(5) NOT NULL,
  `customer` int(5) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `grades` ADD PRIMARY KEY (`id`);

ALTER TABLE `grades` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `products` ADD `pricing_type` VARCHAR(10) NULL AFTER `remark`;

CREATE TABLE `product_customers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pricing_type` varchar(10) DEFAULT NULL,
  `price` varchar(50) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `product_customers` ADD PRIMARY KEY (`id`);

ALTER TABLE `product_customers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- 11/01/2026 --
ALTER TABLE `users` CHANGE `baudrate` `baudrate` VARCHAR(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

ALTER TABLE `users` CHANGE `databits` `databits` VARCHAR(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

ALTER TABLE `users` CHANGE `parity` `parity` VARCHAR(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

ALTER TABLE `users` CHANGE `stopbits` `stopbits` VARCHAR(5) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL;

-- 12/01/2026 --
ALTER TABLE `companies` ADD `parent` INT(11) NULL AFTER `products`;
