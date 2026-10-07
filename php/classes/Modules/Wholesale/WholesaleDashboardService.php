<?php
namespace App\Modules\Wholesale;

use App\Core\BaseService;

require_once __DIR__ . '/../../../lookup.php';

/**
 * Dashboard tabs of the wholesales table; SADMIN sees all companies.
 * Wholesales tab: both record types (RECEIVING + INCOMING, DISPATCH + OUTGOING), with the breakdown Excel exports (templates in partial/export).
 * Pulp & paste tab: industrial records only.
 */
class WholesaleDashboardService extends BaseService
{
    private const RECEIVING_STATUSES = ['RECEIVING', 'INCOMING'];
    private const DISPATCH_STATUSES = ['DISPATCH', 'OUTGOING'];
    private const EXPORT_TEMPLATE_DIR = __DIR__ . '/partial/export/';
    private const EXPORT_TEMPLATES = [
        'customer' => 'exportCustomerBreakdown.php',
        'supplier' => 'exportSupplierBreakdown.php',
        'customer_individual' => 'exportCustomerIndividual.php',
        'supplier_individual' => 'exportSupplierIndividual.php',
        'grade' => 'exportGradeDistribution.php'
    ];

    private string $recordType;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $recordType = 'wholesales')
    {
        parent::__construct($db, $company, $user, $role);
        $this->recordType = $recordType === 'industrial' ? 'industrial' : 'wholesales';
    }

    /**
     * Dashboard data. Filters: fromDate, toDate (d/m/Y), status (RECEIVING / DISPATCH / empty = both), customer, supplier,
     * location, partyType (Normal / Packing, with a status), category (only weight rows of that category are counted)
     */
    public function getSummary(array $filters): array
    {
        if ($this->recordType === 'industrial') {
            return $this->industrialSummary($filters);
        }

        $status = (string)($filters['status'] ?? '');
        $where = "w.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'w.company');
        $this->applyDateFilters($where, $params, $types, $filters);
        $where .= $this->statusCondition($status);

        foreach (['customer' => 'w.customer', 'supplier' => 'w.supplier', 'location' => 'w.location'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $this->addCondition($where, $params, $types, "$column = ?", $filters[$key]);
            }
        }

        $partyType = (string)($filters['partyType'] ?? '');
        if ($partyType !== '' && $status === 'RECEIVING') {
            $this->addCondition($where, $params, $types, "s.supplier_type = ?", $partyType);
        } elseif ($partyType !== '' && $status === 'DISPATCH') {
            $this->addCondition($where, $params, $types, "c.customer_type = ?", $partyType);
        }

        // Category: records holding a product of the category; only those weight rows are counted
        $categoryProductIds = null;
        if (($filters['category'] ?? '') !== '') {
            $categoryWhere = "category = ? AND deleted = '0'";
            $categoryParams = [$filters['category']];
            $categoryTypes = 's';
            $this->applyCompanyScope($categoryWhere, $categoryParams, $categoryTypes, 'customer');
            $categoryProductIds = array_map('strval', array_column($this->fetchAll("SELECT id FROM products WHERE $categoryWhere", $categoryTypes, $categoryParams), 'id'));

            if (empty($categoryProductIds)) {
                $where .= " AND 1 = 0";
            } else {
                $likes = [];
                foreach ($categoryProductIds as $productId) {
                    $likes[] = "w.weight_details LIKE ?";
                    $params[] = '%"product":"' . intval($productId) . '"%';
                    $types .= 's';
                }
                $where .= " AND (" . implode(' OR ', $likes) . ")";
            }
        }

        $records = $this->fetchAll(
            "SELECT w.status, w.weight_details, w.supplier, w.customer, s.supplier_name, s.supplier_type, c.customer_name, c.customer_type,
                    w.start_time, DATE(w.start_time) AS trade_date
             FROM wholesales w LEFT JOIN supplies s ON w.supplier = s.id LEFT JOIN customers c ON w.customer = c.id
             WHERE $where",
            $types,
            $params
        );

        $sides = [];
        foreach (['receiving', 'dispatch'] as $side) {
            $sides[$side] = [
                'weight' => 0, 'count' => 0, 'value' => [], 'parties' => [], 'normal' => [], 'packing' => [],
                'grades' => [], 'hourly' => array_fill(0, 24, 0)
            ];
        }
        $trendMap = [];
        $currencyCache = [];
        $productCache = [];

        foreach ($records as $row) {
            $details = json_decode((string)$row['weight_details'], true);
            if (!is_array($details)) {
                $details = [];
            }

            if ($categoryProductIds !== null) {
                $details = array_values(array_filter($details, function ($item) use ($categoryProductIds) {
                    return in_array((string)($item['product'] ?? ''), $categoryProductIds, true);
                }));
                if (empty($details)) {
                    continue;
                }
            }

            $rowNet = 0;
            $rowValue = [];
            foreach ($details as $item) {
                $rowNet += floatval($item['net'] ?? 0);
                $currency = $this->currencyName((string)($item['currency'] ?? ''), $currencyCache);
                $rowValue[$currency] = ($rowValue[$currency] ?? 0) + floatval($item['total'] ?? 0);
            }

            $date = $row['trade_date'];
            if (!isset($trendMap[$date])) {
                $trendMap[$date] = ['receiving' => 0, 'dispatch' => 0];
            }

            if (in_array($row['status'], self::RECEIVING_STATUSES, true)) {
                $side = 'receiving';
                $partyName = $row['supplier_name'] ?: 'Unknown';
                $partyType = $row['supplier_type'] ?? 'Normal';
            } elseif (in_array($row['status'], self::DISPATCH_STATUSES, true)) {
                $side = 'dispatch';
                $partyName = $row['customer_name'] ?: 'Unknown';
                $partyType = $row['customer_type'] ?? 'Normal';
            } else {
                continue;
            }

            $data = &$sides[$side];
            $data['weight'] += $rowNet;
            $data['count']++;
            foreach ($rowValue as $currency => $value) {
                $data['value'][$currency] = ($data['value'][$currency] ?? 0) + $value;
            }
            $data['parties'][$partyName] = ($data['parties'][$partyName] ?? 0) + $rowNet;
            $typeKey = $partyType === 'Packing' ? 'packing' : 'normal';
            $data[$typeKey][$partyName] = ($data[$typeKey][$partyName] ?? 0) + $rowNet;
            $trendMap[$date][$side] += $rowNet;
            $data['hourly'][(int)date('G', strtotime($row['start_time']))] += $rowNet;

            foreach ($details as $item) {
                $productId = $item['product'] ?? '';
                $productName = $productId != '' ? (getProductById($productId, $this->db, $productCache)['product_name'] ?? 'Unknown') : 'Unknown';
                $gradeName = $item['grade'] ?? 'Unknown';
                $data['grades'][$productName][$gradeName] = ($data['grades'][$productName][$gradeName] ?? 0) + floatval($item['net'] ?? 0);
            }
            unset($data);
        }

        ksort($trendMap);
        $volumeTrend = [];
        foreach ($trendMap as $date => $values) {
            $volumeTrend[] = ['date' => $date, 'receiving' => round($values['receiving'], 2), 'dispatch' => round($values['dispatch'], 2)];
        }

        $round = function (array $values): array {
            return array_map(function ($value) {
                return round($value, 2);
            }, $values);
        };
        // Supplier breakdowns are empty when only dispatch is shown, customer breakdowns when only receiving
        $showSuppliers = $status !== 'DISPATCH';
        $showCustomers = $status !== 'RECEIVING';

        return [
            'summary' => [
                'receiving_weight' => round($sides['receiving']['weight'], 2),
                'receiving_count' => $sides['receiving']['count'],
                'receiving_value' => $round($sides['receiving']['value']),
                'dispatch_weight' => round($sides['dispatch']['weight'], 2),
                'dispatch_count' => $sides['dispatch']['count'],
                'dispatch_value' => $round($sides['dispatch']['value'])
            ],
            'supplierBreakdown' => $showSuppliers ? $this->breakdown($sides['receiving']['parties']) : [],
            'supplierNormalBreakdown' => $showSuppliers ? $this->breakdown($sides['receiving']['normal']) : [],
            'supplierPackingBreakdown' => $showSuppliers ? $this->breakdown($sides['receiving']['packing']) : [],
            'customerBreakdown' => $showCustomers ? $this->breakdown($sides['dispatch']['parties']) : [],
            'customerNormalBreakdown' => $showCustomers ? $this->breakdown($sides['dispatch']['normal']) : [],
            'customerPackingBreakdown' => $showCustomers ? $this->breakdown($sides['dispatch']['packing']) : [],
            'gradeDistribution' => $this->gradeDistribution($sides['receiving']['grades']),
            'gradeDistributionDispatch' => $this->gradeDistribution($sides['dispatch']['grades']),
            'volumeTrend' => $volumeTrend,
            'hourlyReceiving' => $round($sides['receiving']['hourly']),
            'hourlyDispatch' => $round($sides['dispatch']['hourly'])
        ];
    }

    /**
     * Pulp & paste tab data (industrial records). Filters: fromDate, toDate, status (INCOMING / OUTGOING / empty = both),
     * customer, supplier, location
     */
    private function industrialSummary(array $filters): array
    {
        $status = (string)($filters['status'] ?? '');
        $where = "w.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'w.company');
        $where .= " AND w.records_type = 'industrial'";
        $this->applyDateFilters($where, $params, $types, $filters);
        // INCOMING / OUTGOING map to the same directions as RECEIVING / DISPATCH
        $where .= $this->statusCondition(['INCOMING' => 'RECEIVING', 'OUTGOING' => 'DISPATCH'][$status] ?? '');

        foreach (['customer' => 'w.customer', 'supplier' => 'w.supplier', 'location' => 'w.location'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $this->addCondition($where, $params, $types, "$column = ?", $filters[$key]);
            }
        }

        $records = $this->fetchAll(
            "SELECT w.status, w.weight_details, s.supplier_name, c.customer_name, DATE(w.start_time) AS trade_date
             FROM wholesales w LEFT JOIN supplies s ON w.supplier = s.id LEFT JOIN customers c ON w.customer = c.id
             WHERE $where",
            $types,
            $params
        );

        $totals = ['incoming' => ['weight' => 0, 'count' => 0], 'outgoing' => ['weight' => 0, 'count' => 0]];
        $supplierMap = [];
        $customerMap = [];
        $trendMap = [];

        foreach ($records as $row) {
            $rowNet = 0;
            foreach (json_decode((string)$row['weight_details'], true) ?: [] as $item) {
                $rowNet += floatval($item['net'] ?? 0);
            }

            $date = $row['trade_date'];
            if (!isset($trendMap[$date])) {
                $trendMap[$date] = ['incoming' => 0, 'outgoing' => 0];
            }

            if (in_array($row['status'], self::RECEIVING_STATUSES, true)) {
                $totals['incoming']['weight'] += $rowNet;
                $totals['incoming']['count']++;
                $name = $row['supplier_name'] ?: 'Unknown';
                $supplierMap[$name] = ($supplierMap[$name] ?? 0) + $rowNet;
                $trendMap[$date]['incoming'] += $rowNet;
            } elseif (in_array($row['status'], self::DISPATCH_STATUSES, true)) {
                $totals['outgoing']['weight'] += $rowNet;
                $totals['outgoing']['count']++;
                $name = $row['customer_name'] ?: 'Unknown';
                $customerMap[$name] = ($customerMap[$name] ?? 0) + $rowNet;
                $trendMap[$date]['outgoing'] += $rowNet;
            }
        }

        ksort($trendMap);
        $volumeTrend = [];
        foreach ($trendMap as $date => $values) {
            $volumeTrend[] = ['date' => $date, 'incoming' => round($values['incoming'], 2), 'outgoing' => round($values['outgoing'], 2)];
        }

        return [
            'summary' => [
                'incoming_weight' => round($totals['incoming']['weight'], 2),
                'incoming_count' => $totals['incoming']['count'],
                'outgoing_weight' => round($totals['outgoing']['weight'], 2),
                'outgoing_count' => $totals['outgoing']['count']
            ],
            'supplierBreakdown' => $status !== 'OUTGOING' ? $this->breakdown($supplierMap) : [],
            'customerBreakdown' => $status !== 'INCOMING' ? $this->breakdown($customerMap) : [],
            'volumeTrend' => $volumeTrend
        ];
    }

    /**
     * Breakdown Excel. $type: customer / supplier (by party, product, grade), customer_individual / supplier_individual
     * (by party and date), grade (grade distribution; status RECEIVING / DISPATCH / both).
     * Filters: fromDate, toDate, customer / supplier, location (breakdowns and grade), partyType, status (grade). Null for an unknown type.
     */
    public function buildExport(string $type, array $filters): ?array
    {
        if (!isset(self::EXPORT_TEMPLATES[$type])) {
            return null;
        }

        // Variables used by the templates
        $db = $this->db;
        $records = $this->exportRecords($type, $filters);
        $companyDetail = searchCompanyById($this->company, $db);
        $allowPrice = $companyDetail['include_price'];
        $fromDate = (string)($filters['fromDate'] ?? '');
        $toDate = (string)($filters['toDate'] ?? '');
        $partyType = (string)($filters['partyType'] ?? '');
        $status = (string)($filters['status'] ?? '');
        $spreadsheet = null;
        $fileName = '';

        require self::EXPORT_TEMPLATE_DIR . self::EXPORT_TEMPLATES[$type];

        return ['spreadsheet' => $spreadsheet, 'fileName' => $fileName];
    }

    /**
     * Active records for an export type (dates compare on the day of start_time)
     */
    private function exportRecords(string $type, array $filters): array
    {
        $where = "w.deleted = 0";
        $params = [];
        $types = '';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        if ($type === 'grade') {
            $where .= $this->statusCondition((string)($filters['status'] ?? ''));
            $this->applyDateFilters($where, $params, $types, $filters);
            if (($filters['location'] ?? '') !== '') {
                $this->addCondition($where, $params, $types, "w.location = ?", $filters['location']);
            }

            return $this->fetchAll(
                "SELECT w.weight_details, w.status, DATE(w.start_time) AS trade_date FROM wholesales w WHERE $where ORDER BY w.start_time",
                $types,
                $params
            );
        }

        $isCustomer = strpos($type, 'customer') === 0;
        $party = $isCustomer ? 'customer' : 'supplier';
        $where .= $this->statusCondition($isCustomer ? 'DISPATCH' : 'RECEIVING');
        $this->applyDateFilters($where, $params, $types, $filters);
        if (($filters[$party] ?? '') !== '') {
            $this->addCondition($where, $params, $types, "w.$party = ?", $filters[$party]);
        }
        // The individual exports have no location filter (as before)
        if (strpos($type, '_individual') === false && ($filters['location'] ?? '') !== '') {
            $this->addCondition($where, $params, $types, "w.location = ?", $filters['location']);
        }
        if (($filters['partyType'] ?? '') !== '') {
            $this->addCondition($where, $params, $types, $isCustomer ? "c.customer_type = ?" : "s.supplier_type = ?", $filters['partyType']);
        }

        $sql = $isCustomer
            ? "SELECT w.*, c.customer_name FROM wholesales w LEFT JOIN customers c ON w.customer = c.id WHERE $where ORDER BY c.customer_name, w.start_time"
            : "SELECT w.*, s.supplier_name FROM wholesales w LEFT JOIN supplies s ON w.supplier = s.id WHERE $where ORDER BY s.supplier_name, w.start_time";

        return $this->fetchAll($sql, $types, $params);
    }

    /**
     * RECEIVING = receiving + incoming, DISPATCH = dispatch + outgoing, anything else = all four
     */
    private function statusCondition(string $status): string
    {
        if ($status === 'RECEIVING') {
            return " AND w.status IN ('RECEIVING','INCOMING')";
        }
        if ($status === 'DISPATCH') {
            return " AND w.status IN ('DISPATCH','OUTGOING')";
        }

        return " AND w.status IN ('RECEIVING','INCOMING','DISPATCH','OUTGOING')";
    }

    private function applyDateFilters(string &$where, array &$params, string &$types, array $filters): void
    {
        foreach (['fromDate' => '>=', 'toDate' => '<='] as $key => $operator) {
            $date = \DateTime::createFromFormat('d/m/Y', (string)($filters[$key] ?? ''));
            if ($date) {
                $this->addCondition($where, $params, $types, "DATE(w.start_time) $operator ?", $date->format('Y-m-d'));
            }
        }
    }

    private function addCondition(string &$where, array &$params, string &$types, string $condition, $value): void
    {
        $where .= " AND $condition";
        $params[] = $value;
        $types .= 's';
    }

    /**
     * Currency name of a weight row (MYR when empty / unknown)
     */
    private function currencyName(string $currencyId, array &$cache): string
    {
        if ($currencyId === '') {
            return 'MYR';
        }
        if (!isset($cache[$currencyId])) {
            $cache[$currencyId] = searchCurrencyNameById($currencyId, $this->db) ?: 'MYR';
        }

        return $cache[$currencyId];
    }

    /**
     * [name => weight] as [{name, total_weight}], heaviest first
     */
    private function breakdown(array $map): array
    {
        $result = [];
        foreach ($map as $name => $weight) {
            $result[] = ['name' => $name, 'total_weight' => round($weight, 2)];
        }
        usort($result, function ($a, $b) {
            return $b['total_weight'] <=> $a['total_weight'];
        });

        return $result;
    }

    /**
     * [product][grade => weight] as [{product, grades: [{name, weight}]}], heaviest grades / products first
     */
    private function gradeDistribution(array $map): array
    {
        $result = [];
        foreach ($map as $productName => $grades) {
            arsort($grades);
            $gradeList = [];
            foreach ($grades as $gradeName => $weight) {
                $gradeList[] = ['name' => $gradeName, 'weight' => round($weight, 2)];
            }
            $result[] = ['product' => $productName, 'grades' => $gradeList];
        }
        usort($result, function ($a, $b) {
            return array_sum(array_column($b['grades'], 'weight')) <=> array_sum(array_column($a['grades'], 'weight'));
        });

        return $result;
    }
}
