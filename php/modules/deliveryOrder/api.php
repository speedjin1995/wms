<?php
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../bootstrap.php';

use App\Controllers\DeliveryOrderController;
use App\Services\DeliveryOrderService;

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode(['status' => 'failed', 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['featureFlags']['include_delivery_order'] ?? 'N') != 'Y') {
    echo json_encode(['status' => 'failed', 'message' => 'Delivery order is not enabled']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$service = new DeliveryOrderService(
    $db,
    (int)$_SESSION['customer'],
    (int)$_SESSION['userID'],
    (string)($_SESSION['role'] ?? '')
);
$controller = new DeliveryOrderController($service);

$categoryIds = [];
$userModuleAccess = $_SESSION['userModuleAccess'] ?? [];
if (!empty($userModuleAccess['categories'])) {
    $allowedModules = ['wholesale', 'processing'];
    foreach ($userModuleAccess['categories'] as $module => $moduleCategories) {
        if (in_array($module, $allowedModules)) {
            $categoryIds = array_merge($categoryIds, $moduleCategories);
        }
    }
    $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
}

try {
    switch ($action) {
        case 'list':
            echo json_encode($controller->list());
            break;

        case 'get':
            echo json_encode($controller->get());
            break;

        case 'save':
            echo json_encode($controller->save());
            break;

        case 'delete':
            echo json_encode($controller->delete());
            break;

        case 'options':
            echo json_encode($controller->options($categoryIds));
            break;

        case 'import':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            echo json_encode($controller->import($categoryIds));
            break;

        case 'template':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            $opts = $service->getOptions($categoryIds);

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

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="delivery_order_template.xlsx"');
            header('Cache-Control: max-age=0');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            exit;

        case 'report':
            echo json_encode($controller->report());
            break;

        case 'report_excel':
        case 'report_pdf':
            require_once __DIR__ . '/../../../vendor/autoload.php';
            $language = $_SESSION['language'] ?? 'en';
            $t = function ($key, $default) use ($language) {
                return $_SESSION['languageArray'][$key][$language] ?? $default;
            };

            // Same rows as the screen: DO No header + subtotal per DO, loss/% left blank when 0
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
            foreach ($controller->report()['data'] as $r) {
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
            $period = $_GET['date'] ?? '';
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

            if ($action === 'report_excel') {
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

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="do_receiving_report.xlsx"');
                header('Cache-Control: max-age=0');
                (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
                exit;
            }

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
            $mpdf->Output('do_receiving_report.pdf', 'D');
            exit;

        default:
            echo json_encode(['status' => 'failed', 'message' => 'Invalid action']);
    }
} catch (\Throwable $e) {
    error_log('deliveryOrder/api.php - ' . $e->getMessage());
    echo json_encode(['status' => 'failed', 'message' => 'Something went wrong']);
}
