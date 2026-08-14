<?php
// Invoice Listing Report — grouped by customer/supplier, shows Doc No, Date, Code, Name, Amount
// Variables available: $db, $mpdf, $query, $companyDetail, $allowPrice, $defaultCurrency, $fromDate, $toDate, $transactionStatus

$isDispatchStatus = in_array($transactionStatus, ['DISPATCH', 'OUTGOING', 'STOCK-BAL']);

function buildInvoiceRows($query, $isDispatchStatus, $defaultCurrency, $db) {
  $grandTotal    = [];
  $grouped       = [];
  $customerCache = [];
  $supplierCache = [];

  if ($query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
      $startTime    = new DateTime($row['start_time']);
      $formattedDate = $startTime->format('d/m/Y');

      $weighingDetails = json_decode($row['weight_details'], true) ?? [];
      $totalPrice = 0;
      $currencyTotals  = [];

      foreach ($weighingDetails as $detail) {
        $detailCurrency = !empty($detail['currency']) ? searchCurrencyNameById($detail['currency'], $db) : $defaultCurrency;
        if (empty($detailCurrency)) {
          $detailCurrency = $defaultCurrency;
        }

        $amt = floatval($detail['total'] ?? 0);
        $totalPrice += $amt;
        
        if (!isset($currencyTotals[$detailCurrency])) {
          $currencyTotals[$detailCurrency] = 0;
        }
        $currencyTotals[$detailCurrency] += $amt;
      }

      $isDispatch = in_array($row['status'], ['DISPATCH', 'OUTGOING', 'STOCK-BAL']);
      $partyCode  = $isDispatch ? $row['customer'] : $row['supplier'];
      $partyOther = $isDispatch ? $row['other_customer'] : $row['other_supplier'];

      if ($isDispatch) {
        $partyRow = !empty($partyCode) ? getCustomerById($partyCode, $db, $customerCache) : [];
        $partyName = !empty($partyRow) ? ($partyRow['customer_name'] ?? $partyOther) : $partyOther;
        $partyCodeLabel = $partyRow['customer_code'] ?? '';
      } else {
        $partyRow = !empty($partyCode) ? getSupplierById($partyCode, $db, $supplierCache) : [];
        $partyName = !empty($partyRow) ? ($partyRow['supplier_name'] ?? $partyOther) : $partyOther;
        $partyCodeLabel = $partyRow['supplier_code'] ?? '';
      }

      $partyKey = $partyCode ?: ('OTHER_' . $partyOther);
      if (!isset($grouped[$partyKey])) {
        $grouped[$partyKey] = [
          'code' => $partyCodeLabel,
          'name' => $partyName,
          'rows' => [],
          'currencyTotals' => [],
        ];
      }
      $grouped[$partyKey]['rows'][] = [
        'doc_no' => $row['serial_no'],
        'doc_date' => $formattedDate,
        'currencyTotals' => $currencyTotals,
        'totalPrice' => $totalPrice,
      ];

      foreach ($currencyTotals as $cur => $amt) {
        if (!isset($grouped[$partyKey]['currencyTotals'][$cur])) {
          $grouped[$partyKey]['currencyTotals'][$cur] = 0;
        }

        $grouped[$partyKey]['currencyTotals'][$cur] += $amt;

        if (!isset($grandTotal[$cur])) {
          $grandTotal[$cur] = 0;
        }

        $grandTotal[$cur] += $amt;
      }
    }
  }

  return [$grouped, $grandTotal];
}

[$grouped, $grandTotal] = buildInvoiceRows($query, $isDispatchStatus, $defaultCurrency, $db);

// Transaction summary: group by currency
$transactionSummaryRows = [];
foreach ($grandTotal as $cur => $amt) {
  $transactionSummaryRows[] = ['description' => $isDispatchStatus ? 'SALES' : 'PURCHASE', 'currency' => $cur, 'amount' => $amt];
}

// Build rows HTML
$rowsHtml = '';
$totalRows = 0;
if (!empty($grouped)) {
  foreach ($grouped as $party) {
    foreach ($party['rows'] as $r) {
      $amountDisplay = '';
      foreach ($r['currencyTotals'] as $cur => $amt) {
        $amountDisplay .= $cur . ' ' . number_format($amt, 2) . '<br>';
      }
      $rowsHtml .= '<tr>';
      $rowsHtml .= '<td style="text-align:left;">' . $r['doc_no'] . '</td>';
      $rowsHtml .= '<td style="text-align:center;">' . $r['doc_date'] . '</td>';
      $rowsHtml .= '<td style="text-align:left;">' . $party['code'] . '</td>';
      $rowsHtml .= '<td style="text-align:left;">' . htmlspecialchars($party['name']) . '</td>';
      $rowsHtml .= '<td style="text-align:right;">' . rtrim($amountDisplay, '<br>') . '</td>';
      $rowsHtml .= '</tr>';
      $totalRows++;
    }
  }
} else {
  $rowsHtml = '<tr><td colspan="5" style="text-align:center;">No records found.</td></tr>';
}

// Grand total rows
$grandTotalHtml = '';
foreach ($grandTotal as $cur => $amt) {
  $grandTotalHtml .= '
  <tr style="font-weight:bold; border-top: 2px solid #000; border-bottom: 3px double #000;">
    <td colspan="4" style="text-align:right; font-size:12px;">Grand Total Amount ('.$cur.')</td>
    <td style="text-align:right; font-size:12px;">'.number_format($amt, 2).'</td>
  </tr>';
}

// Account summary rows
$transactionHtml = '';
foreach ($transactionSummaryRows as $ar) {
  $transactionHtml .= '
  <tr>
    <td style="text-align:left;">'.$ar['description'].'</td>
    <td style="text-align:right;">'.$ar['currency'].' '.number_format($ar['amount'], 2).'</td>
    <td width="60%"></td>
  </tr>';
}

$printDate   = date('d/m/y H:i A');
$reportDate  = isset($toDate) ? $toDate : date('d/m/Y');
$reportTitle = in_array($transactionStatus, ['DISPATCH', 'OUTGOING', 'STOCK-BAL']) ? 'Invoice Listing' : 'Purchase Invoice Listing';
$partyLabel  = $isDispatchStatus ? 'Customer' : 'Supplier';
$partyFilter = $isDispatchStatus ? (empty($_GET['customer']) ? 'All' : searchCustomerNameById($_GET['customer'], '', $db)) : (empty($_GET['supplier']) ? 'All' : searchSupplierNameById($_GET['supplier'], '', $db));
$categoryFilter  = empty($_GET['category'])   ? 'All' : searchCategoryById($_GET['category'], $db);
$locationFilter  = empty($_GET['location'])   ? 'All' : searchLocationById($_GET['location'], $db);
$checkedByFilter = empty($_GET['checkedBy'])  ? 'All' : searchUserNameById($_GET['checkedBy'], $db);
$weightedByFilter= empty($_GET['weightedBy']) ? 'All' : searchUserNameById($_GET['weightedBy'], $db);

if (empty($_GET['vehicle']) || $_GET['vehicle'] == '-') {
  $vehicleFilter = 'All';
} elseif (in_array($_GET['vehicle'], ['UNKOWN NO', 'OTHERS', 'UNKNOWN'])) {
  $vehicleFilter = (empty($_GET['otherVehicle']) || $_GET['otherVehicle'] == '-') ? 'All' : $_GET['otherVehicle'];
} else {
  $vehicleFilter = $_GET['vehicle'];
}

$headerHtml = '
<table class="filter-table" style="border-collapse:collapse; width:100%;">
  <tr>
    <td style="width:40%; vertical-align:top;">
      <table style="border-collapse:collapse;">
        <tr>
          <td>Date</td>
          <td>:</td>
          <td>'.$fromDate.' - '.$toDate.'</td>
        </tr>
        <tr>
          <td>'.$partyLabel.'</td>
          <td>:</td>
          <td>'.$partyFilter.'</td>
        </tr>
        <tr>
          <td>Category</td>
          <td>:</td>
          <td>'.$categoryFilter.'</td>
        </tr>
        <tr>
          <td>Vehicle No</td>
          <td>:</td>
          <td>'.$vehicleFilter.'</td>
        </tr>
        <tr>
          <td>Location</td>
          <td>:</td>
          <td>'.$locationFilter.'</td>
        </tr>
        <tr>
          <td>Checked By</td>
          <td>:</td>
          <td>'.$checkedByFilter.'</td>
        </tr>
        <tr>
          <td>Weighted By</td>
          <td>:</td>
          <td>'.$weightedByFilter.'</td>
        </tr>
      </table>
    </td>
    <td style="width:60%; vertical-align:top; text-align: center;">
      <div class="title">'.$reportTitle.'</div>
      <div class="subtitle">As At '.$reportDate.'</div>
    </td>
  </tr>
</table>
<table style="width:100%; border-collapse:collapse; border:none; margin-top:4px;">
  <tr>
    <td style="border:none;" class="company-name">'.htmlspecialchars($companyDetail['name']).' ('.htmlspecialchars($companyDetail['reg_no']).')</td>
    <td style="border:none; text-align:right;">'.$printDate.'<br>Page {PAGENO} of {nbpg}</td>
  </tr>
</table>
';

$html = '
<html>
<head>
<title>'.$reportTitle.'</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
  .filter-table td { padding: 1px 3px; font-size: 12px; }
  .filter-table td:first-child { width: 100px; }
  .filter-table td:nth-child(2) { width: 8px; }
  .title { font-size:16px; font-weight:bold; margin: 6px 0 2px; }
  .subtitle { font-size:14px; font-weight:bold; margin-bottom: 6px; }
  .company-name { font-weight:bold; font-size:12px; }
  table.main { width:100%; border-collapse:collapse; }
  table.main thead th { border-top:1px solid #000; border-bottom:1px solid #000; padding:3px 4px; font-size:12px; }
  table.main thead th:last-child { text-align:right; }
  table.main tbody td { padding:2px 4px; font-size:12px; border:none; }
  table.main tfoot td { padding:3px 4px; font-size:12px; }
  .transaction-box { border:1px solid #000; margin-top:16px; padding:6px 8px; }
  .transaction-box .transaction-title { font-size:12px; margin-bottom:4px; }
  table.transaction { width:100%; border-collapse:collapse; }
  table.transaction thead th { font-size:12px; font-weight:normal; border-bottom:1px solid #ccc; padding:2px 3px; }
  table.transaction thead th:last-child { text-align:right; }
  table.transaction tbody td { font-size:12px; padding:2px 3px; }
  table.transaction tbody td:last-child { text-align:right; }
  .transaction-footer { text-align:right; font-size:12px; margin-top:3px; }
</style>
</head>
<body>

<table class="main">
  <thead>
    <tr>
      <th style="text-align:left; width:18%;">Doc. No</th>
      <th style="text-align:center; width:12%;">Doc. Date</th>
      <th style="text-align:left; width:12%;">Code</th>
      <th style="text-align:left;">Name</th>
      <th style="text-align:right; width:14%;">Amount</th>
    </tr>
  </thead>
  <tbody>
  '.$rowsHtml.'
  <tr><td colspan="5" style="border-bottom: 1px solid #000; padding:0;"></td></tr>
  </tbody>
  <tfoot>'.$grandTotalHtml.'</tfoot>
</table>

<div class="transaction-box">
  <div class="transaction-title">&#8212; Transaction Summary &#8212;</div>
  <table class="transaction">
    <thead>
      <tr>
        <th style="text-align:left;">Description</th>
        <th style="text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>'.$transactionHtml.'</tbody>
  </table>
  <div class="transaction-footer">Total transaction(s) : &nbsp; '.$totalRows.'</div>
</div>

</body>
</html>';

$mpdf->SetHTMLHeader($headerHtml);

$mpdf->WriteHTML($html);
