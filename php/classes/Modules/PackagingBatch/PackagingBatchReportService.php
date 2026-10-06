<?php
namespace App\Modules\PackagingBatch;

use App\Core\BaseService;

/**
 * Builds packaging batch documents (packing list print HTML).
 * Data comes from PackagingBatchService; this class only formats it.
 */
class PackagingBatchReportService extends BaseService
{
    /**
     * Packing list HTML for the print window
     */
    public function buildPrintHtml(array $data): string
    {
        $e = [$this, 'escape'];
        $batch = $data['header'];
        $logoSrc = !empty($batch['company_logo'])
            ? 'php/viewPhoto.php?file=' . urlencode($batch['company_logo']) . '&type=file_table'
            : '';

        $tableRows = '';
        $totalNetWeight = 0.0;
        foreach ($data['items'] as $i => $item) {
            $boxPacking = trim(
                ($item['packaging_name'] ?? '') . ' ' .
                ($item['pkg_weight'] !== null ? number_format((float)$item['pkg_weight'], 0) . 'kg' : '')
            );
            $totalNetWeight += (float)($item['weight'] ?? 0);

            $tableRows .= '
            <tr>
                <td style="text-align:center;">' . ($i + 1) . '</td>
                <td>' . $e($item['product_name']) . '</td>
                <td style="text-align:center;">' . $e($item['grade_name']) . '</td>
                <td style="text-align:center;">' . $e($boxPacking) . '</td>
                <td style="text-align:center;">' . $e($item['label']) . '</td>
                <td style="text-align:center;">' . (int)($item['units_per_box'] ?? 0) . '</td>
                <td style="text-align:center;">' . number_format((float)($item['gross'] ?? 0), 2) . '</td>
                <td style="text-align:center;">' . number_format((float)($item['tare'] ?? 0), 2) . '</td>
                <td style="text-align:center;">' . number_format((float)($item['weight'] ?? 0), 2) . '</td>
            </tr>';
        }

        $batchDate = !empty($batch['packaging_date']) ? date('d/m/Y', strtotime($batch['packaging_date'])) : '';
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/wms/';
        $addressLines = '';
        foreach (['company_address', 'company_address2', 'company_address3'] as $key) {
            if (!empty($batch[$key])) {
                $addressLines .= '<p>' . $e($batch[$key]) . '</p>';
            }
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="' . $e($baseUrl) . '">
    <title>Packing List</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }

        @page {
            size: A4 portrait;
            margin: 12mm;
            @bottom-center {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 12px;
            }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .header-left { display: flex; align-items: flex-start; gap: 10px; }
        .header-left h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header-left p { font-size: 12px; line-height: 1.7; }
        .header-right { text-align: right; }
        .header-right h2 { font-size: 18px; font-weight: bold; text-decoration: underline; margin-bottom: 6px; }
        .header-right p { font-size: 12px; line-height: 1.8; }
        .divider-dot { border: none; border-top: 2px dashed #000; margin: 8px 0; }
        .sub-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 2px 0;
            margin-bottom: 4px;
        }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th,
        .items-table td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        .items-table th { font-weight: bold; text-align: center; }
        .items-table td:nth-child(2) { text-align: left; }
        .items-table tfoot td { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            ' . ($logoSrc ? '<img src="' . $e($logoSrc) . '" style="width:80px;height:auto;flex-shrink:0;">' : '') . '
            <div>
                <h1>' . $e($batch['company_name']) . '</h1>
                ' . $addressLines . '
                <p>PHONE : ' . $e($batch['company_phone']) . '</p>
                <p>Email : ' . $e($batch['company_email']) . '</p>
            </div>
        </div>
        <div class="header-right">
            <h2>Packing List</h2>
            <p><strong>Batch No : ' . $e($batch['batch_no']) . '</strong></p>
            <p>Date : ' . $batchDate . '</p>
        </div>
    </div>
    <hr class="divider-dot">
    <div class="sub-header">
        <span><strong>To Customer :</strong> &nbsp;' . $e(implode(', ', $data['customers'])) . '</span>
        <span><strong>Location :</strong> ' . $e($batch['locations']) . '.</span>
        <span><strong>Line :</strong> ' . $e($batch['production_line_name']) . '</span>
        <span><strong>Weight By :</strong> ' . $e($batch['created_by_name']) . '</span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Item Decription</th>
                <th>Grade</th>
                <th>Box / Packing</th>
                <th>Label No</th>
                <th>Pcs/Box<br>(kg)</th>
                <th>Gross Weight</th>
                <th>Tare Weight</th>
                <th>Net Weight</th>
            </tr>
        </thead>
        <tbody>' . $tableRows . '</tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">Total Count :</td>
                <td style="text-align:center;">' . count($data['items']) . '</td>
                <td colspan="2" style="text-align:right;">Total Weight :</td>
                <td style="text-align:center;">' . number_format($totalNetWeight, 2) . '</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>';
    }

    public function escape($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
