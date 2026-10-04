<?php
namespace App\Services;

class ProductService extends BaseService
{
    private const LIST_FROM = 'products LEFT JOIN categories ON products.category = categories.id LEFT JOIN units ON products.uom = units.id';

    /**
     * Get paginated products for DataTables
     */
    public function getList(int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $where = "products.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'products.customer');

        $totalRecords = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $this->applySearch($where, $params, $types, ['products.product_name', 'products.remark', 'categories.category_name'], $search);
        $totalFiltered = $this->countRows(self::LIST_FROM, $where, $types, $params);

        $orderBy = $this->orderBy([
            'product_code' => 'products.product_code',
            'product_name' => 'products.product_name',
            'category_name' => 'categories.category_name',
            'weight' => 'products.weight',
            'remark' => 'products.remark'
        ], $orderColumn, $orderDir, 'products.product_name');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT products.*, categories.category_name, units.units AS unit_name FROM " . self::LIST_FROM . "
             WHERE $where ORDER BY products.deleted, $orderBy LIMIT ?, ?",
            $types, $params
        );

        $data = [];
        foreach ($rows as $row) {
            $uom = !empty($row['uom']) && !empty($row['unit_name']) ? $row['unit_name'] : 'g';

            $data[] = [
                'id' => $row['id'],
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'],
                'category_name' => $row['category_name'] ?? '',
                'pricing_type' => $row['pricing_type'],
                'price' => $row['price'],
                'weight' => $row['weight'] . ' ' . $uom,
                'uom' => $row['uom'],
                'unit' => $uom,
                'remark' => $row['remark'],
                'is_manual' => $row['is_manual'],
                'deleted' => $row['deleted']
            ];
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    public function reactivate(int $id): array
    {
        return $this->reactivateRecord('products', $id);
    }

    /**
     * Bulk insert products from Excel upload
     */
    public function upload(array $rows): array
    {
        return $this->uploadRecords('products', 'product_name', 'Product Name', $rows, function (array $row): array {
            return [
                'product_code' => !empty($row['ProductCode']) ? trim($row['ProductCode']) : '',
                'product_name' => !empty($row['ProductName']) ? trim($row['ProductName']) : '',
                'weight' => !empty($row['Weight']) ? trim($row['Weight']) : '',
                'pricing_type' => !empty($row['PricingType']) ? trim($row['PricingType']) : '',
                'price' => !empty($row['Price']) ? trim($row['Price']) : ''
            ];
        });
    }

    /**
     * Get product with its customer, supplier and grade pricing rows
     */
    public function getById(int $id): ?array
    {
        $where = "p.id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types, 'p.customer');

        $row = $this->fetchOne(
            "SELECT p.*, u.units AS uom_name, pk.packaging_name
             FROM products p
             LEFT JOIN units u ON p.uom = u.id
             LEFT JOIN packaging pk ON p.packaging = pk.id
             WHERE $where",
            $types, $params
        );

        if (!$row) {
            return null;
        }

        $product = [
            'id' => $row['id'],
            'product_code' => $row['product_code'],
            'product_name' => $row['product_name'],
            'product_sn' => $row['product_sn'],
            'batch_no' => $row['batch_no'],
            'parts_no' => $row['parts_no'],
            'uom' => $row['uom'],
            'remark' => $row['remark'],
            'pricing_type' => $row['pricing_type'],
            'pricing_currency' => $row['pricing_currency'],
            'price' => $row['price'],
            'purchasing_pricing_type' => $row['purchasing_pricing_type'],
            'purchasing_pricing_currency' => $row['purchasing_pricing_currency'],
            'purchasing_price' => $row['purchasing_price'],
            'weight' => $row['weight'],
            'customer' => $row['customer'],
            'range_set' => $row['range_set'],
            'ok_weight' => $row['ok_weight'],
            'ok_weight_unit' => $row['ok_weight_unit'],
            'lo_weight' => $row['lo_weight'],
            'lo_weight_unit' => $row['lo_weight_unit'],
            'hi_weight' => $row['hi_weight'],
            'hi_weight_unit' => $row['hi_weight_unit'],
            'packaging' => $row['packaging'],
            'category' => $row['category'],
            'product_image' => $row['product_image'],
            'uom_name' => $row['uom_name'],
            'packaging_name' => $row['packaging_name'],
            'state' => $row['state'] !== null ? json_decode($row['state'], true) : null,
            'colour' => $row['colour']
        ];

        $product['productCustomers'] = [];
        foreach ($this->fetchChildRows('product_customers', $id) as $i => $child) {
            $product['productCustomers'][] = [
                'no' => $i + 1,
                'id' => $child['id'],
                'product_id' => $child['product_id'],
                'customer_id' => $child['customer_id'],
                'grade_id' => $child['grade_id'],
                'type' => $child['type'] ?? 'Local',
                'pricing_type' => $child['pricing_type'],
                'pricing_currency' => $child['pricing_currency'],
                'price' => $child['price'],
                'purchasing_pricing_type' => $child['purchasing_pricing_type'],
                'purchasing_price' => $child['purchasing_price']
            ];
        }

        $product['productSuppliers'] = [];
        foreach ($this->fetchChildRows('product_suppliers', $id) as $i => $child) {
            $product['productSuppliers'][] = [
                'no' => $i + 1,
                'id' => $child['id'],
                'product_id' => $child['product_id'],
                'supplier_id' => $child['supplier_id'],
                'grade_id' => $child['grade_id'],
                'type' => $child['type'] ?? 'Local',
                'purchasing_pricing_type' => $child['purchasing_pricing_type'],
                'purchasing_pricing_currency' => $child['purchasing_pricing_currency'],
                'purchasing_price' => $child['purchasing_price']
            ];
        }

        $product['productGrades'] = [];
        foreach ($this->fetchChildRows('product_grades', $id) as $i => $child) {
            $product['productGrades'][] = [
                'no' => $i + 1,
                'id' => $child['id'],
                'product_id' => $child['product_id'],
                'grade_id' => $child['grade_id'],
                'type' => $child['type'] ?? 'Local',
                'pricing_type' => $child['pricing_type'],
                'pricing_currency' => $child['pricing_currency'],
                'price' => $child['price'],
                'purchasing_pricing_type' => $child['purchasing_pricing_type'],
                'purchasing_pricing_currency' => $child['purchasing_pricing_currency'],
                'purchasing_price' => $child['purchasing_price']
            ];
        }

        return $product;
    }

    /**
     * Resolve the price for a product by status, customer/supplier, grade and currency.
     * RECEIVING / INCOMING use purchasing prices and supplier pricing; others use selling prices and customer pricing.
     */
    public function getPrice(int $id, string $status, string $partnerId, string $grade, string $currency): ?array
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        $product = $this->fetchOne("SELECT * FROM products WHERE $where", $types, $params);

        if (!$product) {
            return null;
        }

        $isPurchase = ($status == 'RECEIVING' || $status == 'INCOMING');
        $productPricingType = $isPurchase ? $product['purchasing_pricing_type'] : $product['pricing_type'];
        $productCurrency = $isPurchase ? $product['purchasing_pricing_currency'] : $product['pricing_currency'];
        $productPrice = $isPurchase ? $product['purchasing_price'] : $product['price'];
        $currencyField = $isPurchase ? 'purchasing_pricing_currency' : 'pricing_currency';
        $currencyValue = $currency !== '' ? $currency : null;

        // Product price, or 0 when a different currency is requested
        $productResult = [
            'pricingType' => $productPricingType,
            'price' => ($currency !== '' && $currency != $productCurrency) ? 0 : $productPrice
        ];

        if ($partnerId !== '') {
            if ($grade === '') {
                return ['pricingType' => null, 'price' => 0];
            }

            // Customer / supplier specific pricing for this grade
            $partnerTable = $isPurchase ? 'product_suppliers' : 'product_customers';
            $partnerColumn = $isPurchase ? 'supplier_id' : 'customer_id';
            $partnerRow = $this->fetchPricingRow(
                "SELECT * FROM $partnerTable WHERE product_id = ? AND $partnerColumn = ? AND grade_id = ? AND $currencyField = ? AND deleted = 0",
                'isss',
                [$id, $partnerId, $grade, $currencyValue]
            );

            if ($partnerRow) {
                $pricingType = $isPurchase ? $partnerRow['purchasing_pricing_type'] : $partnerRow['pricing_type'];
                $price = $isPurchase ? $partnerRow['purchasing_price'] : $partnerRow['price'];

                if ($pricingType == 'Standard') {
                    return $productResult;
                }

                return ['pricingType' => $pricingType, 'price' => $price];
            }

            // No customer specific pricing, check product grade pricing
            $gradeRow = $this->fetchGradePricingRow($id, $grade, $currencyField, $currencyValue);
            if ($gradeRow) {
                $pricingType = $isPurchase ? $gradeRow['purchasing_pricing_type'] : $gradeRow['pricing_type'];
                $price = $isPurchase ? $gradeRow['purchasing_price'] : $gradeRow['price'];

                if ($pricingType != 'Standard') {
                    return ['pricingType' => $pricingType, 'price' => $price];
                }
            }

            return ['pricingType' => $productPricingType, 'price' => $currency !== '' ? 0 : $productPrice];
        }

        if ($grade !== '') {
            $gradeRow = $this->fetchGradePricingRow($id, $grade, $currencyField, $currencyValue);
            if ($gradeRow) {
                $pricingType = $isPurchase ? $gradeRow['purchasing_pricing_type'] : $gradeRow['pricing_type'];
                $price = $isPurchase ? $gradeRow['purchasing_price'] : $gradeRow['price'];

                if ($pricingType != 'Standard') {
                    return ['pricingType' => $pricingType, 'price' => $price];
                }
            }
        }

        return $productResult;
    }

    /**
     * Create or update product with grade pricing rows and image.
     * $grades null means no grade rows were submitted.
     */
    public function saveProduct(int $id, array $data, int $company, ?array $grades, ?array $image): array
    {
        if ($id && !$this->ownsProduct($id)) {
            return ['status' => 'failed', 'message' => 'Product not found'];
        }

        $this->db->begin_transaction();

        try {
            if ($id) {
                $data['is_manual'] = 'N';
                $data['modified_by'] = $this->user;

                if (!$this->updateRow('products', $data, "id = ?", 'i', [$id])) {
                    throw new \Exception('Failed to update product');
                }

                $this->replaceGrades($id, $grades);
                $productId = $id;
            } else {
                $data['customer'] = $this->resolveCompany($company);
                $data['created_by'] = $this->user;

                $productId = $this->insertRow('products', $data);
                if (!$productId) {
                    throw new \Exception('Failed to insert product');
                }

                if ($grades !== null) {
                    $this->insertGrades($productId, $grades);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->db->query("SET @skip_grade_log = NULL");
            error_log('ProductService::saveProduct - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => $id ? 'Failed to update product' : 'Failed to add product'];
        }

        if ($image !== null && ($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $this->saveImage($productId, $image, (bool)$id);
        }

        return ['status' => 'success', 'message' => $id ? 'Updated Successfully!!' : 'Added Successfully!!'];
    }

    /**
     * Replace customer and supplier pricing rows for a product
     */
    public function saveCustomerSupplier(int $productId, array $customerRows, array $supplierRows): array
    {
        if (!$this->ownsProduct($productId)) {
            return ['status' => 'failed', 'message' => 'Product not found'];
        }

        $this->db->begin_transaction();

        try {
            // Customer rows (suppress trigger log while soft-deleting before re-saving)
            if (!empty($customerRows)) {
                $this->db->query("SET @skip_customer_log = 1");
                $this->softDeleteChildRows('product_customers', $productId);
                $this->db->query("SET @skip_customer_log = NULL");

                foreach ($customerRows as $row) {
                    if ($row['id'] !== '') {
                        $ok = $this->executeWrite(
                            "UPDATE product_customers SET customer_id = ?, grade_id = ?, type = ?, pricing_type = ?, pricing_currency = ?, price = ?, deleted = '0', modified_by = ? WHERE id = ? AND product_id = ?",
                            'ssssssiii',
                            [$row['partner'], $row['grade'], $row['type'], $row['pricingType'], $row['currency'], $row['price'], $this->user, (int)$row['id'], $productId]
                        );
                    } else {
                        $ok = $this->executeWrite(
                            "INSERT INTO product_customers (product_id, customer_id, grade_id, type, pricing_type, pricing_currency, price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            'issssssi',
                            [$productId, $row['partner'], $row['grade'], $row['type'], $row['pricingType'], $row['currency'], $row['price'], $this->user]
                        );
                    }
                    if (!$ok) {
                        throw new \Exception('Failed to save product customer');
                    }
                }
            } else {
                $this->softDeleteChildRows('product_customers', $productId);
            }

            // Supplier rows (suppress trigger log while soft-deleting before re-saving)
            if (!empty($supplierRows)) {
                $this->db->query("SET @skip_supplier_log = 1");
                $this->softDeleteChildRows('product_suppliers', $productId);
                $this->db->query("SET @skip_supplier_log = NULL");

                foreach ($supplierRows as $row) {
                    if ($row['id'] !== '') {
                        $ok = $this->executeWrite(
                            "UPDATE product_suppliers SET supplier_id = ?, grade_id = ?, type = ?, purchasing_pricing_type = ?, purchasing_pricing_currency = ?, purchasing_price = ?, deleted = '0', modified_by = ? WHERE id = ? AND product_id = ?",
                            'ssssssiii',
                            [$row['partner'], $row['grade'], $row['type'], $row['pricingType'], $row['currency'], $row['price'], $this->user, (int)$row['id'], $productId]
                        );
                    } else {
                        $ok = $this->executeWrite(
                            "INSERT INTO product_suppliers (product_id, supplier_id, grade_id, type, purchasing_pricing_type, purchasing_pricing_currency, purchasing_price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            'issssssi',
                            [$productId, $row['partner'], $row['grade'], $row['type'], $row['pricingType'], $row['currency'], $row['price'], $this->user]
                        );
                    }
                    if (!$ok) {
                        throw new \Exception('Failed to save product supplier');
                    }
                }
            } else {
                $this->softDeleteChildRows('product_suppliers', $productId);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $this->db->query("SET @skip_customer_log = NULL");
            $this->db->query("SET @skip_supplier_log = NULL");
            error_log('ProductService::saveCustomerSupplier - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save pricing'];
        }

        return ['status' => 'success', 'message' => 'Saved successfully.'];
    }

    /**
     * Soft delete products and mark their inventory as deleted
     */
    public function delete(array $ids): array
    {
        $ids = $this->cleanIds($ids);

        if (empty($ids)) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        // Restrict to products within the session company
        $where = "id IN (" . $this->placeholders(count($ids)) . ")";
        $params = $ids;
        $types = str_repeat('i', count($ids));
        $this->applyCompanyScope($where, $params, $types);

        $ownedIds = array_map('intval', array_column($this->fetchAll("SELECT id FROM products WHERE $where", $types, $params), 'id'));

        if (empty($ownedIds)) {
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        $placeholders = $this->placeholders(count($ownedIds));
        $idTypes = str_repeat('i', count($ownedIds));

        $this->db->begin_transaction();

        try {
            if (!$this->executeWrite("UPDATE products SET deleted = 1, modified_by = ? WHERE id IN ($placeholders)", 'i' . $idTypes, array_merge([$this->user], $ownedIds))) {
                throw new \Exception('Failed to delete products');
            }

            // Update the deleted products in the inventory table
            if (!$this->executeWrite("UPDATE inventory SET status = '1' WHERE product_id IN ($placeholders)", $idTypes, $ownedIds)) {
                throw new \Exception('Failed to update inventory');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('ProductService::delete - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Get products with grades filtered by type (Local/Export)
     */
    public function getProductsWithGrades(?array $categoryIds = null, string $type = 'Local'): array
    {
        $where = "p.deleted = 0 AND p.customer = ?";
        $params = [$this->company];
        $types = 'i';

        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where .= " AND p.category IN ($placeholders)";
            foreach ($categoryIds as $catId) {
                $params[] = $catId;
                $types .= 'i';
            }
        }

        // Only get products that have grades of the specified type
        $where .= " AND EXISTS (SELECT 1 FROM product_grades pg WHERE pg.product_id = p.id AND pg.deleted = 0 AND pg.type = ?)";
        $params[] = $type;
        $types .= 's';

        $sql = "SELECT p.id, p.product_code, p.product_name, p.category, c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category = c.id
                WHERE $where
                ORDER BY c.category_name ASC, p.product_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['grades'] = $this->getGradesForProduct((int)$row['id'], $type);
            $products[] = $row;
        }
        $stmt->close();

        return $products;
    }

    /**
     * Get grades for a specific product filtered by type
     */
    public function getGradesForProduct(int $productId, string $type = 'Local'): array
    {
        $stmt = $this->db->prepare(
            "SELECT pg.id as product_grade_id, pg.grade_id, pg.purchasing_price, pg.price, g.units as grade_name
             FROM product_grades pg
             LEFT JOIN grades g ON pg.grade_id = g.id
             WHERE pg.product_id = ? AND pg.deleted = 0 AND pg.type = ?
             ORDER BY g.units ASC"
        );
        $stmt->bind_param('is', $productId, $type);
        $stmt->execute();
        $result = $stmt->get_result();

        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt->close();

        return $grades;
    }

    /**
     * Soft delete old grade rows, then update / insert submitted rows
     */
    private function replaceGrades(int $productId, ?array $grades): void
    {
        if ($grades === null) {
            $this->softDeleteChildRows('product_grades', $productId);
            return;
        }

        $this->db->query("SET @skip_grade_log = 1");
        $this->softDeleteChildRows('product_grades', $productId);
        $this->db->query("SET @skip_grade_log = NULL");

        foreach ($grades as $row) {
            if ($row['id'] !== '') {
                $ok = $this->executeWrite(
                    "UPDATE product_grades SET grade_id = ?, type = ?, pricing_type = ?, pricing_currency = ?, price = ?, purchasing_pricing_type = ?, purchasing_pricing_currency = ?, purchasing_price = ?, modified_by = ?, deleted = '0' WHERE id = ? AND product_id = ?",
                    'ssssssssiii',
                    [$row['grade'], $row['type'], $row['pricingType'], $row['pricingCurrency'], $row['price'], $row['purchasingPricingType'], $row['purchasingPricingCurrency'], $row['purchasingPrice'], $this->user, (int)$row['id'], $productId]
                );
                if (!$ok) {
                    throw new \Exception('Failed to update product grade');
                }
            } else {
                $this->insertGrades($productId, [$row]);
            }
        }
    }

    private function insertGrades(int $productId, array $grades): void
    {
        foreach ($grades as $row) {
            $ok = $this->executeWrite(
                "INSERT INTO product_grades (product_id, grade_id, type, pricing_type, pricing_currency, price, purchasing_pricing_type, purchasing_pricing_currency, purchasing_price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                'issssssssi',
                [$productId, $row['grade'], $row['type'], $row['pricingType'], $row['pricingCurrency'], $row['price'], $row['purchasingPricingType'], $row['purchasingPricingCurrency'], $row['purchasingPrice'], $this->user]
            );
            if (!$ok) {
                throw new \Exception('Failed to insert product grade');
            }
        }
    }

    private function softDeleteChildRows(string $table, int $productId): void
    {
        if (!$this->executeWrite("UPDATE $table SET deleted = '1', modified_by = ? WHERE product_id = ? AND deleted = '0'", 'ii', [$this->user, $productId])) {
            throw new \Exception('Failed to soft delete ' . $table);
        }
    }

    /**
     * Upload product image, replacing the old one on update
     */
    private function saveImage(int $productId, array $image, bool $replaceOld): void
    {
        if ($replaceOld) {
            $row = $this->fetchOne("SELECT product_image FROM products WHERE id = ?", 'i', [$productId]);
            if ($row && $row['product_image']) {
                deleteOldFile($row['product_image'], $this->db);
            }
        }

        $result = uploadFile($image, 'photo', $productId, $this->db);
        if ($result['status'] === 'success' && $result['fid']) {
            $fid = (string)$result['fid'];
            $this->executeWrite("UPDATE products SET product_image = ? WHERE id = ?", 'si', [$fid, $productId]);
        }
    }

    private function ownsProduct(int $id): bool
    {
        $where = "id = ?";
        $params = [$id];
        $types = 'i';
        $this->applyCompanyScope($where, $params, $types);

        return $this->fetchOne("SELECT id FROM products WHERE $where", $types, $params) !== null;
    }

    private function fetchChildRows(string $table, int $productId): array
    {
        $rows = $this->fetchAll("SELECT * FROM $table WHERE product_id = ? AND deleted = '0' ORDER BY type ASC, id ASC", 'i', [$productId]);

        // Keep values as strings, matching the previous non-prepared response
        foreach ($rows as &$row) {
            $row = array_map(function ($value) {
                return $value === null ? null : (string)$value;
            }, $row);
        }
        unset($row);

        return $rows;
    }

    private function fetchGradePricingRow(int $productId, string $grade, string $currencyField, ?string $currency): ?array
    {
        return $this->fetchPricingRow(
            "SELECT * FROM product_grades WHERE product_id = ? AND grade_id = ? AND $currencyField = ? AND type = 'Local' AND deleted = 0",
            'iss',
            [$productId, $grade, $currency]
        );
    }

    /**
     * Last matching pricing row
     */
    private function fetchPricingRow(string $sql, string $types, array $params): ?array
    {
        return $this->fetchOne($sql . " ORDER BY id DESC LIMIT 1", $types, $params);
    }
}
