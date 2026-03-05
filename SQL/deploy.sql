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

CREATE TABLE `product_grades` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `product_grades` ADD PRIMARY KEY (`id`);

ALTER TABLE `product_grades` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `customers` ADD `parent` INT(11) NULL AFTER `pic`;

ALTER TABLE `supplies` ADD `parent` INT(11) NULL AFTER `pic`;

-- 16/01/2026 --
ALTER TABLE `companies` ADD `packages` TEXT NOT NULL DEFAULT '[]' AFTER `products`;

-- 18/01/2026 --
ALTER TABLE `wholesales` ADD `delete_reason` TEXT NULL AFTER `deleted`;

-- 02/02/2026 --
ALTER TABLE `wholesales` ADD `modified_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `end_time`, ADD `modified_by` VARCHAR(10) NULL AFTER `modified_at`;

CREATE TABLE `wholesales_log` (
  `id` int(10) NOT NULL,
  `wholesale_id` int(10) NOT NULL,
  `serial_no` varchar(30) DEFAULT NULL,
  `po_no` varchar(50) DEFAULT NULL,
  `security_bills` varchar(15) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `customer` varchar(10) DEFAULT NULL,
  `supplier` varchar(10) DEFAULT NULL,
  `product` varchar(50) DEFAULT NULL,
  `package` text DEFAULT NULL,
  `vehicle_no` varchar(15) DEFAULT NULL,
  `driver` text DEFAULT NULL,
  `driver_ic` text DEFAULT NULL,
  `other_customer` varchar(100) DEFAULT NULL,
  `other_supplier` varchar(100) DEFAULT NULL,
  `units` varchar(10) DEFAULT NULL,
  `weight_details` text DEFAULT '[]',
  `reject_details` text DEFAULT '[]',
  `total_item` varchar(10) NOT NULL DEFAULT '0',
  `total_weight` varchar(10) NOT NULL DEFAULT '0.0',
  `total_reject` varchar(10) NOT NULL DEFAULT '0.0',
  `total_price` varchar(10) NOT NULL DEFAULT '0.00',
  `remark` text DEFAULT NULL,
  `created_datetime` datetime DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `checked_by` varchar(30) DEFAULT NULL,
  `company` int(5) DEFAULT NULL,
  `weighted_by` varchar(30) DEFAULT NULL,
  `indicator` varchar(30) DEFAULT NULL,
  `deleted` int(3) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `action_id` varchar(5) NOT NULL,
  `action_by` varchar(15) NOT NULL,
  `event_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `wholesales_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `wholesales_log` MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_WHOLESALES` AFTER INSERT ON `wholesales` FOR EACH ROW INSERT INTO wholesales_log (
    wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_WHOLESALES` BEFORE UPDATE ON `wholesales` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    INSERT INTO wholesales_log (
        wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

CREATE TABLE `Weight_Log` (
  `id` int(11) NOT NULL,
  `weight_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `transaction_status` varchar(100) NOT NULL,
  `weight_type` varchar(100) NOT NULL,
  `customer_type` varchar(100) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `lorry_plate_no1` varchar(100) DEFAULT NULL,
  `lorry_plate_no2` varchar(100) DEFAULT NULL,
  `supplier_weight` varchar(100) DEFAULT NULL,
  `order_weight` varchar(100) DEFAULT NULL,
  `plant_code` varchar(50) DEFAULT NULL,
  `plant_name` varchar(50) DEFAULT NULL,
  `site_code` varchar(50) DEFAULT NULL,
  `site_name` varchar(100) DEFAULT NULL,
  `agent_code` varchar(50) DEFAULT NULL,
  `agent_name` varchar(50) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(50) DEFAULT NULL,
  `supplier_code` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(50) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(50) DEFAULT NULL,
  `product_description` varchar(150) DEFAULT NULL,
  `ex_del` varchar(5) DEFAULT 'EX',
  `raw_mat_code` varchar(50) DEFAULT NULL,
  `raw_mat_name` varchar(100) DEFAULT NULL,
  `container_no` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `seal_no` varchar(50) DEFAULT NULL,
  `container_no2` varchar(50) DEFAULT NULL,
  `seal_no2` varchar(50) DEFAULT NULL,
  `purchase_order` varchar(50) DEFAULT NULL,
  `delivery_no` varchar(50) DEFAULT NULL,
  `transporter_code` varchar(50) DEFAULT NULL,
  `transporter` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_code` varchar(100) DEFAULT NULL,
  `destination_code` varchar(50) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `gross_weight1` varchar(100) DEFAULT NULL,
  `gross_weight1_date` datetime DEFAULT NULL,
  `gross_weight_by1` varchar(50) DEFAULT NULL,
  `tare_weight1` varchar(100) DEFAULT NULL,
  `tare_weight1_date` datetime DEFAULT NULL,
  `tare_weight_by1` varchar(50) DEFAULT NULL,
  `nett_weight1` varchar(100) NOT NULL,
  `lorry_no2_weight` varchar(100) DEFAULT NULL,
  `empty_container2_weight` varchar(100) DEFAULT NULL,
  `replacement_container` varchar(100) DEFAULT NULL,
  `gross_weight2` varchar(100) DEFAULT NULL,
  `gross_weight2_date` datetime DEFAULT NULL,
  `gross_weight_by2` varchar(50) DEFAULT NULL,
  `tare_weight2` varchar(100) DEFAULT NULL,
  `tare_weight2_date` datetime DEFAULT NULL,
  `tare_weight_by2` varchar(50) DEFAULT NULL,
  `nett_weight2` varchar(100) DEFAULT NULL,
  `reduce_weight` varchar(100) NOT NULL DEFAULT '0',
  `final_weight` varchar(150) DEFAULT NULL,
  `weight_different` varchar(100) DEFAULT NULL,
  `weight_different_perc` varchar(50) DEFAULT NULL,
  `is_complete` varchar(100) NOT NULL DEFAULT 'N',
  `is_cancel` varchar(100) NOT NULL DEFAULT 'N',
  `is_approved` varchar(3) NOT NULL DEFAULT 'Y',
  `manual_weight` varchar(100) NOT NULL DEFAULT 'N',
  `indicator_id` varchar(100) DEFAULT NULL,
  `weighbridge_id` varchar(100) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `indicator_id_2` varchar(50) DEFAULT NULL,
  `unit_price` varchar(10) DEFAULT NULL,
  `sub_total` varchar(10) NOT NULL DEFAULT '0.00',
  `sst` varchar(10) NOT NULL DEFAULT '0.00',
  `total_price` varchar(10) NOT NULL DEFAULT '0.00',
  `load_drum` varchar(4) DEFAULT NULL,
  `no_of_drum` int(100) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `approved_by` int(5) DEFAULT NULL,
  `approved_reason` text DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `company` int(10) NOT NULL,
  `records_type` varchar(30) NOT NULL DEFAULT 'weighbridge',
  `action_id` varchar(5) NOT NULL,
  `action_by` varchar(15) NOT NULL,
  `event_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `Weight_Log` ADD PRIMARY KEY (`id`);

ALTER TABLE `Weight_Log` MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_WEIGHT` AFTER INSERT ON `Weight` FOR EACH ROW INSERT INTO Weight_Log (
    weight_id, transaction_id, transaction_status, weight_type, customer_type, transaction_date, lorry_plate_no1, lorry_plate_no2, supplier_weight, order_weight, plant_code, plant_name, site_code, site_name, agent_code, agent_name, customer_code, customer_name, supplier_code, supplier_name, product_code, product_name, product_description, ex_del, raw_mat_code, raw_mat_name, container_no, invoice_no, seal_no, container_no2, seal_no2, purchase_order, delivery_no, transporter_code, transporter, driver_name, driver_code, destination_code, destination, remarks, gross_weight1, gross_weight1_date, gross_weight_by1, tare_weight1, tare_weight1_date, tare_weight_by1, nett_weight1, lorry_no2_weight, empty_container2_weight, replacement_container, gross_weight2, gross_weight2_date, gross_weight_by2, tare_weight2, tare_weight2_date, tare_weight_by2, nett_weight2, reduce_weight, final_weight, weight_different, weight_different_perc, is_complete, is_cancel, is_approved, manual_weight, indicator_id, weighbridge_id, created_date, indicator_id_2, unit_price, sub_total, sst, total_price, load_drum, no_of_drum, status, approved_by, approved_reason, cancelled_reason, company, records_type, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.transaction_id, NEW.transaction_status, NEW.weight_type, NEW.customer_type, NEW.transaction_date, NEW.lorry_plate_no1, NEW.lorry_plate_no2, NEW.supplier_weight, NEW.order_weight, NEW.plant_code, NEW.plant_name, NEW.site_code, NEW.site_name, NEW.agent_code, NEW.agent_name, NEW.customer_code, NEW.customer_name, NEW.supplier_code, NEW.supplier_name, NEW.product_code, NEW.product_name, NEW.product_description, NEW.ex_del, NEW.raw_mat_code, NEW.raw_mat_name, NEW.container_no, NEW.invoice_no, NEW.seal_no, NEW.container_no2, NEW.seal_no2, NEW.purchase_order, NEW.delivery_no, NEW.transporter_code, NEW.transporter, NEW.driver_name, NEW.driver_code, NEW.destination_code, NEW.destination, NEW.remarks, NEW.gross_weight1, NEW.gross_weight1_date, NEW.gross_weight_by1, NEW.tare_weight1, NEW.tare_weight1_date, NEW.tare_weight_by1, NEW.nett_weight1, NEW.lorry_no2_weight, NEW.empty_container2_weight, NEW.replacement_container, NEW.gross_weight2, NEW.gross_weight2_date, NEW.gross_weight_by2, NEW.tare_weight2, NEW.tare_weight2_date, NEW.tare_weight_by2, NEW.nett_weight2, NEW.reduce_weight, NEW.final_weight, NEW.weight_different, NEW.weight_different_perc, NEW.is_complete, NEW.is_cancel, NEW.is_approved, NEW.manual_weight, NEW.indicator_id, NEW.weighbridge_id, NEW.created_date, NEW.indicator_id_2, NEW.unit_price, NEW.sub_total, NEW.sst, NEW.total_price, NEW.load_drum, NEW.no_of_drum, NEW.status, NEW.approved_by, NEW.approved_reason, NEW.cancelled_reason, NEW.company, NEW.records_type, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_WEIGHT` BEFORE UPDATE ON `Weight` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    IF NEW.status = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    INSERT INTO Weight_Log (
        weight_id, transaction_id, transaction_status, weight_type, customer_type, transaction_date, lorry_plate_no1, lorry_plate_no2, supplier_weight, order_weight, plant_code, plant_name, site_code, site_name, agent_code, agent_name, customer_code, customer_name, supplier_code, supplier_name, product_code, product_name, product_description, ex_del, raw_mat_code, raw_mat_name, container_no, invoice_no, seal_no, container_no2, seal_no2, purchase_order, delivery_no, transporter_code, transporter, driver_name, driver_code, destination_code, destination, remarks, gross_weight1, gross_weight1_date, gross_weight_by1, tare_weight1, tare_weight1_date, tare_weight_by1, nett_weight1, lorry_no2_weight, empty_container2_weight, replacement_container, gross_weight2, gross_weight2_date, gross_weight_by2, tare_weight2, tare_weight2_date, tare_weight_by2, nett_weight2, reduce_weight, final_weight, weight_different, weight_different_perc, is_complete, is_cancel, is_approved, manual_weight, indicator_id, weighbridge_id, created_date, indicator_id_2, unit_price, sub_total, sst, total_price, load_drum, no_of_drum, status, approved_by, approved_reason, cancelled_reason, company, records_type, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.transaction_id, NEW.transaction_status, NEW.weight_type, NEW.customer_type, NEW.transaction_date, NEW.lorry_plate_no1, NEW.lorry_plate_no2, NEW.supplier_weight, NEW.order_weight, NEW.plant_code, NEW.plant_name, NEW.site_code, NEW.site_name, NEW.agent_code, NEW.agent_name, NEW.customer_code, NEW.customer_name, NEW.supplier_code, NEW.supplier_name, NEW.product_code, NEW.product_name, NEW.product_description, NEW.ex_del, NEW.raw_mat_code, NEW.raw_mat_name, NEW.container_no, NEW.invoice_no, NEW.seal_no, NEW.container_no2, NEW.seal_no2, NEW.purchase_order, NEW.delivery_no, NEW.transporter_code, NEW.transporter, NEW.driver_name, NEW.driver_code, NEW.destination_code, NEW.destination, NEW.remarks, NEW.gross_weight1, NEW.gross_weight1_date, NEW.gross_weight_by1, NEW.tare_weight1, NEW.tare_weight1_date, NEW.tare_weight_by1, NEW.nett_weight1, NEW.lorry_no2_weight, NEW.empty_container2_weight, NEW.replacement_container, NEW.gross_weight2, NEW.gross_weight2_date, NEW.gross_weight_by2, NEW.tare_weight2, NEW.tare_weight2_date, NEW.tare_weight_by2, NEW.nett_weight2, NEW.reduce_weight, NEW.final_weight, NEW.weight_different, NEW.weight_different_perc, NEW.is_complete, NEW.is_cancel, NEW.is_approved, NEW.manual_weight, NEW.indicator_id, NEW.weighbridge_id, NEW.created_date, NEW.indicator_id_2, NEW.unit_price, NEW.sub_total, NEW.sst, NEW.total_price, NEW.load_drum, NEW.no_of_drum, NEW.status, NEW.approved_by, NEW.approved_reason, NEW.cancelled_reason, NEW.company, NEW.records_type, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

-- 05/02/2026 --
ALTER TABLE `vehicles` ADD `vehicle_weight` VARCHAR(50) NULL AFTER `veh_number`;

-- 07/02/2026 --
ALTER TABLE `users` ADD `allow_edit` VARCHAR(1) NOT NULL DEFAULT 'Y' AFTER `stopbits`, ADD `allow_delete` VARCHAR(1) NOT NULL DEFAULT 'Y' AFTER `allow_edit`;

ALTER TABLE `users` ADD `languages` VARCHAR(5) NOT NULL DEFAULT 'en' AFTER `allow_delete`;

ALTER TABLE `message_resource` ADD `ja` TEXT NULL AFTER `ne`, ADD `company` INT(11) NOT NULL AFTER `ja`;

-- 27/02/2026 --
ALTER TABLE `grades` CHANGE `units` `units` VARCHAR(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
