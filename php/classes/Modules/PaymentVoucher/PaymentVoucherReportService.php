<?php
namespace App\Modules\PaymentVoucher;

use App\Core\BaseService;

/**
 * Builds payment voucher documents (voucher slip, statement and PV report print HTML).
 * Data comes from PaymentVoucherService; $t translates a language key (with an optional default).
 */
class PaymentVoucherReportService extends BaseService
{
    private const MONTH_KEYS = [
        1 => 'january_code', 2 => 'february_code', 3 => 'march_code', 4 => 'april_code', 5 => 'may_code', 6 => 'june_code',
        7 => 'july_code', 8 => 'august_code', 9 => 'september_code', 10 => 'october_code', 11 => 'november_code', 12 => 'december_code'
    ];

    /**
     * Voucher slip ('pv') or weighing statement ('statement') HTML
     */
    public function buildPrintHtml(array $data, string $slipType, callable $t): string
    {
        return $slipType === 'statement' ? $this->statementHtml($data, $t) : $this->slipHtml($data, $t);
    }

    /**
     * PV report HTML (prints itself when opened)
     */
    public function buildReportHtml(array $data, string $fromDate, string $toDate, callable $t): string
    {
        $e = [$this, 'escape'];
        $company = $data['company'];
        $address = implode(', ', array_filter([$company['address'] ?? '', $company['address2'] ?? '', $company['address3'] ?? '']));
        $typeLabel = $data['isIncoming'] ? $t('receiving_code', 'Receiving') : $t('dispatch_code', 'Dispatch');

        $rows = '';
        $grandNett = 0.0;
        $grandAmount = 0.0;
        foreach ($data['rows'] as $i => $row) {
            $nett = (float)str_replace(',', '', (string)$row['total_nett_weight']);
            $grandNett += $nett;
            $grandAmount += (float)$row['final_amount'];
            $rows .= '
            <tr>
                <td>' . ($i + 1) . '</td>
                <td>' . date('d/m/Y', strtotime($row['voucher_date'])) . '</td>
                <td>' . $e($row['voucher_no'] ?? '-') . '</td>
                <td>' . $e($row['entity_name']) . '</td>
                <td>' . $e($row['invoice_no'] ?? '-') . '</td>
                <td class="text-center">' . number_format($nett, 2) . '</td>
                <td class="text-center">' . number_format((float)$row['unit_price'], 2) . '</td>
                <td class="text-center">' . number_format((float)$row['final_amount'], 2) . '</td>
            </tr>';
        }

        return '
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
        }
        body { font-family: "Times New Roman", serif; font-size: 12px; margin: 20px; padding: 0; }
        .page-header { text-align: center; margin-bottom: 15px; }
        .page-header h2 { margin: 0 0 3px 0; font-size: 17px; }
        .page-header p { margin: 2px 0; font-size: 12px; }
        .divider { border-bottom: 2px solid #000; margin: 8px 0; }
        .report-title { font-size: 15px; font-weight: bold; text-transform: uppercase; margin: 8px 0 3px 0; }
        .report-subtitle { font-size: 12px; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 4px 6px; font-size: 11px; border: none; }
        .table-border th { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .border-top { border-top: 1px solid #000 !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="page-header">
        <h2>' . $e($company['name'] ?? '') . '</h2>
        <p>(' . $e($company['reg_no'] ?? '') . ')</p>
        <p>' . $e($address) . '</p>
        <p>Tel: ' . $e($company['phone'] ?? '') . '</p>
        <div class="divider"></div>
        <div class="report-title">' . $e($t('payment_voucher_code')) . ' Report</div>
        <div class="report-subtitle">' . $e($typeLabel) . ' &nbsp;|&nbsp; ' . $e($fromDate ?: '-') . ' - ' . $e($toDate ?: '-') . '</div>
    </div>

    <table>
        <thead>
            <tr class="table-border">
                <th class="text-left">#</th>
                <th class="text-left">' . $e($t('voucher_date_code')) . '</th>
                <th class="text-left">' . $e($t('voucher_no_code')) . '</th>
                <th class="text-left">' . $e($t('name_code')) . '</th>
                <th class="text-left">' . $e($t('invoice_no_code')) . '</th>
                <th class="text-center">' . $e($t('total_nett_weight_code')) . ' (KG)</th>
                <th class="text-center">' . $e($t('unit_price_code')) . ' (RM)</th>
                <th class="text-center">' . $e($t('total_price_code')) . ' (RM)</th>
            </tr>
        </thead>
        <tbody>' . $rows . '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold border-top">Total</td>
                <td class="text-center border-top font-bold">' . number_format($grandNett, 2) . '</td>
                <td class="border-top"></td>
                <td class="text-center border-top font-bold">' . number_format($grandAmount, 2) . '</td>
            </tr>
        </tfoot>
    </table>
    <script>window.onload = function() { setTimeout(function() { window.print(); window.close(); }, 500); }</script>
</body>
</html>';
    }

    public function escape($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function slipHtml(array $data, callable $t): string
    {
        $e = [$this, 'escape'];
        $pv = $data['pv'];
        $company = $data['company'];
        $address = implode(', ', [$company['address'] ?? '', $company['address2'] ?? '', $company['address3'] ?? '', $company['address4'] ?? '']);
        $amount = function ($key) use ($pv): float {
            return (float)str_replace(['RM ', ','], '', (string)($pv[$key] ?? 0));
        };

        return '
<html>
<head>
    <style>
        @media print {
            @page {
                size: A5 landscape;
                margin: 0px;
            }
        }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; padding: 0; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h3 { margin: 0; font-size: 13px; font-weight: bold; }
        .header p { margin: 2px 0; font-size: 10px; }
        .title { text-align: center; font-size: 14px; font-weight: bold; margin: 10px 0; text-decoration: underline; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; gap: 20px; }
        .info-item { display: flex; align-items: baseline; flex: 1; }
        .info-label { font-weight: bold; width: 100px; display: inline-block; }
        .info-value { border-bottom: 1px solid #000; flex: 1; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px; }
        th { font-weight: bold; background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .total-row { font-weight: bold; }
        .footer { margin-top: 15px; font-size: 10px; }
        .signature { margin-top: 50px; display: flex; flex-direction: column; align-items: flex-end; }
        .signature p { text-align: left; width: 200px; margin: 0; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin-top: 50px; }
        .text-caps { text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <h3>' . $e($company['name'] ?? '') . ' (No. Daftar: ' . $e($company['reg_no'] ?? '') . ')</h3>
        <p>' . $e($address) . '</p>
        <p>TEL: ' . $e($company['phone'] ?? '') . '</p>
    </div>

    <div class="title text-caps">' . $e($t('payment_voucher_code')) . '</div>

    <div class="info-row">
        <div class="info-item">
            <span class="info-label text-caps">' . $e($t('name_code')) . ' :</span>
            <span class="info-value">' . $e($data['entityName']) . '</span>
        </div>
        <div class="info-item">
            <span class="info-label text-caps">' . $e($t('date_code')) . ' :</span>
            <span class="info-value">' . date('d/m/Y', strtotime($pv['voucher_date'])) . '</span>
        </div>
    </div>

    <div class="info-row">
        <div class="info-item">
            <span class="info-label text-caps">' . $e($t('voucher_no_code')) . ' :</span>
            <span class="info-value">' . $e($pv['voucher_no']) . '</span>
        </div>
        <div class="info-item">
            <span class="info-label text-caps">' . $e($t('invoice_no_code')) . ' :</span>
            <span class="info-value">' . $e($pv['invoice_no']) . '</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-caps">' . $e($t('number_short_code')) . '</th>
                <th class="text-caps">' . $e($t('item_code')) . '</th>
                <th class="text-caps">' . $e($t('unit_code')) . '/KG</th>
                <th class="text-caps">' . $e($t('price_code')) . ' (RM)</th>
                <th class="text-caps">' . $e($t('total_code')) . ' (RM)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-left">' . $e($this->monthYear($pv['voucher_date'], $t)) . '</td>
                <td>' . number_format($amount('total_nett_weight'), 2) . '</td>
                <td class="text-center">RM' . number_format($amount('unit_price'), 2) . '</td>
                <td class="text-center">RM' . number_format($amount('total_amount'), 2) . '</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-left text-caps" style="border-right:none"><strong>' . $e($t('bank_account_no_code')) . ' : </strong></td>
                <td class="text-right text-caps" style="border-left:none"><strong>' . $e($t('total_code')) . ' (RM)</strong></td>
                <td class="text-center"><strong>RM' . number_format($amount('final_amount'), 2) . '</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature">
        <p class="text-caps">' . $e($t('received_by_code')) . ' :</p>
        <div class="signature-line"></div>
    </div>
</body>
</html>';
    }

    private function statementHtml(array $data, callable $t): string
    {
        $e = [$this, 'escape'];
        $pv = $data['pv'];
        $company = $data['company'];
        $unitPrice = (float)$pv['unit_price'];
        $tax = (float)$pv['tax'];
        $addressLines = $e($company['address'] ?? '');
        foreach (['address2', 'address3', 'address4'] as $key) {
            if (!empty($company[$key])) {
                $addressLines .= '<br>' . $e($company[$key]);
            }
        }

        $rows = '';
        foreach ($data['items'] as $item) {
            $itemAmount = $unitPrice * $item['nett'];
            $rows .= '<tr>
                            <td>' . $e($item['date']) . '</td>
                            <td class="text-center">' . $e($item['serial_no']) . '</td>
                            <td class="text-center">' . $e($item['categories']) . '</td>
                            <td class="text-center">' . number_format($item['nett'], 2) . '</td>
                            <td class="text-center">' . number_format($unitPrice, 2) . '</td>
                            <td class="text-center">' . number_format($itemAmount + $itemAmount * ($tax / 100), 2) . '</td>
                        </tr>';
        }

        return '
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <script>
        class HideFooterHandler extends Paged.Handler {
            constructor(chunker, polisher, caller) {
                super(chunker, polisher, caller);
            }
            afterRendered(pages) {
                var allFooters = document.querySelectorAll(".pagedjs_margin-bottom-center .pagedjs_margin-content");
                allFooters.forEach(function(el, i) {
                    if (i < allFooters.length - 1) {
                        el.style.visibility = "hidden";
                    }
                });
            }
        }
        Paged.registerHandlers(HideFooterHandler);
    </script>
    <style>
        @page {
            size: A4;
            margin: 55mm 10mm 65mm 10mm;
            @top-center { content: element(pageHeader); width: 190mm; }
            @bottom-center { content: element(pageFooter); width: 190mm; }
        }
        body { font-family: "Times New Roman", serif; font-size: 13px; margin: 0; padding: 0; }
        #pageHeader { position: running(pageHeader); width: 100%; text-align: center; font-size: 12px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        #pageFooter { position: running(pageFooter); width: 100%; font-size: 12px; border-top: 1px solid #000; padding-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { padding: 3px 6px; font-size: 12px; border: none; }
        .table-border th { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .border-top { border-top: 1px solid #000 !important; }
        .border-bottom { border-bottom: 1px solid #000 !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .footer-signatures { display: flex; justify-content: space-between; margin-top: 15px; }
        .footer-signatures > div { width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #000; width: 180px; margin: 60px auto 0 auto; }
        .text-caps { text-transform: uppercase; }
    </style>
</head>
<body>
    <div id="pageHeader">
        <div style="text-align:center; font-size:13px; line-height:1.4;">
            <strong style="font-size:16px;">' . $e($company['name'] ?? '') . '</strong><br>
            (' . $e($company['reg_no'] ?? '') . ')<br>
            ' . $addressLines . '<br>
            Tel: ' . $e($company['phone'] ?? '') . '<br>
            <strong style="font-size:15px; text-transform:uppercase;">' . $e($t('statement_code')) . '</strong><br>
            <span style="font-size:12px; text-transform:uppercase;">' . $e($t('for_the_month_of_code')) . ' ' . $e($this->monthYear($pv['voucher_date'], $t)) . '</span>
        </div>
    </div>

    <div id="pageFooter">
        <table style="margin:0;">
            <tr>
                <td>' . $e($t('payment_date_code')) . ':</td>
                <td class="border-bottom" style="width:180px;"></td>
                <td></td>
                <td>' . $e($t('net_amount_code')) . ' (RM):</td>
                <td class="text-right border-bottom" style="width:130px;">' . number_format((float)$pv['final_amount'], 2) . '</td>
            </tr>
        </table>
        <div class="footer-signatures">
            <div>
                <p style="margin:0;">' . $e($company['name'] ?? '') . '</p>
                <div class="signature-line"></div>
                <p style="margin-top:4px;">' . $e($t('authorised_signature_code')) . '</p>
            </div>
            <div>
                <p style="margin:0;">' . $e($t('kindly_acknowledge_receipt_code')) . '</p>
                <div class="signature-line"></div>
                <p style="margin-top:4px;">' . $e($t('received_by_code')) . '</p>
            </div>
        </div>
    </div>

    <table>
        <tr>
            <td style="width:60%; font-weight:bold; font-size:15px;">' . $e($data['entityName']) . '</td>
            <td style="width:20%;">' . $e($t('invoice_no_code')) . ':</td>
            <td style="width:20%;">' . $e($pv['invoice_no']) . '</td>
        </tr>
        <tr>
            <td></td>
            <td>' . $e($t('date_code')) . ':</td>
            <td>' . date('d/m/Y', strtotime($pv['voucher_date'])) . '</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr class="table-border">
                <th class="text-left">' . $e($t('date_code')) . '</th>
                <th class="text-center">' . $e($t('serial_no_code')) . '</th>
                <th class="text-center">' . $e($t('category_code')) . '</th>
                <th class="text-center">' . $e($t('nett_weight_code')) . ' (KG)</th>
                <th class="text-center">' . $e($t('price_code')) . ' (RM)</th>
                <th class="text-center">' . $e($t('total_amount_code')) . ' (RM)</th>
            </tr>
        </thead>
        <tbody>
            ' . $rows . '
            <tr>
                <td></td>
                <td><b>Total</b></td>
                <td></td>
                <td class="text-center border-top">' . number_format((float)str_replace(',', '', (string)$pv['total_nett_weight']), 2) . '</td>
                <td class="text-center border-top"></td>
                <td class="text-center border-top">' . number_format((float)str_replace(['RM ', ','], '', (string)$pv['total_amount']), 2) . '</td>
            </tr>
        </tbody>
    </table>
</body>
</html>';
    }

    /**
     * Translated month name + year of a date
     */
    private function monthYear(string $date, callable $t): string
    {
        $time = strtotime($date);

        return $t(self::MONTH_KEYS[(int)date('n', $time)]) . ' ' . date('Y', $time);
    }
}
