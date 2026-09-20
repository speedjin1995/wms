<?php
/**
 * Generate Stock Adjustment Print HTML
 */
function generateStockAdjustmentPdf(array $data, array $company): void
{
    // Load template
    $template = file_get_contents(__DIR__ . '/stockAdjustmentTemplate.html');

    // Prepare replacements
    $replacements = buildReplacements($data, $company);

    // Inject data into template
    $html = str_replace(array_keys($replacements), array_values($replacements), $template);

    // Output HTML
    echo $html;
}

/**
 * Build replacement array for template
 */
function buildReplacements(array $data, array $company): array
{
    // Logo
    $logoHtml = '';
    if (!empty($company['company_logo'])) {
        $logoSrc = '../../../viewPhoto.php?file=' . urlencode($company['company_logo']) . '&type=file_table';
        $logoHtml = '<div class="logo"><img src="' . $logoSrc . '"></div>';
    }

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
        
        $itemsHtml .= '<div class="item-row">
            <div class="col-no">' . $rowNum++ . '</div>
            <div class="col-code">' . htmlspecialchars($item['product_code'] ?? '-') . '</div>
            <div class="col-desc">' . htmlspecialchars($description) . '</div>
            <div class="col-qty">' . formatAdjAmount($qty) . '</div>
            <div class="col-uom">KG</div>
            <div class="col-unit">' . number_format($unitCost, 2) . '</div>
            <div class="col-total">' . formatAdjAmount($subtotal) . '</div>
        </div>';
    }

    // Date
    $adjDate = !empty($data['adjustment_date']) 
        ? date('d/m/Y', strtotime($data['adjustment_date'])) 
        : '';

    return [
        '{{LOGO_TD}}' => $logoHtml,
        '{{COMPANY_NAME}}' => htmlspecialchars($company['name'] ?? ''),
        '{{COMPANY_REG}}' => htmlspecialchars($company['reg_no'] ?? ''),
        '{{COMPANY_ADDRESS}}' => htmlspecialchars($company['address'] ?? ''),
        '{{COMPANY_ADDRESS2}}' => htmlspecialchars($company['address2'] ?? ''),
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
    return number_format($num, 2);
}
