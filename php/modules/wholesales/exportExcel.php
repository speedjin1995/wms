<?php
require_once '../../db_connect.php';
require_once '../../lookup.php';
require_once '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

if (empty($_SESSION['userID']) || empty($_SESSION['customer'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid record ID');
}

$company = $_SESSION['customer'];
$role = $_SESSION['role'] ?? 'NORMAL';
$sql = "SELECT id, company, serial_no, weight_details FROM wholesales WHERE id = ? AND records_type = 'wholesales'";
if ($role !== 'SADMIN') {
    $sql .= ' AND company = ?';
}

$stmt = $db->prepare($sql);
if ($role !== 'SADMIN') {
    $stmt->bind_param('is', $id, $company);
} else {
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    http_response_code(404);
    exit('Record not found');
}

$companyDetail = searchCompanyById($record['company'], $db);
$featureFlags = $_SESSION['featureFlags'] ?? [];
$allowPhoto = $role === 'SADMIN' ? 'Y' : ($featureFlags['include_photo'] ?? 'N');
$allowPrice = $role === 'SADMIN' ? 'Y' : ($featureFlags['include_price'] ?? ($companyDetail['include_price'] ?? 'N'));
$allowPcsBasket = $role === 'SADMIN' ? 'Y' : ($featureFlags['include_pcs_basket'] ?? ($companyDetail['include_pcs_basket'] ?? 'N'));
$showPrice = $allowPrice === 'Y' && ($_SESSION['userAllowPrice'] ?? 'N') === 'Y';
$languageArray = $_SESSION['languageArray'] ?? [];
$language = $_SESSION['language'] ?? '';
$label = static function ($key, $fallback) use ($languageArray, $language) {
    return $languageArray[$key][$language] ?? $fallback;
};

$headers = [
    $label('product_code', 'Product'),
    $label('grade_code', 'Grade'),
    $label('gross_code', 'Gross'),
    $label('tare_code', 'Tare'),
    $label('net_code', 'Net'),
];
if ($allowPcsBasket === 'Y') {
    $headers[] = $label('pcs_basket_code', 'Pcs/Basket');
}
if ($showPrice) {
    $headers[] = $label('currency_code', 'Currency');
    $headers[] = $label('price_code', 'Price');
    $headers[] = $label('before_disc_code', 'Before Disc');
    $headers[] = $label('discount_code', 'Discount');
    $headers[] = $label('total_code', 'Total');
}
$headers[] = $label('time_code', 'Time');
if ($allowPhoto === 'Y') {
    $headers[] = $label('photo_code', 'Photo');
}

$weightDetails = json_decode($record['weight_details'] ?? '[]', true);
if (!is_array($weightDetails)) {
    $weightDetails = [];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Weighing Details');
$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
$sheet->setCellValue('A1', $label('serial_no_code', 'Serial No.') . ': ' . ($record['serial_no'] ?? ''));
$sheet->mergeCells('A1:' . $lastColumn . '1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->fromArray($headers, null, 'A3');
$sheet->getStyle('A3:' . $lastColumn . '3')->getFont()->setBold(true);
$sheet->getStyle('A3:' . $lastColumn . '3')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE9ECEF');
$sheet->getStyle('A3:' . $lastColumn . '3')->getBorders()->getAllBorders()
    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
$sheet->freezePane('A4');
$sheet->setAutoFilter('A3:' . $lastColumn . '3');

$totals = [
    'gross' => 0.0,
    'tare' => 0.0,
    'net' => 0.0,
    'basket' => 0,
    'before_discount' => 0.0,
    'discount' => 0.0,
    'total' => 0.0,
];

foreach ($weightDetails as $detail) {
    $rowIndex = $sheet->getHighestRow() + 1;
    $rowData = [
        searchProductNameById($detail['product'] ?? '', $db),
        $detail['grade'] ?? '',
        (float)($detail['gross'] ?? 0),
        (float)($detail['tare'] ?? 0),
        (float)($detail['net'] ?? 0),
    ];
    $totals['gross'] += (float)($detail['gross'] ?? 0);
    $totals['tare'] += (float)($detail['tare'] ?? 0);
    $totals['net'] += (float)($detail['net'] ?? 0);

    if ($allowPcsBasket === 'Y') {
        $basketCount = (int)($detail['no_per_basket'] ?? 0);
        $rowData[] = $basketCount;
        $totals['basket'] += $basketCount;
    }
    if ($showPrice) {
        $beforeDiscount = (float)($detail['before_discount'] ?? 0);
        $discount = (float)($detail['discount'] ?? 0);
        $discountAmount = ($detail['discount_type'] ?? '') === 'percent'
            ? $beforeDiscount * $discount / 100
            : $discount;
        $rowData[] = searchCurrencyNameById($detail['currency'] ?? '', $db);
        $rowData[] = (float)($detail['price'] ?? 0);
        $rowData[] = $beforeDiscount;
        $rowData[] = ($detail['discount_type'] ?? '') === 'percent'
            ? number_format($discount, 2) . '%'
            : $discount;
        $rowData[] = (float)($detail['total'] ?? 0);
        $totals['before_discount'] += $beforeDiscount;
        $totals['discount'] += $discountAmount;
        $totals['total'] += (float)($detail['total'] ?? 0);
    }

    $rowData[] = $detail['time'] ?? '';
    if ($allowPhoto === 'Y') {
        $rowData[] = $detail['photoPath'] ?? '';
    }

    foreach ($rowData as $columnIndex => $value) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowIndex;
        if (is_string($value)) {
            $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
        } else {
            $sheet->setCellValue($cell, $value);
        }
    }
}

$totalRow = $sheet->getHighestRow() + 1;
$sheet->setCellValue('A' . $totalRow, $label('total_code', 'Total'));
$sheet->setCellValue('C' . $totalRow, $totals['gross']);
$sheet->setCellValue('D' . $totalRow, $totals['tare']);
$sheet->setCellValue('E' . $totalRow, $totals['net']);
$columnIndex = 6;
if ($allowPcsBasket === 'Y') {
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['basket']);
}
if ($showPrice) {
    $columnIndex += 2;
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['before_discount']);
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex++) . $totalRow, $totals['discount']);
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex) . $totalRow, $totals['total']);
}
$sheet->getStyle('A' . $totalRow . ':' . $lastColumn . $totalRow)->getFont()->setBold(true);

foreach (range(1, count($headers)) as $columnIndex) {
    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
    $sheet->getColumnDimension($column)->setAutoSize(true);
}
$sheet->getStyle('C4:' . $lastColumn . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

$fileName = 'Weighing_Details_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $record['serial_no'] ?? (string)$id) . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

(new Xlsx($spreadsheet))->save('php://output');
exit;