<?php
namespace App\Modules\PackagingBatch;

use App\Core\BaseController;

class PackagingBatchController extends BaseController
{
    private PackagingBatchService $service;
    private ?PackagingBatchReportService $reportService;
    private ?PackagingBatchDashboardService $dashboardService;

    public function __construct(
        PackagingBatchService $service,
        ?PackagingBatchReportService $reportService = null,
        ?PackagingBatchDashboardService $dashboardService = null
    ) {
        $this->service = $service;
        $this->reportService = $reportService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Packaging dashboard tab data (fromDate, toDate, location, productionLine)
     */
    public function dashboard(): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'location', 'productionLine'] as $key) {
            $filters[$key] = trim((string)($_POST[$key] ?? ''));
        }

        return ['status' => 'success', 'message' => $this->dashboardService->getSummary($filters)];
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('packaging_date');

        try {
            $result = $this->service->getList($this->filters($_POST), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('PackagingBatchController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Get single record with items
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        try {
            $record = $this->service->getById($id);
        } catch (\Exception $e) {
            error_log('PackagingBatchController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update record with weight items and photos (requires allow_add / allow_edit)
     */
    public function save(): array
    {
        $id = $this->postId();
        $permissions = $this->service->getPermissions();

        if (($id && !$permissions['allowEdit']) || (!$id && !$permissions['allowAdd'])) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $packagingDate = $this->postDateTime('packagingDate');
        $location = $this->postId('location');

        if ($packagingDate === null || !$location) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $productionLine = $this->postId('productionLines');
        $header = [
            'packaging_date' => $packagingDate,
            'location' => $location,
            'production_line' => $productionLine ?: null,
            'remarks' => $this->optionalText('remarks'),
            'label_remark' => $this->optionalText('labelSummary'),
            'type' => trim((string)($_POST['gradeType'] ?? 'Local'))
        ];

        $items = $this->postItems();
        if ($items === null) {
            return ['status' => 'failed', 'message' => 'Please complete category, product, grade, packaging size, unit per box and weights for every row'];
        }

        return $this->service->save($id, $header, $items);
    }

    /**
     * Cancel record with reason (requires allow_delete)
     */
    public function cancel(): array
    {
        if (!$this->service->getPermissions()['allowDelete']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $id = $this->postId();
        $reason = $this->postText('cancelReason');

        if (!$id || $reason === '') {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        return $this->service->cancel($id, $reason);
    }

    /**
     * Packing list print HTML
     */
    public function printSlip(): array
    {
        $id = $this->postId();
        $data = $id ? $this->service->getPrintData($id) : null;

        if (!$data) {
            return ['status' => 'failed', 'message' => 'Data Not Found'];
        }

        return ['status' => 'success', 'message' => $this->reportService->buildPrintHtml($data)];
    }

    /**
     * List filter values from a request array
     */
    public function filters(array $source): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'location', 'productionLine', 'category'] as $key) {
            $filters[$key] = (string)($source[$key] ?? '');
        }

        return $filters;
    }

    /**
     * Weight rows from POST with their uploaded photo (by row index). Null when a row is incomplete.
     */
    private function postItems(): ?array
    {
        $rows = $_POST['weightDetails'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $index => $row) {
            $category = (int)($row['category'] ?? 0);
            $product = (int)($row['product'] ?? 0);
            $grade = (int)($row['grade'] ?? 0);
            $packagingSize = (int)($row['packaging_size'] ?? 0);
            $unitPerBox = trim((string)($row['unit_per_box'] ?? ''));
            $gross = trim((string)($row['gross'] ?? ''));
            $tare = trim((string)($row['tare'] ?? ''));
            $tare = $tare === '' ? '0' : $tare;
            $time = trim((string)($row['time'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));

            if (!$category || !$product || !$grade || !$packagingSize || !ctype_digit($unitPerBox) || (int)$unitPerBox < 1
                || !is_numeric($gross) || !is_numeric($tare) || (float)$gross <= 0 || (float)$gross - (float)$tare <= 0) {
                return null;
            }
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                $time = date('H:i:s');
            }

            $items[] = [
                'batchItemId' => (int)($row['batchItemId'] ?? 0),
                'supplier' => (int)($row['supplier'] ?? 0),
                'category' => $category,
                'product' => $product,
                'grade' => $grade,
                'packaging_size' => $packagingSize,
                'label' => $label === '' ? null : mb_substr($label, 0, 100),
                'unit_per_box' => $unitPerBox,
                'gross' => $gross,
                'tare' => $tare,
                'weight' => number_format((float)$gross - (float)$tare, 2, '.', ''),
                'time' => strlen($time) === 5 ? $time . ':00' : $time,
                'photo' => $this->uploadedFile('photoFiles', $index)
            ];
        }

        return $items;
    }
}
