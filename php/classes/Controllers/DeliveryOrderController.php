<?php
namespace App\Controllers;

use App\Integrations\LlmExtractor;
use App\Services\DeliveryOrderService;
use App\Services\IntegrationService;

class DeliveryOrderController extends BaseController
{
    private DeliveryOrderService $service;

    public function __construct(DeliveryOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * DO list for the date range
     */
    public function list(): array
    {
        $dateFrom = $this->parseDateInput($_POST['date_from'] ?? '');
        $dateTo = $this->parseDateInput($_POST['date_to'] ?? '');

        return ['status' => 'success', 'data' => $this->service->getList($dateFrom, $dateTo)];
    }

    /**
     * Receiving report rows; Excel/PDF exports pass the filters by GET
     */
    public function report(): array
    {
        // Daily report: a single date, falls back to today
        $date = $this->parseDateInput($_REQUEST['date'] ?? '') ?: date('Y-m-d');

        return ['status' => 'success', 'data' => $this->service->getReport($date, $date, (int)($_REQUEST['supplier'] ?? 0))];
    }

    /**
     * DO header with linked wholesales rows
     */
    public function get(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        $record = $this->service->getById($id);

        if (!$record) {
            return ['status' => 'failed', 'message' => 'Delivery order not found'];
        }

        return ['status' => 'success', 'data' => $record];
    }

    /**
     * Create or update the DO header
     */
    public function save(): array
    {
        $doDate = $this->parseDateInput($_POST['do_date'] ?? '');

        if (!$doDate) {
            return ['status' => 'failed', 'message' => 'Invalid date format'];
        }

        $remark = trim($_POST['remark'] ?? '');

        $items = json_decode($_POST['items'] ?? '[]', true);

        return $this->service->save($this->postId(), trim($_POST['do_no'] ?? ''), $doDate, $remark === '' ? null : $remark, is_array($items) ? $items : []);
    }

    public function delete(): array
    {
        $id = $this->postId();

        if (!$id) {
            return ['status' => 'failed', 'message' => 'Missing Attribute'];
        }

        return $this->service->delete($id);
    }

    public function options(array $categoryIds): array
    {
        return ['status' => 'success', 'data' => $this->service->getOptions($categoryIds)];
    }

    /**
     * Read the uploaded DO Excel (first sheet) and match it to suppliers, products and grades
     */
    public function import(array $categoryIds): array
    {
        $file = $_FILES['file']['tmp_name'] ?? '';

        if (!$file || !is_uploaded_file($file)) {
            return ['status' => 'failed', 'message' => 'Please select an Excel file'];
        }

        try {
            $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getSheet(0)->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => 'Unable to read the Excel file'];
        }

        return $this->service->importSheet($rows, $categoryIds);
    }

    /**
     * Read an uploaded DO photo / PDF with the company's AI connection (own key, else platform default)
     */
    public function extract(array $categoryIds, IntegrationService $integrations): array
    {
        $file = $_FILES['file']['tmp_name'] ?? '';

        if (!$file || !is_uploaded_file($file)) {
            return ['status' => 'failed', 'message' => 'Please select a photo or PDF'];
        }

        if (filesize($file) > 10 * 1024 * 1024) {
            return ['status' => 'failed', 'message' => 'File is larger than 10 MB'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            return ['status' => 'failed', 'message' => 'Only JPG, PNG, WEBP or PDF files are allowed'];
        }

        $config = $integrations->getConfig('do_extract');
        if (!$config) {
            return ['status' => 'failed', 'message' => 'AI extraction is not configured'];
        }

        $json = (new LlmExtractor())->extract($config, $mime, base64_encode(file_get_contents($file)), $this->service->extractPrompt($categoryIds));
        if ($json === null) {
            return ['status' => 'failed', 'message' => 'Unable to read the document. Please try again or fill it in manually.'];
        }

        return $this->service->extractDocument($json, $categoryIds);
    }

    /**
     * DO Excel template download
     */
    public function template(array $categoryIds): void
    {
        $this->sendXlsx($this->service->buildTemplate($categoryIds), 'delivery_order_template.xlsx');
    }

    /**
     * Daily receiving report as Excel
     */
    public function reportExcel(): void
    {
        $this->sendXlsx($this->service->reportSpreadsheet($this->report()['data'], $this->translator(), $_GET['date'] ?? ''), 'do_receiving_report.xlsx');
    }

    /**
     * Daily receiving report as PDF
     */
    public function reportPdf(): void
    {
        $this->service->reportPdf($this->report()['data'], $this->translator(), $_GET['date'] ?? '')->Output('do_receiving_report.pdf', 'D');
    }

    private function translator(): callable
    {
        $language = $_SESSION['language'] ?? 'en';

        return function ($key, $default) use ($language) {
            return $_SESSION['languageArray'][$key][$language] ?? $default;
        };
    }

    private function sendXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    }

    /**
     * Parse date from d/m/Y format to Y-m-d
     */
    private function parseDateInput(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
        return $dateObj ? $dateObj->format('Y-m-d') : null;
    }
}
