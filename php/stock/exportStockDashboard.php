<?php
require_once '../db_connect.php';
require_once '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();

$company       = $_SESSION['customer'];
$role          = $_SESSION['role'] ?? 'NORMAL';
$companyFilter = ($role != 'SADMIN') ? "AND t.company = '$company'" : '';

$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003392']],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];
$dataStyle = [
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$totalStyle = [
    'font'    => ['bold' => true],
    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EDF5']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

$spreadsheet = new Spreadsheet();

/* ── Sheet 1: Raw Material ─────────────────────────── */
$sheet1 = $spreadsheet->getActiveSheet()->setTitle('Raw Material');
$sheet1->fromArray(['Category', 'Product', 'Grade', 'Balance (kg)'], null, 'A1');
$sheet1->getStyle('A1:D1')->applyFromArray($headerStyle);

$raw = $db->query("SELECT t.product_id, t.grade, t.balance,
                          p.product_name, c.category_name, g.units as grade_name
                   FROM raw_stock_balance t
                   LEFT JOIN products p   ON p.id = t.product_id
                   LEFT JOIN categories c ON c.id = p.category
                   LEFT JOIN grades g     ON g.id = t.grade
                   WHERE t.deleted = 0 AND t.balance > 0 $companyFilter
                   ORDER BY c.category_name, p.product_name, g.units");

$row = 2; $total = 0;
while ($r = $raw->fetch_assoc()) {
    $sheet1->fromArray([$r['category_name'], $r['product_name'], $r['grade_name'], floatval($r['balance'])], null, 'A'.$row);
    $sheet1->getStyle('A'.$row.':D'.$row)->applyFromArray($dataStyle);
    $sheet1->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.00');
    $total += floatval($r['balance']);
    $row++;
}
$sheet1->fromArray(['', '', 'Total', $total], null, 'A'.$row);
$sheet1->getStyle('A'.$row.':D'.$row)->applyFromArray($totalStyle);
$sheet1->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.00');
foreach (range('A','D') as $col) { $sheet1->getColumnDimension($col)->setAutoSize(true); }

/* ── Sheet 2: Graded Stock ─────────────────────────── */
$sheet2 = $spreadsheet->createSheet()->setTitle('Graded Stock');
$sheet2->fromArray(['Category', 'Product', 'Grade', 'Balance (kg)'], null, 'A1');
$sheet2->getStyle('A1:D1')->applyFromArray($headerStyle);

$graded = $db->query("SELECT t.product_id, t.grade, t.balance,
                             p.product_name, c.category_name, g.units as grade_name
                      FROM grading_stock_balance t
                      LEFT JOIN products p   ON p.id = t.product_id
                      LEFT JOIN categories c ON c.id = p.category
                      LEFT JOIN grades g     ON g.id = t.grade
                      WHERE t.deleted = 0 AND t.balance > 0 $companyFilter
                      ORDER BY c.category_name, p.product_name, g.units");

$row = 2; $total = 0;
while ($r = $graded->fetch_assoc()) {
    $sheet2->fromArray([$r['category_name'], $r['product_name'], $r['grade_name'], floatval($r['balance'])], null, 'A'.$row);
    $sheet2->getStyle('A'.$row.':D'.$row)->applyFromArray($dataStyle);
    $sheet2->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.00');
    $total += floatval($r['balance']);
    $row++;
}
$sheet2->fromArray(['', '', 'Total', $total], null, 'A'.$row);
$sheet2->getStyle('A'.$row.':D'.$row)->applyFromArray($totalStyle);
$sheet2->getStyle('D'.$row)->getNumberFormat()->setFormatCode('0.00');
foreach (range('A','D') as $col) { $sheet2->getColumnDimension($col)->setAutoSize(true); }

/* ── Sheet 3: Packed Stock ─────────────────────────── */
$sheet3 = $spreadsheet->createSheet()->setTitle('Packed Stock');
$sheet3->fromArray(['Category', 'Product', 'Grade', 'Packaging Size', 'Boxes'], null, 'A1');
$sheet3->getStyle('A1:E1')->applyFromArray($headerStyle);

$packed = $db->query("SELECT t.product_id, t.grade, t.packaging_size, t.box_quantity,
                             p.product_name, c.category_name, g.units as grade_name, pk.packaging_name
                      FROM stock_balances t
                      LEFT JOIN products p   ON p.id = t.product_id
                      LEFT JOIN categories c ON c.id = p.category
                      LEFT JOIN grades g     ON g.id = t.grade
                      LEFT JOIN packaging pk ON pk.id = t.packaging_size
                      WHERE t.deleted = 0 AND t.box_quantity > 0 $companyFilter
                      ORDER BY c.category_name, p.product_name, g.units, pk.packaging_name");

$row = 2; $total = 0;
while ($r = $packed->fetch_assoc()) {
    $sheet3->fromArray([$r['category_name'], $r['product_name'], $r['grade_name'], $r['packaging_name'], intval($r['box_quantity'])], null, 'A'.$row);
    $sheet3->getStyle('A'.$row.':E'.$row)->applyFromArray($dataStyle);
    $total += intval($r['box_quantity']);
    $row++;
}
$sheet3->fromArray(['', '', '', 'Total', $total], null, 'A'.$row);
$sheet3->getStyle('A'.$row.':E'.$row)->applyFromArray($totalStyle);
foreach (range('A','E') as $col) { $sheet3->getColumnDimension($col)->setAutoSize(true); }

/* ── Output ────────────────────────────────────────── */
$spreadsheet->setActiveSheetIndex(0);
$fileName = 'Stock_Dashboard_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
