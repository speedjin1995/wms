<?php
namespace App\Modules\Grading;

use App\Core\BaseController;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GradingController extends BaseController
{
    private GradingService $service;
    private ?GradingReportService $reportService;

    public function __construct(GradingService $service, ?GradingReportService $reportService = null)
    {
        $this->service = $service;
        $this->reportService = $reportService;
    }

    /**
     * DataTables server-side list
     */
    public function list(): array
    {
        $p = $this->dataTableParams('grading_no');

        try {
            $result = $this->service->getList($this->filters($_POST), $p['start'], $p['length'], $p['orderColumn'], $p['orderDir'], $p['search']);
        } catch (\Exception $e) {
            error_log('GradingController::list - ' . $e->getMessage());
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
            error_log('GradingController::get - ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Something went wrong'];
        }

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Record not found'];
        }

        return ['status' => 'success', 'message' => $record];
    }

    /**
     * Create or update record with weight / reject items and photos (requires allow_add / allow_edit)
     */
    public function save(): array
    {
        $id = $this->postId();
        $permissions = $this->service->getPermissions();

        if (($id && !$permissions['allowEdit']) || (!$id && !$permissions['allowAdd'])) {
            return ['status' => 'failed', 'message' => 'You do not have permission to perform this action'];
        }

        $startDate = $this->postDateTime('startTime');
        $location = $this->postId('location');

        if ($startDate === null || !$location) {
            return ['status' => 'failed', 'message' => 'Please fill in all the fields'];
        }

        $category = $this->postId('category');

        $header = [
            'location' => $location,
            'start_date' => $startDate,
            'end_date' => $this->postDateTime('endTime'),
            'product_category' => $category ?: null,
            'remark' => $this->optionalText('remarks')
        ];

        $weights = $this->postItems('weightDetails', 'photoFiles', false);
        $rejects = $this->postItems('rejectDetails', 'rejectPhotoFiles', true);

        if ($weights === null || $rejects === null) {
            return ['status' => 'failed', 'message' => 'Please complete product, grade and weights for every row'];
        }

        return $this->service->save($id, $header, array_merge($weights, $rejects));
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
     * Grading print HTML
     */
    public function printSlip(): array
    {
        $id = $this->postId();
        $data = $id ? $this->service->getPrintData($id) : null;

        if (!$data) {
            return ['status' => 'failed', 'message' => 'Data Not Found'];
        }

        return ['status' => 'success', 'message' => $this->reportService->buildPrintHtml($data, ($_POST['withPhoto'] ?? 'N') === 'Y')];
    }

    /**
     * Stream the grading report as an Excel download (GET filters, or isMulti=Y with ids)
     */
    public function exportExcel(): void
    {
        [$fromDate, $toDate] = $this->reportDates();
        $spreadsheet = $this->reportService->buildExcel($this->reportRows(), $fromDate, $toDate);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Grading_Report_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
    }

    /**
     * Stream the grading report as a PDF download (GET filters, or isMulti=Y with ids)
     */
    public function exportPdf(): void
    {
        [$fromDate, $toDate] = $this->reportDates();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => sys_get_temp_dir(),
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'fontDir' => [dirname(__DIR__, 4) . '/vendor/mpdf/mpdf/ttfonts/'],
            'fontdata' => ['sunexta' => ['R' => 'Sun-ExtA.ttf']],
            'default_font' => 'sunexta'
        ]);
        $mpdf->WriteHTML($this->reportService->buildPdfHtml($this->reportRows(), $fromDate, $toDate));
        $mpdf->Output('Grading_Report_' . date('Y-m-d') . '.pdf', 'D');
    }

    /**
     * List / report filter values from a request array
     */
    public function filters(array $source): array
    {
        $filters = [];
        foreach (['fromDate', 'toDate', 'category', 'location'] as $key) {
            $filters[$key] = (string)($source[$key] ?? '');
        }

        return $filters;
    }

    private function reportRows(): array
    {
        $ids = (($_GET['isMulti'] ?? '') === 'Y') ? explode(',', (string)($_GET['ids'] ?? '')) : [];

        return $this->service->getReportRows($this->filters($_GET), $ids);
    }

    /**
     * Valid DD/MM/YYYY report dates (blank when missing)
     */
    private function reportDates(): array
    {
        $dates = [];
        foreach (['fromDate', 'toDate'] as $key) {
            $date = \DateTime::createFromFormat('d/m/Y', (string)($_GET[$key] ?? ''));
            $dates[] = $date ? $date->format('d/m/Y') : '';
        }

        return $dates;
    }

    /**
     * Weight / reject rows from POST with their uploaded photo (by row index). Null when a row is incomplete.
     */
    private function postItems(string $key, string $fileKey, bool $isReject): ?array
    {
        $rows = $_POST[$key] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $index => $row) {
            $product = (int)($row['product'] ?? 0);
            $grade = $isReject ? 'REJ' : trim((string)($row['to_grade'] ?? ''));
            $gross = trim((string)($row['gross'] ?? ''));
            $tare = trim((string)($row['tare'] ?? ''));
            $time = trim((string)($row['time'] ?? ''));

            if (!$product || $grade === '' || !is_numeric($gross) || !is_numeric($tare)) {
                return null;
            }
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                $time = date('H:i:s');
            }

            $items[] = [
                'gradingItemId' => (int)($row['gradingItemId'] ?? 0),
                'product' => $product,
                'grade' => $grade,
                'gross' => $gross,
                'tare' => $tare,
                'time' => strlen($time) === 5 ? $time . ':00' : $time,
                'photo' => $this->uploadedFile($fileKey, $index)
            ];
        }

        return $items;
    }
}
