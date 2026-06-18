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

-- 05/04/2026 --
ALTER TABLE `companies` ADD `company_logo` INT(11) NULL AFTER `sst`;

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `filename` text NOT NULL,
  `filepath` text NOT NULL,
  `method` varchar(50) NOT NULL,
  `company` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `files` ADD PRIMARY KEY (`id`);

ALTER TABLE `files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `roles` (`id`, `role_code`, `role_name`, `deleted`) VALUES ('4', 'SADMIN', 'Super Admin', '0')

UPDATE `users` SET `role_code` = 'SADMIN' WHERE `users`.`id` = 2;

UPDATE `users` SET `role_code` = 'SADMIN' WHERE `users`.`id` = 40;

ALTER TABLE `companies` ADD `include_price` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `company_logo`, ADD `include_photo` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `include_price`, ADD `include_barcode` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `include_photo`;

ALTER TABLE `companies` ADD `photo_upload_mode` VARCHAR(50) NOT NULL DEFAULT 'local' AFTER `include_barcode`;

-- 08/04/2026 --
INSERT INTO `companies` (`id`, `reg_no`, `name`, `address`, `address2`, `address3`, `address4`, `phone`, `fax`, `email`, `products`, `packages`, `parent`, `sst`, `company_logo`, `include_price`, `include_photo`, `include_barcode`, `photo_upload_mode`, `deleted`) VALUES
(0, 'test', 'Synctronix WMS', 'test', NULL, NULL, NULL, '018-7894562', NULL, 'test@test.com', '[\"wholesale\",\"fruits\",\"industrial\",\"second_remarks\"]', '[\"M\",\"P\"]', NULL, 'N', NULL, 'Y', 'Y', 'Y', 'local', '0');

UPDATE `companies` SET `id` = 0 WHERE `name` = 'Synctronix WMS';

ALTER TABLE `companies` ADD `include_sec_remark` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `include_barcode`;

-- 10/04/2026 --
ALTER TABLE `product_grades` ADD `pricing_type` VARCHAR(10) NULL AFTER `grade_id`, ADD `price` VARCHAR(100) NULL AFTER `pricing_type`;

ALTER TABLE `customers` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

ALTER TABLE `supplies` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

ALTER TABLE `products` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

ALTER TABLE `drivers` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

ALTER TABLE `vehicles` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

ALTER TABLE `grades` ADD `is_manual` VARCHAR(1) NOT NULL DEFAULT 'N' AFTER `customer`;

CREATE TABLE `food_packaging` (
  `id` int(10) NOT NULL,
  `serial_no` varchar(30) NOT NULL,
  `po_no` varchar(50) DEFAULT NULL,
  `security_bills` varchar(15) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'SALES',
  `customer` varchar(10) DEFAULT NULL,
  `supplier` varchar(10) DEFAULT NULL,
  `product` varchar(50) DEFAULT NULL,
  `package` text DEFAULT NULL,
  `vehicle_no` varchar(15) NOT NULL,
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
  `remarks2` text DEFAULT NULL,
  `created_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `modified_at` datetime DEFAULT current_timestamp(),
  `modified_by` varchar(10) DEFAULT NULL,
  `checked_by` varchar(30) DEFAULT NULL,
  `company` int(5) DEFAULT NULL,
  `weighted_by` varchar(30) DEFAULT NULL,
  `indicator` varchar(30) DEFAULT NULL,
  `deleted` int(3) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `records_type` varchar(15) NOT NULL DEFAULT 'wholesales'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `food_packaging_log` (
  `id` int(10) NOT NULL,
  `food_packaging_id` int(10) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

DELIMITER $$
CREATE TRIGGER `TRG_INS_FOOD_PACKAGING` AFTER INSERT ON `food_packaging` FOR EACH ROW INSERT INTO food_packaging_log (
    food_packaging_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;
DELIMITER $$

CREATE TRIGGER `TRG_UPD_FOOD_PACKAGING` BEFORE UPDATE ON `food_packaging` FOR EACH ROW BEGIN
    DECLARE action_value INT;
    DECLARE action_by_value VARCHAR(255);

    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    -- If modified_by is NULL, use 'SYSTEM'
    SET action_by_value = IFNULL(NEW.modified_by, 'SYSTEM');

    INSERT INTO food_packaging_log (
        food_packaging_id,
        serial_no,
        po_no,
        security_bills,
        status,
        customer,
        supplier,
        product,
        package,
        vehicle_no,
        driver,
        driver_ic,
        other_customer,
        other_supplier,
        units,
        weight_details,
        reject_details,
        total_item,
        total_weight,
        total_reject,
        total_price,
        remark,
        created_datetime,
        created_by,
        end_time,
        checked_by,
        company,
        weighted_by,
        indicator,
        deleted,
        delete_reason,
        action_id,
        action_by,
        event_date
    ) 
    VALUES (
        NEW.id,
        NEW.serial_no,
        NEW.po_no,
        NEW.security_bills,
        NEW.status,
        NEW.customer,
        NEW.supplier,
        NEW.product,
        NEW.package,
        NEW.vehicle_no,
        NEW.driver,
        NEW.driver_ic,
        NEW.other_customer,
        NEW.other_supplier,
        NEW.units,
        NEW.weight_details,
        NEW.reject_details,
        NEW.total_item,
        NEW.total_weight,
        NEW.total_reject,
        NEW.total_price,
        NEW.remark,
        NEW.created_datetime,
        NEW.created_by,
        NEW.end_time,
        NEW.checked_by,
        NEW.company,
        NEW.weighted_by,
        NEW.indicator,
        NEW.deleted,
        NEW.delete_reason,
        action_value,
        action_by_value,
        NOW()
    );
END
$$
DELIMITER ;

ALTER TABLE `food_packaging`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `food_packaging`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `food_packaging_log`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `food_packaging_log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `companies` ADD `pricing_mode` VARCHAR(15) NOT NULL DEFAULT 'Standard' AFTER `include_sec_remark`;

ALTER TABLE `companies` ADD `wholesale_mode` VARCHAR(15) NOT NULL DEFAULT 'Standard' AFTER `pricing_mode`;

-- 26/04/2026 (SKY)--
ALTER TABLE `customers` ADD `fax` VARCHAR(100) NULL AFTER `pic`;

ALTER TABLE `customers` ADD `billing_name` VARCHAR(100) NULL AFTER `fax`, ADD `billing_address` TEXT NULL AFTER `billing_name`, ADD `billing_address2` TEXT NULL AFTER `billing_address`, ADD `billing_address3` TEXT NULL AFTER `billing_address2`, ADD `billing_address4` TEXT NULL AFTER `billing_address3`, ADD `billing_state` INT(5) NULL AFTER `billing_address4`, ADD `billing_pic` VARCHAR(50) NULL AFTER `billing_state`, ADD `billing_phone` VARCHAR(50) NULL AFTER `billing_pic`, ADD `billing_fax` VARCHAR(50) NULL AFTER `billing_phone`;

ALTER TABLE `customers` CHANGE `reg_no` `reg_no` TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

ALTER TABLE `supplies` ADD `fax` VARCHAR(100) NULL AFTER `pic`;

ALTER TABLE `supplies` ADD `billing_name` VARCHAR(100) NULL AFTER `fax`, ADD `billing_address` TEXT NULL AFTER `billing_name`, ADD `billing_address2` TEXT NULL AFTER `billing_address`, ADD `billing_address3` TEXT NULL AFTER `billing_address2`, ADD `billing_address4` TEXT NULL AFTER `billing_address3`, ADD `billing_state` INT(5) NULL AFTER `billing_address4`, ADD `billing_pic` VARCHAR(50) NULL AFTER `billing_state`, ADD `billing_phone` VARCHAR(50) NULL AFTER `billing_pic`, ADD `billing_fax` VARCHAR(50) NULL AFTER `billing_phone`;

ALTER TABLE `supplies` CHANGE `reg_no` `reg_no` TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL;

-- 02/05/2026 --
ALTER TABLE `companies` ADD `pulp_and_paste_mode` VARCHAR(15) NOT NULL DEFAULT 'Portrait' AFTER `wholesale_mode`;

ALTER TABLE `companies` ADD `waste_mode` VARCHAR(15) NOT NULL DEFAULT 'Portrait' AFTER `pulp_and_paste_mode`;

ALTER TABLE `users` ADD `allow_add` VARCHAR(1) NOT NULL DEFAULT 'Y' AFTER `stopbits`;

ALTER TABLE `products` ADD `range_set` INT(1) NOT NULL DEFAULT '0' AFTER `is_manual`, ADD `ok_weight` VARCHAR(100) NULL AFTER `range_set`, ADD `ok_weight_unit` INT(11) NULL AFTER `ok_weight`, ADD `lo_weight` VARCHAR(100) NULL AFTER `ok_weight_unit`, ADD `lo_weight_unit` INT(11) NULL AFTER `lo_weight`, ADD `hi_weight` VARCHAR(100) NULL AFTER `lo_weight_unit`, ADD `hi_weight_unit` INT(11) NULL AFTER `hi_weight`;

-- 07/05/2026 --
CREATE TABLE `running_no_setup` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `transaction_status` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 08/05/2026 --
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `customer` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `categories` ADD PRIMARY KEY (`id`);
ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `packaging` (
  `id` int(11) NOT NULL,
  `packaging_name` varchar(100) NOT NULL,
  `customer` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `packaging` ADD PRIMARY KEY (`id`);
ALTER TABLE `packaging` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `packaging` (`id`, `packaging_name`, `customer`, `deleted`) VALUES
(1, 'Box', 10, 0),
(2, 'KG', 10, 0);

ALTER TABLE `products` ADD `packaging` INT(11) NULL AFTER `hi_weight_unit`, ADD `category` INT(11) NULL AFTER `packaging`;

ALTER TABLE `products` ADD `product_image` TEXT NULL AFTER `category`;

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(100) DEFAULT NULL,
  `subtotal` varchar(100) DEFAULT NULL,
  `tax` varchar(50) DEFAULT NULL,
  `tax_amount` varchar(100) DEFAULT NULL,
  `discount` varchar(100) DEFAULT NULL,
  `total_price` varchar(100) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT 0,
  `company` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_datetime` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sales` ADD PRIMARY KEY (`id`);
  
ALTER TABLE `sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `sales_cart` (
  `id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `price` varchar(100) NOT NULL,
  `total_price` VARCHAR(100) NOT NULL,
  `status` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sales_cart` ADD PRIMARY KEY (`id`);

ALTER TABLE `sales_cart` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `purchase_no` varchar(100) NOT NULL,
  `total_price` varchar(50) NOT NULL,
  `status` int(1) NOT NULL DEFAULT 0,
  `company` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_datetime` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `purchases` ADD PRIMARY KEY (`id`);
  
ALTER TABLE `purchases` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `purchases_cart` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `price` varchar(100) NOT NULL,
  `total_price` varchar(100) NOT NULL,
  `status` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `purchases_cart` ADD PRIMARY KEY (`id`);

ALTER TABLE `purchases_cart` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` varchar(100) NOT NULL,
  `status` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `inventory` ADD PRIMARY KEY (`id`);

ALTER TABLE `inventory` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `companies` ADD `packing_mode` VARCHAR(20) NOT NULL DEFAULT 'Food_Packaging' AFTER `waste_mode`;

-- 09/05/2026 --
ALTER TABLE `packaging` ADD `packaging_type` VARCHAR(30) NOT NULL DEFAULT 'original' AFTER `packaging_name`;

CREATE TABLE `statuses` (
  `id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `prefix` VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `statuses` ADD PRIMARY KEY (`id`);

ALTER TABLE `statuses` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `running_no_setup` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT, add PRIMARY KEY (`id`);

-- 10/05/2026 --
ALTER TABLE `sales` ADD `payments` TEXT NULL AFTER `payment_method`;

ALTER TABLE `companies` ADD `indicator` INT(11) NULL AFTER `photo_upload_mode`;

ALTER TABLE `sales` ADD `total_paid_amount` VARCHAR(100) NULL AFTER `payments`, ADD `change_amount` VARCHAR(100) NULL AFTER `total_payment_amount`;

ALTER TABLE `packaging` ADD `is_by_weight` VARCHAR(3) NOT NULL DEFAULT 'N' AFTER `packaging_type`;

-- 18/05/2026 --
ALTER TABLE `categories` ADD `module` VARCHAR(50) NOT NULL DEFAULT 'wholesale' AFTER `category_name`;

-- 22/05/2026 --
ALTER TABLE `products` ADD `state` TEXT NULL AFTER `product_image`;

CREATE TABLE `daily_sales_setup` (
  `id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `state` text NOT NULL,
  `company` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_datetime` datetime DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_datetime` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `daily_sales_setup` ADD PRIMARY KEY (`id`);
  
ALTER TABLE `daily_sales_setup` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `daily_sales_setup_log` (
  `id` int(11) NOT NULL,
  `daily_sales_setup_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `state` text NOT NULL,
  `company` int(11) NOT NULL,
  `action_id` varchar(5) NOT NULL,
  `action_by` varchar(15) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `daily_sales_setup_log` ADD PRIMARY KEY (`id`);
  
ALTER TABLE `daily_sales_setup_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_DSS` AFTER INSERT ON `daily_sales_setup` FOR EACH ROW 
  INSERT INTO daily_sales_setup_log (
    daily_sales_setup_id, module, state, company, action_id, action_by, event_date
  ) 
  VALUES (
    NEW.id, NEW.module, NEW.state, NEW.company, 1, NEW.created_by, NOW()
  )
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_DSS` BEFORE UPDATE ON `daily_sales_setup` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    INSERT INTO daily_sales_setup_log (
      daily_sales_setup_id, module, state, company, action_id, action_by, event_date
    ) 
    VALUES (
      NEW.id, NEW.module, NEW.state, NEW.company, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

ALTER TABLE `companies` ADD `enable_daily_sales_setup` VARCHAR(1) NULL DEFAULT 'N' AFTER `photo_upload_mode`, ADD `daily_sales_modules` TEXT NULL AFTER `enable_daily_sales_setup`;

-- 28/05/2026 --
ALTER TABLE `companies` CHANGE `email` `email` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

-- 04/06/2026 --
ALTER TABLE `products` ADD `purchasing_price` TEXT NULL AFTER `price`;
ALTER TABLE `product_customers` ADD `purchasing_price` TEXT NULL AFTER `price`;
ALTER TABLE `product_grades` ADD `purchasing_price` TEXT NULL AFTER `price`;

-- 05/06/2026 --
ALTER TABLE `products` ADD `purchasing_pricing_type` VARCHAR(10) NULL AFTER `price`;
ALTER TABLE `product_customers` ADD `purchasing_pricing_type` VARCHAR(10) NULL AFTER `price`;
ALTER TABLE `product_grades` ADD `purchasing_pricing_type` VARCHAR(10) NULL AFTER `price`;

-- 05/06/2026 (part 2) --
CREATE TABLE `shipment_types` (
  `id` int(11) NOT NULL,
  `shipment_type` varchar(100) NOT NULL,
  `customers` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_datetime` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `shipment_types` ADD PRIMARY KEY (`id`);

ALTER TABLE `shipment_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `shipment_types_log` (
  `id` int(11) NOT NULL,
  `shipment_type_id` int(11) NOT NULL,
  `shipment_type` varchar(100) NOT NULL,
  `customers` int(11) NOT NULL,
  `action_id` int(1) NOT NULL,
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `shipment_types_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `shipment_types_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_SHIPMENT_TYPES` AFTER INSERT ON `shipment_types` FOR EACH ROW INSERT INTO shipment_types_log (
  shipment_type_id, shipment_type, customers, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.shipment_type, NEW.customers, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_SHIPMENT_TYPES` BEFORE UPDATE ON `shipment_types` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO shipment_types_log (
      shipment_type_id, shipment_type, customers, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.shipment_type, NEW.customers, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

ALTER TABLE `locations` ADD `created_by` INT(11) NOT NULL AFTER `customer`, ADD `created_datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`, ADD `modified_by` INT(11) NULL AFTER `created_datetime`, ADD `modified_date` DATETIME on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `modified_by`;

CREATE TABLE `locations_log` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `location` varchar(50) NOT NULL,
  `customers` int(11) NOT NULL,
  `action_id` int(1) NOT NULL,
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

ALTER TABLE `locations_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `locations_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_LOCATIONS` AFTER INSERT ON `locations` FOR EACH ROW INSERT INTO locations_log (
  location_id, location, customers, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.locations, NEW.customer, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_LOCATIONS` BEFORE UPDATE ON `locations` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO locations_log (
      location_id, location, customers, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.locations, NEW.customer, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

CREATE TABLE `production_lines` (
  `id` int(11) NOT NULL,
  `production_line` varchar(100) NOT NULL,
  `customers` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_datetime` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `production_lines` ADD PRIMARY KEY (`id`);

ALTER TABLE `production_lines` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `production_lines_log` (
  `id` int(11) NOT NULL,
  `production_line_id` int(11) NOT NULL,
  `production_line` varchar(100) NOT NULL,
  `customers` int(11) NOT NULL,
  `action_id` int(1) NOT NULL,
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `production_lines_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `production_lines_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PRODUCTION_LINES` AFTER INSERT ON `production_lines` FOR EACH ROW INSERT INTO production_lines_log (
  production_line_id, production_line, customers, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.production_line, NEW.customers, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PRODUCTION_LINES` BEFORE UPDATE ON `production_lines` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO production_lines_log (
      production_line_id, production_line, customers, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.production_line, NEW.customers, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

-- 06/06/2026 --
CREATE TABLE `grading` (
  `id` int(11) NOT NULL,
  `grading_no` varchar(100) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `product_category` int(11) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `customers` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `grading` ADD PRIMARY KEY (`id`);
ALTER TABLE `grading` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `grading_log` (
  `id` int(11) NOT NULL,
  `grading_id` int(11) NOT NULL,
  `grading_no` varchar(100) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `product_category` int(11) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `customers` int(11) NOT NULL,
  `action_id` int(1) NOT NULL,
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `grading_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `grading_log` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_GRADING` AFTER INSERT ON `grading` FOR EACH ROW INSERT INTO grading_log (
  grading_id, grading_no, start_date, end_date, product_category, remark, customers, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.grading_no, NEW.start_date, NEW.end_date, NEW.product_category, NEW.remark, NEW.customers, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_GRADING` BEFORE UPDATE ON `grading` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO grading_log (
      grading_id, grading_no, start_date, end_date, product_category, remark, customers, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.grading_no, NEW.start_date, NEW.end_date, NEW.product_category, NEW.remark, NEW.customers, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

CREATE TABLE `grading_items` (
  `id` int(11) NOT NULL,
  `grading_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `wholesales_id` int(11) DEFAULT NULL,
  `from_grade` varchar(50) DEFAULT NULL,
  `to_grade` varchar(50) DEFAULT NULL,
  `gross_weight` varchar(100) DEFAULT NULL,
  `tare_weight` varchar(100) DEFAULT NULL,
  `nett_weight` varchar(100) DEFAULT NULL,
  `weighing_time` datetime NOT NULL,
  `photo_path` text DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `grading_items` ADD PRIMARY KEY (`id`);

ALTER TABLE `grading_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- 06/06/2026 (part02) --
ALTER TABLE `shipment_types` CHANGE `customers` `customer` INT(11) NOT NULL;

ALTER TABLE `shipment_types_log` CHANGE `customers` `customer` INT(11) NOT NULL;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_SHIPMENT_TYPES` AFTER INSERT ON `shipment_types` FOR EACH ROW INSERT INTO shipment_types_log (
  shipment_type_id, shipment_type, customer, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.shipment_type, NEW.customer, 1, NEW.created_by, NEW.created_datetime
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_SHIPMENT_TYPES` BEFORE UPDATE ON `shipment_types` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO shipment_types_log (
      shipment_type_id, shipment_type, customer, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.shipment_type, NEW.customer, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

-- 06/06/2026 (part03) --
CREATE TABLE `raw_stock_balance` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `balance` varchar(100) NOT NULL,
  `company` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modifed_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `raw_stock_balance` ADD PRIMARY KEY (`id`);

ALTER TABLE `raw_stock_balance` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `grading` ADD `delete_reason` TEXT NULL AFTER `deleted`;
ALTER TABLE `grading_log` CHANGE `customers` `company` INT(11) NOT NULL;
ALTER TABLE `grading_log` ADD `deleted` int(1) NOT NULL DEFAULT 0 AFTER `company`;
ALTER TABLE `grading_log` ADD `delete_reason` TEXT NULL AFTER `deleted`;

-- ALTER TABLE `grading` ADD `location` INT(11) NOT NULL AFTER `grading_no`, ADD `indicator` VARCHAR(50) NOT NULL DEFAULT "X722" AFTER `location`;
ALTER TABLE `grading_log` ADD `location` INT(11) NOT NULL AFTER `grading_no`, ADD `indicator` VARCHAR(50) NOT NULL DEFAULT "X722" AFTER `location`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_GRADING` AFTER INSERT ON `grading` FOR EACH ROW INSERT INTO grading_log (
  grading_id, grading_no, location, indicator, start_date, end_date, product_category, remark, company, deleted, delete_reason, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.grading_no, NEW.location, NEW.indicator, NEW.start_date, NEW.end_date, NEW.product_category, NEW.remark, NEW.company, NEW.deleted, NEW.delete_reason, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_GRADING` BEFORE UPDATE ON `grading` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO grading_log (
      grading_id, grading_no, location, indicator, start_date, end_date, product_category, remark, company, deleted, delete_reason, action_id, action_by, event_date
  ) 
  VALUES (
      NEW.id, NEW.grading_no, NEW.location, NEW.indicator, NEW.start_date, NEW.end_date, NEW.product_category, NEW.remark, NEW.company, NEW.deleted, NEW.delete_reason, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

CREATE TABLE `packaging_batches` (
  `id` int(11) NOT NULL,
  `batch_no` varchar(50) NOT NULL,
  `packaging_date` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `location` int(11) DEFAULT NULL,
  `production_line` int(11) DEFAULT NULL,
  `company` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `packaging_batches` ADD PRIMARY KEY (`id`);

ALTER TABLE `packaging_batches` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `packaging_batch_items` (
  `id` int(11) NOT NULL,
  `packaging_batch_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `packaging_size` varchar(100) NOT NULL,
  `units_per_box` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `packing_time` datetime NOT NULL,
  `photo_path` text DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `packaging_batch_items` ADD PRIMARY KEY (`id`);

ALTER TABLE `packaging_batch_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `packaging_batch_logs` (
  `id` int(11) NOT NULL,
  `packaging_batch_id` int(11) NOT NULL,
  `batch_no` varchar(50) NOT NULL,
  `packaging_date` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `location` int(11) DEFAULT NULL,
  `production_line` int(11) DEFAULT NULL,
  `company` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` TEXT NULL,
  `action_id` int(1) NOT NULL,
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `packaging_batch_logs` ADD PRIMARY KEY (`id`);

ALTER TABLE `packaging_batch_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PACKAGING_BATCH` AFTER INSERT ON `packaging_batches` FOR EACH ROW INSERT INTO packaging_batch_logs (
  packaging_batch_id, batch_no, packaging_date, remarks, location, production_line, company, deleted, delete_reason, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.batch_no, NEW.packaging_date, NEW.remarks, NEW.location, NEW.production_line, NEW.company, NEW.deleted, NEW.delete_reason, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PACKAGING_BATCH` BEFORE UPDATE ON `packaging_batches` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO packaging_batch_logs (
    packaging_batch_id, batch_no, packaging_date, remarks, location, production_line, company, deleted, delete_reason, action_id, action_by, event_date
  ) 
  VALUES (
    NEW.id, NEW.batch_no, NEW.packaging_date, NEW.remarks, NEW.location, NEW.production_line, NEW.company, NEW.deleted, NEW.delete_reason, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

-- 07/06/2026 --
ALTER TABLE `packaging` ADD `weight` VARCHAR(100) NULL AFTER `packaging_type`;

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `movement_no` varchar(20) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `company` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `source_id` int(11) NOT NULL,
  `movement_type` varchar(10) NOT NULL COMMENT 'ADD, MINUS, REVERSAL',
  `status` varchar(20) NOT NULL COMMENT 'RECEIVING, DISPATCH, INCOMING, OUTGOING, etc.',
  `quantity` varchar(100) NOT NULL,
  `balance_before` varchar(100) NOT NULL,
  `balance_after` varchar(100) NOT NULL,
  `customer` int(11) DEFAULT NULL,
  `supplier` int(11) DEFAULT NULL,
  `original_movement_id` int(11) DEFAULT NULL COMMENT 'Points to the reversed movement row',
  `edit_ref` varchar(20) DEFAULT NULL COMMENT 'Copied from movement_no of the original row on reversal+new entry pair',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `stock_movements` ADD PRIMARY KEY (`id`);
ALTER TABLE `stock_movements` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Loading Module --
CREATE TABLE `stock_balances` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `packaging_size` int(11) NOT NULL COMMENT 'FK to packaging.id',
  `box_quantity` int(11) NOT NULL DEFAULT 0,
  `company` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `stock_balances` ADD PRIMARY KEY (`id`);
ALTER TABLE `stock_balances` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `loading_orders` (
  `id` int(11) NOT NULL,
  `loading_no` varchar(50) NOT NULL,
  `loading_date` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `shipment_type` int(11) NOT NULL,
  `company` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `status` VARCHAR(10) NOT NULL DEFAULT 'pending' 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `loading_orders` ADD PRIMARY KEY (`id`);
ALTER TABLE `loading_orders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `loading_order_items` (
  `id` int(11) NOT NULL,
  `loading_order_id` int(11) NOT NULL,
  `packaging_batch_item_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `packaging_size` varchar(100) NOT NULL,
  `units_per_box` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `loading_time` datetime NOT NULL,
  `photo_path` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `loading_order_items` ADD PRIMARY KEY (`id`);
ALTER TABLE `loading_order_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `loading_order_logs` (
  `id` int(11) NOT NULL,
  `loading_order_id` int(11) NOT NULL,
  `loading_no` varchar(50) NOT NULL,
  `loading_date` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `shipment_type` int(11) NOT NULL,
  `company` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `status` VARCHAR(10) NOT NULL DEFAULT 'pending',
  `action_id` int(1) NOT NULL COMMENT '1=INSERT, 2=UPDATE, 3=DELETE',
  `action_by` int(11) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `loading_order_logs` ADD PRIMARY KEY (`id`);
ALTER TABLE `loading_order_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_LOADING_ORDER` AFTER INSERT ON `loading_orders` FOR EACH ROW INSERT INTO loading_order_logs (
  loading_order_id, loading_no, loading_date, remarks, shipment_type, company, deleted, delete_reason, status, action_id, action_by, event_date
)
VALUES (
  NEW.id, NEW.loading_no, NEW.loading_date, NEW.remarks, NEW.shipment_type, NEW.company, NEW.deleted, NEW.delete_reason, NEW.status, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_LOADING_ORDER` BEFORE UPDATE ON `loading_orders` FOR EACH ROW BEGIN
  DECLARE action_value INT;
  IF NEW.deleted = 1 THEN
    SET action_value = 3;
  ELSE
    SET action_value = 2;
  END IF;
  INSERT INTO loading_order_logs (
    loading_order_id, loading_no, loading_date, remarks, shipment_type, company, deleted, delete_reason, status, action_id, action_by, event_date
  )
  VALUES (
    NEW.id, NEW.loading_no, NEW.loading_date, NEW.remarks, NEW.shipment_type, NEW.company, NEW.deleted, NEW.delete_reason, NEW.status, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

ALTER TABLE `packaging_batches` ADD `status` VARCHAR(10) NOT NULL DEFAULT 'pending' AFTER `delete_reason`;
ALTER TABLE `packaging_batch_logs` ADD `status` VARCHAR(10) NOT NULL DEFAULT 'pending' AFTER `delete_reason`;
ALTER TABLE `packaging_batch_items` ADD `status` VARCHAR(10) NOT NULL DEFAULT 'pending' AFTER `deleted`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PACKAGING_BATCH` AFTER INSERT ON `packaging_batches` FOR EACH ROW INSERT INTO packaging_batch_logs (
  packaging_batch_id, batch_no, packaging_date, remarks, location, production_line, company, deleted, delete_reason, status, action_id, action_by, event_date
) 
VALUES (
  NEW.id, NEW.batch_no, NEW.packaging_date, NEW.remarks, NEW.location, NEW.production_line, NEW.company, NEW.deleted, NEW.delete_reason, NEW.status, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PACKAGING_BATCH` BEFORE UPDATE ON `packaging_batches` FOR EACH ROW BEGIN
  DECLARE action_value INT;

  IF NEW.deleted = 1 THEN
      SET action_value = 3;
  ELSE
      SET action_value = 2;
  END IF;

  INSERT INTO packaging_batch_logs (
    packaging_batch_id, batch_no, packaging_date, remarks, location, production_line, company, deleted, delete_reason, status, action_id, action_by, event_date
  ) 
  VALUES (
    NEW.id, NEW.batch_no, NEW.packaging_date, NEW.remarks, NEW.location, NEW.production_line, NEW.company, NEW.deleted, NEW.delete_reason, NEW.status, action_value, NEW.modified_by, NOW()
  );
END
$$
DELIMITER ;

-- 09/06/2026 --
CREATE TABLE `grading_stock_balance` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `balance` varchar(100) NOT NULL,
  `company` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modifed_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `grading_stock_balance` ADD PRIMARY KEY (`id`);

ALTER TABLE `grading_stock_balance` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `stock_transfers` (
  `id` int(11) NOT NULL,
  `transfer_no` varchar(50) NOT NULL,
  `from_batch_id` int(11) NOT NULL,
  `to_batch_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `company` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `modified_date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `stock_transfers` ADD PRIMARY KEY (`id`);
ALTER TABLE `stock_transfers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `stock_transfer_items` (
  `id` int(11) NOT NULL,
  `stock_transfer_id` int(11) NOT NULL,
  `packaging_batch_item_id` int(11) NOT NULL,
  `from_batch_id` int(11) NOT NULL,
  `to_batch_id` int(11) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `stock_transfer_items` ADD PRIMARY KEY (`id`);
ALTER TABLE `stock_transfer_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- 12/06/2026 --
ALTER TABLE `customers` ADD `pending_bins` INT(11) NOT NULL DEFAULT 0;

CREATE TABLE `customer_bin_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT(11) NOT NULL,
  `type` VARCHAR(3) NOT NULL,
  `qty` INT(11) NOT NULL,
  `remark` VARCHAR(255) NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `wholesales` ADD `pv_id` int(11) DEFAULT NULL AFTER `records_type`;
ALTER TABLE `wholesales_log` ADD `records_type` VARCHAR(15) NOT NULL DEFAULT 'wholesales' AFTER `delete_reason`;
ALTER TABLE `wholesales_log` ADD `pv_id` int(11) DEFAULT NULL AFTER `records_type`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_WHOLESALES` AFTER INSERT ON `wholesales` FOR EACH ROW INSERT INTO wholesales_log (
    wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, 1, NEW.created_by, NEW.created_datetime
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
        wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

CREATE TABLE `payment_vouchers` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `voucher_no` varchar(50) NOT NULL,
  `voucher_date` date NOT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `unit_price` varchar(50) DEFAULT NULL,
  `tax` varchar(50) DEFAULT NULL,
  `total_nett_weight` varchar(50) DEFAULT NULL,
  `total_amount` varchar(50) DEFAULT NULL,
  `deduction_amount` varchar(50) DEFAULT NULL,
  `addition_amount` varchar(50) DEFAULT NULL,
  `final_amount` varchar(50) DEFAULT NULL,
  `deduction_details` text DEFAULT NULL,
  `addition_details` text DEFAULT NULL,
  `company` int(11) NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) NOT NULL,
  `modified_date` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` varchar(30) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `payment_vouchers` ADD PRIMARY KEY (`id`);

ALTER TABLE `payment_vouchers`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `payment_vouchers_log` (
  `id` int(11) NOT NULL,
  `payment_voucher_id` int(11) NOT NULL,
  `supplier_id` varchar(100) NOT NULL,
  `voucher_no` varchar(50) NOT NULL,
  `voucher_date` datetime NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `unit_price` varchar(100) NOT NULL DEFAULT '0',
  `tax` varchar(3) NOT NULL DEFAULT '0',
  `total_nett_weight` varchar(100) DEFAULT NULL,
  `total_amount` varchar(100) DEFAULT NULL,
  `deduction_amount` varchar(100) DEFAULT NULL,
  `addition_amount` varchar(100) DEFAULT NULL,
  `final_amount` varchar(100) DEFAULT NULL,
  `deduction_details` text DEFAULT NULL,
  `addition_details` text DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0,
  `delete_reason` text DEFAULT NULL,
  `company` int(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  `action_by` varchar(50) NOT NULL,
  `event_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `payment_vouchers_log` ADD PRIMARY KEY (`id`);

ALTER TABLE `payment_vouchers_log`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PAY` AFTER INSERT ON `payment_vouchers` FOR EACH ROW INSERT INTO payment_vouchers_log (
    payment_voucher_id, supplier_id, voucher_no,  voucher_date, invoice_no, unit_price, tax, total_nett_weight, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.supplier_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;
DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PAY` BEFORE UPDATE ON `payment_vouchers` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    -- Check if deleted = 1, set action_id to 3, otherwise set to 2
    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    -- Insert into payment_vouchers_log table
    INSERT INTO payment_vouchers_log (
        payment_voucher_id, supplier_id, voucher_no, voucher_date, invoice_no, unit_price, tax, total_nett_weight, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.supplier_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, action_value, NEW.modified_by, NEW.modified_date
    );
END
$$
DELIMITER ;

-- 13/06/2026 --
ALTER TABLE product_customers ADD COLUMN grade_id int(11) NULL AFTER customer_id;

ALTER TABLE `users` ADD `location` INT(11) NULL AFTER `allow_delete`;

ALTER TABLE `wholesales` ADD `location` INT(11) NULL AFTER `pv_id`;

ALTER TABLE `wholesales_log` ADD `location` INT(11) NULL AFTER `pv_id`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_WHOLESALES` AFTER INSERT ON `wholesales` FOR EACH ROW INSERT INTO wholesales_log (
    wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, location, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, NEW.location, 1, NEW.created_by, NEW.created_datetime
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
        wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, location, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, NEW.location, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

-- 15/06/2026 --
ALTER TABLE `wholesales` ADD `start_time` DATETIME NOT NULL AFTER `created_by`;

UPDATE `wholesales` SET start_time = created_datetime;

ALTER TABLE `wholesales_log` ADD `start_time` DATETIME NOT NULL AFTER `created_by`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_WHOLESALES` AFTER INSERT ON `wholesales` FOR EACH ROW INSERT INTO wholesales_log (
    wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, start_time, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, location, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.start_time, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, NEW.location, 1, NEW.created_by, NEW.created_datetime
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
        wholesale_id, serial_no, po_no, security_bills, status, customer, supplier, product, package, vehicle_no, driver, driver_ic, other_customer, other_supplier, units, weight_details, reject_details, total_item, total_weight, total_reject, total_price, remark, created_datetime, created_by, start_time, end_time, checked_by, company, weighted_by, indicator, deleted, delete_reason, records_type, pv_id, location, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.serial_no, NEW.po_no, NEW.security_bills, NEW.status, NEW.customer, NEW.supplier, NEW.product, NEW.package, NEW.vehicle_no, NEW.driver, NEW.driver_ic, NEW.other_customer, NEW.other_supplier, NEW.units, NEW.weight_details, NEW.reject_details, NEW.total_item, NEW.total_weight, NEW.total_reject, NEW.total_price, NEW.remark, NEW.created_datetime, NEW.created_by, NEW.start_time, NEW.end_time, NEW.checked_by, NEW.company, NEW.weighted_by, NEW.indicator, NEW.deleted, NEW.delete_reason, NEW.records_type, NEW.pv_id, NEW.location, action_value, NEW.modified_by, NOW()
    );
END
$$
DELIMITER ;

CREATE TABLE `product_suppliers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `grade_id` int(11) DEFAULT NULL,
  `purchasing_pricing_type` varchar(10) DEFAULT NULL,
  `purchasing_price` text DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `product_suppliers` ADD PRIMARY KEY (`id`);

ALTER TABLE `product_suppliers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_vouchers` CHANGE `supplier_id` `entity_id` INT(11) NOT NULL;

ALTER TABLE `payment_vouchers` ADD `status` VARCHAR(100) NOT NULL AFTER `id`;

ALTER TABLE `payment_vouchers_log` CHANGE `supplier_id` `entity_id` INT(11) NOT NULL;

ALTER TABLE `payment_vouchers_log` ADD `status` VARCHAR(100) NOT NULL AFTER `payment_voucher_id`;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PAY` AFTER INSERT ON `payment_vouchers` FOR EACH ROW INSERT INTO payment_vouchers_log (
    payment_voucher_id, status, entity_id, voucher_no,  voucher_date, invoice_no, unit_price, tax, total_nett_weight, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.status, NEW.entity_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;
DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PAY` BEFORE UPDATE ON `payment_vouchers` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    -- Check if deleted = 1, set action_id to 3, otherwise set to 2
    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    -- Insert into payment_vouchers_log table
    INSERT INTO payment_vouchers_log (
        payment_voucher_id, status, entity_id, voucher_no, voucher_date, invoice_no, unit_price, tax, total_nett_weight, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.status, NEW.entity_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, action_value, NEW.modified_by, NEW.modified_date
    );
END
$$
DELIMITER ;

-- 17/06/2026 --
ALTER TABLE `payment_vouchers` ADD `nett_amount` VARCHAR(100) NULL AFTER `total_nett_weight`, ADD `tax_amount` VARCHAR(100) NULL AFTER `nett_amount`;
ALTER TABLE `payment_vouchers` CHANGE `invoice_no` `invoice_no` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;

ALTER TABLE `payment_vouchers_log` ADD `nett_amount` VARCHAR(100) NULL AFTER `total_nett_weight`, ADD `tax_amount` VARCHAR(100) NULL AFTER `nett_amount`;

ALTER TABLE `payment_vouchers_log` CHANGE `invoice_no` `invoice_no` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;

DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_INS_PAY` AFTER INSERT ON `payment_vouchers` FOR EACH ROW INSERT INTO payment_vouchers_log (
    payment_voucher_id, status, entity_id, voucher_no,  voucher_date, invoice_no, unit_price, tax, total_nett_weight, nett_amount, tax_amount, total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
) 
VALUES (
    NEW.id, NEW.status, NEW.entity_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.nett_amount, NEW.tax_amount, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, 1, NEW.created_by, NEW.created_date
)
$$
DELIMITER ;
DELIMITER $$
CREATE OR REPLACE TRIGGER `TRG_UPD_PAY` BEFORE UPDATE ON `payment_vouchers` FOR EACH ROW BEGIN
    DECLARE action_value INT;

    -- Check if deleted = 1, set action_id to 3, otherwise set to 2
    IF NEW.deleted = 1 THEN
        SET action_value = 3;
    ELSE
        SET action_value = 2;
    END IF;

    -- Insert into payment_vouchers_log table
    INSERT INTO payment_vouchers_log (
        payment_voucher_id, status, entity_id, voucher_no, voucher_date, invoice_no, unit_price, tax, total_nett_weight, nett_amount, tax_amount,total_amount, deduction_amount, addition_amount, final_amount, deduction_details, addition_details, deleted, delete_reason, company, action_id, action_by, event_date
    ) 
    VALUES (
        NEW.id, NEW.status, NEW.entity_id, NEW.voucher_no, NEW.voucher_date, NEW.invoice_no, NEW.unit_price, NEW.tax, NEW.total_nett_weight, NEW.nett_amount, NEW.tax_amount, NEW.total_amount, NEW.deduction_amount, NEW.addition_amount, NEW.final_amount, NEW.deduction_details, NEW.addition_details, NEW.deleted, NEW.delete_reason, NEW.company, action_value, NEW.modified_by, NEW.modified_date
    );
END
$$
DELIMITER ;