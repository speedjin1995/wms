<?php
// Stock Month End Balance Report
// Variables available: $db, $mpdf, $companyDetail, $company, $query, $asAtDate, $defaultCurrency

// ── Processing ───────────────────────────────────────────────────────────────

$locationFilter = empty($_GET['location']) ? 'All' : searchLocationById($_GET['location'], $db);
$categoryFilter = empty($_GET['category']) ? 'All' : searchCategoryById($_GET['category'], $db);
$productFilter  = empty($_GET['product'])  ? 'All' : searchProductNameById($_GET['product'], $db);

$locResult = $db->query("SELECT id, locations FROM locations WHERE customer = '$company' AND deleted = '0' ORDER BY locations");
$locations = [];
while ($lr = $locResult->fetch_assoc()) {
  $locations[$lr['id']] = $lr['locations'];
}

if (isset($_GET['location']) && $_GET['location'] != null && $_GET['location'] != '' && $_GET['location'] != '-') {
  $locations = array_intersect_key($locations, [$_GET['location'] => true]);
}

$productCache      = [];
$categoryNameCache = [];
$grouped           = [];
$allCurrencies     = [];
$grandInQty        = 0;
$grandOutQty       = 0;
$grandInCost       = [];
$grandOutCost      = [];

$inStatuses  = ['RECEIVING', 'INCOMING'];
$outStatuses = ['DISPATCH', 'OUTGOING', 'STOCK-BAL'];

while ($wRow = $query->fetch_assoc()) {
  $isIn  = in_array($wRow['status'], $inStatuses);
  $isOut = in_array($wRow['status'], $outStatuses);
  if (!$isIn && !$isOut) {
    continue;
  }

  $details = json_decode($wRow['weight_details'], true) ?? [];
  $locId   = $wRow['location'] ?? '';

  foreach ($details as $detail) {
    $productId = $detail['product'] ?? '';
    $gradeId = $detail['grade'] ?? '';
    if (empty($productId)) {
      continue;
    }

    $pRow = getProductById($productId, $db, $productCache);

    if (!empty($_GET['category']) && $_GET['category'] != '-') {
      if (($pRow['category'] ?? '') != $_GET['category']) {
        continue;
      }
    }
    if (!empty($_GET['product']) && $_GET['product'] != '-') {
      if ($productId != $_GET['product']) {
        continue;
      }
    }

    $catId = $pRow['category'] ?? '';
    $category = '';
    if (!empty($catId)) {
      if (!isset($categoryNameCache[$catId])) {
        $categoryNameCache[$catId] = searchCategoryById($catId, $db) ?? '';
      }
      $category = $categoryNameCache[$catId];
    }

    $itemKey = $productId . '_' . $gradeId;
    if (!isset($grouped[$category][$itemKey])) {
      $gradeName = !empty($gradeId) ? (searchGradeNameById($gradeId, $db) ?? '') : '';
      $grouped[$category][$itemKey] = [
        'code' => $pRow['product_code'] ?? '',
        'name' => ($pRow['product_name'] ?? '') . ($gradeName ? ' (' . $gradeName . ')' : ''),
        'locations'    => [],
        'totalInQty'   => 0,
        'totalInCost'  => [],
        'totalOutQty'  => 0,
        'totalOutCost' => [],
      ];
    }

    $net      = floatval($detail['net']   ?? 0);
    $cost     = floatval($detail['total'] ?? 0);
    $currency = !empty($detail['currency']) ? searchCurrencyNameById($detail['currency'], $db) : $defaultCurrency;
    if (empty($currency)) $currency = $defaultCurrency;
    $allCurrencies[$currency] = true;

    if (!isset($grouped[$category][$itemKey]['locations'][$locId])) {
      $grouped[$category][$itemKey]['locations'][$locId] = ['inQty' => 0, 'inCost' => [], 'outQty' => 0, 'outCost' => []];
    }
    if ($isIn) {
      $grouped[$category][$itemKey]['locations'][$locId]['inQty']  += $net;
      $grouped[$category][$itemKey]['locations'][$locId]['inCost'][$currency]  = ($grouped[$category][$itemKey]['locations'][$locId]['inCost'][$currency]  ?? 0) + $cost;
      $grouped[$category][$itemKey]['totalInQty']  += $net;
      $grouped[$category][$itemKey]['totalInCost'][$currency]  = ($grouped[$category][$itemKey]['totalInCost'][$currency]  ?? 0) + $cost;
      $grandInQty += $net;
      $grandInCost[$currency] = ($grandInCost[$currency] ?? 0) + $cost;
    } else {
      $grouped[$category][$itemKey]['locations'][$locId]['outQty']  += $net;
      $grouped[$category][$itemKey]['locations'][$locId]['outCost'][$currency] = ($grouped[$category][$itemKey]['locations'][$locId]['outCost'][$currency] ?? 0) + $cost;
      $grouped[$category][$itemKey]['totalOutQty']  += $net;
      $grouped[$category][$itemKey]['totalOutCost'][$currency] = ($grouped[$category][$itemKey]['totalOutCost'][$currency] ?? 0) + $cost;
      $grandOutQty += $net;
      $grandOutCost[$currency] = ($grandOutCost[$currency] ?? 0) + $cost;
    }
  }
}

ksort($grouped);
foreach ($grouped as &$items) { 
  ksort($items); 
}
unset($items);
ksort($allCurrencies);
$currencies = array_keys($allCurrencies);
if (empty($currencies)) {
  $currencies = [$defaultCurrency];
}
// fixed 4 cols per location: outQty + outCost + inQty + inCost
$locCols     = 4;
$printDate   = date('d/m/y H:i A');
$locColCount = count($locations);
$totalCols   = 4 + ($locColCount * $locCols) + $locCols;

$locNameHeaderHtml = '';
foreach ($locations as $locId => $locName) {
  $locNameHeaderHtml .= '<th colspan="4" class="bc">'.htmlspecialchars($locName).'</th>';
}

$locSubHeaderHtml = '';
foreach ($locations as $locId => $locName) {
  $locSubHeaderHtml .= '<th colspan="2" class="bc">Dispatch</th>';
  $locSubHeaderHtml .= '<th colspan="2" class="bc">Receiving</th>';
}

$locSubSubHeaderHtml = '';
foreach ($locations as $locId => $locName) {
  $locSubSubHeaderHtml .= '<th class="bc">Qty</th>';
  $locSubSubHeaderHtml .= '<th class="bc">Cost</th>';
  $locSubSubHeaderHtml .= '<th class="bc">Qty</th>';
  $locSubSubHeaderHtml .= '<th class="bc">Cost</th>';
}

$rowsHtml = '';
foreach ($grouped as $category => $items) {
  $sgRowspan = count($items);
  $first = true;
  foreach ($items as $item) {
    $rowsHtml .= '<tr>';
    if ($first) {
      $rowsHtml .= '<td rowspan="'.$sgRowspan.'" class="vt">'.htmlspecialchars($category).'</td>';
      $first = false;
    }
    $rowsHtml .= '<td class="bd">'.htmlspecialchars($item['code']).'</td>';
    $rowsHtml .= '<td class="bd">'.htmlspecialchars($item['name']).'</td>';
    $rowsHtml .= '<td class="bc" style="padding:2px 4px;">KG</td>';
    foreach ($locations as $locId => $locName) {
      $outQty   = $item['locations'][$locId]['outQty']  ?? 0;
      $outCostL = $item['locations'][$locId]['outCost'] ?? [];
      $inQty    = $item['locations'][$locId]['inQty']   ?? 0;
      $inCostL  = $item['locations'][$locId]['inCost']  ?? [];
      $outCostStr = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($outCostL), $outCostL));
      $inCostStr  = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($inCostL),  $inCostL));
      $rowsHtml .= '<td class="br">'.($outQty != 0 ? number_format($outQty, 2) : '').'</td>';
      $rowsHtml .= '<td class="br">'.$outCostStr.'</td>';
      $rowsHtml .= '<td class="br">'.($inQty  != 0 ? number_format($inQty,  2) : '').'</td>';
      $rowsHtml .= '<td class="br">'.$inCostStr.'</td>';
    }
    $outTotalStr = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($item['totalOutCost']), $item['totalOutCost']));
    $inTotalStr  = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($item['totalInCost']),  $item['totalInCost']));
    $rowsHtml .= '<td class="brt">'.number_format($item['totalOutQty'] ?? 0, 2).'</td>';
    $rowsHtml .= '<td class="brt">'.$outTotalStr.'</td>';
    $rowsHtml .= '<td class="brt">'.number_format($item['totalInQty']  ?? 0, 2).'</td>';
    $rowsHtml .= '<td class="brt">'.$inTotalStr.'</td>';
    $rowsHtml .= '</tr>';
  }
}

if (empty($grouped)) {
  $rowsHtml = '<tr><td colspan="'.$totalCols.'" class="bc" style="padding:4px;">No records found.</td></tr>';
}

$footerCellsHtml = '';
foreach ($locations as $locId => $locName) {
  $loTotal = 0; $liTotal = 0; $lcOutTotal = []; $lcInTotal = [];
  foreach ($grouped as $items) {
    foreach ($items as $item) {
      $loTotal += $item['locations'][$locId]['outQty'] ?? 0;
      $liTotal += $item['locations'][$locId]['inQty']  ?? 0;
      foreach ($item['locations'][$locId]['outCost'] ?? [] as $c => $v) { $lcOutTotal[$c] = ($lcOutTotal[$c] ?? 0) + $v; }
      foreach ($item['locations'][$locId]['inCost']  ?? [] as $c => $v) { $lcInTotal[$c]  = ($lcInTotal[$c]  ?? 0) + $v; }
    }
  }
  $outStr = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($lcOutTotal), $lcOutTotal));
  $inStr  = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($lcInTotal),  $lcInTotal));
  $footerCellsHtml .= '<td class="br">'.number_format($loTotal, 2).'</td>';
  $footerCellsHtml .= '<td class="br">'.$outStr.'</td>';
  $footerCellsHtml .= '<td class="br">'.number_format($liTotal, 2).'</td>';
  $footerCellsHtml .= '<td class="br">'.$inStr.'</td>';
}

// ── HTML ─────────────────────────────────────────────────────────────────────

$headerHtml = '
<table style="border-collapse:collapse; width:100%;">
  <tr>
    <td style="width:40%; vertical-align:top;">
      <table style="border-collapse:collapse;">
        <tr>
          <td style="font-size:12px; width:90px;">Location</td>
          <td style="font-size:12px; width:8px;">:</td>
          <td style="font-size:12px;">'.$locationFilter.'</td>
        </tr>
        <tr>
          <td style="font-size:12px;">Category</td>
          <td style="font-size:12px;">:</td>
          <td style="font-size:12px;">'.$categoryFilter.'</td>
        </tr>
        <tr>
          <td style="font-size:12px;">Product</td>
          <td style="font-size:12px;">:</td>
          <td style="font-size:12px;">'.$productFilter.'</td>
        </tr>
      </table>
    </td>
    <td style="width:60%; vertical-align:middle; text-align:center;">
      <div style="font-size:16px; font-weight:bold;">Stock Month End Balance</div>
      <div style="font-size:14px; font-weight:bold;">as at '.$asAtDate.'</div>
    </td>
  </tr>
</table>
<table style="width:100%; border-collapse:collapse; border:none; margin-top:4px;">
  <tr>
    <td style="border:none; font-weight:bold; font-size:12px;">'.htmlspecialchars($companyDetail['name']).'</td>
    <td style="border:none; text-align:right; font-size:12px;">'.$printDate.'<br>Page {PAGENO} of {nbpg}</td>
  </tr>
</table>
';

$gtOutCostStr     = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($grandOutCost), $grandOutCost));
$gtInCostStr      = implode('<br>', array_map(fn($c, $v) => $c.' '.number_format($v, 2), array_keys($grandInCost),  $grandInCost));
$gtDispatchFooter = '<td class="br">'.$gtOutCostStr.'</td>';
$gtReceiveFooter  = '<td class="br">'.$gtInCostStr.'</td>';

$html = '
<html>
<head>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
  table.main { width:100%; border-collapse:collapse; }
  table.main th { font-size:11px; padding:2px 4px; background:#f0f0f0; }
  table.main td { font-size:11px; }
  table.main tfoot td { font-weight:bold; background:#e8edf5; border:1px solid #000; padding:2px 4px; }
  .bc  { text-align:center; border:1px solid #000; }
  .bl  { text-align:left;   border:1px solid #000; }
  .br  { text-align:right;  border:1px solid #000; padding:2px 4px; }
  .brt { text-align:right;  border:1px solid #000; padding:2px 4px; font-weight:bold; }
  .bd  { border:1px solid #000; padding:2px 4px; }
  .vt  { vertical-align:top; border:1px solid #000; padding:2px 4px; }
</style>
</head>
<body>

<table class="main">
  <thead>
    <tr>
      <th rowspan="4" class="bl" style="width:12%;">Category</th>
      <th rowspan="4" class="bl" style="width:10%;">Product Code</th>
      <th rowspan="4" class="bl">Description</th>
      <th rowspan="4" class="bc" style="width:5%;">UOM</th>
      '.($locColCount > 0 ? '<th colspan="'.($locColCount * $locCols).'" class="bc">Location</th>' : '').'
      <th colspan="'.$locCols.'" rowspan="2" class="bc">Grand Total</th>
    </tr>
    <tr>
      '.$locNameHeaderHtml.'
    </tr>
    <tr>
      '.$locSubHeaderHtml.'
      <th colspan="2" class="bc">Dispatch</th>
      <th colspan="2" class="bc">Receiving</th>
    </tr>
    <tr>
      '.$locSubSubHeaderHtml.'
      <th class="bc">Qty</th>
      <th class="bc">Cost</th>
      <th class="bc">Qty</th>
      <th class="bc">Cost</th>
    </tr>
  </thead>
  <tbody>
  '.$rowsHtml.'
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" style="text-align:right;">Grand Total</td>
      '.$footerCellsHtml.'
      <td class="br">'.number_format($grandOutQty, 2).'</td>
      '.$gtDispatchFooter.'
      <td class="br">'.number_format($grandInQty,  2).'</td>
      '.$gtReceiveFooter.'
    </tr>
  </tfoot>
</table>

</body>
</html>';

$mpdf->SetHTMLHeader($headerHtml);
$mpdf->WriteHTML($html);
