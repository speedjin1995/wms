<?php
namespace App\Modules\Wholesale;

use App\Core\BaseService;

require_once __DIR__ . '/../../../uploadFileHelper.php';
require_once __DIR__ . '/../../../services/stockManagementService.php';

/**
 * Weighing records in the wholesales table (records_type = wholesales / industrial).
 * Weight and reject rows are stored as JSON in weight_details / reject_details.
 */
class WholesaleService extends BaseService
{
    private const CATEGORY_MODULES = [
        'wholesales' => ['wholesale', 'processing'],
        'industrial' => ['industrial']
    ];
    private const SUPPLIER_STATUSES = ['RECEIVING', 'INCOMING'];
    private const OTHER_VEHICLES = ['OTHERS', 'UNKNOWN', 'UNKOWN NO'];
    private const STATUSES = [
        'wholesales' => ['DISPATCH', 'RECEIVING', 'STOCK-BAL'],
        'industrial' => ['INCOMING', 'OUTGOING']
    ];
    // Stock movements of both record types are written under this module
    private const STOCK_MODULE = 'wholesales';

    private string $recordType;
    private array $userModuleAccess;
    private bool $stockEnabled;

    public function __construct(\mysqli $db, int $company, int $user, string $role, string $recordType = 'wholesales', array $userModuleAccess = [], bool $stockEnabled = false)
    {
        parent::__construct($db, $company, $user, $role);
        $this->recordType = $recordType === 'industrial' ? 'industrial' : 'wholesales';
        $this->userModuleAccess = $userModuleAccess;
        $this->stockEnabled = $stockEnabled;
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    /**
     * Current user's add / edit / delete / price flags (from the users table)
     */
    public function getPermissions(): array
    {
        $row = $this->fetchOne("SELECT allow_add, allow_edit, allow_delete, allow_price FROM users WHERE id = ?", 'i', [$this->user]);

        return [
            'allowAdd' => ($row['allow_add'] ?? 'N') === 'Y',
            'allowEdit' => ($row['allow_edit'] ?? 'N') === 'Y',
            'allowDelete' => ($row['allow_delete'] ?? 'N') === 'Y',
            'allowPrice' => ($row['allow_price'] ?? 'N') === 'Y'
        ];
    }

    /**
     * Company feature flags used by the page (SADMIN gets everything except basket tare, as before).
     * Industrial reads the include_* columns of the companies table, wholesales the company_features table.
     */
    public function getFeatureFlags(): array
    {
        if ($this->isSuperAdmin()) {
            return [
                'photo' => true, 'price' => true, 'invoice' => true, 'payment' => $this->recordType === 'wholesales',
                'pcsBasket' => $this->recordType === 'wholesales', 'basketTare' => false, 'secRemark' => true
            ];
        }

        if ($this->recordType === 'industrial') {
            $company = $this->fetchOne("SELECT include_photo, include_price, include_invoice, include_sec_remark FROM companies WHERE id = ?", 'i', [$this->company]);

            return [
                'photo' => ($company['include_photo'] ?? 'N') === 'Y',
                'price' => ($company['include_price'] ?? 'N') === 'Y',
                'invoice' => ($company['include_invoice'] ?? 'N') === 'Y',
                'payment' => false,
                'pcsBasket' => false,
                'basketTare' => false,
                'secRemark' => ($company['include_sec_remark'] ?? 'N') === 'Y'
            ];
        }

        $features = [];
        foreach ($this->fetchAll("SELECT feature, value FROM company_features WHERE company = ?", 'i', [$this->company]) as $row) {
            $features[$row['feature']] = $row['value'];
        }
        $on = function ($key) use ($features) {
            return ($features[$key] ?? 'N') === 'Y';
        };

        return [
            'photo' => $on('include_photo'),
            'price' => $on('include_price'),
            'invoice' => $on('include_invoice'),
            'payment' => $on('include_payment'),
            'pcsBasket' => $on('include_pcs_basket'),
            'basketTare' => $on('allow_basket_tare'),
            'secRemark' => $on('include_sec_remark')
        ];
    }

    /**
     * Dropdown data for the filter / entry forms
     */
    public function getLookups(): array
    {
        $modules = "'" . implode("','", self::CATEGORY_MODULES[$this->recordType]) . "'";

        if ($this->isSuperAdmin()) {
            $categories = $this->fetchAll("SELECT id, category_name FROM categories WHERE deleted = '0' AND module IN ($modules) ORDER BY category_name ASC");
        } else {
            $categoryIds = $this->accessibleCategoryIds($this->userModuleAccess, self::CATEGORY_MODULES[$this->recordType]);
            [$categoryFilter, $categoryTypes] = $this->categoryIdFilter('id', $categoryIds);
            $categories = $this->fetchAll(
                "SELECT id, category_name FROM categories WHERE deleted = '0' AND customer = ? AND module IN ($modules)$categoryFilter ORDER BY category_name ASC",
                'i' . $categoryTypes,
                array_merge([$this->company], $categoryIds)
            );
        }

        $defaultCurrency = $this->fetchOne("SELECT id FROM currency WHERE deleted = 0 AND customer = ? AND is_default = 1", 'i', [$this->company]);

        return [
            'categories' => $categories,
            'products' => $this->recordType === 'industrial' ? $this->industrialProducts() : [],
            'customers' => $this->companyRows("SELECT id, customer_name, currency, customer_type FROM customers WHERE deleted = '0'", 'customer', 'customer_name'),
            'suppliers' => $this->companyRows("SELECT id, supplier_name, currency, supplier_type FROM supplies WHERE deleted = '0'", 'customer', 'supplier_name'),
            'vehicles' => $this->companyRows("SELECT veh_number FROM vehicles WHERE deleted = '0'", 'customer', 'veh_number'),
            'drivers' => $this->companyRows("SELECT driver_name FROM drivers WHERE deleted = '0'", 'customer', 'driver_name'),
            'users' => $this->companyRows("SELECT id, name FROM users WHERE deleted = '0'", 'customer', 'name'),
            'locations' => $this->companyRows("SELECT id, locations FROM locations WHERE deleted = '0'", 'customer', 'locations'),
            'currencies' => $this->companyRows("SELECT id, currency FROM currency WHERE deleted = '0'", 'customer', 'currency'),
            'indicators' => $this->companyRows("SELECT nickname FROM indicators WHERE 1 = 1", 'customer', 'nickname'),
            'defaultCurrencyId' => $defaultCurrency['id'] ?? null,
            'columnSetup' => $this->columnSetup()
        ];
    }

    /**
     * Paginated records for DataTables
     */
    public function getList(array $filters, int $start, int $length, string $orderColumn, string $orderDir, string $search): array
    {
        $from = "wholesales w
                 LEFT JOIN customers c ON w.customer = c.id LEFT JOIN customers cp ON c.parent = cp.id
                 LEFT JOIN supplies s ON w.supplier = s.id LEFT JOIN supplies sp ON s.parent = sp.id
                 LEFT JOIN users wu ON w.weighted_by = wu.id LEFT JOIN users mu ON w.modified_by = mu.id
                 LEFT JOIN locations l ON w.location = l.id";
        $where = "w.records_type = ?";
        $params = [$this->recordType];
        $types = 's';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        $totalRecords = $this->countRows('wholesales w', $where, $types, $params);

        $this->applyFilters($where, $params, $types, $filters);
        $this->applySearch($where, $params, $types, ['w.serial_no', 'w.po_no', 'w.vehicle_no', 'w.driver', 'c.customer_name', 's.supplier_name'], $search);
        $totalFiltered = $this->countRows($from, $where, $types, $params);

        $orderBy = $this->orderBy([
            'id' => 'w.id',
            'serial_no' => 'w.serial_no',
            'po_no' => 'w.po_no',
            'location' => 'l.locations',
            'security_bills' => 'w.security_bills',
            'start_time' => 'w.start_time',
            'end_time' => 'w.end_time',
            'parent' => 'cp.customer_name',
            'customer_supplier' => 'COALESCE(c.customer_name, s.supplier_name)',
            'vehicle_no' => 'w.vehicle_no',
            'driver' => 'w.driver',
            'total_item' => 'w.total_item',
            'total_weight' => 'w.total_weight',
            'total_price' => 'w.total_price',
            'total_reject' => 'w.total_reject',
            'weighted_by' => 'wu.name',
            'checked_by' => 'w.checked_by',
            'modified_by' => 'mu.name',
            'remarks2' => 'w.remarks2'
        ], $orderColumn, $orderDir, 'w.id');
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $rows = $this->fetchAll(
            "SELECT w.*, c.customer_name, cp.customer_name AS customer_parent, s.supplier_name, sp.supplier_name AS supplier_parent,
                    wu.name AS weighted_by_name, mu.name AS modified_by_name, l.locations AS location_name
             FROM $from WHERE $where ORDER BY $orderBy LIMIT ?, ?",
            $types,
            $params
        );

        $currencyNames = $this->currencyNames();
        $defaultCurrency = $this->fetchOne("SELECT currency FROM currency WHERE customer = ? AND is_default = 1 AND deleted = 0 LIMIT 1", 'i', [$this->company]);
        $defaultCurrencyName = $defaultCurrency['currency'] ?? 'MYR';
        $money = function ($value) {
            return number_format((float)$value, 2, '.', ',');
        };

        $data = [];
        foreach ($rows as $row) {
            $weightDetails = json_decode((string)$row['weight_details'], true) ?: [];
            $totals = ['gross' => 0, 'tare' => 0, 'net' => 0, 'variance' => 0, 'varPerc' => 0];
            $currencyTotals = [];
            foreach ($weightDetails as $detail) {
                foreach (array_keys($totals) as $key) {
                    $totals[$key] += floatval($detail[$key] ?? 0);
                }
                $currencyName = $currencyNames[$detail['currency'] ?? ''] ?? $defaultCurrencyName;
                $currencyTotals[$currencyName] = ($currencyTotals[$currencyName] ?? 0) + floatval($detail['total'] ?? 0);
            }

            $totalPrice = [];
            foreach ($currencyTotals as $currencyName => $amount) {
                $totalPrice[] = $currencyName . ' ' . $money($amount);
            }

            $item = [
                'id' => $row['id'],
                'indicator' => $row['indicator'],
                'serial_no' => $row['serial_no'],
                'security_bills' => $row['security_bills'] ?? '',
                'po_no' => $row['po_no'] ?? '',
                'status' => $row['status'],
                'parent' => (string)($this->isSupplierSide($row['status']) ? $row['supplier_parent'] : $row['customer_parent']),
                'customer_supplier' => $this->partyName($row),
                'vehicle_no' => $row['vehicle_no'],
                'driver' => $row['driver'] ?? '',
                'total_item' => $row['total_item'],
                'total_weight' => $money($row['total_weight']),
                'total_reject' => $money($row['total_reject']),
                'total_price' => implode('<br>', $totalPrice),
                'remark' => $row['remark'] ?? '',
                'created_datetime' => $row['created_datetime'],
                'start_time' => $row['start_time'] ? date('d/m/Y H:i', strtotime($row['start_time'])) : null,
                'end_time' => $row['end_time'] ? date('d/m/Y H:i', strtotime($row['end_time'])) : null,
                'created_by' => $row['created_by'],
                'company' => $row['company'],
                'weighted_by' => (string)$row['weighted_by_name'],
                'checked_by' => $row['checked_by'] === 'JACKY' ? '' : $row['checked_by'],
                'location' => (string)$row['location_name'],
                'modified_by' => (string)$row['modified_by_name'],
                'remarks2' => $row['remarks2'] ?? ''
            ];

            if ($this->recordType === 'industrial') {
                $item['total_gross'] = $money($totals['gross']);
                $item['total_tare'] = $money($totals['tare']);
                $item['total_nett'] = $money($totals['net']);
                $item['total_variance'] = $money($totals['variance']);
                $item['total_variance_perc'] = $money($totals['varPerc']);
            }

            $data[] = $item;
        }

        return ['totalRecords' => $totalRecords, 'totalFiltered' => $totalFiltered, 'data' => $data];
    }

    /**
     * Single record with names resolved and weight / reject rows (same shape as the legacy getWholesale.php)
     */
    public function getById(int $id): ?array
    {
        $row = $this->findRecord($id);
        if (!$row) {
            return null;
        }

        $names = $this->fetchOne(
            "SELECT c.customer_name, cp.customer_name AS customer_parent, s.supplier_name, sp.supplier_name AS supplier_parent,
                    l.locations AS location_name, u.name AS weighted_by_name, cat.category_name,
                    (SELECT COUNT(*) FROM vehicles v WHERE v.veh_number = w.vehicle_no AND v.customer = w.company AND v.deleted = 0) AS vehicle_count
             FROM wholesales w
             LEFT JOIN customers c ON w.customer = c.id LEFT JOIN customers cp ON c.parent = cp.id
             LEFT JOIN supplies s ON w.supplier = s.id LEFT JOIN supplies sp ON s.parent = sp.id
             LEFT JOIN locations l ON w.location = l.id LEFT JOIN users u ON w.weighted_by = u.id
             LEFT JOIN categories cat ON w.category = cat.id
             WHERE w.id = ?",
            'i',
            [$id]
        );

        $weightDetails = json_decode((string)$row['weight_details'], true) ?: [];
        $rejectDetails = json_decode((string)$row['reject_details'], true) ?: [];
        $productIds = array_column($weightDetails, 'product');
        $gradeIds = array_filter(array_column($weightDetails, 'grade_id'));
        $productNames = $this->namesById('products', 'product_name', $productIds);
        $gradeNames = $this->namesById('grades', 'units', $gradeIds, true);
        $currencyNames = $this->currencyNames();
        $currency = function (array $detail) use ($currencyNames) {
            $currencyId = $detail['currency'] ?? null;
            if (!$currencyId || $currencyId === '0' || $currencyId === 'null') {
                $currencyId = null;
            }
            return [$currencyId, $currencyId ? ($currencyNames[$currencyId] ?? '') : ''];
        };

        $totalWeight = 0;
        $totalPrice = 0;
        $totalGross = 0;
        $totalVariance = 0;
        foreach ($weightDetails as &$weight) {
            $weight['product_name'] = $productNames[$weight['product'] ?? ''] ?? '';
            [$weight['currency'], $weight['currency_name']] = $currency($weight);

            // Older rows stored the grade name only
            if (empty($weight['grade_id']) && !empty($weight['grade'])) {
                $grade = $this->fetchOne("SELECT id FROM grades WHERE units = ? AND customer = ? AND deleted = 0 LIMIT 1", 'si', [$weight['grade'], (int)$row['company']]);
                $weight['grade_id'] = $grade ? (int)$grade['id'] : null;
            } elseif (!empty($weight['grade_id'])) {
                $weight['grade'] = $gradeNames[$weight['grade_id']] ?? '';
            }

            $totalWeight += floatval($weight['net'] ?? 0);
            $totalPrice += floatval($weight['total'] ?? 0);
            $totalGross += floatval($weight['gross'] ?? 0);
            $totalVariance += floatval($weight['variance'] ?? 0);
        }
        unset($weight);

        $totalReject = 0;
        foreach ($rejectDetails as &$reject) {
            [$reject['currency'], $reject['currency_name']] = $currency($reject);
            $totalReject += floatval($reject['net'] ?? 0);
        }
        unset($reject);

        return [
            'id' => $row['id'],
            'serial_no' => $row['serial_no'],
            'security_bills' => $row['security_bills'],
            'po_no' => $row['po_no'],
            'status' => $row['status'],
            'customer' => $row['customer'],
            'supplier' => $row['supplier'],
            'product' => $row['product'] ?? null,
            'package' => $row['package'] ?? null,
            'location' => $row['location'],
            'location_name' => (string)($names['location_name'] ?? ''),
            'vehicle_no' => $row['vehicle_no'],
            'other_vehicle' => (int)($names['vehicle_count'] ?? 0) === 0,
            'driver' => $row['driver'],
            'other_customer' => $row['other_customer'],
            'other_supplier' => $row['other_supplier'],
            'units' => $row['units'] ?? null,
            'total_item' => $row['total_item'],
            'total_weight' => $row['total_weight'],
            'total_reject' => $row['total_reject'],
            'total_price' => $row['total_price'],
            'weighted_by' => (string)($names['weighted_by_name'] ?? ''),
            'checked_by' => $row['checked_by'],
            'remark' => $row['remark'],
            'remarks2' => $row['remarks2'],
            'created_datetime' => $row['created_datetime'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'records_type' => $row['records_type'],
            'payment_method' => $row['payment_method'],
            'category' => $row['category'],
            'category_name' => (string)($names['category_name'] ?? ''),
            'empty_baskets_weight' => $row['empty_baskets_weight'],
            'basket_count' => $row['basket_count'],
            'avg_basket_weight' => $row['avg_basket_weight'],
            'type' => $row['type'] ?? 'Local',
            'customer_supplier' => $this->partyName($row + (array)$names),
            'parent' => (string)($this->isSupplierSide($row['status']) ? ($names['supplier_parent'] ?? '') : ($names['customer_parent'] ?? '')),
            'weightDetails' => $weightDetails,
            'rejectDetails' => $rejectDetails,
            'totalItems' => count($weightDetails),
            'totalWeight' => $totalWeight,
            'totalPrice' => $totalPrice,
            'totalReject' => $totalReject,
            'totalGross' => $totalGross,
            'totalNett' => $totalWeight,
            'totalVariance' => $totalVariance,
            'indicator' => $row['indicator']
        ];
    }

    /**
     * Create or update a record with its running numbers, photos and raw stock movements (one transaction).
     * $header: wholesales column => value; $weights / $rejects: detail rows with 'photoFile' => uploaded file|null.
     */
    public function save(int $id, array $header, array $weights, array $rejects): array
    {
        $existing = null;
        if ($id) {
            $existing = $this->findRecord($id);
            if (!$existing) {
                return ['status' => 'failed', 'message' => 'Record not found'];
            }
        }
        $recordCompany = $existing ? (int)$existing['company'] : $this->company;

        if (!in_array($header['status'], self::STATUSES[$this->recordType], true)) {
            return ['status' => 'failed', 'message' => 'Invalid status'];
        }
        if ($this->recordType === 'industrial' && empty($header['vehicle_no'])) {
            $header['vehicle_no'] = '-';
        }

        // Photo IDs posted back must belong to this record
        $knownPhotos = [];
        if ($existing) {
            foreach (array_merge(json_decode((string)$existing['weight_details'], true) ?: [], json_decode((string)$existing['reject_details'], true) ?: []) as $detail) {
                if (!empty($detail['photoPath'])) {
                    $knownPhotos[(string)$detail['photoPath']] = true;
                }
            }
        }

        $oldPhotos = [];
        $this->db->begin_transaction();

        try {
            $weights = $this->storePhotos($weights, $knownPhotos, $recordCompany, $oldPhotos);
            $rejects = $this->storePhotos($rejects, $knownPhotos, $recordCompany, $oldPhotos);

            $totalWeight = 0;
            $totalPrice = 0;
            foreach ($weights as $weight) {
                $totalWeight += floatval($weight['net']);
                $totalPrice += floatval($weight['total']);
            }
            $totalReject = 0;
            foreach ($rejects as $reject) {
                $totalReject += floatval($reject['net']);
            }

            $runningNo = null;
            if (($header['po_no'] ?? null) === null) {
                $runningNo = $this->nextDoPoNo($header, $recordCompany);
                $header['po_no'] = $runningNo['doPoNo'];
            }

            $data = $header + [
                'weight_details' => json_encode($weights),
                'reject_details' => json_encode($rejects),
                'total_item' => (string)count($weights),
                'total_weight' => (string)$totalWeight,
                'total_reject' => (string)$totalReject,
                'total_price' => (string)$totalPrice
            ];

            if ($existing) {
                if (!$this->updateRow('wholesales', $data + ['modified_by' => $this->user], "id = ?", 'i', [$id])) {
                    throw new \Exception('Failed to update wholesales');
                }
            } else {
                $id = $this->insertRow('wholesales', $data + [
                    'serial_no' => $this->nextSerialNo($header['status'], $header['start_time']),
                    'created_by' => $this->user,
                    'company' => $recordCompany,
                    'weighted_by' => $this->user,
                    'indicator' => 'web',
                    'records_type' => $this->recordType
                ]);
                if (!$id) {
                    throw new \Exception('Failed to insert wholesales');
                }
            }

            if ($runningNo !== null) {
                $this->advanceDoPoNo($runningNo, $header['status'], $recordCompany);
            }

            if ($this->stockEnabled) {
                $this->syncRawStock($id, $recordCompany, $existing, $data);
            }

            $this->db->commit();
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            return ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('WholesaleService::save - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to save record'];
        }

        foreach ($oldPhotos as $oldPhoto) {
            deleteOldFile($oldPhoto, $this->db, 'photoPath');
        }

        return ['status' => 'success', 'message' => $existing ? 'Updated Successfully!!' : 'Added Successfully!!'];
    }

    /**
     * Soft delete a record with a reason and reverse its raw stock
     */
    public function cancel(int $id, string $reason): array
    {
        $record = $this->findRecord($id);
        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        $this->db->begin_transaction();

        try {
            if (!$this->executeWrite("UPDATE wholesales SET deleted = '1', delete_reason = ?, modified_by = ? WHERE id = ?", 'sii', [$reason, $this->user, $id])) {
                throw new \Exception('Failed to delete wholesales');
            }

            if ($this->stockEnabled) {
                $result = processDeleteRawStock($this->db, $id, self::STOCK_MODULE, $record['company'], $this->user, $record['type'] ?? 'Local');
                if (($result['status'] ?? '') !== 'success') {
                    throw new \Exception('Failed to reverse raw stock: ' . ($result['message'] ?? ''));
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('WholesaleService::cancel - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Failed to delete record'];
        }

        return ['status' => 'success', 'message' => 'Deleted'];
    }

    /**
     * Records for the report page exports: the selected $ids, otherwise the active records matching the report filters.
     * $dateColumn is the column the from / to dates apply to (the integration export uses created_datetime).
     */
    public function getReportRecords(array $filters, array $ids, string $dateColumn = 'start_time'): array
    {
        $where = "w.records_type = ?";
        $params = [$this->recordType];
        $types = 's';
        $this->applyCompanyScope($where, $params, $types, 'w.company');

        // Ticked records in ID order, filtered records by weighing time
        $ids = $this->cleanIds($ids);
        if (!empty($ids)) {
            $where .= " AND w.id IN (" . $this->placeholders(count($ids)) . ")";
            $params = array_merge($params, $ids);
            $types .= str_repeat('i', count($ids));
            $orderBy = 'w.id';
        } else {
            $where .= " AND w.deleted = '0'";
            $this->applyFilters($where, $params, $types, $filters, $dateColumn === 'created_datetime' ? 'w.created_datetime' : 'w.start_time');
            $orderBy = 'w.start_time, w.id';
        }

        return $this->fetchAll(
            "SELECT w.* FROM wholesales w LEFT JOIN customers c ON w.customer = c.id LEFT JOIN supplies s ON w.supplier = s.id
             WHERE $where ORDER BY $orderBy",
            $types,
            $params
        );
    }

    /**
     * Active records (both record types) of the session company for the stock balance report.
     * Filters: asAtDate (that day only; all dates when empty), location, category, product, type (Local / Export).
     */
    public function getStockBalanceRecords(array $filters): array
    {
        $where = "deleted = '0' AND company = ?";
        $params = [$this->company];
        $types = 'i';

        $date = \DateTime::createFromFormat('d/m/Y', $filters['asAtDate'] ?? '');
        if ($date) {
            $where .= " AND start_time >= ? AND start_time <= ?";
            $params[] = $date->format('Y-m-d 00:00:00');
            $params[] = $date->format('Y-m-d 23:59:59');
            $types .= 'ss';
        }

        if (($filters['location'] ?? '') !== '' && $filters['location'] !== '-') {
            $where .= " AND location = ?";
            $params[] = $filters['location'];
            $types .= 's';
        }

        $category = (string)($filters['category'] ?? '');
        $this->applyProductCategoryFilter($where, $params, $types, $category === '-' ? '' : $category, 'weight_details');

        if (($filters['product'] ?? '') !== '' && $filters['product'] !== '-') {
            $where .= " AND weight_details LIKE ?";
            $params[] = '%"product":"' . $filters['product'] . '"%';
            $types .= 's';
        }

        if (in_array($filters['type'] ?? '', ['Local', 'Export'], true)) {
            $where .= " AND type = ?";
            $params[] = $filters['type'];
            $types .= 's';
        }

        return $this->fetchAll("SELECT * FROM wholesales WHERE $where ORDER BY id", $types, $params);
    }

    /**
     * Active record of this record type within the session company (any company for SADMIN)
     */
    private function findRecord(int $id): ?array
    {
        $where = "id = ? AND deleted = '0' AND records_type = ?";
        $params = [$id, $this->recordType];
        $types = 'is';
        $this->applyCompanyScope($where, $params, $types, 'company');

        return $this->fetchOne("SELECT * FROM wholesales WHERE $where", $types, $params);
    }

    /**
     * Upload each row's new photo; keeps only photo IDs already on the record. Replaced photos are added to $oldPhotos.
     */
    private function storePhotos(array $details, array $knownPhotos, int $company, array &$oldPhotos): array
    {
        foreach ($details as $index => $detail) {
            $photoPath = isset($knownPhotos[$detail['photoPath']]) ? $detail['photoPath'] : '';

            if (!empty($detail['photoFile'])) {
                $upload = uploadFile($detail['photoFile'], 'photo', (string)$company, $this->db, 'photoPath');
                if ($upload['status'] !== 'success' || !$upload['fid']) {
                    throw new \RuntimeException($upload['message'] ?? 'Failed to upload photo');
                }
                if ($photoPath !== '') {
                    $oldPhotos[] = $photoPath;
                }
                $photoPath = (string)$upload['fid'];
            }

            unset($detail['photoFile']);
            $detail['photoPath'] = $photoPath;
            $details[$index] = $detail;
        }

        return $details;
    }

    private function applyFilters(string &$where, array &$params, string &$types, array $filters, string $dateColumn = 'w.start_time'): void
    {
        $add = function (string $condition, $value, string $type = 's') use (&$where, &$params, &$types) {
            $where .= " AND $condition";
            $params[] = $value;
            $types .= $type;
        };

        foreach (['fromDate' => ['>=', '00:00:00'], 'toDate' => ['<=', '23:59:59']] as $key => [$operator, $time]) {
            $date = \DateTime::createFromFormat('d/m/Y', $filters[$key] ?? '');
            if ($date) {
                $add("$dateColumn $operator ?", $date->format('Y-m-d') . ' ' . $time);
            }
        }

        $columns = [
            'transactionStatus' => 'w.status',
            'product' => 'w.product',
            'customer' => 'w.customer',
            'supplier' => 'w.supplier',
            'checkedBy' => 'w.checked_by',
            'weightedBy' => 'w.weighted_by',
            'location' => 'w.location',
            'indicator' => 'w.indicator'
        ];
        foreach ($columns as $key => $column) {
            if (($filters[$key] ?? '') !== '' && $filters[$key] !== '-') {
                $add("$column = ?", $filters[$key]);
            }
        }

        $vehicle = $filters['vehicle'] ?? '';
        if (in_array($vehicle, self::OTHER_VEHICLES, true)) {
            $vehicle = $filters['otherVehicle'] ?? '';
        }
        if ($vehicle !== '' && $vehicle !== '-') {
            $add("w.vehicle_no = ?", $vehicle);
        }

        if (($filters['partyType'] ?? '') !== '') {
            $where .= " AND (c.customer_type = ? OR s.supplier_type = ?)";
            $params[] = $filters['partyType'];
            $params[] = $filters['partyType'];
            $types .= 'ss';
        }

        // active / deleted; anything else lists both (as before)
        if (($filters['status'] ?? '') === 'active') {
            $where .= " AND w.deleted = '0'";
        } elseif (($filters['status'] ?? '') === 'deleted') {
            $where .= " AND w.deleted = '1'";
        }

        $this->applyProductCategoryFilter($where, $params, $types, (string)($filters['category'] ?? ''));
    }

    /**
     * Records whose weight rows hold a product of the chosen category, otherwise of the user's accessible categories
     */
    private function applyProductCategoryFilter(string &$where, array &$params, string &$types, string $category, string $column = 'w.weight_details'): void
    {
        if ($category !== '') {
            $products = $this->fetchAll("SELECT id FROM products WHERE category = ? AND deleted = '0'", 'i', [(int)$category]);
        } else {
            $categoryIds = $this->accessibleCategoryIds($this->userModuleAccess, self::CATEGORY_MODULES[$this->recordType]);
            if (empty($categoryIds)) {
                return;
            }
            $products = $this->fetchAll(
                "SELECT id FROM products WHERE deleted = '0' AND category IN (" . $this->placeholders(count($categoryIds)) . ")",
                str_repeat('i', count($categoryIds)),
                $categoryIds
            );
        }

        if (empty($products)) {
            $where .= " AND 1 = 0";
            return;
        }

        // Product IDs are stored as JSON strings, e.g. "product":"12"
        $likes = [];
        foreach ($products as $product) {
            $likes[] = "$column LIKE ?";
            $params[] = '%"product":"' . $product['id'] . '"%';
            $types .= 's';
        }
        $where .= " AND (" . implode(' OR ', $likes) . ")";
    }

    private function isSupplierSide(string $status): bool
    {
        return in_array($status, self::SUPPLIER_STATUSES, true);
    }

    /**
     * Customer / supplier name of a record row joined with customer_name / supplier_name ("OTHERS" uses the typed name)
     */
    private function partyName(array $row): string
    {
        if ($this->isSupplierSide($row['status'])) {
            return (string)($row['supplier'] === 'OTHERS' ? $row['other_supplier'] : ($row['supplier_name'] ?? ''));
        }

        return (string)($row['customer'] === 'OTHERS' ? $row['other_customer'] : ($row['customer_name'] ?? ''));
    }

    /**
     * Rows of a master table for the session company (all companies for SADMIN), ordered by $orderColumn
     */
    private function companyRows(string $select, string $companyColumn, string $orderColumn): array
    {
        if ($this->isSuperAdmin()) {
            return $this->fetchAll("$select ORDER BY $orderColumn ASC");
        }

        return $this->fetchAll("$select AND $companyColumn = ? ORDER BY $orderColumn ASC", 'i', [$this->company]);
    }

    /**
     * Industrial entry products (with ok_weight for the variance): products of industrial categories, limited to the
     * daily sales states when that setup is enabled for industrial; all company products when none match (as before)
     */
    private function industrialProducts(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->fetchAll("SELECT id, product_name, ok_weight FROM products WHERE deleted = '0' ORDER BY product_name ASC");
        }

        $stateFilter = '';
        $params = [$this->company];
        $types = 'i';
        $company = $this->fetchOne("SELECT enable_daily_sales_setup, daily_sales_modules FROM companies WHERE id = ?", 'i', [$this->company]);
        $dailySalesModules = json_decode((string)($company['daily_sales_modules'] ?? ''), true);
        if (($company['enable_daily_sales_setup'] ?? '') === 'Y' && is_array($dailySalesModules) && in_array('industrial', $dailySalesModules)) {
            $states = [];
            foreach ($this->fetchAll("SELECT state FROM daily_sales_setup WHERE module = 'industrial' AND company = ? AND deleted = 0", 'i', [$this->company]) as $row) {
                $decoded = json_decode((string)$row['state'], true);
                if (is_array($decoded)) {
                    $states = array_merge($states, $decoded);
                }
            }
            if (!empty($states)) {
                $stateFilter = " AND JSON_OVERLAPS(p.state, ?)";
                $params[] = json_encode(array_values($states));
                $types .= 's';
            }
        }

        $products = $this->fetchAll(
            "SELECT p.id, p.product_name, p.ok_weight FROM products p INNER JOIN categories c ON p.category = c.id
             WHERE p.deleted = '0' AND p.customer = ? AND c.module = 'industrial' AND c.deleted = '0'$stateFilter ORDER BY p.product_name ASC",
            $types,
            $params
        );
        if (empty($products)) {
            $products = $this->fetchAll("SELECT id, product_name, ok_weight FROM products WHERE deleted = '0' AND customer = ? ORDER BY product_name ASC", 'i', [$this->company]);
        }

        return $products;
    }

    private function columnSetup(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        $company = $this->fetchOne("SELECT column_setup FROM companies WHERE id = ?", 'i', [$this->company]);
        $setup = json_decode((string)($company['column_setup'] ?? ''), true);
        $key = $this->recordType === 'wholesales' ? 'wholesale' : $this->recordType;

        return $setup[$key]['columns'] ?? [];
    }

    private function currencyNames(): array
    {
        $names = [];
        foreach ($this->fetchAll("SELECT id, currency FROM currency") as $row) {
            $names[$row['id']] = $row['currency'];
        }

        return $names;
    }

    /**
     * id => name for the given IDs of a table
     */
    private function namesById(string $table, string $column, array $ids, bool $activeOnly = false): array
    {
        $ids = array_values(array_unique($this->cleanIds($ids)));
        if (empty($ids)) {
            return [];
        }

        $names = [];
        $rows = $this->fetchAll(
            "SELECT id, $column FROM $table WHERE id IN (" . $this->placeholders(count($ids)) . ")" . ($activeOnly ? " AND deleted = 0" : ''),
            str_repeat('i', count($ids)),
            $ids
        );
        foreach ($rows as $row) {
            $names[$row['id']] = $row[$column];
        }

        return $names;
    }

    /**
     * Serial no: prefix + start date (Ymd) + 4-digit count, unique across the table
     */
    private function nextSerialNo(string $status, string $startTime): string
    {
        if ($this->recordType === 'industrial') {
            $prefix = $status === 'INCOMING' ? 'I' : 'O';
        } else {
            $prefixes = ['DISPATCH' => 'S', 'RECEIVING' => 'P', 'NITROGEN' => 'N', 'REJECT' => 'REJ'];
            $prefix = $prefixes[$status] ?? 'SB';
        }

        return $this->nextRunningNo('wholesales', 'serial_no', $prefix . date('Ymd', strtotime($startTime)), null);
    }

    /**
     * DO / PO no from the company's running number setup. Returns the number and what to advance after saving.
     * running_no_type 1: per customer / supplier (running_no_entity), e.g. D-INV-2506/001
     * otherwise: running_no_setup per record type and status, e.g. DO25000012
     */
    private function nextDoPoNo(array $header, int $company): array
    {
        $companyRow = $this->fetchOne("SELECT running_no_type FROM companies WHERE id = ?", 'i', [$company]);
        $status = $header['status'];

        if ((int)($companyRow['running_no_type'] ?? 0) === 1) {
            $isSupplier = $this->isSupplierSide($status);
            $entityId = (int)($isSupplier ? $header['supplier'] : $header['customer']);
            $entityType = $isSupplier ? 'Supplier' : 'Customer';
            $entityTable = $isSupplier ? 'supplies' : 'customers';
            $codeField = $isSupplier ? 'supplier_code' : 'customer_code';
            $module = $this->runningNoModule();

            $entityRow = $this->fetchOne(
                "SELECT prefix, value FROM running_no_entity WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?",
                'isis',
                [$company, $module, $entityId, $status]
            );
            $entity = $this->fetchOne("SELECT invoice_code, $codeField AS code FROM $entityTable WHERE id = ? AND customer = ?", 'ii', [$entityId, $company]);

            if ($entityRow) {
                $prefix = (string)$entityRow['prefix'];
                $value = (int)$entityRow['value'];
                $invoiceCode = (string)($entity['invoice_code'] ?? '');
            } else {
                $statusRow = $this->fetchOne("SELECT prefix FROM statuses WHERE module = ? AND status = ? AND entity_type = ?", 'sss', [$module, $status, $entityType]);
                $prefix = (string)($statusRow['prefix'] ?? '');
                $value = 1;
                $invoiceCode = (string)(!empty($entity['invoice_code']) ? $entity['invoice_code'] : ($entity['code'] ?? ''));
            }

            $number = date('ym') . '/' . str_pad((string)$value, 3, '0', STR_PAD_LEFT);
            $doPoNo = $invoiceCode !== '' ? $prefix . '-' . $invoiceCode . '-' . $number : $prefix . '-' . $number;

            return [
                'doPoNo' => $doPoNo,
                'type' => 'entity',
                'exists' => (bool)$entityRow,
                'value' => $value,
                'prefix' => $prefix,
                'module' => $module,
                'entityId' => $entityId,
                'entityTable' => $entityTable,
                'codeField' => $codeField
            ];
        }

        $setup = $this->fetchOne(
            "SELECT name, value FROM running_no_setup WHERE module = ? AND company_id = ? AND transaction_status = ?",
            'sis',
            [$this->recordType, $company, $status]
        );
        $value = $setup ? (int)$setup['value'] : 1;

        return [
            'doPoNo' => ($setup['name'] ?? '') . date('y') . str_pad((string)$value, 6, '0', STR_PAD_LEFT),
            'type' => 'setup',
            'exists' => (bool)$setup,
            'value' => $value
        ];
    }

    private function advanceDoPoNo(array $runningNo, string $status, int $company): void
    {
        $next = (string)($runningNo['value'] + 1);

        if ($runningNo['type'] === 'setup') {
            if ($runningNo['exists'] && !$this->executeWrite(
                "UPDATE running_no_setup SET value = ? WHERE module = ? AND company_id = ? AND transaction_status = ?",
                'ssis',
                [$next, $this->recordType, $company, $status]
            )) {
                throw new \Exception('Failed to update running_no_setup');
            }
            return;
        }

        if ($runningNo['exists']) {
            if (!$this->executeWrite(
                "UPDATE running_no_entity SET value = ? WHERE company_id = ? AND module = ? AND entity_id = ? AND transaction_status = ?",
                'sisis',
                [$next, $company, $runningNo['module'], $runningNo['entityId'], $status]
            )) {
                throw new \Exception('Failed to update running_no_entity');
            }
            return;
        }

        if (!$this->insertRow('running_no_entity', [
            'company_id' => $company,
            'module' => $runningNo['module'],
            'transaction_status' => $status,
            'entity_id' => $runningNo['entityId'],
            'prefix' => $runningNo['prefix'],
            'value' => $next
        ])) {
            throw new \Exception('Failed to insert running_no_entity');
        }

        // Entities without an invoice code start using their customer / supplier code
        if (!$this->executeWrite(
            "UPDATE {$runningNo['entityTable']} SET invoice_code = {$runningNo['codeField']} WHERE id = ? AND customer = ? AND (invoice_code IS NULL OR invoice_code = '')",
            'ii',
            [$runningNo['entityId'], $company]
        )) {
            throw new \Exception('Failed to update entity invoice code');
        }
    }

    private function runningNoModule(): string
    {
        return $this->recordType === 'wholesales' ? 'wholesale' : $this->recordType;
    }

    /**
     * Bring raw stock in line with the saved record.
     * Same status and type: adjust each product / grade by its net change (rows removed from the form go to 0).
     * Status or type changed: zero what the old record applied, then apply the new record.
     * Zeroing (instead of reversing) leaves a 0-qty latest movement, so a later delete cannot reverse twice.
     */
    private function syncRawStock(int $id, int $company, ?array $existing, array $data): void
    {
        $before = $existing ? $this->appliedWeights($existing) : [];
        $after = $this->appliedWeights($data);
        $type = (string)$data['type'];

        if (!$existing || ($existing['status'] === $data['status'] && ($existing['type'] ?? 'Local') === $type)) {
            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                $beforeNet = $before[$key]['net'] ?? 0;
                $afterNet = $after[$key]['net'] ?? 0;
                if (floatval($afterNet) == floatval($beforeNet)) {
                    continue;
                }
                $group = $after[$key] ?? $before[$key];
                $this->moveRawStock($group, $company, $afterNet, $data, isset($before[$key]), $beforeNet, $id);
            }
            return;
        }

        foreach ($before as $group) {
            $this->moveRawStock($group, $company, 0, $existing, true, $group['net'], $id);
        }
        foreach ($after as $group) {
            $this->moveRawStock($group, $company, $group['net'], $data, false, 0, $id);
        }
    }

    private function moveRawStock(array $group, int $company, $net, array $record, bool $isEdit, $beforeNet, int $id): void
    {
        $result = processRawStock(
            $this->db, $group['product'], $group['grade_id'], $company, $net, $this->user, $record['status'],
            $isEdit, $beforeNet, $id, self::STOCK_MODULE, $record['customer'], $record['supplier'], $record['type'] ?? 'Local'
        );
        if (($result['status'] ?? '') !== 'success') {
            throw new \Exception('Failed to update raw stock: ' . ($result['message'] ?? ''));
        }
    }

    /**
     * Net weight per product / grade that a record puts on raw stock (none for Packing / non-Normal parties)
     */
    private function appliedWeights(array $record): array
    {
        $isSupplier = $this->isSupplierSide($record['status']);
        $party = $isSupplier
            ? $this->fetchOne("SELECT supplier_type AS party_type FROM supplies WHERE id = ?", 's', [(string)$record['supplier']])
            : $this->fetchOne("SELECT customer_type AS party_type FROM customers WHERE id = ?", 's', [(string)$record['customer']]);
        if (($party['party_type'] ?? '') !== 'Normal') {
            return [];
        }

        $grouped = [];
        foreach (json_decode((string)$record['weight_details'], true) ?: [] as $detail) {
            $key = ($detail['product'] ?? '') . '_' . ($detail['grade_id'] ?? '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['product' => $detail['product'] ?? '', 'grade_id' => $detail['grade_id'] ?? '', 'net' => 0];
            }
            $grouped[$key]['net'] += floatval($detail['net'] ?? 0);
        }

        return $grouped;
    }
}
