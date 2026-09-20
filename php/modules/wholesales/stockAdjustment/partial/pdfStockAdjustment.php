<?php
use Mpdf\Mpdf;

/**
 * Generate Stock Adjustment PDF using template
 */
function generateStockAdjustmentPdf(array $data, array $companyDetail): void
{
    // Load template
    $template = file_get_contents(__DIR__ . '/stockAdjustmentTemplate.html');

    // Prepare replacements
    $replacements = buildReplacements($data, $companyDetail);

    // Inject data into template
    $html = str_replace(array_keys($replacements), array_values($replacements), $template);

    // Generate PDF
    try {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => sys_get_temp_dir(),
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output('StockAdjustment_' . ($data['adjustment_no'] ?? 'unknown') . '.pdf', 'I');
    } catch (\Mpdf\MpdfException $e) {
        echo $e->getMessage();
    }
}

/**
 * Build replacement array for template
 */
function buildReplacements(array $data, array $company): array
{
    // Logo
    $logoHtml = '';
    if (!empty($company['logo'])) {
        $logoPath = __DIR__ . '/../../../../../uploads/' . $company['logo'];
        if (file_exists($logoPath)) {
            $logoHtml = '<img src="' . $logoPath . '" style="width:70px; height:auto;">';
        }
    }

    // Company city
    $companyCity = implode(', ', array_filter([
        $company['city'] ?? '',
        $company['postcode'] ?? '',
        $company['state'] ?? '',
        $company['country'] ?? ''
    ]));

    // Items rows
    $itemsHtml = '';
    $rowNum = 1;
    foreach ($data['items'] ?? [] as $item) {
        $qty = floatval($item['adjustment_qty'] ?? 0);
        $unitCost = floatval($item['unit_cost'] ?? 0);
        $subtotal = floatval($item['total_cost'] ?? 0);
        
        $description = ($item['product_name'] ?? '');
        if (!empty($item['grade_name'])) {
            $description .= ' - ' . $item['grade_name'];
        }
        
        $itemsHtml .= '<tr>
            <td class="tc">' . $rowNum++ . '</td>
            <td>' . htmlspecialchars($item['product_code'] ?? '-') . '</td>
            <td>' . htmlspecialchars($description) . '</td>
            <td class="tc">-</td>
            <td class="tr">' . formatAdjAmount($qty) . '</td>
            <td class="tc">KG</td>
            <td class="tr">' . number_format($unitCost, 2) . '</td>
            <td class="tr">' . formatAdjAmount($subtotal) . '</td>
        </tr>';
    }

    // Date
    $adjDate = !empty($data['adjustment_date']) 
        ? date('d/m/Y', strtotime($data['adjustment_date'])) 
        : '';

    return [
        '{{LOGO}}' => $logoHtml,
        '{{COMPANY_NAME}}' => htmlspecialchars($company['name'] ?? ''),
        '{{COMPANY_REG}}' => htmlspecialchars($company['registration_no'] ?? ''),
        '{{COMPANY_ADDRESS}}' => htmlspecialchars($company['address'] ?? ''),
        '{{COMPANY_CITY}}' => htmlspecialchars($companyCity),
        '{{COMPANY_PHONE}}' => htmlspecialchars($company['phone'] ?? ''),
        '{{COMPANY_EMAIL}}' => htmlspecialchars($company['email'] ?? ''),
        '{{ADJUSTMENT_NO}}' => htmlspecialchars($data['adjustment_no'] ?? ''),
        '{{ADJUSTMENT_DATE}}' => $adjDate,
        '{{ITEMS_ROWS}}' => $itemsHtml,
        '{{REMARK}}' => htmlspecialchars($data['remark'] ?? ''),
        '{{TOTAL_COST}}' => formatAdjAmount(floatval($data['total_cost'] ?? 0)),
    ];
}

/**
 * Format amount with parentheses for negative values
 */
function formatAdjAmount($value): string
{
    $num = floatval($value);
    if ($num < 0) {
        return '(' . number_format(abs($num), 2) . ')';
    }
    return number_format($num, 2);
}
