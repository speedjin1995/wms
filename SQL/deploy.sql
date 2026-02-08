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

INSERT INTO `message_resource` (`message_key_code`, `en`, `zh`, `my`, `ne`, `ja`, `company`) VALUES
('home_code', 'Home', '首页', 'Laman Utama', 'முகப்பு', 'ホーム', 10),
('welcome_code', 'Welcome', '欢迎', 'Selamat Datang', 'வரவேற்கிறோம்', 'ようこそ', 10),
('weighing_code', 'Weighing', '称重', 'Penimbangan', 'எடைபோடுதல்', '計量', 10),
('wholesales_code', 'Wholesales', '批发', 'Borong', 'மொத்த விற்பனை', '卸売', 10),
('weighbridge_code', 'Weighbridge', '地磅', 'Jambatan Timbang', 'எடை மேடை', '計量橋', 10),
('reports_code', 'Reports', '报表', 'Laporan', 'அறிக்கைகள்', 'レポート', 10),
('master_data_code', 'Master Data', '主数据', 'Data Induk', 'முதன்மை தரவு', 'マスターデータ', 10),
('translations_code', 'Translations', '翻译', 'Terjemahan', 'மொழிபெயர்ப்புகள்', '翻訳', 10),
('units_code', 'Units', '单位', 'Unit', 'அலகுகள்', '単位', 10),
('customer_code', 'Customer', '客户', 'Pelanggan', 'வாடிக்கையாளர்', '顧客', 10),
('supplier_code', 'Supplier', '供应商', 'Pembekal', 'விநியோகஸ்தர்', '仕入先', 10),
('products_code', 'Products', '产品', 'Produk', 'தயாரிப்புகள்', '製品', 10),
('drivers_code', 'Drivers', '司机', 'Pemandu', 'ஓட்டுநர்கள்', 'ドライバー', 10),
('vehicles_code', 'Vehicles', '车辆', 'Kenderaan', 'வாகனங்கள்', '車両', 10),
('transporters_code', 'Transporters', '运输商', 'Pengangkut', 'போக்குவரத்தாளர்கள்', '運送業者', 10),
('grades_code', 'Grades', '等级', 'Gred', 'தரங்கள்', '等級', 10),
('settings_code', 'Settings', '设置', 'Tetapan', 'அமைப்புகள்', '設定', 10),
('company_profile_code', 'Company Profile', '公司资料', 'Profil Syarikat', 'நிறுவன சுயவிவரம்', '会社情報', 10),
('staffs_code', 'Staffs', '员工', 'Kakitangan', 'பணியாளர்கள்', 'スタッフ', 10),
('port_setup_code', 'Port Setup', '端口设置', 'Tetapan Port', 'போர்ட் அமைப்பு', 'ポート設定', 10),
('profile_code', 'Profile', '个人资料', 'Profil', 'சுயவிவரம்', 'プロフィール', 10),
('change_password_code', 'Change Password', '更改密码', 'Tukar Kata Laluan', 'கடவுச்சொல்லை மாற்று', 'パスワード変更', 10),
('logout_code', 'Logout', '退出登录', 'Log Keluar', 'வெளியேறு', 'ログアウト', 10),
('my_profile_code', 'My Profile', '我的资料', 'Profil Saya', 'எனது சுயவிவரம்', 'マイプロフィール', 10),
('full_name_code', 'Full Name', '全名', 'Nama Penuh', 'முழுப் பெயர்', '氏名', 10),
('username_code', 'Username', '用户名', 'Nama Pengguna', 'பயனர்பெயர்', 'ユーザー名', 10),
('language_code', 'Language', '语言', 'Bahasa', 'மொழி', '言語', 10),
('save_code', 'Save', '保存', 'Simpan', 'சேமிக்க', '保存', 10),
('enter_full_name_code', 'Enter Full Name', '请输入全名', 'Masukkan Nama Penuh', 'முழுப் பெயரை உள்ளிடவும்', '氏名を入力', 10),
('enter_username_code', 'Enter Username', '请输入用户名', 'Masukkan Nama Pengguna', 'பயனர்பெயரை உள்ளிடவும்', 'ユーザー名を入力', 10),
('company_profile_code', 'Company Profile', '公司资料', 'Profil Syarikat', 'நிறுவன சுயவிவரம்', '会社情報', 10),
('company_reg_no_code', 'Company Reg No.', '公司注册号', 'No. Pendaftaran Syarikat', 'நிறுவன பதிவு எண்', '会社登録番号', 10),
('enter_company_reg_no_code', 'Enter Company Reg No.', '请输入公司注册号', 'Masukkan No. Pendaftaran Syarikat', 'நிறுவன பதிவு எண்ணை உள்ளிடவும்', '会社登録番号を入力', 10),
('company_name_code', 'Company Name', '公司名称', 'Nama Syarikat', 'நிறுவனத்தின் பெயர்', '会社名', 10),
('enter_company_name_code', 'Enter Company Name', '请输入公司名称', 'Masukkan Nama Syarikat', 'நிறுவனத்தின் பெயரை உள்ளிடவும்', '会社名を入力', 10),
('company_address_line_1_code', 'Company Address Line 1', '公司地址第1行', 'Alamat Syarikat Baris 1', 'நிறுவன முகவரி வரி 1', '会社住所1行目', 10),
('enter_company_address_line_1_code', 'Enter Company Address Line 1', '请输入公司地址第1行', 'Masukkan Alamat Syarikat Baris 1', 'நிறுவன முகவரி வரி 1 ஐ உள்ளிடவும்', '会社住所1行目を入力', 10),
('company_address_line_2_code', 'Company Address Line 2', '公司地址第2行', 'Alamat Syarikat Baris 2', 'நிறுவன முகவரி வரி 2', '会社住所2行目', 10),
('enter_company_address_line_2_code', 'Enter Company Address Line 2', '请输入公司地址第2行', 'Masukkan Alamat Syarikat Baris 2', 'நிறுவன முகவரி வரி 2 ஐ உள்ளிடவும்', '会社住所2行目を入力', 10),
('company_address_line_3_code', 'Company Address Line 3', '公司地址第3行', 'Alamat Syarikat Baris 3', 'நிறுவன முகவரி வரி 3', '会社住所3行目', 10),
('enter_company_address_line_3_code', 'Enter Company Address Line 3', '请输入公司地址第3行', 'Masukkan Alamat Syarikat Baris 3', 'நிறுவன முகவரி வரி 3 ஐ உள்ளிடவும்', '会社住所3行目を入力', 10),
('company_address_line_4_code', 'Company Address Line 4', '公司地址第4行', 'Alamat Syarikat Baris 4', 'நிறுவன முகவரி வரி 4', '会社住所4行目', 10),
('enter_company_address_line_4_code', 'Enter Company Address Line 4', '请输入公司地址第4行', 'Masukkan Alamat Syarikat Baris 4', 'நிறுவன முகவரி வரி 4 ஐ உள்ளிடவும்', '会社住所4行目を入力', 10),
('company_phone_code', 'Company Phone', '公司电话', 'Telefon Syarikat', 'நிறுவன தொலைபேசி', '会社電話番号', 10),
('enter_phone_code', 'Enter Phone', '请输入电话号码', 'Masukkan Telefon', 'தொலைபேசி எண்ணை உள்ளிடவும்', '電話番号を入力', 10),
('company_email_code', 'Company Email', '公司电子邮件', 'Emel Syarikat', 'நிறுவன மின்னஞ்சல்', '会社メール', 10),
('enter_email_code', 'Enter Email', '请输入电子邮件', 'Masukkan Emel', 'மின்னஞ்சலை உள்ளிடவும்', 'メールを入力', 10),
('company_fax_code', 'Company Fax', '公司传真', 'Faks Syarikat', 'நிறுவன ஃபாக்ஸ்', '会社FAX', 10),
('enter_fax_code', 'Enter Fax', '请输入传真', 'Masukkan Faks', 'ஃபாக்ஸ் எண்ணை உள்ளிடவும்', 'FAXを入力', 10),
('change_password_code', 'Change Password', '更改密码', 'Tukar Kata Laluan', 'கடவுச்சொல்லை மாற்று', 'パスワード変更', 10),
('old_password_code', 'Old Password', '旧密码', 'Kata Laluan Lama', 'பழைய கடவுச்சொல்', '現在のパスワード', 10),
('new_password_code', 'New Password', '新密码', 'Kata Laluan Baharu', 'புதிய கடவுச்சொல்', '新しいパスワード', 10),
('confirm_password_code', 'Confirm Password', '确认密码', 'Sahkan Kata Laluan', 'கடவுச்சொல்லை உறுதிப்படுத்து', 'パスワード確認', 10),
('setup_code', 'Setup', '设置', 'Tetapan', 'அமைப்பு', '設定', 10),
('serial_port_code', 'Serial Port', '串口', 'Port Bersiri', 'சீரியல் போர்ட்', 'シリアルポート', 10),
('baud_rate_code', 'Baud Rate', '波特率', 'Kadar Baud', 'பாட் வீதம்', 'ボーレート', 10),
('data_bits_code', 'Data Bits', '数据位', 'Bit Data', 'தரவு பிட்கள்', 'データビット', 10),
('parity_code', 'Parity', '奇偶校验', 'Pariti', 'பாரிட்டி', 'パリティ', 10),
('stop_bits_code', 'Stop Bits', '停止位', 'Bit Henti', 'நிறுத்த பிட்கள்', 'ストップビット', 10),
('parity_none_code', 'None', '无', 'Tiada', 'இல்லை', 'なし', 10),
('parity_odd_code', 'Odd', '奇校验', 'Ganjil', 'ஒற்றை', '奇数', 10),
('parity_even_code', 'Even', '偶校验', 'Genap', 'இரட்டை', '偶数', 10),
('parity_mark_code', 'Mark', '标记', 'Tanda', 'குறி', 'マーク', 10),
('parity_space_code', 'Space', '空格', 'Ruang', 'வெற்று', 'スペース', 10);


