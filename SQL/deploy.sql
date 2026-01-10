-- 09/01/2025 --
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