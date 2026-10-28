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
     * Receiving report: received vs DO weight per DO supplier (its wholesales serial) + product + grade, ordered by DO No for grouping
     */
    public function getReport(?string $dateFrom, ?string $dateTo, int $supplier = 0): array
    {
        $where = "i.company = ? AND i.deleted = 0";
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
        if ($supplier) {
            $where .= " AND i.supplier = ?";
            $params[] = $supplier;
            $types .= 'i';
        }

        return $this->fetchAll(
            "SELECT i.do_id, d.do_no, i.supplier, s.supplier_name,
                (SELECT MIN(w.serial_no) FROM wholesales w WHERE w.do_id = i.do_id AND w.supplier = i.supplier AND w.company = i.company AND w.deleted = '0') AS serial_no,
                c.category_name, p.product_name, g.units AS grade_name,
                SUM(i.total_received_weight) AS received_weight, SUM(i.total_do_weight) AS do_weight
            FROM delivery_order_items i
            JOIN delivery_orders d ON d.id = i.do_id AND d.company = i.company AND d.deleted = 0
            LEFT JOIN supplies s ON s.id = i.supplier
            LEFT JOIN categories c ON c.id = i.category
            LEFT JOIN products p ON p.id = i.product
            LEFT JOIN grades g ON g.id = i.grade_id
            WHERE $where
            GROUP BY i.do_id, d.do_no, i.supplier, i.category, i.product, i.grade_id, s.supplier_name, c.category_name, p.product_name, g.units
            ORDER BY d.do_no ASC, s.supplier_name ASC, serial_no ASC, c.category_name ASC, p.product_name ASC, g.units ASC",
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
     * DO Excel template with dropdowns for supplier, product and grade
     */
    public function buildTemplate(array $categoryIds): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $opts = $this->getOptions($categoryIds);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DO');
        $lists = $spreadsheet->createSheet();
        $lists->setTitle('Lists');

        // Table layout: one row per weight. Rows with the same DO No are one DO; within it the same supplier + product + grade are one item
        $lastRow = 504;
        $sheet->fromArray(['DATE', 'DO NO', 'SUPPLIER', 'PRODUCT', 'GRADE', 'DO WEIGHT'], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle('A2:A' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('0.00');
        $sheet->freezePane('A2');
        foreach (['A' => 14, 'B' => 16, 'C' => 40, 'D' => 36, 'E' => 14, 'F' => 16] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $lists->fromArray(array_map(function ($s) {
            return [$s['supplier_code'] . ' - ' . $s['supplier_name']];
        }, $opts['suppliers']), null, 'A1');
        $lists->fromArray(array_map(function ($p) {
            return [$p['product_code'] . ' - ' . $p['product_name']];
        }, $opts['products']), null, 'B1');

        // Each product row: C = grade count, D onwards = that product's grades, for the GRADE dropdown lookup
        foreach (array_values($opts['products']) as $i => $p) {
            $grades = array_map(function ($g) {
                return $g['grade_name'];
            }, $p['grades']);
            $lists->fromArray(array_merge([count($grades)], $grades), null, 'C' . ($i + 1));
        }
        $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
        $spreadsheet->setActiveSheetIndex(0);

        // $col is the template column, $listCol the hidden Lists column holding its values
        $addList = function ($col, $listCol, $count) use ($sheet, $lastRow) {
            if ($count === 0) {
                return;
            }

            $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('Lists!$' . $listCol . '$1:$' . $listCol . '$' . $count);
            $sheet->setDataValidation($col . '2:' . $col . $lastRow, $validation);
        };
        $addList('C', 'A', count($opts['suppliers']));
        $addList('D', 'B', count($opts['products']));

        // GRADE lists only the grades of the PRODUCT picked on the same row
        if (count($opts['products']) > 0) {
            $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('OFFSET(Lists!$D$1,MATCH($D2,Lists!$B:$B,0)-1,0,1,MAX(1,INDEX(Lists!$C:$C,MATCH($D2,Lists!$B:$B,0))))');
            $sheet->setDataValidation('E2:E' . $lastRow, $validation);
        }

        return $spreadsheet;
    }

    /**
     * Daily receiving report rows, grouped by DO No with subtotals and a grand total
     */
    private function reportLayout(array $rows, callable $t): array
    {
        $lines = [];
        $totalReceived = 0;
        $totalDo = 0;
        $lastDoNo = null;
        $groupReceived = 0;
        $groupDo = 0;
        $subtotalLine = function ($received, $do) use ($t) {
            $loss = round($do - $received, 2);
            return ['type' => 'subtotal', 'line' => [$t('subtotal_code', 'Subtotal'), '', '', '', '', $received, $do, $loss, $do > 0 ? $loss / $do : 0]];
        };
        foreach ($rows as $r) {
            if ($lastDoNo !== $r['do_no']) {
                if ($lastDoNo !== null) {
                    $lines[] = $subtotalLine($groupReceived, $groupDo);
                }
                $lines[] = ['type' => 'header', 'text' => $t('do_no_code', 'DO No') . ': ' . $r['do_no']];
                $groupReceived = 0;
                $groupDo = 0;
            }
            $lastDoNo = $r['do_no'];
            $received = (float)$r['received_weight'];
            $do = (float)$r['do_weight'];
            $loss = round($do - $received, 2);
            $lines[] = [
                $r['supplier_name'],
                $r['serial_no'],
                $r['category_name'],
                $r['product_name'],
                $r['grade_name'],
                $received,
                $do,
                $loss != 0 ? $loss : null,
                ($loss != 0 && $do > 0) ? $loss / $do : null
            ];
            $totalReceived += $received;
            $totalDo += $do;
            $groupReceived += $received;
            $groupDo += $do;
        }
        if ($lastDoNo !== null) {
            $lines[] = $subtotalLine($groupReceived, $groupDo);
        }
        $totalLoss = round($totalDo - $totalReceived, 2);
        $totalLine = [$t('total_code', 'Total'), '', '', '', '', $totalReceived, $totalDo, $totalLoss, $totalDo > 0 ? $totalLoss / $totalDo : 0];

        $title = $t('do_receiving_report_code', 'Daily Receiving Report');
        $headers = [
            $t('supplier_code', 'Supplier'),
            $t('serial_no_code', 'Serial No'),
            $t('category_code', 'Category'),
            $t('product_code', 'Product'),
            $t('grade_code', 'Grade'),
            $t('received_weight_code', 'Received Weight'),
            $t('do_weight_code', 'DO Weight'),
            $t('weight_loss_code', 'Weight Loss'),
            '%'
        ];

        return [$title, $headers, $lines, $totalLine];
    }

    /**
     * Daily receiving report as an Excel workbook
     */
    public function reportSpreadsheet(array $rows, callable $t, string $period): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        [$title, $headers, $lines, $totalLine] = $this->reportLayout($rows, $t);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->setCellValue('A2', $period);
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getAlignment()->setHorizontal('center');

        $row = 5;
        foreach ($lines as $line) {
            if (($line['type'] ?? '') === 'header') {
                $sheet->setCellValue('A' . $row, $line['text']);
                $sheet->mergeCells('A' . $row . ':I' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F7');
            } elseif (($line['type'] ?? '') === 'subtotal') {
                $sheet->fromArray($line['line'], null, 'A' . $row, true);
                $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
            } else {
                $sheet->fromArray($line, null, 'A' . $row, true);
            }
            $row++;
        }
        $row++;
        $sheet->fromArray($totalLine, null, 'A' . $row, true);
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');

        $sheet->getStyle('A4:I' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('F5:H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('I5:I' . $row)->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('H4:I' . $row)->getFont()->getColor()->setRGB('0070C0');
        foreach (['A' => 32, 'B' => 16, 'C' => 18, 'D' => 24, 'E' => 12, 'F' => 16, 'G' => 16, 'H' => 14, 'I' => 10] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return $spreadsheet;
    }

    /**
     * Daily receiving report as a landscape A4 PDF
     */
    public function reportPdf(array $rows, callable $t, string $period): \Mpdf\Mpdf
    {
        [$title, $headers, $lines, $totalLine] = $this->reportLayout($rows, $t);

        $e = function ($v) {
            return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        };
        $cells = function ($line) use ($e) {
            $out = '';
            for ($i = 0; $i < 5; $i++) {
                $out .= '<td>' . $e($line[$i]) . '</td>';
            }
            return $out
                . '<td class="num">' . number_format($line[5], 2) . '</td>'
                . '<td class="num">' . number_format($line[6], 2) . '</td>'
                . '<td class="num loss">' . ($line[7] !== null ? number_format($line[7], 2) : '') . '</td>'
                . '<td class="num loss">' . ($line[8] !== null ? number_format($line[8] * 100, 2) . '%' : '') . '</td>';
        };

        $html = '<style>
            body { font-size: 10pt; }
            h2 { text-decoration: underline; margin: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 3px 5px; }
            th { text-align: center; }
            .num { text-align: right; }
            .loss { color: #0070C0; }
            .total td { background: #FFFF00; font-weight: bold; }
            .grp td { background: #EEF2F7; font-weight: bold; }
            .sub td { font-weight: bold; }
        </style>';
        $html .= '<h2>' . $e($title) . '</h2><div>' . $e($period) . '</div>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . $e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($lines as $line) {
            if (($line['type'] ?? '') === 'header') {
                $html .= '<tr class="grp"><td colspan="9">' . $e($line['text']) . '</td></tr>';
            } elseif (($line['type'] ?? '') === 'subtotal') {
                $html .= '<tr class="sub">' . $cells($line['line']) . '</tr>';
            } else {
                $html .= '<tr>' . $cells($line) . '</tr>';
            }
        }
        $html .= '<tr><td colspan="9">&nbsp;</td></tr><tr class="total">' . $cells($totalLine) . '</tr>';
        $html .= '</tbody></table>';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => sys_get_temp_dir(),
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => [__DIR__ . '/../../../vendor/mpdf/mpdf/ttfonts/'],
            'fontdata' => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
            'default_font' => 'sunexta',
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf;
    }

    /**
     * DO Excel upload: one matched entry per DO No. Entries with warnings are not saved by the page.
     */
    public function importSheet(array $rows, array $categoryIds): array
    {
        $dos = $this->parseTable($rows);

        if ($dos === null) {
            return ['status' => 'failed', 'message' => 'Excel layout not recognised. Please download the latest template.'];
        }

        if (empty($dos)) {
            return ['status' => 'failed', 'message' => 'No DO found in the Excel file.'];
        }

        $opts = $this->getOptions($categoryIds);
        $result = [];

        foreach ($dos as $do) {
            $data = $this->matchSheet($do, $opts)['data'];

            if ($do['doNo'] === '') {
                $data['warnings'][] = 'DO No is missing';
            } elseif ($do['date'] === '') {
                $data['warnings'][] = 'Date is missing or invalid';
            } elseif ($this->doNoExists($do['doNo'])) {
                $data['warnings'][] = "DO No '{$do['doNo']}' already exists";
            }

            $result[] = $data;
        }

        return ['status' => 'success', 'data' => ['dos' => $result]];
    }

    private function doNoExists(string $doNo): bool
    {
        return (bool)$this->fetchOne("SELECT id FROM delivery_orders WHERE company = ? AND do_no = ? AND deleted = 0", 'is', [$this->company, $doNo]);
    }

    /**
     * Prompt for reading a scanned / PDF DO, listing this company's names so the model writes back ones we can match
     */
    public function extractPrompt(array $categoryIds): string
    {
        $opts = $this->getOptions($categoryIds);

        $suppliers = array_map(function ($s) {
            return $s['supplier_code'] . ' - ' . $s['supplier_name'];
        }, $opts['suppliers']);

        $products = array_map(function ($p) {
            $grades = array_map(function ($g) {
                return $g['grade_name'];
            }, $p['grades']);
            return $p['product_code'] . ' - ' . $p['product_name'] . ' (grades: ' . implode(', ', $grades) . ')';
        }, $opts['products']);

        return "Read this delivery order document and return ONLY a JSON object, no other text, in exactly this shape:\n"
            . '{"date":"DD/MM/YYYY","doNo":"","rows":[{"supplier":"","product":"","grade":"","do_weight":0}]}' . "\n"
            . "One row per weight line. do_weight is a number in kg. Leave a field empty if it is not on the document.\n"
            . "When a supplier, product or grade matches one of these, write it exactly as listed:\n"
            . "Suppliers:\n" . implode("\n", $suppliers) . "\n"
            . "Products:\n" . implode("\n", $products);
    }

    /**
     * LLM answer from extractPrompt() grouped like parseTable() and matched the same way as the Excel upload
     */
    public function extractDocument(array $json, array $categoryIds): array
    {
        $date = trim((string)($json['date'] ?? ''));
        $sheet = [
            'date' => preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date) ? $date : '',
            'doNo' => trim((string)($json['doNo'] ?? '')),
            'suppliers' => []
        ];

        foreach (($json['rows'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $supplier = trim((string)($row['supplier'] ?? ''));
            $product = trim((string)($row['product'] ?? ''));
            $grade = trim((string)($row['grade'] ?? ''));
            $pair = ['do' => is_numeric($row['do_weight'] ?? null) ? (float)$row['do_weight'] : ''];

            if ($supplier === '' && $product === '' && $grade === '' && $pair['do'] === '') {
                continue;
            }

            $key = $product . "\n" . $grade;
            if (!isset($sheet['suppliers'][$supplier][$key])) {
                $sheet['suppliers'][$supplier][$key] = ['product' => $product, 'grade' => $grade, 'pairs' => []];
            }
            $sheet['suppliers'][$supplier][$key]['pairs'][] = $pair;
        }

        if (empty($sheet['suppliers'])) {
            return ['status' => 'failed', 'message' => 'No DO lines found in the document.'];
        }

        return $this->matchSheet($sheet, $this->getOptions($categoryIds));
    }

    /**
     * Grouped DO rows matched to this company's suppliers, products and grades
     */
    private function matchSheet(array $sheet, array $opts): array
    {
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
                // Product codes can repeat: prefer a name match, then the product that has this grade
                $candidates = array_values(array_filter($opts['products'], function ($p) use ($matches, $item) {
                    return $matches($item['product'], $p['product_code'], $p['product_name']);
                }));
                $textName = $this->splitCode($item['product'])[1];
                $named = array_values(array_filter($candidates, function ($p) use ($upper, $item, $textName) {
                    return $upper($p['product_name']) === $upper($item['product'])
                        || ($textName !== '' && $upper($p['product_name']) === $upper($textName));
                }));
                $pool = $named ?: $candidates;
                $product = $this->firstMatch($pool, function ($p) use ($upper, $item) {
                    foreach ($p['grades'] as $g) {
                        if ($upper($g['grade_name']) === $upper($item['grade'])) {
                            return true;
                        }
                    }
                    return false;
                }) ?? ($pool[0] ?? null);

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
     * DO table layout: a header row with DATE, DO NO, SUPPLIER, PRODUCT, GRADE, DO WEIGHT, then one row per weight.
     * Rows with the same DO No are one DO; within it, rows with the same supplier + product + grade are one item.
     * Returns null when no header row is found.
     */
    private function parseTable(array $rows): ?array
    {
        $columns = null;
        $dos = [];

        foreach ($rows as $row) {
            $row = array_values($row);
            $cells = array_map(function ($value) {
                return trim((string)($value ?? ''));
            }, $row);

            if ($columns === null) {
                $keys = array_map(function ($cell) {
                    return strtoupper(preg_replace('/\s+/', '', $cell));
                }, $cells);

                if (in_array('SUPPLIER', $keys, true) && in_array('DONO', $keys, true) && in_array('DOWEIGHT', $keys, true)) {
                    $columns = [
                        'date' => array_search('DATE', $keys, true),
                        'doNo' => array_search('DONO', $keys, true),
                        'supplier' => array_search('SUPPLIER', $keys, true),
                        'product' => array_search('PRODUCT', $keys, true),
                        'grade' => array_search('GRADE', $keys, true),
                        'do' => array_search('DOWEIGHT', $keys, true)
                    ];
                }
                continue;
            }

            $cell = function ($column) use ($columns, $cells) {
                return $columns[$column] === false ? '' : ($cells[$columns[$column]] ?? '');
            };
            $weight = function ($column) use ($cell) {
                return is_numeric($cell($column)) ? (float)$cell($column) : '';
            };

            $doNo = $cell('doNo');
            $supplier = $cell('supplier');
            $product = $cell('product');
            $grade = $cell('grade');
            $pair = ['do' => $weight('do')];

            if ($doNo === '' && $supplier === '' && $product === '' && $grade === '' && $pair['do'] === '') {
                continue;
            }

            // Date reads the raw cell: an Excel date is a serial number, not text
            $date = $columns['date'] === false ? '' : $this->excelDate($row[$columns['date']] ?? '');

            if (!isset($dos[$doNo])) {
                $dos[$doNo] = ['date' => '', 'doNo' => $doNo, 'suppliers' => []];
            }
            if ($dos[$doNo]['date'] === '') {
                $dos[$doNo]['date'] = $date;
            }

            $key = $product . "\n" . $grade;
            if (!isset($dos[$doNo]['suppliers'][$supplier][$key])) {
                $dos[$doNo]['suppliers'][$supplier][$key] = ['product' => $product, 'grade' => $grade, 'pairs' => []];
            }
            $dos[$doNo]['suppliers'][$supplier][$key]['pairs'][] = $pair;
        }

        return $columns === null ? null : array_values($dos);
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
