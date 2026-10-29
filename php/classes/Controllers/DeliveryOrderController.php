<?php
namespace App\Controllers;

use App\Services\DeliveryOrderService;

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
