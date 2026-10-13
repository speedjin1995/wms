<?php
namespace App\Modules\Wholesale;

use App\Core\BaseController;

class BulkPriceUpdateController extends BaseController
{
    private const PRICING_TYPES = ['Float', 'Fixed'];

    private BulkPriceUpdateService $service;

    public function __construct(BulkPriceUpdateService $service)
    {
        $this->service = $service;
    }

    /**
     * DataTables server-side list of matching records
     */
    public function list(): array
    {
        $p = $this->dataTableParams('start_time', 'asc');

        try {
            $result = $this->service->getList($this->filters(), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('BulkPriceUpdateController::list - ' . $e->getMessage());
            $result = $this->emptyListResult();
        }

        return $this->dataTableResponse($p['draw'], $result);
    }

    /**
     * Product / grade pairs for the price input step
     */
    public function productGrades(): array
    {
        try {
            return ['status' => 'success', 'message' => $this->service->getProductGrades($this->filters())];
        } catch (\Exception $e) {
            error_log('BulkPriceUpdateController::productGrades - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
    }

    /**
     * Rows the update would change, with old and new values
     */
    public function preview(): array
    {
        $priceRows = $this->priceRows();
        if (empty($priceRows)) {
            return ['status' => 'failed', 'message' => 'No price rows provided.'];
        }

        try {
            return ['status' => 'success', 'message' => $this->service->preview($this->filters(), $priceRows)];
        } catch (\Exception $e) {
            error_log('BulkPriceUpdateController::preview - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }
    }

    /**
     * Apply the new prices (requires allow_edit, same as the page's Update Price button)
     */
    public function update(): array
    {
        if (!$this->service->getPermissions()['allowEdit']) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $priceRows = $this->priceRows();
        if (empty($priceRows)) {
            return ['status' => 'failed', 'message' => 'No price rows provided.'];
        }

        return $this->service->update($this->filters(), $priceRows);
    }

    private function filters(): array
    {
        return [
            'date' => trim((string)($_POST['date'] ?? '')),
            'status' => trim((string)($_POST['status'] ?? '')),
            'customer' => trim((string)($_POST['customer'] ?? '')),
            'supplier' => trim((string)($_POST['supplier'] ?? '')),
            'product' => trim((string)($_POST['product'] ?? '')),
            'grade' => trim((string)($_POST['grade'] ?? ''))
        ];
    }

    /**
     * Posted new prices keyed by "productId||grade"; negative or non-numeric prices are skipped
     */
    private function priceRows(): array
    {
        $rows = $_POST['priceRows'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $priceRows = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_numeric($row['newPrice'] ?? '') || (float)$row['newPrice'] < 0) {
                continue;
            }

            $pricingType = (string)($row['pricingType'] ?? 'Float');
            $priceRows[($row['product'] ?? '') . '||' . ($row['grade'] ?? '')] = [
                'newPrice' => (float)$row['newPrice'],
                'pricingType' => in_array($pricingType, self::PRICING_TYPES, true) ? $pricingType : 'Float'
            ];
        }

        return $priceRows;
    }
}
