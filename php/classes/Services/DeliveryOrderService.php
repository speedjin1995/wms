<?php
namespace App\Services;

/**
 * Delivery Order header. Each supplier in a DO is a normal wholesales record (RECEIVING)
 * linked by wholesales.do_id and saved through php/modules/wholesales/wholesales.php.
 */
class DeliveryOrderService extends BaseService
{
    /**
     * DO list with supplier count and total weight of the linked wholesales
     */
    public function getList(?string $dateFrom, ?string $dateTo): array
    {
        $where = "d.company = ? AND d.deleted = 0";
        $params = [$this->company];
        $types = 'i';

        if ($dateFrom) {
            $where .= " AND d.do_date >= ?";
            $params[] = $dateFrom;
            $types .= 's';
        }
        if ($dateTo) {
            $where .= " AND d.do_date <= ?";
            $params[] = $dateTo;
            $types .= 's';
        }

        return $this->fetchAll(
            "SELECT d.id, d.do_no, DATE_FORMAT(d.do_date, '%d/%m/%Y') AS do_date_display, d.remark,
                (SELECT COUNT(DISTINCT i.supplier) FROM delivery_order_items i WHERE i.do_id = d.id AND i.company = d.company AND i.deleted = 0) AS supplier_count,
                (SELECT COALESCE(SUM(i.total_do_weight), 0) FROM delivery_order_items i WHERE i.do_id = d.id AND i.company = d.company AND i.deleted = 0) AS do_weight,
                (SELECT COALESCE(SUM(i.total_received_weight), 0) FROM delivery_order_items i WHERE i.do_id = d.id AND i.company = d.company AND i.deleted = 0) AS received_weight,
                u.name AS created_by_name
            FROM delivery_orders d
            LEFT JOIN users u ON d.created_by = u.id
            WHERE $where
            ORDER BY d.do_date DESC, d.do_no DESC",
            $types,
            $params
        );
    }

    /**
     * DO header with its linked wholesales rows
     */
    public function getById(int $id): ?array
    {
        $header = $this->fetchOne(
            "SELECT id, do_no, DATE_FORMAT(do_date, '%d/%m/%Y') AS do_date_display, remark
            FROM delivery_orders WHERE id = ? AND company = ? AND deleted = 0",
            'ii',
            [$id, $this->company]
        );

        if (!$header) {
            return null;
        }

        $rows = $this->fetchAll(
            "SELECT w.id, w.serial_no, w.supplier, s.supplier_code, s.supplier_name, w.category, w.weight_details, w.total_weight
            FROM wholesales w
            LEFT JOIN supplies s ON w.supplier = s.id
            WHERE w.do_id = ? AND w.company = ? AND w.deleted = '0'
            ORDER BY w.id ASC",
            'ii',
            [$id, $this->company]
        );

        foreach ($rows as &$row) {
            $row['weight_details'] = json_decode($row['weight_details'] ?? '[]', true) ?: [];
        }
        unset($row);

        $header['suppliers'] = $rows;

        $items = $this->fetchAll(
            "SELECT id, supplier, category, product, grade_id, weight_details
            FROM delivery_order_items WHERE do_id = ? AND company = ? AND deleted = 0
            ORDER BY id ASC",
            'ii',
            [$id, $this->company]
        );

        foreach ($items as &$item) {
            $item['weight_details'] = json_decode($item['weight_details'] ?? '[]', true) ?: [];
        }
        unset($item);

        $header['items'] = $items;

        return $header;
    }

    /**
     * Create or update the DO header. DO No is entered by the user and must be unique.
     */
    public function save(int $id, string $doNo, string $doDate, ?string $remark, array $items = []): array
    {
        if ($id && !$this->fetchOne("SELECT id FROM delivery_orders WHERE id = ? AND company = ? AND deleted = 0", 'ii', [$id, $this->company])) {
            return ['status' => 'failed', 'message' => 'Delivery order not found'];
        }

        if ($doNo === '') {
            return ['status' => 'failed', 'message' => 'DO No is required'];
        }

        $duplicate = $this->fetchOne(
            "SELECT id FROM delivery_orders WHERE company = ? AND do_no = ? AND deleted = 0 AND id <> ?",
            'isi',
            [$this->company, $doNo, $id]
        );
        if ($duplicate) {
            return ['status' => 'failed', 'message' => "DO No. '$doNo' already exists."];
        }

        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['pairs']) || !is_array($item['pairs'])) {
                continue;
            }
            $row = $this->prepareItem($item);
            if (!$row) {
                return ['status' => 'failed', 'message' => 'Invalid supplier, product or grade in delivery order'];
            }
            $rows[] = $row;
        }

        $data = ['do_no' => $doNo, 'do_date' => $doDate, 'remark' => $remark];

        $this->db->begin_transaction();

        if ($id) {
            $data['modified_by'] = $this->user;
            $saved = $this->updateRow('delivery_orders', $data, 'id = ? AND company = ?', 'ii', [$id, $this->company]);
        } else {
            $data['company'] = $this->company;
            $data['created_by'] = $this->user;
            $id = $this->insertRow('delivery_orders', $data);
            $saved = $id > 0;
        }

        $saved = $saved && $this->executeWrite(
            "UPDATE delivery_order_items SET deleted = 1, modified_by = ? WHERE do_id = ? AND company = ? AND deleted = 0",
            'iii',
            [$this->user, $id, $this->company]
        );

        foreach ($rows as $row) {
            if (!$saved) {
                break;
            }
            $row += ['do_id' => $id, 'company' => $this->company, 'created_by' => $this->user];
            $saved = $this->insertRow('delivery_order_items', $row) > 0;
        }

        if (!$saved) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => 'Failed to save delivery order'];
        }

        $this->db->commit();

        return ['status' => 'success', 'message' => 'Saved', 'data' => ['id' => $id, 'do_no' => $doNo]];
    }

    /**
     * Validate one supplier line against this company and total its DO / received weights.
     * Returns null when the supplier, product or grade does not belong to this company.
     */
    private function prepareItem(array $item): ?array
    {
        $supplier = (int)($item['supplier'] ?? 0);
        $product = (int)($item['product'] ?? 0);
        $gradeId = (int)($item['grade_id'] ?? 0);

        if (!$supplier || !$product || !$gradeId) {
            return null;
        }

        $productRow = $this->fetchOne("SELECT category FROM products WHERE id = ? AND customer = ? AND deleted = 0", 'ii', [$product, $this->company]);
        if (!$productRow) {
            return null;
        }
        if (!$this->fetchOne("SELECT id FROM supplies WHERE id = ? AND customer = ? AND deleted = '0'", 'ii', [$supplier, $this->company])) {
            return null;
        }
        if (!$this->fetchOne("SELECT id FROM product_grades WHERE product_id = ? AND grade_id = ? AND deleted = 0", 'ii', [$product, $gradeId])) {
            return null;
        }

        $pairs = [];
        $totalDo = 0;
        $totalReceived = 0;
        foreach ($item['pairs'] as $pair) {
            $do = max(0, (float)($pair['do'] ?? 0));
            $received = max(0, (float)($pair['received'] ?? 0));
            if ($do <= 0 && $received <= 0) {
                continue;
            }
            $pairs[] = ['do' => $do, 'received' => $received];
            $totalDo += $do;
            $totalReceived += $received;
        }

        return [
            'supplier' => $supplier,
            'category' => $productRow['category'],
            'product' => $product,
            'grade_id' => $gradeId,
            'weight_details' => json_encode($pairs),
            'total_do_weight' => round($totalDo, 2),
            'total_received_weight' => round($totalReceived, 2)
        ];
    }

    /**
     * Soft delete the header; the page deletes the linked wholesales first
     */
    public function delete(int $id): array
    {
        $this->db->begin_transaction();

        $ok = $this->executeWrite(
            "UPDATE delivery_orders SET deleted = 1, modified_by = ? WHERE id = ? AND company = ?",
            'iii',
            [$this->user, $id, $this->company]
        );
        $ok = $ok && $this->executeWrite(
            "UPDATE delivery_order_items SET deleted = 1, modified_by = ? WHERE do_id = ? AND company = ?",
            'iii',
            [$this->user, $id, $this->company]
        );

        $ok ? $this->db->commit() : $this->db->rollback();

        return $ok
            ? ['status' => 'success', 'message' => 'Deleted']
            : ['status' => 'failed', 'message' => 'Failed to delete delivery order'];
    }

    /**
     * Suppliers, categories, products with grades and the default currency for the form
     */
    public function getOptions(array $categoryIds): array
    {
        $suppliers = $this->fetchAll(
            "SELECT id, supplier_code, supplier_name, currency FROM supplies WHERE customer = ? AND deleted = '0' ORDER BY supplier_name ASC",
            'i',
            [$this->company]
        );

        $where = "deleted = '0' AND customer = ? AND module IN ('wholesale', 'processing')";
        $params = [$this->company];
        $types = 'i';
        if (!empty($categoryIds)) {
            $where .= " AND id IN (" . $this->placeholders(count($categoryIds)) . ")";
            $params = array_merge($params, $categoryIds);
            $types .= str_repeat('i', count($categoryIds));
        }
        $categories = $this->fetchAll("SELECT id, category_name FROM categories WHERE $where ORDER BY category_name ASC", $types, $params);

        $products = (new ProductService($this->db, $this->company))->getProductsWithGrades(!empty($categoryIds) ? $categoryIds : null, 'Local');

        $currency = $this->fetchOne("SELECT id FROM currency WHERE deleted = 0 AND customer = ? AND is_default = 1", 'i', [$this->company]);

        return [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'products' => $products,
            'defaultCurrency' => $currency['id'] ?? null
        ];
    }

    /**
     * DO Excel upload matched to this company's suppliers, products and grades.
     * The page fills the form from it; unmatched names come back as warnings.
     */
    public function importSheet(array $rows, array $categoryIds): array
    {
        $sheet = $this->parseTable($rows);

        if ($sheet === null) {
            return ['status' => 'failed', 'message' => 'Excel layout not recognised. Please download the latest template.'];
        }

        if (empty($sheet['suppliers'])) {
            return ['status' => 'failed', 'message' => 'No supplier found in the Excel file.'];
        }

        $opts = $this->getOptions($categoryIds);
        $upper = function ($value) {
            return mb_strtoupper(trim((string)$value));
        };
        // "code - name" or just the code / name, as picked from the template dropdown or typed
        $matches = function ($text, $code, $name) use ($upper) {
            [$textCode, $textName] = $this->splitCode($text);
            return $upper($code) === $upper($textCode)
                || $upper($name) === $upper($text)
                || ($textName !== '' && $upper($name) === $upper($textName));
        };
        $suppliers = [];
        $warnings = [];

        foreach ($sheet['suppliers'] as $supplierText => $items) {
            $supplier = $this->firstMatch($opts['suppliers'], function ($s) use ($matches, $supplierText) {
                return $matches($supplierText, $s['supplier_code'], $s['supplier_name']);
            });

            if (!$supplier) {
                $warnings[] = 'Supplier ' . $supplierText;
            }

            $lines = [];
            foreach ($items as $item) {
                $product = $this->firstMatch($opts['products'], function ($p) use ($matches, $item) {
                    return $matches($item['product'], $p['product_code'], $p['product_name']);
                });

                $grade = null;
                if ($product) {
                    $grade = $this->firstMatch($product['grades'], function ($g) use ($upper, $item) {
                        return $upper($g['grade_name']) === $upper($item['grade']);
                    });
                }

                if (!$product || !$grade) {
                    $warnings[] = $item['product'] . ' / ' . $item['grade'] . ' (' . $supplierText . ')';
                }

                $lines[] = [
                    'category' => $product['category'] ?? null,
                    'product' => $product['id'] ?? null,
                    'grade_id' => $grade['grade_id'] ?? null,
                    'pairs' => $item['pairs']
                ];
            }

            $suppliers[] = ['supplier' => $supplier['id'] ?? null, 'lines' => $lines];
        }

        return [
            'status' => 'success',
            'data' => [
                'date' => $sheet['date'],
                'doNo' => $sheet['doNo'],
                'suppliers' => $suppliers,
                'warnings' => $warnings
            ]
        ];
    }

    /**
     * DO table layout: "DATE:" / "DO NO:" label cells above the header (value after the colon or in the
     * next filled cell), a header row with SUPPLIER, PRODUCT, GRADE, DO WEIGHT, then one
     * row per weight. Rows with the same supplier + product + grade are grouped into one item.
     * Returns null when no header row is found.
     */
    private function parseTable(array $rows): ?array
    {
        $result = ['date' => '', 'doNo' => '', 'suppliers' => []];
        $columns = null;

        foreach ($rows as $row) {
            $row = array_values($row);
            $cells = array_map(function ($value) {
                return trim((string)($value ?? ''));
            }, $row);

            if ($columns === null) {
                $keys = array_map(function ($cell) {
                    return strtoupper(preg_replace('/\s+/', '', $cell));
                }, $cells);

                if (in_array('SUPPLIER', $keys, true) && in_array('DOWEIGHT', $keys, true)) {
                    $columns = [
                        'supplier' => array_search('SUPPLIER', $keys, true),
                        'product' => array_search('PRODUCT', $keys, true),
                        'grade' => array_search('GRADE', $keys, true),
                        'do' => array_search('DOWEIGHT', $keys, true)
                    ];
                    continue;
                }

                foreach ($keys as $i => $key) {
                    if (strpos($key, 'DATE:') === 0) {
                        $label = 'date';
                    } elseif (strpos($key, 'DONO:') === 0) {
                        $label = 'doNo';
                    } else {
                        continue;
                    }

                    $value = trim(substr($cells[$i], strpos($cells[$i], ':') + 1));

                    if ($value === '') {
                        for ($j = $i + 1; $j < count($row); $j++) {
                            if ($cells[$j] !== '') {
                                $value = $row[$j];
                                break;
                            }
                        }
                    }

                    $result[$label] = $label === 'date' ? $this->excelDate($value) : trim((string)$value);
                }
                continue;
            }

            $cell = function ($column) use ($columns, $cells) {
                return $columns[$column] === false ? '' : ($cells[$columns[$column]] ?? '');
            };
            $weight = function ($column) use ($cell) {
                return is_numeric($cell($column)) ? (float)$cell($column) : '';
            };

            $supplier = $cell('supplier');
            $product = $cell('product');
            $grade = $cell('grade');
            $pair = ['do' => $weight('do')];

            if ($supplier === '' && $product === '' && $grade === '' && $pair['do'] === '') {
                continue;
            }

            $key = $product . "\n" . $grade;
            if (!isset($result['suppliers'][$supplier][$key])) {
                $result['suppliers'][$supplier][$key] = ['product' => $product, 'grade' => $grade, 'pairs' => []];
            }
            $result['suppliers'][$supplier][$key]['pairs'][] = $pair;
        }

        return $columns === null ? null : $result;
    }

    /**
     * Excel date serial or dd.mm.yyyy / dd/mm/yyyy text to DD/MM/YYYY; '' when not a date
     */
    private function excelDate($value): string
    {
        if (is_int($value) || is_float($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('d/m/Y');
        }

        if (preg_match('/^(\d{1,2})([.\/])(\d{1,2})\2(\d{4})$/', trim((string)$value), $m) && checkdate((int)$m[3], (int)$m[1], (int)$m[4])) {
            return sprintf('%02d/%02d/%s', $m[1], $m[3], $m[4]);
        }

        return '';
    }

    /**
     * Split on the first " - " (or "-"): "D197 - CC KP" -> ["D197", "CC KP"]
     */
    private function splitCode(string $text): array
    {
        $idx = strpos($text, ' - ');
        $len = 3;

        if ($idx === false) {
            $idx = strpos($text, '-');
            $len = 1;
        }

        if ($idx === false) {
            return [trim($text), ''];
        }

        return [trim(substr($text, 0, $idx)), trim(substr($text, $idx + $len))];
    }

    private function firstMatch(array $items, callable $match): ?array
    {
        foreach ($items as $item) {
            if ($match($item)) {
                return $item;
            }
        }

        return null;
    }
}
