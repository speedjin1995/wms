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
